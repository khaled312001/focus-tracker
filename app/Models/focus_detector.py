import cv2
import numpy as np
import time
from datetime import datetime
import mediapipe as mp
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class FocusDetector:
    def __init__(self):
        try:
            # Initialize mediapipe face detection
            self.mp_face_detection = mp.solutions.face_detection
            self.mp_face_mesh = mp.solutions.face_mesh
            self.mp_drawing = mp.solutions.drawing_utils
            
            # Initialize face detection and face mesh
            self.face_detection = self.mp_face_detection.FaceDetection(
                model_selection=0,  # 0 for close-range, 1 for far-range
                min_detection_confidence=0.5
            )
            self.face_mesh = self.mp_face_mesh.FaceMesh(
                max_num_faces=1,
                refine_landmarks=True,
                min_detection_confidence=0.5,
                min_tracking_confidence=0.5
            )
            
            logger.info("Successfully initialized mediapipe face detection")
            
            # Focus tracking parameters
            self.last_focus_time = time.time()
            self.total_focus_time = 0
            self.focus_threshold = 0.5  # Lowered to 50% threshold for easier focus
            self.focus_history = []
            self.prev_face_pos = None
            self.movement_history = []
            self.last_update_time = time.time()
            self.update_interval = 1.0  # Update every second
            
        except Exception as e:
            logger.error(f"Error initializing FocusDetector: {str(e)}")
            raise

    def analyze_frame(self, frame):
        try:
            # Convert the frame to RGB for mediapipe
            frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            
            # Initialize metrics
            metrics = {
                'focus_score': 0,
                'is_focused': False,
                'face_detected': False,
                'looking_away': False,
                'total_focus_time': self.total_focus_time,
                'timestamp': datetime.now().isoformat(),
                'face_box': None,
                'attention_points': [],
                'average_focus': self._calculate_average_focus()
            }
            
            # Detect face
            face_detection_results = self.face_detection.process(frame_rgb)
            face_mesh_results = self.face_mesh.process(frame_rgb)
            
            if face_detection_results.detections:
                metrics['face_detected'] = True
                detection = face_detection_results.detections[0]  # Get the first face
                
                # Get face bounding box
                bbox = detection.location_data.relative_bounding_box
                h, w, _ = frame.shape
                x = int(bbox.xmin * w)
                y = int(bbox.ymin * h)
                width = int(bbox.width * w)
                height = int(bbox.height * h)
                
                # Set face box
                metrics['face_box'] = {
                    'x1': x,
                    'y1': y,
                    'x2': x + width,
                    'y2': y + height
                }
                
                # Calculate stability score
                stability_score = self._calculate_stability(x, y, width, height)
                
                # Calculate attention score using face mesh landmarks
                attention_score = 0.0
                if face_mesh_results.multi_face_landmarks:
                    attention_score = self._calculate_attention_from_mesh(
                        face_mesh_results.multi_face_landmarks[0], 
                        frame.shape
                    )
                    
                    # Add eye landmarks as attention points
                    landmarks = face_mesh_results.multi_face_landmarks[0].landmark
                    # Left eye points
                    for idx in [33, 133, 157, 158, 159, 160, 161, 246]:
                        metrics['attention_points'].append({
                            'x': int(landmarks[idx].x * w),
                            'y': int(landmarks[idx].y * h)
                        })
                    # Right eye points
                    for idx in [362, 263, 386, 387, 388, 389, 390, 466]:
                        metrics['attention_points'].append({
                            'x': int(landmarks[idx].x * w),
                            'y': int(landmarks[idx].y * h)
                        })
                
                # Calculate final focus score
                weights = {
                    'stability': 0.4,
                    'attention': 0.6
                }
                
                metrics['focus_score'] = (
                    (stability_score * weights['stability']) +
                    (attention_score * weights['attention'])
                ) * 100
                
                # Ensure score is between 0 and 100
                metrics['focus_score'] = max(0, min(100, metrics['focus_score']))
                
                # Update looking away status
                metrics['looking_away'] = attention_score < 0.2
                
                # Update focus time if focused
                if metrics['focus_score'] >= self.focus_threshold * 100:
                    current_time = time.time()
                    self.total_focus_time += current_time - self.last_focus_time
                    metrics['is_focused'] = True
                
                self.last_focus_time = time.time()
                
                # Store focus history
                self.focus_history.append({
                    'timestamp': metrics['timestamp'],
                    'score': metrics['focus_score'],
                    'is_focused': metrics['is_focused']
                })
                
                # Keep only last 5 minutes of history
                five_minutes_ago = time.time() - 300
                self.focus_history = [entry for entry in self.focus_history 
                                    if datetime.fromisoformat(entry['timestamp']).timestamp() > five_minutes_ago]
                
                # Update average focus every second
                current_time = time.time()
                if current_time - self.last_update_time >= self.update_interval:
                    metrics['average_focus'] = self._calculate_average_focus()
                    self.last_update_time = current_time
            
            # Draw focus visualization on frame
            self._draw_focus_visualization(frame, metrics)
            
            return metrics, frame
        except Exception as e:
            logger.error(f"Error in analyze_frame: {str(e)}")
            # Return a safe default response
            return {
                'focus_score': 0,
                'is_focused': False,
                'face_detected': False,
                'looking_away': False,
                'total_focus_time': self.total_focus_time,
                'timestamp': datetime.now().isoformat(),
                'face_box': None,
                'attention_points': [],
                'average_focus': 0
            }, frame

    def _calculate_stability(self, x, y, w, h):
        try:
            current_pos = np.array([x + w/2, y + h/2])
            
            if self.prev_face_pos is None:
                self.prev_face_pos = current_pos
                return 1.0

            # Convert previous position to numpy array if it isn't already
            if not isinstance(self.prev_face_pos, np.ndarray):
                self.prev_face_pos = np.array(self.prev_face_pos)

            # Ensure both positions are valid
            if not (np.all(np.isfinite(current_pos)) and np.all(np.isfinite(self.prev_face_pos))):
                logger.warning("Invalid position values detected")
                return 1.0

            movement = np.linalg.norm(current_pos - self.prev_face_pos)
            self.prev_face_pos = current_pos
            
            # Normalize movement (100 pixels as max movement threshold)
            stability = max(0, 1 - (movement / 100))
            
            # Update movement history
            self.movement_history.append(stability)
            if len(self.movement_history) > 5:
                self.movement_history.pop(0)
            
            # Return smoothed stability score
            return sum(self.movement_history) / len(self.movement_history)
        except Exception as e:
            logger.error(f"Error in stability calculation: {str(e)}")
            return 0.5

    def _calculate_attention_from_mesh(self, face_landmarks, frame_shape):
        try:
            # Get eye landmarks
            left_eye = []
            right_eye = []
            
            # Left eye indices
            left_eye_indices = [33, 133, 157, 158, 159, 160, 161, 246]
            right_eye_indices = [362, 263, 386, 387, 388, 389, 390, 466]
            
            for idx in left_eye_indices:
                landmark = face_landmarks.landmark[idx]
                left_eye.append([landmark.x, landmark.y])
            
            for idx in right_eye_indices:
                landmark = face_landmarks.landmark[idx]
                right_eye.append([landmark.x, landmark.y])
            
            # Convert to numpy arrays
            left_eye = np.array(left_eye)
            right_eye = np.array(right_eye)
            
            # Calculate eye aspect ratios
            left_ear = self._eye_aspect_ratio(left_eye)
            right_ear = self._eye_aspect_ratio(right_eye)
            
            # Average eye aspect ratio
            ear = (left_ear + right_ear) / 2.0
            
            # Normalize to attention score (0 to 1)
            # EAR typically ranges from 0.2 (closed) to 0.3 (open)
            attention_score = min(1.0, max(0.0, (ear - 0.2) / 0.1))
            
            return attention_score
        except Exception as e:
            logger.error(f"Error calculating attention from mesh: {str(e)}")
            return 0.5

    def _eye_aspect_ratio(self, eye):
        try:
            # Compute the euclidean distances between the vertical eye landmarks
            A = np.linalg.norm(eye[1] - eye[5])
            B = np.linalg.norm(eye[2] - eye[4])
            
            # Compute the euclidean distance between the horizontal eye landmarks
            C = np.linalg.norm(eye[0] - eye[3])
            
            # Compute the eye aspect ratio
            ear = (A + B) / (2.0 * C)
            
            return ear
        except Exception as e:
            logger.error(f"Error calculating eye aspect ratio: {str(e)}")
            return 0.3  # Return a default "open eye" value

    def _calculate_average_focus(self):
        if not self.focus_history:
            return 0
        
        # Calculate average focus from recent history
        recent_scores = [entry['score'] for entry in self.focus_history]
        return sum(recent_scores) / len(recent_scores)

    def _draw_focus_visualization(self, frame, metrics):
        if metrics['face_detected']:
            # Draw face box with gradient color based on focus score
            box = metrics['face_box']
            score = metrics['focus_score']
            
            # Create gradient color: green for high focus, yellow for medium, red for low
            if score >= 70:
                color = (0, 255, 0)  # Green
                thickness = 3
            elif score >= 40:
                color = (0, 255, 255)  # Yellow
                thickness = 2
            else:
                color = (0, 0, 255)  # Red
                thickness = 2
            
            # Draw face box
            cv2.rectangle(frame, 
                         (box['x1'], box['y1']), 
                         (box['x2'], box['y2']), 
                         color, thickness)
            
            # Draw focus score
            score_text = f"Focus: {score:.1f}%"
            cv2.putText(frame, score_text, 
                       (box['x1'], box['y1'] - 10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)
            
            # Draw attention points
            for point in metrics['attention_points']:
                cv2.circle(frame, (point['x'], point['y']), 2, color, -1)
            
            # Draw focus time
            minutes = int(metrics['total_focus_time'] // 60)
            seconds = int(metrics['total_focus_time'] % 60)
            time_text = f"Focus Time: {minutes:02d}:{seconds:02d}"
            cv2.putText(frame, time_text,
                       (10, 30),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
            
            # Draw attention status
            status_text = "Focused" if metrics['is_focused'] else "Distracted"
            status_color = (0, 255, 0) if metrics['is_focused'] else (0, 0, 255)
            
            # Add background for status
            (text_width, text_height), _ = cv2.getTextSize(
                status_text, cv2.FONT_HERSHEY_SIMPLEX, 0.7, 2)
            cv2.rectangle(frame,
                        (5, 40),
                        (text_width + 15, 65),
                        (0, 0, 0), -1)
            cv2.putText(frame, status_text,
                       (10, 60),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7,
                       status_color, 2)
    
    def get_focus_statistics(self):
        if not self.focus_history:
            return {
                'average_score': 0,
                'total_focus_time': 0,
                'focus_percentage': 0
            }
        
        # Calculate average focus score
        recent_scores = [entry['score'] for entry in self.focus_history]
        average_score = sum(recent_scores) / len(recent_scores)
        
        # Calculate focus percentage
        focused_entries = [entry for entry in self.focus_history if entry['is_focused']]
        focus_percentage = (len(focused_entries) / len(self.focus_history)) * 100 if self.focus_history else 0
        
        return {
            'average_score': round(average_score, 2),
            'total_focus_time': round(self.total_focus_time, 2),
            'focus_percentage': round(focus_percentage, 2)
        } 