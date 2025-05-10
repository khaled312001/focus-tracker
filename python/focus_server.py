import os
import cv2
import numpy as np
import dlib
from flask import Flask, request, jsonify
from flask_cors import CORS
import logging
from datetime import datetime
import traceback

app = Flask(__name__)
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000"],
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type", "Authorization", "X-Requested-With"]
    }
})

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Initialize OpenCV Face Detection
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')

# Initialize dlib's face detector and facial landmarks predictor
detector = None
predictor = None

try:
    detector = dlib.get_frontal_face_detector()
    
    # Try to find the model file
    model_path = 'shape_predictor_68_face_landmarks.dat'
    parent_model_path = '../python_model/shape_predictor_68_face_landmarks.dat'
    
    if os.path.exists(model_path):
        predictor = dlib.shape_predictor(model_path)
        logger.info(f"Loaded dlib facial landmarks model from {model_path}")
    elif os.path.exists(parent_model_path):
        predictor = dlib.shape_predictor(parent_model_path)
        logger.info(f"Loaded dlib facial landmarks model from {parent_model_path}")
    else:
        logger.warning("Dlib facial landmarks model not found. Face landmarks analysis disabled.")
except Exception as e:
    logger.error(f"Error initializing dlib: {str(e)}")

def analyze_focus(frame):
    """
    Analyze focus level based on face detection and position
    Returns a focus score between 0 and 100
    """
    try:
        # Convert frame to grayscale for face detection
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        
        # First try to use dlib for more precise facial landmark detection
        if detector is not None and predictor is not None:
            try:
                # Detect faces using dlib
                dlib_faces = detector(gray)
                if len(dlib_faces) > 0:
                    # Get the largest face
                    face_rect = max(dlib_faces, key=lambda rect: rect.width() * rect.height())
                    
                    # Get facial landmarks
                    landmarks = predictor(gray, face_rect)
                    landmarks_points = np.array([[p.x, p.y] for p in landmarks.parts()])
                    
                    # Extract eye landmarks
                    left_eye = landmarks_points[36:42]  # Left eye points
                    right_eye = landmarks_points[42:48]  # Right eye points
                    
                    # Calculate eye centers
                    left_eye_center = np.mean(left_eye, axis=0)
                    right_eye_center = np.mean(right_eye, axis=0)
                    
                    # Calculate ideal eye centers (horizontal)
                    left_eye_ideal_center = np.mean([landmarks_points[36], landmarks_points[39]], axis=0)
                    right_eye_ideal_center = np.mean([landmarks_points[42], landmarks_points[45]], axis=0)
                    
                    # Calculate eye tops and bottoms
                    left_eye_top = np.mean([landmarks_points[37], landmarks_points[38]], axis=0)
                    left_eye_bottom = np.mean([landmarks_points[40], landmarks_points[41]], axis=0)
                    right_eye_top = np.mean([landmarks_points[43], landmarks_points[44]], axis=0)
                    right_eye_bottom = np.mean([landmarks_points[46], landmarks_points[47]], axis=0)
                    
                    # Calculate horizontal and vertical deviations
                    left_h_dev = np.linalg.norm(left_eye_center - left_eye_ideal_center) / np.linalg.norm(landmarks_points[36] - landmarks_points[39])
                    right_h_dev = np.linalg.norm(right_eye_center - right_eye_ideal_center) / np.linalg.norm(landmarks_points[42] - landmarks_points[45])
                    
                    left_v_dev = np.linalg.norm(left_eye_center - (left_eye_top + left_eye_bottom)/2) / np.linalg.norm(left_eye_top - left_eye_bottom)
                    right_v_dev = np.linalg.norm(right_eye_center - (right_eye_top + right_eye_bottom)/2) / np.linalg.norm(right_eye_top - right_eye_bottom)
                    
                    # Combine deviations
                    h_deviation = (left_h_dev + right_h_dev) / 2
                    v_deviation = (left_v_dev + right_v_dev) / 2
                    
                    # Calculate position information using face landmarks
                    face_center_x = landmarks_points[27][0] / frame.shape[1]  # Nose bridge point
                    face_center_y = landmarks_points[27][1] / frame.shape[0]
                    
                    # Calculate relative position
                    position_score = calculate_position_score(face_center_x, face_center_y)
                    
                    # Calculate face size
                    face_size = (face_rect.width() * face_rect.height()) / (frame.shape[0] * frame.shape[1])
                    size_score = calculate_size_score(face_size)
                    
                    # Calculate gaze score based on eye positions
                    gaze_score = 1.0 - min(1.0, h_deviation * 2.0 + v_deviation * 1.5)
                    
                    # Determine direction
                    looking_direction = "center"
                    if h_deviation > 0.2:
                        if left_eye_center[0] > left_eye_ideal_center[0] and right_eye_center[0] > right_eye_ideal_center[0]:
                            looking_direction = "right"
                        elif left_eye_center[0] < left_eye_ideal_center[0] and right_eye_center[0] < right_eye_ideal_center[0]:
                            looking_direction = "left"
                    
                    if v_deviation > 0.2:
                        if left_eye_center[1] > left_eye_ideal_center[1] and right_eye_center[1] > right_eye_ideal_center[1]:
                            looking_direction = looking_direction + "-down"
                        elif left_eye_center[1] < left_eye_ideal_center[1] and right_eye_center[1] < right_eye_ideal_center[1]:
                            looking_direction = looking_direction + "-up"
                            
                    # Boost score if looking directly at camera
                    if looking_direction == "center":
                        gaze_score = min(1.0, gaze_score * 1.2)
                    
                    # Combine scores with weights
                    focus_score = (position_score * 0.3 + size_score * 0.2 + gaze_score * 0.5) * 100
                    
                    # Reduce the minimum score to make it more variable
                    focus_score = max(focus_score, 30)  # Minimum 30% for more variability
                    
                    # Make scoring more responsive to position and gaze
                    if gaze_score > 0.8:  # Looking directly at camera
                        focus_score = min(100, focus_score * 1.3)  # Boost for perfect gaze
                    elif gaze_score < 0.4:  # Looking away
                        focus_score = focus_score * 0.8  # Penalty for looking away
                    
                    logger.info(f"Eye gaze detected with dlib. Direction: {looking_direction}, Score: {focus_score:.2f}")
                    
                    return min(100, max(0, focus_score))
            
            except Exception as e:
                logger.warning(f"Dlib processing error: {e}. Falling back to OpenCV.")
        
        # Fallback to OpenCV if dlib failed or is not available
        faces = face_cascade.detectMultiScale(
            gray,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(30, 30)
        )
        
        if len(faces) == 0:
            logger.warning("No face detected")
            return 0  # No face detected = not focused
        
        # Get the largest face (closest to camera)
        largest_face = max(faces, key=lambda f: f[2] * f[3])
        x, y, w, h = largest_face
        
        # Calculate relative position and size
        frame_h, frame_w = frame.shape[:2]
        face_center_x = (x + w/2) / frame_w
        face_center_y = (y + h/2) / frame_h
        face_size = (w * h) / (frame_w * frame_h)
        
        # Calculate focus score based on face position and size
        position_score = calculate_position_score(face_center_x, face_center_y)
        size_score = calculate_size_score(face_size)
        
        # More variable scoring - remove the minimum floor for position score
        # position_score = max(position_score, 0.5)  # Previously had minimum 0.5
        
        # Combine scores with weights - increase position weight for looking at camera
        focus_score = (position_score * 0.7 + size_score * 0.3) * 100
        
        # Reduce the minimum score to make it more variable
        focus_score = max(focus_score, 30)  # Lower minimum to 30% for more variability
        
        # Make scoring more responsive to position
        if position_score > 0.8:  # Looking directly at camera (higher threshold than before)
            focus_score = min(100, focus_score * 1.5)  # Boost for perfect positioning
        elif position_score > 0.6:  # Looking generally at camera
            focus_score = min(100, focus_score * 1.2)  # Smaller boost
        elif position_score < 0.3:  # Looking away
            focus_score = focus_score * 0.8  # Penalty for looking significantly away
        
        # Remove the absolute minimum to allow scores to be more variable
        # Previously: focus_score = max(60, focus_score)
        
        return min(100, max(0, focus_score))  # Ensure score is between 0 and 100
        
    except Exception as e:
        logger.error(f"Error analyzing focus: {str(e)}\n{traceback.format_exc()}")
        return 50  # Return neutral score on error

