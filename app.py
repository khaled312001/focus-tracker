from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import cv2
import dlib
import base64
import os
import sys
import logging
import traceback
import time
from datetime import datetime
from scipy.spatial import distance
from imutils import face_utils
from skimage.feature import daisy
from skimage.color import rgb2gray

# Configure logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('python_model.log')
    ]
)
logger = logging.getLogger(__name__)

def convert_to_native_types(obj):
    if isinstance(obj, np.integer):
        return int(obj)
    elif isinstance(obj, np.floating):
        return float(obj)
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    elif isinstance(obj, np.bool_):
        return bool(obj)
    elif isinstance(obj, dict):
        return {key: convert_to_native_types(value) for key, value in obj.items()}
    elif isinstance(obj, list):
        return [convert_to_native_types(item) for item in obj]
    return obj

class FocusDetector:
    def __init__(self):
        try:
            # Initialize dlib's face detector and facial landmark predictor
            self.detector = dlib.get_frontal_face_detector()
            self.predictor = dlib.shape_predictor("python_model/shape_predictor_68_face_landmarks.dat")
            
            # Define eye points
            self.LEFT_EYE_POINTS = [36, 37, 38, 39, 40, 41]
            self.RIGHT_EYE_POINTS = [42, 43, 44, 45, 46, 47]
            
            # Initialize tracking variables
            self.prev_face_pos = None
            self.focus_history = []
            self.total_focus_time = 0
            self.last_focus_time = time.time()
            
            logger.info("Successfully initialized FocusDetector")
        except Exception as e:
            logger.error(f"Error initializing FocusDetector: {str(e)}")
            raise

    def compute_ear(self, eye_points, landmarks):
        """Calculate Eye Aspect Ratio"""
        p1 = np.array([landmarks.part(eye_points[1]).x, landmarks.part(eye_points[1]).y])
        p2 = np.array([landmarks.part(eye_points[2]).x, landmarks.part(eye_points[2]).y])
        p3 = np.array([landmarks.part(eye_points[3]).x, landmarks.part(eye_points[3]).y])
        p4 = np.array([landmarks.part(eye_points[5]).x, landmarks.part(eye_points[5]).y])
        p5 = np.array([landmarks.part(eye_points[0]).x, landmarks.part(eye_points[0]).y])
        p6 = np.array([landmarks.part(eye_points[4]).x, landmarks.part(eye_points[4]).y])

        A = np.linalg.norm(p2 - p4)
        B = np.linalg.norm(p3 - p5)
        C = np.linalg.norm(p1 - p6)
        ear = (A + B) / (2.0 * C)
        return ear

    def extract_daisy_features(self, eye_image):
        """Extract DAISY features from eye region"""
        try:
            gray_eye = rgb2gray(eye_image)
            descs = daisy(gray_eye, step=4, radius=4, rings=2, histograms=6, 
                         orientations=8, visualize=False)
            return np.mean(descs) if descs.size > 0 else 0
        except:
            return 0

    def get_gaze_direction(self, eye_points, landmarks, frame, tolerance=0.3):
        """Determine gaze direction using eye region analysis"""
        # Get eye region coordinates
        eye_region = np.array([(landmarks.part(point).x, landmarks.part(point).y) 
                              for point in eye_points])
        min_x, max_x = np.min(eye_region[:, 0]), np.max(eye_region[:, 0])
        min_y, max_y = np.min(eye_region[:, 1]), np.max(eye_region[:, 1])
        
        # Extract eye region
        eye = frame[min_y:max_y, min_x:max_x]
        if eye.size == 0:
            return "forward", 0.5

        # Process eye region
        gray_eye = cv2.cvtColor(eye, cv2.COLOR_BGR2GRAY)
        gray_eye = cv2.GaussianBlur(gray_eye, (5, 5), 0)
        _, threshold_eye = cv2.threshold(gray_eye, 50, 255, cv2.THRESH_BINARY_INV)
        
        height, width = threshold_eye.shape
        left_side = threshold_eye[:, :width // 2]
        right_side = threshold_eye[:, width // 2:]
        
        left_white = cv2.countNonZero(left_side)
        right_white = cv2.countNonZero(right_side)
        total_white = left_white + right_white

        if total_white == 0:
            return "forward", 0.5

        # Calculate gaze ratios
        left_ratio = left_white / total_white
        right_ratio = right_white / total_white
        
        # Get DAISY features
        daisy_score = self.extract_daisy_features(eye)

        # Determine gaze direction and score
        if abs(left_ratio - right_ratio) < tolerance and daisy_score > 0.03:
            return "forward", 0.9
        elif left_white > right_white:
            return "left", 0.3
        else:
            return "right", 0.3

    def analyze_frame(self, frame):
        try:
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            metrics = {
                'focus_score': 0.0,
                'is_focused': False,
                'face_detected': False,
                'gaze_direction': 'unknown',
                'total_focus_time': float(self.total_focus_time),
                'timestamp': datetime.now().isoformat()
            }
            
            faces = self.detector(gray)
            
            if len(faces) > 0:
                metrics['face_detected'] = True
                face = faces[0]
                landmarks = self.predictor(gray, face)

                # Calculate EAR for both eyes
                left_ear = self.compute_ear(self.LEFT_EYE_POINTS, landmarks)
                right_ear = self.compute_ear(self.RIGHT_EYE_POINTS, landmarks)
                avg_ear = (left_ear + right_ear) / 2

                # Get gaze direction for both eyes
                left_gaze, left_score = self.get_gaze_direction(self.LEFT_EYE_POINTS, landmarks, frame)
                right_gaze, right_score = self.get_gaze_direction(self.RIGHT_EYE_POINTS, landmarks, frame)
                
                # Calculate focus score
                if avg_ear < 0.20:
                    attention_status = "Sleeping"
                    focus_score = 20
                    color = (0, 0, 255)
                elif left_gaze == "forward" and right_gaze == "forward":
                    attention_status = "Focused"
                    focus_score = min(100, 80 + (avg_ear * 20))
                    color = (0, 255, 0)
                else:
                    attention_status = "Distracted"
                    focus_score = max(30, 50 - (abs(left_score - right_score) * 100))
                    color = (0, 140, 255)

                # Draw face rectangle
                x, y = face.left(), face.top()
                w, h = face.width(), face.height()
                cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)

                # Draw eye landmarks
                for point in range(36, 48):
                    pt = landmarks.part(point)
                    cv2.circle(frame, (pt.x, pt.y), 2, (0, 255, 0), -1)

                # Draw status
                cv2.putText(frame, f"{attention_status} ({focus_score:.1f}%)", 
                           (face.left(), face.top() - 10),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)

                # Update metrics
                metrics.update({
                    'focus_score': focus_score,
                    'is_focused': attention_status == "Focused",
                    'gaze_direction': 'forward' if left_gaze == "forward" and right_gaze == "forward" else 'away',
                    'eyeGaze': {
                        'pitch': avg_ear * 100,  # Approximate pitch from EAR
                        'yaw': (left_score + right_score) * 50  # Approximate yaw from gaze scores
                    }
                })

                # Update focus history
                self.focus_history.append({
                    'timestamp': metrics['timestamp'],
                    'score': focus_score,
                    'status': attention_status
                })

                # Update focus time
                current_time = time.time()
                if metrics['is_focused']:
                    self.total_focus_time += current_time - self.last_focus_time
                self.last_focus_time = current_time

            return metrics, frame

        except Exception as e:
            logger.error(f"Error analyzing frame: {str(e)}")
            return metrics, frame

    def get_focus_statistics(self):
        try:
            if not self.focus_history:
                return {
                    'average_score': 0.0,
                    'total_focus_time': 0.0,
                    'focus_percentage': 0.0
                }
            
            recent_scores = [entry['score'] for entry in self.focus_history]
            average_score = sum(recent_scores) / len(recent_scores)
            
            focused_entries = [entry for entry in self.focus_history if entry['score'] >= 40]
            focus_percentage = (len(focused_entries) / len(self.focus_history)) * 100
            
            return {
                'average_score': round(average_score, 2),
                'total_focus_time': round(self.total_focus_time, 2),
                'focus_percentage': round(focus_percentage, 2)
            }
            
        except Exception as e:
            logger.error(f"Error calculating focus statistics: {str(e)}")
            return {
                'average_score': 0.0,
                'total_focus_time': 0.0,
                'focus_percentage': 0.0
            }

# Initialize Flask app
app = Flask(__name__)
CORS(app)

# Create focus detector instance
focus_detector = None
try:
    focus_detector = FocusDetector()
    logger.info("FocusDetector initialized successfully")
except Exception as e:
    logger.error(f"Failed to initialize FocusDetector: {str(e)}")

@app.route('/analyze-focus', methods=['POST'])
def analyze_focus():
    try:
        # Get the image from the request
        file = request.files['frame']
        
        # Convert to OpenCV format
        nparr = np.frombuffer(file.read(), np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if frame is None:
            return jsonify({
                'error': 'Invalid frame data',
                'focusScore': 0,
                'eyeGaze': {'pitch': 0, 'yaw': 0},
                'pupilData': {
                    'leftPupilSize': 0,
                    'rightPupilSize': 0,
                    'avgPupilSize': 0
                }
            }), 400
        
        # Process the frame
        metrics, _ = focus_detector.analyze_frame(frame)
        
        # Get focus statistics
        stats = focus_detector.get_focus_statistics()
        
        # Prepare response
        response = {
            'focusScore': metrics['focus_score'],
            'eyeGaze': metrics['eyeGaze'],
            'pupilData': {
                'leftPupilSize': 0,  # Not directly measured
                'rightPupilSize': 0,  # Not directly measured
                'avgPupilSize': stats['focus_percentage']  # Using focus percentage as proxy
            },
            'message': 'Analysis successful' if metrics['face_detected'] else 'No face detected',
            'statistics': stats
        }
        
        return jsonify(response)
        
    except Exception as e:
        logger.error(f"Error in analyze_focus: {str(e)}")
        return jsonify({
            'error': str(e),
            'focusScore': 0,
            'eyeGaze': {'pitch': 0, 'yaw': 0},
            'pupilData': {
                'leftPupilSize': 0,
                'rightPupilSize': 0,
                'avgPupilSize': 0
            },
            'message': 'Error processing frame'
        }), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True) 