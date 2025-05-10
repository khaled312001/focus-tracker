import cv2
import numpy as np
from focus_detector import FocusDetector
import time
import logging
import json
import websockets
import asyncio
import os
from datetime import datetime
from pathlib import Path
import mediapipe as mp
import random
import argparse
import sys

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('focus_test.log')
    ]
)
logger = logging.getLogger(__name__)

# --- Configuration ---
WEBSOCKET_URI = "ws://127.0.0.1:6001"
RECONNECT_DELAY = 5  # seconds
HEARTBEAT_INTERVAL = 30 # seconds
VIDEO_SOURCE = 0 # 0 for default webcam
SEND_INTERVAL = 2 # seconds how often to send focus score
SESSION_FILE = "../storage/app/focus_session.json"  # Relative to python_model directory

# Colors for visualization
GREEN = (0, 255, 0)
RED = (0, 0, 255)
BLUE = (255, 0, 0)
WHITE = (255, 255, 255)
YELLOW = (0, 255, 255)

# Focus zones
FOCUS_ZONES = {
    'center': {'color': GREEN, 'weight': 1.0},
    'near': {'color': YELLOW, 'weight': 0.7},
    'far': {'color': RED, 'weight': 0.4}
}

# --- Window Setup ---
cv2.startWindowThread()
# Set window properties for proper display
os.environ['OPENCV_WINDOW_FREERATIO'] = '1'  # Allow window resizing
os.environ['OPENCV_WINDOW_KEEPRATIO'] = '1'  # Maintain aspect ratio

# --- MediaPipe Face Mesh Setup ---
mp_face_mesh = mp.solutions.face_mesh
mp_drawing = mp.solutions.drawing_utils
mp_drawing_styles = mp.solutions.drawing_styles

# Initialize face mesh with better settings
face_mesh = mp_face_mesh.FaceMesh(
    max_num_faces=1,
    refine_landmarks=True,
    min_detection_confidence=0.6,
    min_tracking_confidence=0.6
)

# Define important facial landmarks
NOSE_TIP = 4
LEFT_EYE_CENTER = 468
RIGHT_EYE_CENTER = 473
MOUTH_CENTER = 13
LEFT_EAR = 234
RIGHT_EAR = 454
LEFT_EYE_OUTER = 33
RIGHT_EYE_OUTER = 263
LEFT_EYE_INNER = 133
RIGHT_EYE_INNER = 362

def load_session_data():
    """Load session data from the JSON file created by Laravel"""
    try:
        script_dir = Path(__file__).parent
        session_file = script_dir / SESSION_FILE
        
        if not session_file.exists():
            logger.error(f"Session file not found: {session_file}")
            return None
            
        with open(session_file, 'r') as f:
            data = json.load(f)
            
        # Verify the session is recent (within last 5 minutes)
        if time.time() - data.get('timestamp', 0) > 300:
            logger.error("Session data is too old")
            return None
            
        return data
        
    except Exception as e:
        logger.error(f"Error loading session data: {e}")
        return None

