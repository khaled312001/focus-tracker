import os
import sys
import dlib

def verify_model():
    try:
        model_path = 'shape_predictor_68_face_landmarks.dat'
        
        if not os.path.exists(model_path):
            print(f"Error: Model file not found at {model_path}")
            return False
            
        print(f"Found model file at {model_path}")
        print("Attempting to load model...")
        
        # Try to load the model
        predictor = dlib.shape_predictor(model_path)
        print("Successfully loaded model!")
        return True
        
    except Exception as e:
        print(f"Error verifying model: {str(e)}", file=sys.stderr)
        return False

if __name__ == '__main__':
    success = verify_model()
    sys.exit(0 if success else 1) 