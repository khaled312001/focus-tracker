<?php

namespace FocusTracker\RatchetServer;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\TimerInterface;

/**
 * @property array $meetings
 */
class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $meetings;
    protected $clientIds;
    protected $loop;

    public function __construct($loop)
    {
        $this->clients = new \SplObjectStorage;
        $this->meetings = [];
        $this->clientIds = new \SplObjectStorage;
        $this->loop = $loop;
        $this->log("WebSocket Server Started");
    }

    protected function log($message, $level = 'info')
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] [$level] $message\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $clientId = uniqid('client_');
        $this->clients->attach($conn);
        $this->clientIds->attach($conn, $clientId);
        $this->log("New connection established (ID: {$clientId})", 'success');
    }

    public function onClose(ConnectionInterface $conn)
    {
        $clientId = $this->getClientId($conn);
        $this->clients->detach($conn);
        $this->clientIds->detach($conn);
        $this->log("Connection {$clientId} has disconnected");
        
        // Remove from meetings
        foreach ($this->meetings as $meetingId => $meeting) {
            if (isset($meeting['users'][$clientId])) {
                unset($this->meetings[$meetingId]['users'][$clientId]);
                $this->broadcastMeetingState($meetingId);
            }
        }
    }

    protected function getClientId(ConnectionInterface $conn)
    {
        return $this->clientIds->contains($conn) ? $this->clientIds->offsetGet($conn) : 'unknown';
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $clientId = $this->getClientId($from);
        try {
            $data = json_decode($msg, true);
            if (!$data || !isset($data['TYPE'])) {
                $this->log("Invalid message format from {$clientId}", 'error');
                return;
            }

            $this->log("Received message from {$clientId}: " . json_encode($data), 'debug');
            $this->handleMessage($from, $data);

        } catch (\Exception $e) {
            $this->log("Error processing message from {$clientId}: {$e->getMessage()}", 'error');
        }
    }

    protected function handleMessage(ConnectionInterface $from, array $data)
    {
        $clientId = $this->getClientId($from);
        switch ($data['TYPE']) {
            case 'JOIN':
                $this->handleJoin($from, $data);
                break;
            case 'STUDENT_STATE':
                $this->handleStudentState($from, $data);
                break;
            case 'REQUEST_MEETING_STATE':
                $this->handleMeetingStateRequest($from, $data);
                break;
            default:
                $this->log("Unknown message type from {$clientId}: {$data['TYPE']}", 'warning');
        }
    }

    protected function debug($message, $data = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        echo "[{$timestamp}] [DEBUG] {$message}\n";
        if (!empty($data)) {
            echo "Data: {$jsonData}\n";
        }
    }

    protected function handleJoin(ConnectionInterface $from, array $data)
    {
        $clientId = $this->getClientId($from);
        $meetingId = $data['meetingId'] ?? null;
        
        if (!$meetingId) {
            $this->log("Invalid meeting ID in join request from {$clientId}", 'error');
            return;
        }

        // Initialize meeting if it doesn't exist
        if (!isset($this->meetings[$meetingId])) {
            $this->meetings[$meetingId] = [
                'id' => $meetingId,
                'users' => []
            ];
        }

        // Add user to meeting
        $this->meetings[$meetingId]['users'][$clientId] = [
            'userId' => $data['userId'] ?? null,
            'userName' => $data['userName'] ?? 'Unknown User',
            'userRole' => $data['userRole'] ?? 'student',
            'focusScore' => 0,
            'lastUpdate' => time()
        ];

        $this->log("User {$data['userName']} joined meeting {$meetingId} as {$data['userRole']}", 'success');
        $this->broadcastMeetingState($meetingId);
    }

    protected function handleFocusUpdate(ConnectionInterface $from, array $data)
    {
        if (!isset($data['meetingId'], $data['studentId'], $data['focusScore'])) {
            $this->log("Invalid focus update data received", 'error');
            return;
        }

        $meetingId = $data['meetingId'];
        $studentId = $data['studentId'];
        $focusScore = $data['focusScore'];
        $userName = $this->clients->offsetGet($from)['userName'] ?? 'Unknown';

        // Update student's focus score
        if (isset($this->meetings[$meetingId]['users'][$studentId])) {
            $this->meetings[$meetingId]['users'][$studentId]['focusScore'] = $focusScore;
            $this->meetings[$meetingId]['users'][$studentId]['lastUpdate'] = time();
            
            $this->log("Updated focus score for student {$userName}: {$focusScore}%");
            
            // Broadcast update to teachers
            $this->broadcastMeetingState($meetingId);
        } else {
            $this->log("Student {$studentId} not found in meeting {$meetingId}", 'error');
        }
    }

    protected function handleStudentState(ConnectionInterface $from, array $data) {
        $clientId = $this->getClientId($from);
        $meetingId = $data['meetingId'] ?? null;
        
        if (!$meetingId || !isset($this->meetings[$meetingId])) {
            $this->log("Invalid meeting ID in student state update from {$clientId}", 'error');
            return;
        }

        // Update student state
        if (isset($this->meetings[$meetingId]['users'][$clientId])) {
            $this->meetings[$meetingId]['users'][$clientId]['focusScore'] = $data['focusScore'] ?? 0;
            $this->meetings[$meetingId]['users'][$clientId]['lastUpdate'] = time();
            
            // Broadcast updated state to all meeting participants
            $this->broadcastMeetingState($meetingId);
        }
    }

    protected function handleMeetingStateRequest(ConnectionInterface $from, array $data) {
        $clientId = $this->getClientId($from);
        $meetingId = $data['meetingId'] ?? null;
        
        if (!$meetingId) {
            $this->log("Invalid meeting ID in state request from {$clientId}", 'error');
            return;
        }

        // Initialize meeting if it doesn't exist
        if (!isset($this->meetings[$meetingId])) {
            $this->meetings[$meetingId] = [
                'id' => $meetingId,
                'users' => []
            ];
        }

        // Send state to requesting client
        $state = [
            'TYPE' => 'MEETING_STATE',
            'meetingId' => $meetingId,
            'users' => array_values($this->meetings[$meetingId]['users'])
        ];

        $from->send(json_encode($state));
    }

    protected function sendMeetingState(ConnectionInterface $conn, $meetingId) {
        if (!isset($this->meetings[$meetingId])) {
            $this->meetings[$meetingId] = [
                'id' => $meetingId,
                'users' => []
            ];
        }

        $state = [
            'TYPE' => 'MEETING_STATE',
            'meetingId' => $meetingId,
            'users' => array_values($this->meetings[$meetingId]['users'])
        ];

        $conn->send(json_encode($state));
    }

    protected function broadcastMeetingState($meetingId) {
        if (!isset($this->meetings[$meetingId])) {
            return;
        }

        $state = [
            'TYPE' => 'MEETING_STATE',
            'meetingId' => $meetingId,
            'users' => array_values($this->meetings[$meetingId]['users'])
        ];

        $stateJson = json_encode($state);
        
        // Send to all users in the meeting
        foreach ($this->meetings[$meetingId]['users'] as $userId => $userData) {
            foreach ($this->clients as $client) {
                if ($this->getClientId($client) === $userId) {
                    $client->send($stateJson);
                    break;
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $clientId = $this->getClientId($conn);
        $this->log("Error occurred for connection {$clientId}: {$e->getMessage()}", 'error');
        $conn->close();
    }
} 