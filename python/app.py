from flask import Flask, request, jsonify
from flask_cors import CORS
import base64
import numpy as np
import cv2
import io
from PIL import Image

app = Flask(__name__)
CORS(app)

def analyze_focus(frame):
    # Simple focus analysis based on face detection
    # Convert PIL image to OpenCV format
    opencv_img = cv2.cvtColor(np.array(frame), cv2.COLOR_RGB2BGR)
    
    # Load face detection classifier
    face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
    
    # Convert to grayscale
    gray = cv2.cvtColor(opencv_img, cv2.COLOR_BGR2GRAY)
    
    # Detect faces
    faces = face_cascade.detectMultiScale(gray, 1.1, 4)
    
    # Calculate focus score
    if len(faces) > 0:
        # Face detected - calculate focus based on face position and size
        x, y, w, h = faces[0]  # Use first detected face
        
        # Center of frame
        center_x = opencv_img.shape[1] / 2
        center_y = opencv_img.shape[0] / 2
        
        # Face center
        face_center_x = x + w/2
        face_center_y = y + h/2
        
        # Calculate distance from center (normalized)
        max_distance = np.sqrt((center_x**2) + (center_y**2))
        distance = np.sqrt((center_x - face_center_x)**2 + (center_y - face_center_y)**2)
        distance_score = 100 * (1 - distance/max_distance)
        
        # Calculate face size score
        max_size = opencv_img.shape[0] * opencv_img.shape[1]
        face_size = w * h
        size_score = 100 * (face_size/max_size)
        
        # Combine scores
        focus_score = (distance_score * 0.6 + size_score * 0.4)
        
        # Ensure score is between 0 and 100
        focus_score = max(0, min(100, focus_score))
    else:
        # No face detected
        focus_score = 0
    
    return focus_score

@app.route('/analyze-frame', methods=['POST'])
def analyze_frame_route():
    try:
        # Get frame from request
        frame_file = request.files.get('frame')
        if not frame_file:
            return jsonify({'error': 'No frame provided'}), 400
        
        # Convert to PIL Image
        frame = Image.open(frame_file)
        
        # Analyze focus
        focus_score = analyze_focus(frame)
        
        return jsonify({
            'focusScore': focus_score,
            'status': 'success'
        })
    
    except Exception as e:
        return jsonify({
            'error': str(e),
            'status': 'error'
        }), 500

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000) 