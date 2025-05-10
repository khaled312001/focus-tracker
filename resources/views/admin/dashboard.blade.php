@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="mb-4">
        <button class="btn btn-primary" onclick="startTracking()">Start Session</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Focus Percentage</th>
                    <th>Session Time</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->student->name }}</td>
                        <td>{{ $log->focus_percentage }}%</td>
                        <td>{{ $log->session_time }} minutes</td>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        @foreach ($logs as $log)
            <p>{{ $log->student->name }} - {{ $log->focus_percentage }}% - {{ $log->session_time }}</p>
        @endforeach
    </div>
</div>

<script>
function startTracking() {
    fetch('http://localhost:5000/start-session')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Session started:', data);
            // You can add additional success handling here
        })
        .catch(error => {
            console.error('Error starting session:', error);
            // You can add error handling here
        });
}
</script>
@endsection 