from setuptools import setup, find_packages

setup(
    name="focus-tracker",
    version="0.1.0",
    packages=find_packages(),
    install_requires=[
        "flask==3.0.2",
        "flask-cors==4.0.0",
        "numpy==1.26.4",
        "opencv-python==4.9.0.80"
    ],
) 