import os
import urllib.request
import sys
import hashlib
import bz2

def verify_sha256(file_path, expected_hash):
    sha256_hash = hashlib.sha256()
    with open(file_path, "rb") as f:
        # Read and update hash in chunks of 4K
        for byte_block in iter(lambda: f.read(4096), b""):
            sha256_hash.update(byte_block)
    return sha256_hash.hexdigest() == expected_hash

def download_shape_predictor():
    try:
        model_path = 'shape_predictor_68_face_landmarks.dat'
        
        # Download from official dlib model repository
        url = 'http://dlib.net/files/shape_predictor_68_face_landmarks.dat.bz2'
        print(f"Downloading model from dlib.net...")
        
        # Configure the request with headers
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
        # Create a request with headers
        req = urllib.request.Request(url, headers=headers)
        
        # Download and decompress the file
        print("Downloading compressed model file...")
        with urllib.request.urlopen(req) as response:
            compressed_data = response.read()
            
        print("Decompressing model file...")
        decompressed_data = bz2.decompress(compressed_data)
        
        print("Saving model file...")
        with open(model_path, 'wb') as out_file:
            out_file.write(decompressed_data)
        
        print(f"Model file downloaded and decompressed to {model_path}")
        return True
        
    except Exception as e:
        print(f"Error downloading model: {str(e)}", file=sys.stderr)
        if os.path.exists(model_path):
            os.remove(model_path)
        return False

if __name__ == '__main__':
    success = download_shape_predictor()
    sys.exit(0 if success else 1) 