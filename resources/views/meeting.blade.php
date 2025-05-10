@extends('layouts.app')

@section('content')
<div class="meeting-container">
    <div class="meeting-header">
        <h1>Class Session</h1>
        <div class="meeting-status-bar">
            <span class="status-label">Status:</span>
            <span id="meeting-status" class="status-message info">Connecting...</span>
            <span class="duration-label">Duration:</span>
            <span id="session-duration">00:00</span>
        </div>
    </div>

    <div class="meeting-stats">
        <div class="stat-card">
            <h3>Class Overview</h3>
            <div class="stat-row">
                <span class="stat-label">Students:</span>
                <span id="student-count">0</span>
                <span class="stat-separator">/</span>
                <span id="active-students">0</span>
                <span class="stat-label">active</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Average Focus:</span>
                <span id="avg-focus">0%</span>
            </div>
        </div>

        <div class="stat-card">
            <h3>Focus Distribution</h3>
            <div class="focus-distribution">
                <div class="focus-level high">
                    <span class="focus-label">High Focus</span>
                    <span id="high-focus-count">0</span>
                </div>
                <div class="focus-level medium">
                    <span class="focus-label">Medium Focus</span>
                    <span id="medium-focus-count">0</span>
                </div>
                <div class="focus-level low">
                    <span class="focus-label">Low Focus</span>
                    <span id="low-focus-count">0</span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <h3>Class Focus</h3>
            <div class="focus-meter">
                <div class="focus-value">
                    <span id="class-focus">0%</span>
                </div>
                <div class="focus-bar">
                    <div class="focus-fill" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="students-section">
        <div class="students-header">
            <h2>Students</h2>
            <div class="sort-controls">
                <button id="sort-name" class="sort-btn">Sort by Name</button>
                <button id="sort-focus" class="sort-btn">Sort by Focus</button>
            </div>
        </div>

        <div id="students-container" class="students-grid">
            <!-- Student cards will be dynamically added here -->
        </div>

        <div id="empty-state" class="empty-state">
            <p>No students have joined the session yet.</p>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    .meeting-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .meeting-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .meeting-status-bar {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .status-message {
        padding: 0.5rem 1rem;
        border-radius: 4px;
    }

    .status-message.info { background: #e3f2fd; }
    .status-message.success { background: #e8f5e9; }
    .status-message.warning { background: #fff3e0; }
    .status-message.error { background: #ffebee; }

    .meeting-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .focus-distribution {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }

    .focus-level {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        border-radius: 4px;
    }

    .focus-level.high { background: #e8f5e9; }
    .focus-level.medium { background: #fff3e0; }
    .focus-level.low { background: #ffebee; }

    .focus-meter {
        margin-top: 1rem;
        text-align: center;
    }

    .focus-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .focus-bar {
        height: 8px;
        background: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
    }

    .focus-fill {
        height: 100%;
        background: #4caf50;
        transition: width 0.3s ease;
    }

    .students-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .students-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .sort-controls {
        display: flex;
        gap: 1rem;
    }

    .sort-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .sort-btn:hover {
        background: #f5f5f5;
    }

    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }

    .student-card {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 1rem;
        transition: transform 0.2s ease;
    }

    .student-card:hover {
        transform: translateY(-2px);
    }

    .student-card h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
    }

    .student-card p {
        margin: 0.5rem 0;
        color: #666;
    }

    .student-card .status {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 3px;
        font-size: 0.9rem;
    }

    .student-card .status.active {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .student-card .status.inactive {
        background: #ffebee;
        color: #c62828;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
        display: none;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize the teacher meeting with the current user's info
        window.teacherMeeting = new TeacherMeeting(
            '{{ $meetingId }}',
            '{{ $teacherId }}',
            '{{ $teacherName }}'
        );
    });
</script>
@endsection 