import cv2
import logging
import time

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def list_cameras():
    """List all available cameras on the system."""
    available_cameras = []
    
    # Try indices 0-9
    for i in range(10):
        try:
            logger.info(f"Checking camera index {i}...")
            cap = cv2.VideoCapture(i)
            if cap.isOpened():
                # Try to read a frame
                ret, frame = cap.read()
                if ret:
                    # Get camera properties
                    width = cap.get(cv2.CAP_PROP_FRAME_WIDTH)
                    height = cap.get(cv2.CAP_PROP_FRAME_HEIGHT)
                    fps = cap.get(cv2.CAP_PROP_FPS)
                    
                    logger.info(f"Found camera {i}:")
                    logger.info(f"  Resolution: {width}x{height}")
                    logger.info(f"  FPS: {fps}")
                    
                    available_cameras.append({
                        'index': i,
                        'resolution': (width, height),
                        'fps': fps
                    })
                    
                    # Show preview
                    if frame is not None:
                        window_name = f"Camera {i}"
                        cv2.imshow(window_name, frame)
                        cv2.waitKey(1000)  # Show for 1 second
                        cv2.destroyWindow(window_name)
                
                cap.release()
        except Exception as e:
            logger.warning(f"Error checking camera {i}: {str(e)}")
    
    return available_cameras

if __name__ == "__main__":
    logger.info("Scanning for available cameras...")
    cameras = list_cameras()
    
    if cameras:
        logger.info("\nAvailable cameras:")
        for camera in cameras:
            logger.info(f"Camera {camera['index']}:")
            logger.info(f"  Resolution: {camera['resolution']}")
            logger.info(f"  FPS: {camera['fps']}")
    else:
        logger.warning("No cameras found!") 