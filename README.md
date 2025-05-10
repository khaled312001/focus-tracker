# Focus Tracker

A real-time focus tracking application that monitors user attention and engagement using computer vision and web technologies.

## Features

- Real-time focus detection using computer vision
- WebSocket-based real-time communication
- Beautiful and responsive UI using Tailwind CSS
- Real-time analytics and statistics
- Cross-platform compatibility

## Tech Stack

### Frontend
- Vite.js
- Tailwind CSS
- Alpine.js
- Socket.IO Client
- ApexCharts for analytics

### Backend
- Python (Flask)
- OpenCV for computer vision
- WebSocket server (Node.js)
- Laravel Echo Server

## Prerequisites

- Node.js (v14 or higher)
- Python 3.8 or higher
- PHP 8.1 or higher
- Composer
- npm or yarn

## Installation

1. Clone the repository:
```bash
git clone git@github.com:khaled312001/focus-tracker.git
cd focus-tracker
```

2. Install Python dependencies:
```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Install PHP dependencies:
```bash
composer install
```

## Running the Application

### Windows
Simply run the start-all.bat script:
```bash
start-all.bat
```

### Manual Start
1. Start the Python server:
```bash
python focus_server.py
```

2. Start the WebSocket server:
```bash
npm run websocket
```

3. Start the development server:
```bash
npm run dev
```

## Project Structure

- `/app` - Main application code
- `/public` - Public assets
- `/database` - Database files and migrations
- `/python_model` - Python-based focus detection model
- `/resources` - Frontend resources
- `/routes` - Application routes
- `/tests` - Test files
- `/vendor` - Composer dependencies
- `/node_modules` - npm dependencies

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- OpenCV for computer vision capabilities
- Socket.IO for real-time communication
- Tailwind CSS for the beautiful UI components
