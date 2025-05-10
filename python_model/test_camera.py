import cv2
import numpy as np
from app import FocusTracker
import time
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def test_camera():
    try:
        # Initialize focus tracker
        focus_tracker = FocusTracker()
        logging.info("Focus tracker initialized successfully")

        # Try to open the camera
        cap = cv2.VideoCapture(0)
        if not cap.isOpened():
            raise Exception("Could not open camera")

        logging.info("Camera opened successfully")

        while True:
            # Read frame
            ret, frame = cap.read()
            if not ret:
                raise Exception("Failed to grab frame")

            # Save frame temporarily
            frame_path = "test_frame.jpg"
            cv2.imwrite(frame_path, frame)

            # Process frame
            result = focus_tracker.process_frame(frame_path)
            logging.info(f"Focus score: {result['focusScore']}, Message: {result['message']}")

            # Display frame with focus score
            cv2.putText(
                frame,
                f"Focus: {result['focusScore']:.2f}",
                (10, 30),
                cv2.FONT_HERSHEY_SIMPLEX,
                1,
                (0, 255, 0) if result['focusScore'] > 0.5 else (0, 0, 255),
                2
            )

            # Show frame
            cv2.imshow('Focus Test', frame)

            # Break loop on 'q' press
            if cv2.waitKey(1) & 0xFF == ord('q'):
                break

            # Add small delay
            time.sleep(0.1)

    except Exception as e:
        logging.error(f"Error: {str(e)}")
    finally:
        # Clean up
        if 'cap' in locals():
            cap.release()
        cv2.destroyAllWindows()

if __name__ == "__main__":
    test_camera() 