class FocusTest:
    def __init__(self, meeting_id, user_id, user_name):
        self.meeting_id = meeting_id
        self.user_id = user_id
        self.user_name = user_name
        self.websocket = None
        self.is_connected = False
        self.last_send_time = 0
        self.cap = None
        self.current_focus_score = 0 # Initialize focus score
        self.detector = FocusDetector()
        self.ws = None
        self.websocket_url = WEBSOCKET_URI
        self.face_detected = False
        self.face_center = None
        self.looking_away_counter = 0
        self.total_frames = 0
        self.focus_history = []
        self.blink_counter = 0
        self.last_blink_time = time.time()
        self.eye_aspect_ratios = []
        self.face_direction = "center"
        
        # Setup debug file path
        self.debug_dir = Path(__file__).parent
        self.debug_file = self.debug_dir / "student_debug.txt"
        
        # Create debug directory if it doesn't exist
        self.debug_dir.mkdir(parents=True, exist_ok=True)
        
        # Create or clear debug file
        try:
            with open(self.debug_file, 'w', encoding='utf-8') as f:
                f.write(f"Focus Tracking Debug Log - Started at {datetime.now()}\n")
                f.write("-" * 80 + "\n\n")
                logger.info(f"Debug file created at: {self.debug_file}")
        except Exception as e:
            logger.error(f"Error creating debug file: {e}")
            raise

    def log_debug(self, message, data=None):
        """Write debug information to file"""
        try:
            with open(self.debug_file, 'a', encoding='utf-8') as f:
                timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S.%f")[:-3]
                f.write(f"[{timestamp}] {message}\n")
                if data:
                    f.write(f"Data: {json.dumps(data, indent=2)}\n")
                f.write("-" * 80 + "\n")
                f.flush()  # Force write to disk
        except Exception as e:
            logger.error(f"Error writing to debug file: {e}")

    async def connect(self):
        logging.info(f"Attempting to connect to {WEBSOCKET_URI}...")
        while not self.is_connected:
            try:
                self.websocket = await websockets.connect(WEBSOCKET_URI)
                self.is_connected = True
                logging.info("WebSocket connection established.")
                await self.send_join_message()
                # Start heartbeat and receive loops
                asyncio.create_task(self.heartbeat())
                asyncio.create_task(self.receive_messages())
                return True # Indicate successful connection
            except (websockets.exceptions.ConnectionClosedError, OSError, websockets.exceptions.InvalidURI, ConnectionRefusedError) as e:
                logging.error(f"Connection failed: {e}. Retrying in {RECONNECT_DELAY} seconds...")
                self.is_connected = False
                await asyncio.sleep(RECONNECT_DELAY)
            except Exception as e:
                logging.error(f"An unexpected error occurred during connection: {e}")
                await asyncio.sleep(RECONNECT_DELAY)
        return False # Should not be reached if loop continues

    async def send_join_message(self):
        join_message = {
            "type": "join",
            "meetingId": self.meeting_id,
            "userId": self.user_id,
            "userName": self.user_name,
            "userRole": "student"
        }
        await self.send(join_message)
        logging.info(f"Sent join message: {json.dumps(join_message)}")

    async def send(self, message):
        if self.websocket and self.is_connected:
            try:
                await self.websocket.send(json.dumps(message))
                # logging.debug(f"Sent message: {json.dumps(message)}")
            except websockets.exceptions.ConnectionClosedError:
                logging.warning("Connection closed while sending. Attempting to reconnect...")
                self.is_connected = False
                # No need to call connect here, the main loop handles reconnection
            except Exception as e:
                logging.error(f"Error sending message: {e}")
        else:
            logging.warning("WebSocket not connected. Message not sent.")

    async def heartbeat(self):
        while self.is_connected:
            try:
                await self.send({"type": "ping"})
                # logging.debug("Sent ping")
                await asyncio.sleep(HEARTBEAT_INTERVAL)
            except websockets.exceptions.ConnectionClosedError:
                logging.warning("Connection closed during heartbeat. Stopping heartbeat.")
                self.is_connected = False # Ensure flag is set
                break # Exit heartbeat loop
            except Exception as e:
                logging.error(f"Error during heartbeat: {e}")
                await asyncio.sleep(HEARTBEAT_INTERVAL) # Wait before retrying even on error

    async def receive_messages(self):
        while self.is_connected:
            try:
                message = await self.websocket.recv()
                # logging.debug(f"Received message: {message}")
                # Process received messages if needed (e.g., confirmation, commands)
                data = json.loads(message)
                if data.get("type") == "pong":
                    # logging.debug("Received pong")
                    pass # Heartbeat acknowledged
                elif data.get("type") == "join_confirmed":
                    logging.info(f"Join confirmed for meeting {data.get('meetingId')}, user {data.get('userId')}")
                elif data.get("type") == "error":
                    logging.error(f"Received error from server: {data.get('message')}")
                # Add handling for other message types as needed

            except websockets.exceptions.ConnectionClosedError:
                logging.warning("Connection closed while receiving. Stopping receive loop.")
                self.is_connected = False # Ensure flag is set
                break # Exit receive loop
            except json.JSONDecodeError:
                logging.error(f"Failed to decode JSON message: {message}")
            except Exception as e:
                logging.error(f"Error receiving message: {e}")
                # Decide if we need to break or continue based on the error
                if isinstance(e, (ConnectionError, OSError)):
                     logging.warning("Connection issue detected, stopping receive loop.")
                     self.is_connected = False
                     break
                await asyncio.sleep(1) # Avoid tight loop on other errors

    async def send_focus_update(self):
        # In a real scenario, calculate focus score based on CV analysis
        # For this test, we are using the self.current_focus_score updated by process_frame
        # focus_score = random.randint(60, 100) # Replace with actual calculation
        focus_update_message = {
            "type": "focus_update",
            "meetingId": self.meeting_id,
            "userId": self.user_id,
            "focusScore": self.current_focus_score,
            "source": "python_app" # Indicate the source
        }
        await self.send(focus_update_message)
        logging.info(f"Sent focus update: Score {self.current_focus_score}%")

    def initialize_camera(self):
        logging.info(f"Initializing camera with source: {VIDEO_SOURCE}")
        self.cap = cv2.VideoCapture(VIDEO_SOURCE)
        if not self.cap.isOpened():
            logging.error("Error: Could not open video source.")
            return False
        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
        logging.info("Camera initialized successfully.")
        return True

    def calculate_eye_aspect_ratio(self, eye_landmarks):
        """Calculate the eye aspect ratio to detect blinks"""
        # Convert landmarks to numpy array
        points = np.array([[lm.x, lm.y] for lm in eye_landmarks])
        
        # Calculate vertical distances
        v1 = np.linalg.norm(points[1] - points[5])
        v2 = np.linalg.norm(points[2] - points[4])
        
        # Calculate horizontal distance
        h = np.linalg.norm(points[0] - points[3])
        
        # Calculate eye aspect ratio
        ear = (v1 + v2) / (2.0 * h)
        return ear

    def detect_blink(self, face_landmarks, frame_shape):
        """Detect blinks using eye aspect ratio"""
        if not face_landmarks:
            return False
            
        h, w = frame_shape[:2]
        
        # Get left and right eye landmarks
        left_eye = [face_landmarks.landmark[i] for i in range(362, 374)]
        right_eye = [face_landmarks.landmark[i] for i in range(33, 46)]
        
        # Calculate eye aspect ratios
        left_ear = self.calculate_eye_aspect_ratio(left_eye)
        right_ear = self.calculate_eye_aspect_ratio(right_eye)
        
        # Average eye aspect ratio
        ear = (left_ear + right_ear) / 2.0
        
        # Update history
        self.eye_aspect_ratios.append(ear)
        if len(self.eye_aspect_ratios) > 30:
            self.eye_aspect_ratios.pop(0)
        
        # Detect blink
        if ear < 0.2:  # Threshold for blink detection
            current_time = time.time()
            if current_time - self.last_blink_time > 0.5:  # Minimum time between blinks
                self.blink_counter += 1
                self.last_blink_time = current_time
                return True
        
        return False

    def calculate_focus_score(self, face_landmarks, frame_shape):
        if not face_landmarks:
            self.looking_away_counter += 1
            self.face_direction = "away"
            return max(0, min(40, 40 - self.looking_away_counter))

        # Reset counter when face is detected
        self.looking_away_counter = max(0, self.looking_away_counter - 1)
        
        # Get image dimensions
        h, w = frame_shape[:2]
        
        # Extract key facial landmarks
        nose = face_landmarks.landmark[NOSE_TIP]
        left_eye = face_landmarks.landmark[LEFT_EYE_CENTER]
        right_eye = face_landmarks.landmark[RIGHT_EYE_CENTER]
        left_ear = face_landmarks.landmark[LEFT_EAR]
        right_ear = face_landmarks.landmark[RIGHT_EAR]
        
        # Convert to pixel coordinates
        nose_pos = np.array([nose.x * w, nose.y * h])
        center = np.array([w/2, h/2])
        
        # Calculate face rotation using ear positions
        ear_distance = abs(left_ear.x - right_ear.x)
        
        # Calculate distance from center
        distance_from_center = np.linalg.norm(nose_pos - center) / (w/4)  # Normalize by quarter width
        
        # Determine face direction
        if distance_from_center < 0.5:
            self.face_direction = "center"
        elif distance_from_center < 1.0:
            self.face_direction = "near"
        else:
            self.face_direction = "far"
        
        # Calculate base score
        base_score = 100
        
        # Penalize for being too far from center
        center_penalty = min(30, distance_from_center * 15)
        base_score -= center_penalty
        
        # Penalize for face rotation (when one ear is much closer than the other)
        rotation_penalty = min(20, (1 - ear_distance) * 40)
        base_score -= rotation_penalty
        
        # Penalize for excessive blinking
        blink_rate = self.blink_counter / max(1, (time.time() - self.last_blink_time))
        if blink_rate > 0.5:  # More than 1 blink per 2 seconds
            base_score -= min(15, blink_rate * 10)
        
        # Smooth the score using historical data
        self.focus_history.append(base_score)
        if len(self.focus_history) > 30:  # Keep last 30 frames
            self.focus_history.pop(0)
        
        smoothed_score = sum(self.focus_history) / len(self.focus_history)
        
        return max(0, min(100, smoothed_score))

    def draw_focus_indicators(self, frame, face_landmarks):
        h, w = frame.shape[:2]
        center = (w // 2, h // 2)
        
        # Draw focus zones
        cv2.circle(frame, center, 20, FOCUS_ZONES['center']['color'], 2)
        cv2.circle(frame, center, 100, FOCUS_ZONES['near']['color'], 1)
        cv2.circle(frame, center, 200, FOCUS_ZONES['far']['color'], 1)
        
        # Create semi-transparent overlay for text
        overlay = frame.copy()
        cv2.rectangle(overlay, (10, 10), (250, 140), (0, 0, 0), -1)
        cv2.addWeighted(overlay, 0.3, frame, 0.7, 0, frame)
        
        # Draw focus score and status
        score_color = GREEN if self.current_focus_score >= 70 else (
            BLUE if self.current_focus_score >= 40 else RED)
        
        cv2.putText(frame, f'Focus Score: {int(self.current_focus_score)}%',
                    (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.7, score_color, 2)
        
        status = "High Focus" if self.current_focus_score >= 70 else (
            "Moderate Focus" if self.current_focus_score >= 40 else "Low Focus")
        cv2.putText(frame, f'Status: {status}',
                    (20, 70), cv2.FONT_HERSHEY_SIMPLEX, 0.7, score_color, 2)
        
        # Draw face direction and blink count
        cv2.putText(frame, f'Face: {self.face_direction.title()}',
                    (20, 100), cv2.FONT_HERSHEY_SIMPLEX, 0.7, WHITE, 2)
        
        
        if face_landmarks:
            # Draw face mesh with better visibility
            mp_drawing.draw_landmarks(
                image=frame,
                landmark_list=face_landmarks,
                connections=mp_face_mesh.FACEMESH_CONTOURS,
                landmark_drawing_spec=None,
                connection_drawing_spec=mp_drawing_styles.get_default_face_mesh_contours_style()
            )
            
            # Draw attention rectangle
            nose = face_landmarks.landmark[NOSE_TIP]
            nose_pos = (int(nose.x * w), int(nose.y * h))
            rect_size = 50
            cv2.rectangle(frame,
                         (nose_pos[0] - rect_size, nose_pos[1] - rect_size),
                         (nose_pos[0] + rect_size, nose_pos[1] + rect_size),
                         FOCUS_ZONES[self.face_direction]['color'], 2)

    def process_frame(self, frame):
        # Convert to RGB for MediaPipe
        frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = face_mesh.process(frame_rgb)
        
        # Calculate focus score
        face_landmarks = results.multi_face_landmarks[0] if results.multi_face_landmarks else None
        
        # Detect blinks
        if face_landmarks:
            self.detect_blink(face_landmarks, frame.shape)
        
        # Calculate focus score
        self.current_focus_score = self.calculate_focus_score(face_landmarks, frame.shape)
        
        # Flip frame for selfie view
        frame = cv2.flip(frame, 1)
        
        # Draw focus indicators
        self.draw_focus_indicators(frame, face_landmarks)
        
        # Show the frame
        cv2.namedWindow('Python Focus Tracker', cv2.WINDOW_NORMAL)
        cv2.setWindowProperty('Python Focus Tracker', cv2.WND_PROP_TOPMOST, 1)
        cv2.resizeWindow('Python Focus Tracker', 640, 480)
        cv2.imshow('Python Focus Tracker', frame)
        cv2.waitKey(1)

    async def run_focus_tracking(self):
        if not self.initialize_camera():
            return

        logging.info("Starting focus tracking loop.")
        while True:
            if not self.is_connected:
                logging.info("WebSocket disconnected. Attempting to reconnect...")
                if not await self.connect(): # Try connecting, includes join message on success
                    logging.error("Failed to reconnect after multiple attempts. Exiting focus tracking.")
                    break # Exit if connection fails repeatedly
                else:
                     self.last_send_time = 0 # Reset send timer after reconnect

            # Process frame
            ret, frame = self.cap.read()
            if not ret:
                logging.error("Error: Failed to grab frame.")
                await asyncio.sleep(1) # Wait a bit before retrying
                continue

            self.process_frame(frame)

            # Send focus update periodically
            current_time = time.time()
            if self.is_connected and (current_time - self.last_send_time >= SEND_INTERVAL):
                await self.send_focus_update()
                self.last_send_time = current_time

            # Handle window closing
            if cv2.waitKey(1) & 0xFF == ord('q'):
                logging.info("'q' pressed. Stopping focus tracking.")
                break
            # Check if the window was closed
            try:
                if cv2.getWindowProperty('Python Focus Tracker', cv2.WND_PROP_VISIBLE) < 1:
                    logging.info("Window closed. Stopping focus tracking.")
                    break
            except cv2.error:
                 logging.info("Window likely already destroyed. Stopping focus tracking.")
                 break # Exit if window property check fails (window closed)

            await asyncio.sleep(0.01) # Small delay to prevent excessive CPU usage

        # Cleanup
        logging.info("Cleaning up resources...")
        if self.cap:
            self.cap.release()
        cv2.destroyAllWindows()
        if self.websocket and self.is_connected:
            await self.websocket.close()
            logging.info("WebSocket connection closed.")
        logging.info("Focus tracking stopped.")

async def main(meeting_id, user_id, user_name):
    focus_test = FocusTest(meeting_id, user_id, user_name)
    await focus_test.run_focus_tracking()

if __name__ == "__main__":
    # Try to load session data first
    session_data = load_session_data()
    
    if session_data:
        # Use session data
        meeting_id = session_data['meeting_id']
        user_id = session_data['user_id']
        user_name = session_data['user_name']
        logger.info(f"Using session data for Meeting: {meeting_id}, User: {user_name} ({user_id})")
    else:
        # Fall back to command line arguments if session data is not available
        parser = argparse.ArgumentParser(description='Run the focus tracker for a specific meeting and user.')
        parser.add_argument('--meeting-id', type=int, required=True, help='The ID of the meeting.')
        parser.add_argument('--user-id', type=int, required=True, help='The ID of the user.')
        parser.add_argument('--user-name', type=str, required=True, help='The name of the user.')
        args = parser.parse_args()
        
        meeting_id = args.meeting_id
        user_id = args.user_id
        user_name = args.user_name
        logger.info(f"Using command line arguments for Meeting: {meeting_id}, User: {user_name} ({user_id})")

    try:
        asyncio.run(main(meeting_id, user_id, user_name))
    except KeyboardInterrupt:
        logger.info("Script interrupted by user (Ctrl+C).")
    except Exception as e:
        logger.exception(f"An unhandled error occurred in main: {e}")
    finally:
        logger.info("Focus tracker script finished.") 