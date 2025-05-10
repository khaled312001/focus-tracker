@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-900 flex flex-col">
    <!-- Top Bar -->
    <div class="bg-black p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-white">Camera Test</h1>
        <a href="{{ route('teacher.meetings') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Meetings
        </a>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-4">
        <div class="max-w-3xl mx-auto">
            <!-- Camera Preview -->
            <div class="bg-black rounded-lg overflow-hidden mb-6 aspect-video">
                <video id="cameraPreview" autoplay playsinline muted class="w-full h-full object-cover"></video>
            </div>

            <!-- Controls -->
            <div class="flex justify-center space-x-4">
                <button id="toggleVideo" class="flex items-center space-x-2 bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Stop Video</span>
                </button>
                <button id="toggleAudio" class="flex items-center space-x-2 bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span>Mute</span>
                </button>
            </div>

            <!-- Status Messages -->
            <div id="statusMessages" class="mt-6 p-4 bg-gray-800 rounded-lg">
                <h2 class="text-lg font-bold text-white mb-4">Device Status</h2>
                <div class="space-y-2">
                    <div class="flex items-center text-white">
                        <span id="cameraStatus" class="flex-1">Checking camera...</span>
                        <span id="cameraStatusIcon" class="ml-2">⏳</span>
                    </div>
                    <div class="flex items-center text-white">
                        <span id="micStatus" class="flex-1">Checking microphone...</span>
                        <span id="micStatusIcon" class="ml-2">⏳</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('cameraPreview');
    const toggleVideoBtn = document.getElementById('toggleVideo');
    const toggleAudioBtn = document.getElementById('toggleAudio');
    const cameraStatus = document.getElementById('cameraStatus');
    const micStatus = document.getElementById('micStatus');
    const cameraStatusIcon = document.getElementById('cameraStatusIcon');
    const micStatusIcon = document.getElementById('micStatusIcon');

    let stream = null;
    let isVideoEnabled = true;
    let isAudioEnabled = true;

    async function initializeDevices() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            
            // Set up video preview
            video.srcObject = stream;
            await video.play();
            
            // Update status messages
            cameraStatus.textContent = 'Camera is working';
            micStatus.textContent = 'Microphone is working';
            cameraStatusIcon.textContent = '✅';
            micStatusIcon.textContent = '✅';
            
            // Set up toggle buttons
            const videoTrack = stream.getVideoTracks()[0];
            const audioTrack = stream.getAudioTracks()[0];
            
            toggleVideoBtn.addEventListener('click', () => {
                isVideoEnabled = !isVideoEnabled;
                videoTrack.enabled = isVideoEnabled;
                toggleVideoBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>${isVideoEnabled ? 'Stop Video' : 'Start Video'}</span>
                `;
                cameraStatusIcon.textContent = isVideoEnabled ? '✅' : '❌';
            });
            
            toggleAudioBtn.addEventListener('click', () => {
                isAudioEnabled = !isAudioEnabled;
                audioTrack.enabled = isAudioEnabled;
                toggleAudioBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                    <span>${isAudioEnabled ? 'Mute' : 'Unmute'}</span>
                `;
                micStatusIcon.textContent = isAudioEnabled ? '✅' : '❌';
            });
            
        } catch (error) {
            console.error('Error accessing media devices:', error);
            if (error.name === 'NotAllowedError') {
                cameraStatus.textContent = 'Camera access denied';
                micStatus.textContent = 'Microphone access denied';
                cameraStatusIcon.textContent = '❌';
                micStatusIcon.textContent = '❌';
            } else {
                cameraStatus.textContent = 'Error accessing camera';
                micStatus.textContent = 'Error accessing microphone';
                cameraStatusIcon.textContent = '❌';
                micStatusIcon.textContent = '❌';
            }
        }
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });

    initializeDevices();
});
</script>
@endpush

@endsection 