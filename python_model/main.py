import cv2
import numpy as np
import argparse
import requests
import time
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def calculate_focus(face_rect, frame_height, frame_width):
    """
    Calculate focus score based on face position and size.
    """
    try:
        x, y, w, h = face_rect
        
        # Calculate face center position relative to frame
        face_center_x = (x + w/2) / frame_width
        face_center_y = (y + h/2) / frame_height
        
        # Calculate how centered the face is (0 = perfect center, 1 = at edge)
        center_deviation = np.sqrt((face_center_x - 0.5)**2 + (face_center_y - 0.5)**2)
        
        # Calculate face size relative to frame
        face_size_ratio = (w * h) / (frame_width * frame_height)
        
        # Calculate focus score
        # Higher score when face is centered and at a good distance
        position_score = 1.0 - min(center_deviation * 2, 1.0)
        size_score = 1.0 - min(abs(face_size_ratio - 0.15) * 5, 1.0)
        
        focus_score = (position_score * 0.6 + size_score * 0.4)
        return max(0.0, min(focus_score, 1.0))
    except Exception as e:
        logging.error(f"Error calculating focus: {str(e)}")
        return 0.7  # Return a default value if calculation fails

def try_camera(index):
    """Try to open a camera with the given index."""
    cap = cv2.VideoCapture(index)
    if not cap.isOpened():
        return None
    ret, frame = cap.read()
    if not ret:
        cap.release()
        return None
    return cap

def main():
    try:
        parser = argparse.ArgumentParser()
        parser.add_argument('--student_id', required=True)
        parser.add_argument('--meeting_id', required=True)
        args = parser.parse_args()

        # Initialize face detection
        face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
        if face_cascade.empty():
            raise Exception("Error loading face cascade classifier")

        # Try different camera indices
        cap = None
        for i in range(10):  # Try first 10 indices
            logging.info(f"Trying camera index {i}...")
            cap = try_camera(i)
            if cap is not None:
                logging.info(f"Successfully opened camera {i}")
                break

        if cap is None:
            raise Exception("Could not open any camera. Please check if a camera is connected and not in use by another application.")
        
        start_time = time.time()
        focus_scores = []
        last_send_time = start_time
        
        logging.info(f"Starting focus tracking for student {args.student_id} in meeting {args.meeting_id}")
        
        while cap.isOpened():
            success, frame = cap.read()
            if not success:
                logging.warning("Failed to capture frame")
                continue

            # Convert to grayscale for face detection
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            
            # Detect faces
            faces = face_cascade.detectMultiScale(
                gray,
                scaleFactor=1.1,
                minNeighbors=5,
                minSize=(30, 30)
            )
            
            current_time = time.time()
            elapsed_time = current_time - start_time
            
            if len(faces) > 0:
                # Use the first (largest) face detected
                face_rect = faces[0]
                focus_score = calculate_focus(face_rect, frame.shape[0], frame.shape[1])
                focus_scores.append(focus_score)
                
                # Draw rectangle around face
                x, y, w, h = face_rect
                cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
                
                # Display focus score on frame
                cv2.putText(
                    frame,
                    f"Focus: {focus_score:.2%}",
                    (10, 30),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    1,
                    (0, 255, 0),
                    2
                )
                
                # Every 60 seconds, send focus data to Laravel backend
                if current_time - last_send_time >= 60:
                    avg_focus = np.mean(focus_scores) * 100
                    
                    data = {
                        'student_id': args.student_id,
                        'meeting_id': args.meeting_id,
                        'focus_percentage': avg_focus,
                        'session_time': int(elapsed_time / 60)  # Convert to minutes
                    }
                    
                    try:
                        response = requests.post(
                            'http://localhost:8000/api/focus-logs',
                            json=data
                        )
                        if response.status_code == 200:
                            logging.info(f"Focus data sent successfully: {data}")
                        else:
                            logging.error(f"Failed to send focus data: {response.status_code} - {response.text}")
                    except Exception as e:
                        logging.error(f"Error sending focus data: {str(e)}")
                    
                    # Reset for next interval
                    focus_scores = []
                    last_send_time = current_time
            else:
                logging.warning("No face detected in frame")
            
            # Display the frame
            cv2.imshow('Focus Tracking', frame)
            
            if cv2.waitKey(1) & 0xFF == 27:  # ESC key to quit
                break
                
    except Exception as e:
        logging.error(f"An error occurred: {str(e)}")
    finally:
        if 'cap' in locals():
            cap.release()
        cv2.destroyAllWindows()
        logging.info("Focus tracking session ended")

if __name__ == '__main__':
    main()
