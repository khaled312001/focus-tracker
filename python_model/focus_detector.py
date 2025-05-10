import cv2
import numpy as np
import time
from datetime import datetime
import logging
from collections import deque
import os

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class FocusDetector:
    def __init__(self):
        try:
            # Load OpenCV's pre-trained cascades
            cascade_dir = os.path.dirname(cv2.__file__) + '/data/'
            self.face_cascade = cv2.CascadeClassifier(cascade_dir + 'haarcascade_frontalface_default.xml')
            self.eye_cascade = cv2.CascadeClassifier(cascade_dir + 'haarcascade_eye.xml')
            
            if self.face_cascade.empty() or self.eye_cascade.empty():
                raise Exception("Error loading cascade classifiers")
            
            # Initialize tracking variables
            self.prev_face_pos = None
            self.focus_history = deque(maxlen=30)  # 30 seconds of history
            self.movement_history = deque(maxlen=10)
            self.last_update_time = time.time()
            self.last_activity_time = time.time()
            
            logger.info("FocusDetector initialized successfully")
            
        except Exception as e:
            logger.error(f"Error initializing FocusDetector: {str(e)}")
            raise

    def analyze_frame(self, frame):
        try:
            # Convert to grayscale
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            # Initialize metrics
            metrics = {
                'focus_score': 0,
                'is_focused': False,
                'face_detected': False,
                'looking_away': True,
                'timestamp': datetime.now().isoformat(),
                'details': {
                    'eye_openness': 0,
                    'face_stability': 0,
                    'attention_score': 0
                }
            }
            
            # Detect faces
            faces = self.face_cascade.detectMultiScale(
                gray,
                scaleFactor=1.1,
                minNeighbors=5,
                minSize=(30, 30)
            )
            
            if len(faces) > 0:
                metrics['face_detected'] = True
                
                # Get the largest face
                face = max(faces, key=lambda x: x[2] * x[3])
                x, y, w, h = face
                
                # Calculate face stability
                current_pos = np.array([x + w/2, y + h/2])
                stability_score = self._calculate_stability(current_pos)
                metrics['details']['face_stability'] = stability_score
                
                # Get face ROI and detect eyes
                roi_gray = gray[y:y+h, x:x+w]
                eyes = self.eye_cascade.detectMultiScale(
                    roi_gray,
                    scaleFactor=1.1,
                    minNeighbors=5,
                    minSize=(20, 20)
                )
                
                # Calculate eye metrics
                eye_score = self._calculate_eye_score(eyes, roi_gray)
                metrics['details']['eye_openness'] = eye_score
                
                # Calculate attention score based on eye position
                attention_score = self._calculate_attention_score(eyes, w, h)
                metrics['details']['attention_score'] = attention_score
                
                # Calculate final focus score
                focus_score = self._calculate_focus_score(
                    stability_score,
                    eye_score,
                    attention_score
                )
                
                metrics['focus_score'] = focus_score
                metrics['is_focused'] = focus_score >= 70
                metrics['looking_away'] = attention_score < 0.5
                
                # Update focus history
                self.focus_history.append(focus_score)
                
                # Draw rectangles for visualization
                cv2.rectangle(frame, (x, y), (x+w, y+h), (255, 0, 0), 2)
                for (ex, ey, ew, eh) in eyes:
                    cv2.rectangle(frame, (x+ex, y+ey), (x+ex+ew, y+ey+eh), (0, 255, 0), 2)
            
            # Calculate average focus
            if len(self.focus_history) > 0:
                metrics['average_focus'] = sum(self.focus_history) / len(self.focus_history)
            else:
                metrics['average_focus'] = 0
                
            return metrics
            
        except Exception as e:
            logger.error(f"Error in analyze_frame: {str(e)}")
            return {
                'focus_score': 0,
                'is_focused': False,
                'face_detected': False,
                'looking_away': True,
                'timestamp': datetime.now().isoformat(),
                'details': {
                    'eye_openness': 0,
                    'face_stability': 0,
                    'attention_score': 0
                },
                'average_focus': 0
            }

    def _calculate_stability(self, current_pos):
        if self.prev_face_pos is None:
            self.prev_face_pos = current_pos
            return 1.0
        
        movement = np.linalg.norm(current_pos - self.prev_face_pos)
        self.prev_face_pos = current_pos
        
        # Normalize movement (50 pixels as max movement threshold)
        stability = max(0, 1 - (movement / 50))
        
        # Update movement history
        self.movement_history.append(stability)
        
        # Return smoothed stability score
        return sum(self.movement_history) / len(self.movement_history)

    def _calculate_eye_score(self, eyes, roi_gray):
        if len(eyes) == 0:
            return 0.0
            
        total_score = 0
        for (ex, ey, ew, eh) in eyes:
            eye_roi = roi_gray[ey:ey+eh, ex:ex+ew]
            
            # Calculate eye openness based on pixel intensity
            _, threshold = cv2.threshold(eye_roi, 45, 255, cv2.THRESH_BINARY_INV)
            white_pixels = cv2.countNonZero(threshold)
            total_pixels = ew * eh
            
            # Calculate ratio of white pixels (pupil and iris)
            ratio = white_pixels / total_pixels if total_pixels > 0 else 0
            total_score += min(ratio * 3, 1.0)  # Multiply by 3 to make it more sensitive
            
        return min(1.0, total_score / max(len(eyes), 1))

    def _calculate_attention_score(self, eyes, face_width, face_height):
        if len(eyes) == 0:
            return 0.0
            
        # Calculate expected eye positions (roughly 1/3 from top, spaced horizontally)
        expected_y = face_height * 0.33
        expected_left_x = face_width * 0.3
        expected_right_x = face_width * 0.7
        
        best_left = None
        best_right = None
        
        # Find the eyes closest to expected positions
        for (ex, ey, ew, eh) in eyes:
            center_x = ex + ew/2
            center_y = ey + eh/2
            
            # Check if eye is in left or right position
            if center_x < face_width/2:  # Left side
                if best_left is None or abs(center_x - expected_left_x) < abs(best_left[0] - expected_left_x):
                    best_left = (center_x, center_y)
            else:  # Right side
                if best_right is None or abs(center_x - expected_right_x) < abs(best_right[0] - expected_right_x):
                    best_right = (center_x, center_y)
        
        # Calculate attention score based on eye positions
        score = 0.0
        if best_left and best_right:
            # Vertical alignment score
            y_diff = abs(best_left[1] - best_right[1]) / face_height
            vert_score = max(0, 1 - y_diff * 5)  # Penalize vertical misalignment
            
            # Horizontal position score
            left_x_score = max(0, 1 - abs(best_left[0] - expected_left_x) / (face_width * 0.2))
            right_x_score = max(0, 1 - abs(best_right[0] - expected_right_x) / (face_width * 0.2))
            
            # Combine scores
            score = (vert_score + left_x_score + right_x_score) / 3
        elif len(eyes) == 1:
            # If only one eye is detected, give partial score
            score = 0.3
            
        return score

    def _calculate_focus_score(self, stability, eye_score, attention_score):
        # Weight factors
        stability_weight = 0.3
        eye_weight = 0.4
        attention_weight = 0.3
        
        # Calculate weighted score
        focus_score = (
            stability * stability_weight +
            eye_score * eye_weight +
            attention_score * attention_weight
        ) * 100
        
        # Add small random variation to prevent static scores
        variation = np.random.uniform(-2, 2)
        focus_score = max(0, min(100, focus_score + variation))
        
        return focus_score 