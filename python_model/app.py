import os
import cv2
import dlib
import numpy as np
import logging
from datetime import datetime
from collections import deque
from scipy.spatial import distance
from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import base64
import json
import io
from PIL import Image
import uuid
import time
import sys
import random
import traceback

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('python_model.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

# Global logger
logger = logging.getLogger(__name__)

class FocusTracker:
    def __init__(self):
        try:
            # Initialize dlib's face detector and facial landmarks predictor
            self.detector = dlib.get_frontal_face_detector()
            model_path = os.path.join(os.path.dirname(__file__), 'shape_predictor_68_face_landmarks.dat')
            
            if not os.path.exists(model_path):
                # Try to find the model in the parent directory
                parent_model_path = os.path.join(os.path.dirname(__file__), '..', 'shape_predictor_68_face_landmarks.dat')
                if os.path.exists(parent_model_path):
                    model_path = parent_model_path
                else:
                    raise FileNotFoundError(f"Facial landmarks model not found at {model_path} or {parent_model_path}")
            
            self.predictor = dlib.shape_predictor(model_path)
            logger.info(f"Successfully loaded dlib models from {model_path}")
            
            # Initialize OpenCV cascade classifiers as fallback
            self.face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
            self.eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml')
            logger.info("Loaded OpenCV cascade classifiers as fallback detection method")
            
            # Initialize tracking variables
            self.prev_face_pos = None
            self.focus_history = deque(maxlen=5)  # Increased from 3 to 5 for better averaging
            self.gaze_history = deque(maxlen=3)   # Increased from 2 to 3
            self.head_pose_history = deque(maxlen=3)  # Increased from 2 to 3
            self.attention_history = deque(maxlen=3)   # Increased from 2 to 3
            self.blink_counter = 0
            self.last_blink_time = datetime.now()
            self.head_movement_history = deque(maxlen=5)  # Increased from 3 to 5
            self.prev_eyes_state = None
            self.use_dlib = True  # Flag to toggle between dlib and OpenCV
            
            # Component scores for detailed feedback
            self.last_eye_score = 1.0
            self.last_stability_score = 1.0
            self.last_drowsy_score = 1.0
            self.last_gaze_score = 1.0
            self.last_head_pose_score = 1.0
            self.last_attention_score = 1.0  # Track eye region attention
            
            # Session tracking
            self.session_start_time = datetime.now()
            self.total_frames_processed = 0
            self.frames_with_face = 0
            
            logger.info("FocusTracker initialized successfully")
            
        except Exception as e:
            error_msg = f"Error initializing FocusTracker: {str(e)}\n{traceback.format_exc()}"
            logger.error(error_msg)
            raise Exception(error_msg)

    def get_eye_aspect_ratio(self, eye_points):
        """Calculate the eye aspect ratio to detect blinks"""
        try:
            # Convert points to numpy array if not already
            points = np.array(eye_points)
            
            # Compute the euclidean distances between the vertical eye landmarks
            A = np.linalg.norm(points[1] - points[5])
            B = np.linalg.norm(points[2] - points[4])
            
            # Compute the euclidean distance between the horizontal eye landmarks
            C = np.linalg.norm(points[0] - points[3])
            
            # Calculate the eye aspect ratio
            ear = (A + B) / (2.0 * C) if C > 0 else 0
            return ear
        except Exception as e:
            logger.error(f"Error calculating eye aspect ratio: {str(e)}")
            return 0

    def calculate_eye_attention(self, eye_region, gray_frame):
        """Calculate attention score based on eye region analysis"""
        try:
            if eye_region.size == 0:
                return 0.0
                
            # Apply binary threshold to isolate pupil and iris
            _, threshold = cv2.threshold(eye_region, 45, 255, cv2.THRESH_BINARY_INV)
            
            # Calculate non-zero pixels ratio (pupil and iris)
            attention_score = cv2.countNonZero(threshold) / eye_region.size
            
            # Normalize the attention score
            return min(1.0, attention_score / 0.3)  # 0.3 is an expected ratio
        except Exception as e:
            logger.error(f"Error calculating eye attention: {str(e)}")
            return 0.0

    def process_frame(self, frame):
        try:
            self.total_frames_processed += 1
            
            # Validate frame
            if frame is None or not isinstance(frame, np.ndarray):
                logger.warning("Invalid frame data received")
                return {
                    'type': 'focus-score',
                    'focusScore': 0,
                    'message': 'Invalid frame data',
                    'isDrowsy': False,
                    'timestamp': datetime.now().isoformat()
                }
            
            # Convert to grayscale
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            # First try with dlib
            dlib_face_detected = False
            opencv_fallback_used = False
            
            if self.use_dlib:
                faces = self.detector(gray)
                if len(faces) > 0:
                    dlib_face_detected = True
                    self.frames_with_face += 1
                    face = max(faces, key=lambda rect: rect.width() * rect.height())
                    
                    # Get facial landmarks
                    landmarks = self.predictor(gray, face)
                    landmarks_points = np.array([[p.x, p.y] for p in landmarks.parts()])
                    
                    # Get eye landmarks
                    left_eye = landmarks_points[36:42]  # Left eye points
                    right_eye = landmarks_points[42:48]  # Right eye points
                    
                    # Create eye regions for attention calculation
                    left_eye_region = self.get_eye_region(gray, left_eye)
                    right_eye_region = self.get_eye_region(gray, right_eye)
                    
                    # Calculate eye aspect ratios
                    left_ear = self.get_eye_aspect_ratio(left_eye)
                    right_ear = self.get_eye_aspect_ratio(right_eye)
                    
                    # Average eye aspect ratio
                    avg_ear = (left_ear + right_ear) / 2.0
                    
                    # Calculate eye attention scores - new from test.py
                    left_attention = self.calculate_eye_attention(left_eye_region, gray)
                    right_attention = self.calculate_eye_attention(right_eye_region, gray)
                    avg_attention = (left_attention + right_attention) / 2.0
                    
                    # Update attention history
                    self.attention_history.append(avg_attention)
                    
                    # Calculate face stability
                    nose_tip = landmarks_points[30]  # Nose tip point
                    current_pos = nose_tip
                    
                    if self.prev_face_pos is None:
                        self.prev_face_pos = current_pos
                        face_stability = 1.0
                    else:
                        distance_moved = np.linalg.norm(current_pos - self.prev_face_pos)
                        face_stability = max(0, 1 - (distance_moved / 100))  # 100 pixels max movement
                        self.prev_face_pos = current_pos
                    
                    # Detect drowsiness
                    is_drowsy = avg_ear < 0.2  # Threshold for closed eyes
                    
                    if is_drowsy:
                        self.blink_counter += 1
                    else:
                        self.blink_counter = max(0, self.blink_counter - 1)
                    
                    # ENHANCED EYE GAZE DETECTION using facial landmarks
                    # Get additional facial landmarks for better gaze estimation
                    face_center = landmarks_points[27]  # Nose bridge top point
                    
                    # Calculate the eye centers
                    left_eye_center = np.mean(left_eye, axis=0)
                    right_eye_center = np.mean(right_eye, axis=0)
                    
                    # Get pupil position (estimate from eye centers)
                    # For left eye: Points 37 and 38 are top, 41 and 40 are bottom
                    left_eye_top = np.mean([landmarks_points[37], landmarks_points[38]], axis=0)
                    left_eye_bottom = np.mean([landmarks_points[41], landmarks_points[40]], axis=0)
                    left_eye_vertical_center = (left_eye_top + left_eye_bottom) / 2
                    
                    # For right eye: Points 43 and 44 are top, 47 and 46 are bottom
                    right_eye_top = np.mean([landmarks_points[43], landmarks_points[44]], axis=0)
                    right_eye_bottom = np.mean([landmarks_points[47], landmarks_points[46]], axis=0)
                    right_eye_vertical_center = (right_eye_top + right_eye_bottom) / 2
                    
                    # Calculate pupil deviation from perfect center
                    left_eye_ideal_center = np.mean([landmarks_points[36], landmarks_points[39]], axis=0)  # horizontal center
                    right_eye_ideal_center = np.mean([landmarks_points[42], landmarks_points[45]], axis=0)  # horizontal center
                    
                    # Calculate deviation (horizontal and vertical)
                    left_gaze_horizontal_deviation = np.linalg.norm(left_eye_center - left_eye_ideal_center) / np.linalg.norm(landmarks_points[36] - landmarks_points[39])
                    right_gaze_horizontal_deviation = np.linalg.norm(right_eye_center - right_eye_ideal_center) / np.linalg.norm(landmarks_points[42] - landmarks_points[45])
                    
                    left_gaze_vertical_deviation = np.linalg.norm(left_eye_vertical_center - left_eye_center) / np.linalg.norm(left_eye_top - left_eye_bottom)
                    right_gaze_vertical_deviation = np.linalg.norm(right_eye_vertical_center - right_eye_center) / np.linalg.norm(right_eye_top - right_eye_bottom)
                    
                    # Combine deviations
                    gaze_horizontal_deviation = (left_gaze_horizontal_deviation + right_gaze_horizontal_deviation) / 2
                    gaze_vertical_deviation = (left_gaze_vertical_deviation + right_gaze_vertical_deviation) / 2
                    
                    # Calculate gaze score - higher when looking at center
                    gaze_score = 1.0 - min(1.0, gaze_horizontal_deviation * 2.0 + gaze_vertical_deviation * 1.5)
                    
                    # Direction detection (where the person is looking)
                    looking_direction = "center"  # Default
                    if gaze_horizontal_deviation > 0.2:
                        if left_eye_center[0] > left_eye_ideal_center[0] and right_eye_center[0] > right_eye_ideal_center[0]:
                            looking_direction = "right"
                        elif left_eye_center[0] < left_eye_ideal_center[0] and right_eye_center[0] < right_eye_ideal_center[0]:
                            looking_direction = "left"
                    
                    if gaze_vertical_deviation > 0.2:
                        if left_eye_center[1] > left_eye_ideal_center[1] and right_eye_center[1] > right_eye_ideal_center[1]:
                            looking_direction = looking_direction + "-down"
                        elif left_eye_center[1] < left_eye_ideal_center[1] and right_eye_center[1] < right_eye_ideal_center[1]:
                            looking_direction = looking_direction + "-up"
                    
                    # Boost gaze score if looking at center
                    if looking_direction == "center":
                        gaze_score = min(1.0, gaze_score * 1.2)
                    
                    # Update gaze history
                    if not hasattr(self, 'gaze_history'):
                        self.gaze_history = deque(maxlen=2)
                    self.gaze_history.append(gaze_score)
                    
                    # Calculate head pose (simplified)
                    # Using the nose and eyes to estimate head orientation
                    forehead = landmarks_points[27]  # Nose bridge top
                    nose_tip = landmarks_points[30]  # Nose tip
                    chin = landmarks_points[8]   # Chin point
                    
                    # Calculate vertical angle by comparing nose position to ideal center
                    # This is a very simplified approach
                    face_height = np.linalg.norm(chin - forehead)
                    ideal_nose_position = forehead + (chin - forehead) * 0.5
                    nose_offset = np.linalg.norm(nose_tip - ideal_nose_position)
                    head_pose_score = max(0, 1 - (nose_offset / (face_height * 0.3)))
                    
                    # Initialize or update head pose history
                    if not hasattr(self, 'head_pose_history'):
                        self.head_pose_history = deque(maxlen=2)
                    self.head_pose_history.append(head_pose_score)
            
            # If dlib fails, use OpenCV as fallback
            if not dlib_face_detected:
                opencv_fallback_used = True
                logger.info("Using OpenCV cascade classifiers as fallback")
                
                # Detect faces using OpenCV
                faces = self.face_cascade.detectMultiScale(
                    gray,
                    scaleFactor=1.1,
                    minNeighbors=5,
                    minSize=(30, 30)
                )
                
                if len(faces) == 0:
                    return {
                        'type': 'focus-score',
                        'focusScore': 0,
                        'message': 'No face detected',
                        'isDrowsy': False
                    }
                
                # Get the largest face
                face = max(faces, key=lambda x: x[2] * x[3])
                x, y, w, h = face
                
                # Calculate face stability
                current_pos = (x + w/2, y + h/2)
                
                if self.prev_face_pos is None:
                    self.prev_face_pos = current_pos
                    face_stability = 1.0
                else:
                    distance_moved = distance.euclidean(self.prev_face_pos, current_pos)
                    face_stability = max(0, 1 - (distance_moved / 100))  # 100 pixels max movement
                    self.prev_face_pos = current_pos
                
                # Detect eyes within the face region
                roi_gray = gray[y:y+h, x:x+w]
                eyes = self.eye_cascade.detectMultiScale(
                    roi_gray,
                    scaleFactor=1.1,
                    minNeighbors=5,
                    minSize=(20, 20)
                )
                
                # Calculate attention score based on eyes
                total_attention = 0
                for (ex, ey, ew, eh) in eyes:
                    eye_roi = roi_gray[ey:ey+eh, ex:ex+ew]
                    attention = self.calculate_eye_attention(eye_roi, gray)
                    total_attention += attention
                
                avg_attention = total_attention / max(1, len(eyes))
                self.attention_history.append(avg_attention)
                
                # Set default values for dlib-specific metrics
                avg_ear = 0.3 if len(eyes) > 0 else 0.1
                is_drowsy = len(eyes) == 0
                gaze_score = 0.5 if len(eyes) > 0 else 0.0
                head_pose_score = 0.5
                looking_direction = "unknown"
                
                # Simulate gaze and head pose with defaults
                self.gaze_history.append(gaze_score)
                self.head_pose_history.append(head_pose_score)
            
            # Calculate focus score with enhanced metrics
            focus_score = self._calculate_focus_score(
                face_stability,
                avg_ear,
                not (is_drowsy or self.blink_counter >= 5),
                avg_attention
            )
            
            # Update focus history
            self.focus_history.append(focus_score)
            avg_focus = sum(self.focus_history) / len(self.focus_history) if len(self.focus_history) > 0 else 0
            
            # Format focus score for display
            # Return exactly 0 for no focus, but cap at 95 for perfect focus
            if avg_focus == 0.0:
                display_focus = 0
            else:
                # For other scores, round to one decimal place and cap at 95
                display_focus = min(95, round(avg_focus * 100, 1))
            
            # Prepare enhanced response
            result = {
                'type': 'focus-score',
                'focusScore': display_focus,  # Use formatted score
                'message': self._get_focus_message(avg_focus, is_drowsy),
                'isDrowsy': is_drowsy,
                'eyeOpenness': round(avg_ear, 2),
                'faceStability': round(face_stability, 2),
                'gazeQuality': round(gaze_score, 2),
                'headPoseQuality': round(head_pose_score, 2),
                'attentionQuality': round(avg_attention, 2),  # New field
                'lookingDirection': looking_direction,  # New field to show where user is looking
                'fallbackUsed': opencv_fallback_used,  # Indicate if fallback was used
                'timestamp': datetime.now().isoformat()
            }
            
            logger.info(f"Successfully processed frame: {result}")
            return result
            
        except Exception as e:
            logger.error(f"Error processing frame: {str(e)}\n{traceback.format_exc()}")
            return {
                'type': 'focus-score',
                'focusScore': 0,
                'error': str(e),
                'message': 'Error processing frame',
                'isDrowsy': False
            }

    def get_eye_region(self, gray, eye_points):
        """Extract eye region from grayscale image using eye landmarks"""
        try:
            if len(eye_points) == 0:
                return np.array([])
                
            # Get bounding rectangle of eye points
            x_min = int(min(eye_points[:, 0]))
            y_min = int(min(eye_points[:, 1]))
            x_max = int(max(eye_points[:, 0]))
            y_max = int(max(eye_points[:, 1]))
            
            # Extract eye region with small padding
            padding = 3
            eye_region = gray[max(0, y_min-padding):min(gray.shape[0], y_max+padding), 
                             max(0, x_min-padding):min(gray.shape[1], x_max+padding)]
            
            return eye_region
        except Exception as e:
            logger.error(f"Error extracting eye region: {str(e)}")
            return np.array([])

    def _calculate_focus_score(self, face_stability, eye_openness, not_drowsy, attention_score=None):
        # Weight factors - rebalanced for more variable scores
        stability_weight = 0.25  # Increased to make movement affect score more
        eye_weight = 0.25
        drowsy_weight = 0.2
        gaze_weight = 0.15
        head_pose_weight = 0.15
        attention_weight = 0.2

        # Calculate component scores with more variation
        stability_score = max(0.1, min(0.95, face_stability))
        eye_score = max(0.1, min(0.95, eye_openness))
        drowsy_score = 0.9 if not_drowsy else 0.3
        
        # Get gaze score from history
        if hasattr(self, 'gaze_history') and len(self.gaze_history) > 0:
            gaze_score = sum(self.gaze_history) / len(self.gaze_history)
        else:
            gaze_score = 0.3  # Lower default if no history
            
        # Get head pose score from history
        if hasattr(self, 'head_pose_history') and len(self.head_pose_history) > 0:
            head_pose_score = sum(self.head_pose_history) / len(self.head_pose_history)
        else:
            head_pose_score = 0.3  # Lower default if no history

        # Determine attention score from eye regions with more variability
        if attention_score is None:
            attention_score = 0.3  # Lower default
            if hasattr(self, 'attention_history') and len(self.attention_history) > 0:
                attention_score = max(0.3, min(0.95, sum(self.attention_history) / len(self.attention_history)))

        # Store component scores for detailed feedback
        self.last_stability_score = stability_score
        self.last_eye_score = eye_score
        self.last_drowsy_score = drowsy_score
        self.last_gaze_score = gaze_score
        self.last_head_pose_score = head_pose_score
        self.last_attention_score = attention_score

        # Calculate weighted average with new factors
        focus_score = (
            stability_score * stability_weight +
            eye_score * eye_weight +
            drowsy_score * drowsy_weight +
            gaze_score * gaze_weight +
            head_pose_score * head_pose_weight +
            attention_score * attention_weight
        )
        
        # Add a small random variation to the final score to prevent it from being too stable
        focus_variation = random.uniform(-0.05, 0.05)
        focus_score = max(0.1, min(0.95, focus_score + focus_variation))
        
        # Make boost more selective and variable
        if gaze_score > 0.8 and head_pose_score > 0.8 and stability_score > 0.8:
            # Smaller boost for excellent attention
            focus_score = min(0.95, focus_score * 1.05)  # Reduced boost and max
        
        # Return the focus score without the perfect focus condition
        return focus_score

    def _get_focus_message(self, focus_score, is_drowsy):
        if is_drowsy:
            return "You appear to be drowsy. Please take a break if needed."
        
        # Get detailed component scores if available
        eye_status = getattr(self, 'last_eye_score', None)
        gaze_status = None
        if hasattr(self, 'gaze_history') and len(self.gaze_history) > 0:
            gaze_status = sum(self.gaze_history) / len(self.gaze_history)
            
        head_pose_status = None
        if hasattr(self, 'head_pose_history') and len(self.head_pose_history) > 0:
            head_pose_status = sum(self.head_pose_history) / len(self.head_pose_history)
        
        stability_status = getattr(self, 'last_stability_score', None)
        attention_status = getattr(self, 'last_attention_score', None)
        
        # Determine the primary issue affecting focus
        primary_issue = None
        min_score = 1.0
        
        if eye_status is not None and eye_status < min_score:
            min_score = eye_status
            primary_issue = "eye_openness"
            
        if gaze_status is not None and gaze_status < min_score:
            min_score = gaze_status
            primary_issue = "gaze_direction"
            
        if head_pose_status is not None and head_pose_status < min_score:
            min_score = head_pose_status
            primary_issue = "head_position"
            
        if stability_status is not None and stability_status < min_score:
            min_score = stability_status
            primary_issue = "movement"
            
        if attention_status is not None and attention_status < min_score:
            min_score = attention_status
            primary_issue = "attention"
        
        # Return focus message based on focus score and primary issue
        if focus_score == 0:
            return "No focus detected - please check your camera and position"
        elif focus_score == 1:
            return "Perfect focus! Keep maintaining this level of attention"
        elif focus_score < 0.3:
            if primary_issue == "eye_openness":
                return "Please open your eyes more and stay alert"
            elif primary_issue == "gaze_direction":
                return "Try to look directly at the screen"
            elif primary_issue == "head_position":
                return "Please face the camera directly"
            elif primary_issue == "movement":
                return "Try to reduce head movement"
            elif primary_issue == "attention":
                return "Your eyes indicate you're not focused on the screen"
            else:
                return "Please pay more attention"
        elif focus_score < 0.6:
            if primary_issue == "eye_openness":
                return "Your eyes indicate reduced focus"
            elif primary_issue == "gaze_direction":
                return "Your gaze is wandering from the screen"
            elif primary_issue == "head_position":
                return "Your head position could be improved"
            elif primary_issue == "movement":
                return "You're moving more than optimal"
            elif primary_issue == "attention":
                return "Try to focus your eyes on the content"
            else:
                return "Try to focus more"
        elif focus_score < 0.8:
            return "Good focus. Keep it up!"
        else:
            return "Excellent focus! You're fully engaged."

# Initialize Flask app
app = Flask(__name__)
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000", "http://localhost:5173", "http://127.0.0.1:5173", "http://localhost:5174", "http://127.0.0.1:5174"],
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type", "Authorization", "X-Requested-With"]
    }
})

