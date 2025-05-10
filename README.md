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

## Screenshots

![338731b9-a536-425f-b48d-70e7c94a5657](https://github.com/user-attachments/assets/51175b02-d84c-43cd-8042-8c1f94e78755)
![51524cdb-4022-42e3-8fda-062afe70a722](https://github.com/user-attachments/assets/3f08105a-747f-4966-8b49-f42d978aae80)
![66a7cab9-3bd1-49b3-a98d-ad8b85c03fcf](https://github.com/user-attachments/assets/331bd393-feda-4854-89fe-d66e234121d8)
![WhatsApp Image 2025-04-08 at 17 48 33 (1)](https://github.com/user-attachments/assets/dc6502ee-8c52-465a-8552-7913de579025)
![84661a28-b27d-4d8e-9fa2-18dc24f69161](https://github.com/user-attachments/assets/73d8cbdc-4a3a-4625-bd5c-44e40ddafcb3)
![8de3b5ae-9244-45a4-8638-b48c979ea38c](https://github.com/user-attachments/assets/fa4bcd25-4273-4713-b625-b8522a54b27f)
![04c02b0d-6564-4208-8a2e-66181a81f456](https://github.com/user-attachments/assets/9a8d1d24-da94-47e4-ae60-4a85324660a5)
![WhatsApp Image 2025-04-08 at 17 48 33](https://github.com/user-attachments/assets/9c8e667d-d2f4-4fe2-bded-44b0b1a5eba3)
![ba49e0f2-1e00-4c0d-ad5a-69da82e2ef50](https://github.com/user-attachments/assets/634889ae-3756-4bbc-a739-bc8d5f623003)
![f6d90a6e-8907-46cb-ba35-4ea34ea8d6a5](https://github.com/user-attachments/assets/38937064-5061-4040-946b-73a401c4fa77)
![66a7cab9-3bd1-49b3-a98d-ad8b85c03fcf (1)](https://github.com/user-attachments/assets/237fe3b4-872b-4f52-8d49-8d2be70b6f91)


