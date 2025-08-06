// Hàm để tạo màu ngẫu nhiên cho biểu đồ
function getRandomColor() {
    const r = Math.floor(Math.random() * 255);
    const g = Math.floor(Math.random() * 255);
    const b = Math.floor(Math.random() * 255);
    return `rgba(${r}, ${g}, ${b}, 0.7)`;
}

// Hàm khởi tạo biểu đồ hiệu suất Quiz
function initQuizPerformanceChart(quizChartData) {
    const ctxQuiz = document.getElementById('quizPerformanceChart').getContext('2d');

    const quizTitles = quizChartData.map(q => q.title);
    const avgQuizScores = quizChartData.map(q => q.avg_score === 'N/A' ? null : q.avg_score);

    // Tính toán chiều cao động cho container biểu đồ
    const barHeight = 35; // Chiều cao ước tính cho mỗi thanh bar
    const chartPadding = 150; 
    const suggestedHeight = (quizTitles.length * barHeight) + chartPadding;
    const minChartHeight = 270; 
    const actualChartHeight = Math.max(suggestedHeight, minChartHeight);

    const chartContainer = ctxQuiz.canvas.parentNode;
    if (chartContainer) {
        chartContainer.style.height = `${actualChartHeight}px`;
    }

    new Chart(ctxQuiz, {
        type: 'bar',
        data: {
            labels: quizTitles,
            datasets: [{
                label: 'Average Quiz Score',
                data: avgQuizScores,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // Biểu đồ thanh ngang
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Average Scores for Each Quiz'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.raw !== null) {
                                label += context.raw;
                            } else {
                                label += 'N/A';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Score'
                    },
                    min: 0,
                    max: 10
                },
                y: {
                    title: {
                        display: true,
                        text: 'Quiz Title'
                    },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            }
        }
    });
}

// Hàm khởi tạo biểu đồ hiệu suất Assignment
function initAssignmentPerformanceChart(assignmentChartData) {
    const ctxAssignment = document.getElementById('assignmentPerformanceChart').getContext('2d');

    const assignmentTitles = assignmentChartData.map(a => a.title);
    const avgAssignmentGrades = assignmentChartData.map(a => a.avg_grade === 'N/A' ? null : a.avg_grade);

    // Tính toán chiều cao động cho container biểu đồ
    const barHeight = 35; // Chiều cao ước tính cho mỗi thanh bar
    const chartPadding = 150; 
    const suggestedHeight = (assignmentTitles.length * barHeight) + chartPadding;
    const minChartHeight = 270; 
    const actualChartHeight = Math.max(suggestedHeight, minChartHeight);

    const chartContainer = ctxAssignment.canvas.parentNode;
    if (chartContainer) {
        chartContainer.style.height = `${actualChartHeight}px`;
    }

    new Chart(ctxAssignment, {
        type: 'bar',
        data: {
            labels: assignmentTitles,
            datasets: [{
                label: 'Average Assignment Grade',
                data: avgAssignmentGrades,
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y', // Biểu đồ thanh ngang
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Average Grades for Each Assignment'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.raw !== null) {
                                label += context.raw;
                            } else {
                                label += 'N/A';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Grade'
                    },
                    min: 0,
                    max: 10
                },
                y: {
                    title: {
                        display: true,
                        text: 'Assignment Title'
                    },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            }
        }
    });
}