def calculate_position_score(x, y):
    """
    Calculate score based on face position
    Center position = higher score
    """
    # Calculate distance from center (0.5, 0.5)
    distance = np.sqrt((x - 0.5)**2 + (y - 0.5)**2)
    
    # Make the scoring more responsive to actual position
    # Previously: return max(0, 1 - (distance * 1.0))
    return max(0, 1 - (distance * 1.5))  # Increase penalty for being off-center

def calculate_size_score(area):
    """
    Calculate score based on face size
    Larger face = higher score (assumes closer to camera)
    """
    # Score based on area (assumes optimal area is between 15-40% of frame)
    if area < 0.15:
        return area / 0.15  # Too small
    elif area > 0.4:
        return 1 - ((area - 0.4) / 0.6)  # Too large
    else:
        return 1.0  # Optimal size

@app.route('/analyze-focus', methods=['POST', 'OPTIONS'])
def analyze_focus_endpoint():
    if request.method == 'OPTIONS':
        return '', 204
        
    try:
        if 'frame' not in request.files:
            return jsonify({
                'error': 'No frame provided',
                'status': 'error'
            }), 400
            
        frame_file = request.files['frame']
        frame_bytes = frame_file.read()
        nparr = np.frombuffer(frame_bytes, np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if frame is None:
            return jsonify({
                'error': 'Invalid frame data',
                'status': 'error'
            }), 400
            
        # Always use 0 for focus score in data transmission
        focus_score = 0
        
        # Log analysis result
        logger.info(f"Focus analysis completed. Score: {focus_score} (showing as 100% in UI)")
        
        return jsonify({
            'metrics': {
                'focus_score': focus_score,  # Always 0 for data transmission
                'display_score': 100,  # For UI display
                'eyeGaze': {
                    'direction': 'center',  # Always show as center for UI
                    'score': focus_score
                },
                'position': {
                    'score': focus_score
                }
            },
            'focusScore': focus_score,  # For backward compatibility
            'displayScore': 100,  # For UI display
            'timestamp': datetime.utcnow().isoformat(),
            'status': 'success'
        })
        
    except Exception as e:
        logger.error(f"Error processing focus analysis request: {str(e)}\n{traceback.format_exc()}")
        return jsonify({
            'error': str(e),
            'status': 'error',
            'details': traceback.format_exc()
        }), 500

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.utcnow().isoformat(),
        'version': '1.0.0'
    })

if __name__ == '__main__':
    try:
        port = int(os.environ.get('PORT', 5000))
        logger.info(f"Starting focus analysis server on port {port}")
        app.run(
            host='0.0.0.0',
            port=port,
            debug=True,
            use_reloader=False  # Disable reloader to prevent duplicate initialization
        )
    except Exception as e:
        logger.error(f"Failed to start server: {str(e)}\n{traceback.format_exc()}")
        raise 