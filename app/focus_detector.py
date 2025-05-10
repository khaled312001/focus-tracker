import cv2
import numpy as np
import time
from flask import jsonify

class FocusDetector:
    def __init__(self):
        # Load OpenCV's face detection classifier
        self.face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        self.last_detection_time = time.time()
        self.face_detected = False

    def process_frame(self, frame):
        try:
            # Convert frame to grayscale for face detection
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            # Detect faces
            faces = self.face_cascade.detectMultiScale(
                gray,
                scaleFactor=1.1,
                minNeighbors=5,
                minSize=(30, 30)
            )

            # Update face detection status
            self.face_detected = len(faces) > 0
            current_time = time.time()
            
            # Calculate focus score
            if self.face_detected:
                self.last_detection_time = current_time
                focus_score = 100  # Full focus when face is detected
            else:
                time_since_detection = current_time - self.last_detection_time
                if time_since_detection < 3:  # Grace period of 3 seconds
                    focus_score = max(0, 100 - (time_since_detection * 33))  # Gradual decrease
                else:
                    focus_score = 0

            # Draw rectangles around detected faces (for debugging)
            for (x, y, w, h) in faces:
                cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)

            return {
                'focus_score': round(focus_score),
                'face_detected': self.face_detected,
                'faces_count': len(faces)
            }

        except Exception as e:
            print(f"Error processing frame: {str(e)}")
            return {
                'focus_score': 0,
                'face_detected': False,
                'error': str(e)
            }

    def get_focus_score(self, frame_data):
        try:
            # Decode frame from base64 if needed
            if isinstance(frame_data, str):
                # Add base64 decoding here if needed
                pass
            
            # Convert to numpy array if needed
            if isinstance(frame_data, bytes):
                nparr = np.frombuffer(frame_data, np.uint8)
                frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            else:
                frame = frame_data

            # Process the frame
            result = self.process_frame(frame)
            return jsonify(result)

        except Exception as e:
            print(f"Error in get_focus_score: {str(e)}")
            return jsonify({
                'focus_score': 0,
                'face_detected': False,
                'error': str(e)
            }) 