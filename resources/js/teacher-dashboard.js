// Make the function available globally
window.initializeCharts = function(focusData, focusLabels, performanceData, performanceLabels) {
    const isDarkMode = document.documentElement.classList.contains('dark');
    const textColor = isDarkMode ? '#9CA3AF' : '#6B7280';
    const tooltipTheme = isDarkMode ? 'dark' : 'light';

    // Focus Level Chart
    const focusOptions = {
        series: [{
            name: 'Focus Level',
            data: focusData
        }],
        chart: {
            type: 'area',
            height: 250,
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: focusLabels,
            labels: {
                style: {
                    colors: textColor
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: textColor
                }
            }
        },
        tooltip: {
            theme: tooltipTheme
        },
        colors: ['#3B82F6'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3
            }
        }
    };

    const focusChart = new ApexCharts(document.querySelector("#focusChart"), focusOptions);
    focusChart.render();

    // Student Performance Chart
    const performanceOptions = {
        series: [{
            name: 'Performance',
            data: performanceData
        }],
        chart: {
            type: 'bar',
            height: 250,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: performanceLabels,
            labels: {
                style: {
                    colors: textColor
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: textColor
                }
            }
        },
        tooltip: {
            theme: tooltipTheme
        },
        colors: ['#10B981']
    };

    const performanceChart = new ApexCharts(document.querySelector("#performanceChart"), performanceOptions);
    performanceChart.render();
} 