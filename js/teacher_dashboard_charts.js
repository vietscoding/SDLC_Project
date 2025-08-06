// Hàm để khởi tạo biểu đồ tổng quan hiệu suất khóa học
function initCoursePerformanceChart(courseSummaryData) {
    const ctx = document.getElementById('coursePerformanceChart').getContext('2d');

    const courseLabels = courseSummaryData.map(c => c.course_title);
    const avgQuizScores = courseSummaryData.map(c => c.avg_quiz_score === 'N/A' ? null : c.avg_quiz_score);
    const avgAssignmentGrades = courseSummaryData.map(c => c.avg_assignment_grade === 'N/A' ? null : c.avg_assignment_grade);

    new Chart(ctx, {
        type: 'bar', // Hoặc 'line', tùy thuộc vào cách bạn muốn thể hiện
        data: {
            labels: courseLabels,
            datasets: [
                {
                    label: 'Average Quiz Score',
                    data: avgQuizScores,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Average Assignment Grade',
                    data: avgAssignmentGrades,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Average Quiz Scores and Assignment Grades Per Course'
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
                        text: 'Course'
                    },
                    ticks: {
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 0
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Average Score/Grade'
                    },
                    min: 0,
                    max: 10 // Giả sử điểm từ 0-100. Điều chỉnh nếu thang điểm assignment khác (ví dụ 0-10)
                }
            }
        }
    });
}