# Store active sessions and tracker
sessions = {}
focus_tracker = None

def ensure_focus_tracker():
    """Ensure focus tracker is initialized"""
    global focus_tracker
    try:
        if focus_tracker is None:
            focus_tracker = FocusTracker()
            logger.info("Focus tracker initialized successfully")
        return True
    except Exception as e:
        logger.error(f"Failed to initialize focus tracker: {str(e)}\n{traceback.format_exc()}")
        return False

@app.route('/start-session', methods=['POST'])
def start_session():
    """Start a new focus tracking session"""
    try:
        data = request.json
        meeting_id = data.get('meetingId')
        user_id = data.get('userId')
        user_name = data.get('userName')
        
        if not all([meeting_id, user_id, user_name]):
            return jsonify({
                'success': False,
                'message': 'Missing required parameters: meetingId, userId, userName'
            }), 400
        
        # Ensure focus tracker is initialized
        if not ensure_focus_tracker():
            return jsonify({
                'success': False,
                'message': 'Failed to initialize focus tracker'
            }), 500
        
        # Generate session ID
        session_id = str(uuid.uuid4())
        
        # Store session info
        sessions[session_id] = {
            'meeting_id': meeting_id,
            'user_id': user_id,
            'user_name': user_name,
            'start_time': datetime.now().isoformat(),
            'last_active': datetime.now().isoformat()
        }
        
        logger.info(f"Started new session {session_id} for user {user_name} in meeting {meeting_id}")
        
        return jsonify({
            'success': True,
            'sessionId': session_id,
            'message': 'Session started successfully'
        })
        
    except Exception as e:
        logger.error(f"Error starting session: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Error: {str(e)}'
        }), 500

