function initializeFocusChart(focusLabels, focusData) {
    const ctx = document.getElementById('focusTrendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: focusLabels,
            datasets: [{
                label: 'Average Focus',
                data: focusData,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
} 