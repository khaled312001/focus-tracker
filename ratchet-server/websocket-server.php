<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class MeetingServer implements MessageComponentInterface {
    protected $clients;
    protected $meetings;
    protected $userData;
    protected $focusData;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->meetings = [];
        $this->userData = [];
        $this->focusData = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received message from connection {$from->resourceId}: {$msg}\n";
        
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "Invalid JSON message\n";
            return;
        }
        
        if (!isset($data['type'])) {
            echo "Message missing type field\n";
            return;
        }
        
        $meetingId = $data['meetingId'] ?? null;
        $userId = $data['userId'] ?? null;
        $userName = $data['userName'] ?? 'Unknown';
        $userRole = $data['userRole'] ?? 'student';
        
        echo "Processing message type: {$data['type']}, meeting: {$meetingId}, user: {$userName} ({$userRole})\n";
        
        // Store user data
        $this->userData[$from->resourceId] = [
            'meetingId' => $meetingId,
            'userId' => $userId,
            'userName' => $userName,
            'userRole' => $userRole
        ];
        
        // Initialize meeting if it doesn't exist
        if (!isset($this->meetings[$meetingId])) {
            echo "Creating new meeting: {$meetingId}\n";
            $this->meetings[$meetingId] = [];
        }
        
        // Initialize focus data for meeting if it doesn't exist
        if (!isset($this->focusData[$meetingId])) {
            $this->focusData[$meetingId] = [];
        }
        
        // Add user to meeting
        echo "Adding user to meeting: {$meetingId}\n";
        $this->meetings[$meetingId][$from->resourceId] = $from;
        
        // Handle different message types
        switch ($data['type']) {
            case 'join':
                echo "Connection {$from->resourceId} joining meeting {$meetingId} as {$userName} ({$userRole})\n";
                
                // Send current meeting state to the new user
                echo "Sending meeting state to new user\n";
                $this->sendMeetingState($from, $meetingId);
                
                // Broadcast join to others
                $joinMessage = json_encode([
                    'type' => 'user_joined',
                    'meetingId' => $meetingId,
                    'userId' => $userId,
                    'userName' => $userName,
                    'userRole' => $userRole
                ]);
                
                echo "Broadcasting join message to other users\n";
                $this->broadcastToMeeting($meetingId, $from, $joinMessage, "User {$userName} ({$userRole}) joined meeting {$meetingId}");
                break;
                
            case 'request_meeting_state':
                echo "Connection {$from->resourceId} requesting meeting state for meeting {$meetingId}\n";
                $this->sendMeetingState($from, $meetingId);
                break;
                
            case 'leave':
                echo "Connection {$from->resourceId} leaving meeting {$meetingId}\n";
                $this->broadcastToMeeting($meetingId, $from, $msg);
                break;
                
            case 'chat':
                echo "Connection {$from->resourceId} sending chat message to meeting {$meetingId}\n";
                $this->broadcastToMeeting($meetingId, $from, $msg, "Connection {$from->resourceId} sending message \"{$msg}\" to " . (count($this->meetings[$meetingId]) - 1) . " other connections");
                break;
                
            case 'focus_update':
                // Store focus data
                if (isset($data['focusScore'])) {
                    $this->focusData[$meetingId][$userId] = [
                        'score' => $data['focusScore'],
                        'timestamp' => time()
                    ];
                    
                    // Calculate and include average focus in the message
                    $avgFocus = $this->calculateAverageFocus($meetingId);
                    $data['averageFocus'] = $avgFocus;
                    
                    // Only broadcast focus updates to teachers
                    $this->broadcastToTeachers($meetingId, $from, json_encode($data));
                }
                break;
                
            case 'raise_hand':
                echo "Connection {$from->resourceId} raising/lowering hand in meeting {$meetingId}\n";
                $this->broadcastToMeeting($meetingId, $from, $msg);
                break;
                
            default:
                echo "Unknown message type: {$data['type']}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Get user data
        $userData = $this->userData[$conn->resourceId] ?? null;
        
        if ($userData) {
            $meetingId = $userData['meetingId'];
            $userId = $userData['userId'];
            $userName = $userData['userName'];
            
            // Remove from meeting
            if (isset($this->meetings[$meetingId][$conn->resourceId])) {
                unset($this->meetings[$meetingId][$conn->resourceId]);
                
                // If meeting is empty, remove it
                if (empty($this->meetings[$meetingId])) {
                    unset($this->meetings[$meetingId]);
                    unset($this->focusData[$meetingId]);
                }
            }
            
            // Remove focus data for this user
            if (isset($this->focusData[$meetingId][$userId])) {
                unset($this->focusData[$meetingId][$userId]);
            }
            
            // Notify others that user left
            $leaveMsg = json_encode([
                'type' => 'leave',
                'meetingId' => $meetingId,
                'userId' => $userId,
                'userName' => $userName
            ]);
            
            $this->broadcastToMeeting($meetingId, $conn, $leaveMsg);
        }
        
        // Remove user data
        unset($this->userData[$conn->resourceId]);
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function broadcastToMeeting($meetingId, $from, $msg, $logMessage = null) {
        if (!isset($this->meetings[$meetingId])) {
            return;
        }
        
        foreach ($this->meetings[$meetingId] as $client) {
            if ($client !== $from) {
                $client->send($msg);
            }
        }
        
        if ($logMessage) {
            echo $logMessage . "\n";
        }
    }
    
    protected function broadcastToTeachers($meetingId, $from, $msg) {
        if (!isset($this->meetings[$meetingId])) {
            return;
        }
        
        foreach ($this->meetings[$meetingId] as $clientId => $client) {
            if ($client !== $from && isset($this->userData[$clientId]) && $this->userData[$clientId]['userRole'] === 'teacher') {
                $client->send($msg);
            }
        }
    }
    
    protected function calculateAverageFocus($meetingId) {
        if (!isset($this->focusData[$meetingId]) || empty($this->focusData[$meetingId])) {
            return 0;
        }
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($this->focusData[$meetingId] as $userId => $data) {
            // Only include focus scores from the last 30 seconds
            if (time() - $data['timestamp'] < 30) {
                $totalScore += $data['score'];
                $count++;
            }
        }
        
        return $count > 0 ? round($totalScore / $count, 1) : 0;
    }
    
    protected function sendMeetingState($client, $meetingId) {
        if (!isset($this->meetings[$meetingId])) {
            return;
        }
        
        // Collect current users in the meeting
        $users = [];
        $studentCount = 0;
        $teacherCount = 0;
        
        foreach ($this->meetings[$meetingId] as $clientId => $conn) {
            if (isset($this->userData[$clientId])) {
                $userData = $this->userData[$clientId];
                $users[] = [
                    'userId' => $userData['userId'],
                    'userName' => $userData['userName'],
                    'userRole' => $userData['userRole']
                ];
                
                // Count users by role
                if ($userData['userRole'] === 'student') {
                    $studentCount++;
                } else if ($userData['userRole'] === 'teacher') {
                    $teacherCount++;
                }
            }
        }
        
        // Log meeting state
        echo "Meeting {$meetingId} state: {$studentCount} students, {$teacherCount} teachers\n";
        
        // Send current meeting state to the new user
        $stateMsg = json_encode([
            'type' => 'meeting_state',
            'meetingId' => $meetingId,
            'users' => $users,
            'averageFocus' => $this->calculateAverageFocus($meetingId)
        ]);
        
        $client->send($stateMsg);
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MeetingServer()
        )
    ),
    8080
);

echo "WebSocket server starting on port 8080...\n";
echo "Waiting for connections...\n";
$server->run(); 