@app.route('/analyze-frame', methods=['POST'])
def analyze_frame():
    """Analyze a frame for focus detection"""
    try:
        data = request.json
        session_id = data.get('sessionId')
        frame_data = data.get('frame')
        
        if not session_id or not frame_data:
            return jsonify({
                'success': False,
                'message': 'Missing required parameters: sessionId, frame'
            }), 400
        
        # Check if session exists
        if session_id not in sessions:
            return jsonify({
                'success': False,
                'message': 'Invalid session ID'
            }), 400
        
        # Update session last active time
        sessions[session_id]['last_active'] = datetime.now().isoformat()
        
        # Ensure focus tracker is initialized
        if not ensure_focus_tracker():
            return jsonify({
                'success': False,
                'message': 'Focus tracker not initialized'
            }), 500
        
        # Decode base64 image
        try:
            # Remove data URL prefix if present
            if ',' in frame_data:
                frame_data = frame_data.split(',')[1]
            
            # Decode base64 to image
            img_data = base64.b64decode(frame_data)
            img = Image.open(io.BytesIO(img_data))
            
            # Convert to OpenCV format
            frame = cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)
            
            # Process frame
            result = focus_tracker.process_frame(frame)
            
            # Add session info to result
            result['sessionId'] = session_id
            result['meetingId'] = sessions[session_id]['meeting_id']
            result['userId'] = sessions[session_id]['user_id']
            
            return jsonify(result)
            
        except Exception as e:
            logger.error(f"Error processing frame data: {str(e)}\n{traceback.format_exc()}")
            return jsonify({
                'success': False,
                'message': f'Error processing frame: {str(e)}'
            }), 500
        
    except Exception as e:
        logger.error(f"Error in analyze-frame: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Error: {str(e)}'
        }), 500

