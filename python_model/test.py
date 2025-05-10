from flask import Flask, request, jsonify
from flask_cors import CORS
import cv2
import numpy as np
import logging
import sys
from datetime import datetime
from scipy.spatial import distance
from skimage.feature import daisy
from skimage.color import rgb2gray

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('focus_analysis.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

app = Flask(__name__)
CORS(app)

class FocusAnalyzer:
    def __init__(self):
        self.face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        self.eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml')
        self.prev_face_pos = None
        self.focus_history = []
        self.logger = logging.getLogger(__name__)

    def analyze_frame(self, frame):
        try:
            # Convert to grayscale
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            # Detect faces
            faces = self.face_cascade.detectMultiScale(
                gray,
                scaleFactor=1.1,
                minNeighbors=5,
                minSize=(30, 30)
            )

            if len(faces) == 0:
                return {
                    'focusScore': 0,
                    'message': 'No face detected',
                    'status': 'warning'
                }

            # Get the largest face
            face = max(faces, key=lambda x: x[2] * x[3])
            x, y, w, h = face

            # Calculate face stability
            stability_score = self._calculate_stability(x, y, w, h)

            # Detect eyes
            roi_gray = gray[y:y+h, x:x+w]
            eyes = self.eye_cascade.detectMultiScale(
                roi_gray,
                scaleFactor=1.1,
                minNeighbors=5,
                minSize=(20, 20)
            )

            # Calculate attention score
            attention_score = self._calculate_attention(eyes, roi_gray)

            # Calculate final focus score
            focus_score = self._calculate_focus_score(stability_score, attention_score, len(eyes))

            # Update focus history
            self.focus_history.append(focus_score)
            if len(self.focus_history) > 5:
                self.focus_history.pop(0)

            # Calculate average focus score
            avg_focus = sum(self.focus_history) / len(self.focus_history)

            return {
                'focusScore': round(avg_focus, 2),
                'message': 'Analysis successful',
                'status': 'success',
                'details': {
                    'stability': round(stability_score * 100, 2),
                    'attention': round(attention_score * 100, 2),
                    'eyesDetected': len(eyes)
                }
            }

        except Exception as e:
            self.logger.error(f"Error analyzing frame: {str(e)}")
            return {
                'focusScore': 0,
                'message': str(e),
                'status': 'error'
            }

    def _calculate_stability(self, x, y, w, h):
        current_pos = (x + w/2, y + h/2)
        
        if self.prev_face_pos is None:
            self.prev_face_pos = current_pos
            return 1.0

        movement = distance.euclidean(self.prev_face_pos, current_pos)
        self.prev_face_pos = current_pos
        
        # Normalize movement (50 pixels as max movement threshold)
        stability = max(0, 1 - (movement / 50))
        return stability

    def _calculate_attention(self, eyes, roi_gray):
        if len(eyes) < 2:
            return 0.0

        attention_score = 0
        for (ex, ey, ew, eh) in eyes:
            eye_roi = roi_gray[ey:ey+eh, ex:ex+ew]
            _, threshold = cv2.threshold(eye_roi, 45, 255, cv2.THRESH_BINARY_INV)
            attention_score += cv2.countNonZero(threshold) / (ew * eh)

        return min(1.0, attention_score / (2 * 0.3))  # Normalize by expected eye area ratio

    def _calculate_focus_score(self, stability, attention, num_eyes):
        weights = {
            'stability': 0.4,
            'attention': 0.4,
            'eyes_detected': 0.2
        }

        eyes_score = 1.0 if num_eyes >= 2 else (0.5 if num_eyes == 1 else 0.0)
        
        focus_score = (
            stability * weights['stability'] +
            attention * weights['attention'] +
            eyes_score * weights['eyes_detected']
        ) * 100

        return max(0, min(100, focus_score))

@app.route('/analyze-frame', methods=['POST'])
def analyze_frame():
    try:
        if 'frame' not in request.files:
            return jsonify({
                'error': 'No frame provided',
                'focusScore': 0,
                'status': 'error'
            }), 400

        # Read the frame
        frame_file = request.files['frame']
        frame_data = frame_file.read()
        nparr = np.frombuffer(frame_data, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if frame is None:
            return jsonify({
                'error': 'Invalid frame data',
                'focusScore': 0,
                'status': 'error'
            }), 400

        # Analyze the frame
        analyzer = FocusAnalyzer()
        result = analyzer.analyze_frame(frame)

        return jsonify(result)

    except Exception as e:
        logging.error(f"Error processing request: {str(e)}")
        return jsonify({
            'error': str(e),
            'focusScore': 0,
            'status': 'error'
        }), 500

if __name__ == "__main__":
    app.run(host='127.0.0.1', port=5000, debug=True)