@app.route('/analyze-focus', methods=['POST'])
def analyze_focus():
    # This endpoint aliases to analyze-frame to maintain compatibility with the frontend
    return analyze_frame()

@app.route('/check-camera', methods=['GET'])
def check_camera():
    """Check if camera is available"""
    try:
        # Try to open the default camera
        cap = cv2.VideoCapture(0)
        if not cap.isOpened():
            return jsonify({
                'success': False,
                'message': 'Camera not available',
                'available': False
            }), 200
        
        # Read a frame to confirm camera works
        ret, frame = cap.read()
        cap.release()
        
        if not ret or frame is None:
            return jsonify({
                'success': False,
                'message': 'Failed to capture frame from camera',
                'available': False
            }), 200
        
        # Camera is available
        return jsonify({
            'success': True,
            'message': 'Camera is available',
            'available': True,
            'resolution': {
                'width': frame.shape[1],
                'height': frame.shape[0]
            }
        }), 200
        
    except Exception as e:
        logger.error(f"Error checking camera: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Error checking camera: {str(e)}',
            'available': False
        }), 500

@app.route('/stop-session', methods=['POST'])
def stop_session():
    """Stop a focus tracking session"""
    try:
        data = request.json
        session_id = data.get('sessionId')
        
        if not session_id:
            return jsonify({
                'success': False,
                'message': 'Missing required parameter: sessionId'
            }), 400
        
        # Check if session exists
        if session_id not in sessions:
            return jsonify({
                'success': False,
                'message': 'Invalid session ID'
            }), 400
        
        # Get session info
        session_info = sessions[session_id]
        
        # Calculate session duration
        start_time = datetime.fromisoformat(session_info['start_time'])
        end_time = datetime.now()
        duration = (end_time - start_time).total_seconds()
        
        # Remove session
        del sessions[session_id]
        
        logger.info(f"Stopped session {session_id} for user {session_info['user_name']} in meeting {session_info['meeting_id']}. Duration: {duration:.2f}s")
        
        return jsonify({
            'success': True,
            'message': 'Session stopped successfully',
            'sessionId': session_id,
            'duration': round(duration)
        })
        
    except Exception as e:
        logger.error(f"Error stopping session: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Error: {str(e)}'
        }), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    try:
        # Check if focus tracker is initialized
        tracker_status = "initialized" if focus_tracker is not None else "not initialized"
        
        # Get active sessions count
        active_sessions = len(sessions)
        
        return jsonify({
            'status': 'healthy',
            'timestamp': datetime.now().isoformat(),
            'version': '1.1.0',
            'tracker': tracker_status,
            'activeSessions': active_sessions
        }), 200
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'status': 'unhealthy',
            'error': str(e),
            'timestamp': datetime.now().isoformat()
        }), 500

if __name__ == "__main__":
    logger.info("Starting Focus Tracking Service...")
    # Ensure focus tracker is initialized before starting the server
    if not ensure_focus_tracker():
        logger.error("Failed to initialize focus tracker before server start")
        sys.exit(1)
    
    # Get port from environment variable or use default
    port = int(os.environ.get('PORT', 5000))
    host = os.environ.get('HOST', '127.0.0.1')
    
    logger.info(f"Starting server on {host}:{port}")
    app.run(host=host, port=port, debug=True) 