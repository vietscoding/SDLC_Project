// Hàm tạo màu ngẫu nhiên cho các đường biểu đồ
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

// Hàm khởi tạo biểu đồ quiz
function initQuizScoreChart(studentQuizTrendsData) {
    const ctxQuiz = document.getElementById('studentQuizScoreChart').getContext('2d');

    // Destroy existing chart instance if it exists
    const existingChart = Chart.getChart('studentQuizScoreChart');
    if (existingChart) {
        existingChart.destroy();
    }

    const quizDatasets = [];
    let quizLabels = [];

    // Lấy labels từ sinh viên đầu tiên trong dữ liệu đã lọc (hoặc toàn bộ dữ liệu)
    if (studentQuizTrendsData.length > 0 && studentQuizTrendsData[0].quiz_titles) {
        quizLabels = studentQuizTrendsData[0].quiz_titles;
    } else {
        console.warn("No quiz data available or quiz_titles not found for the selected student(s). Chart may not display correctly.");
    }

    studentQuizTrendsData.forEach(student => {
        const dataPoints = student.moving_average_scores.slice(0, quizLabels.length);
        console.log(`Student: ${student.student_name}, Data Points (Quiz):`, dataPoints);

        quizDatasets.push({
            label: student.student_name,
            data: dataPoints,
            borderColor: getRandomColor(),
            fill: false,
            tension: 0.4
        });
    });

    if (quizDatasets.length > 0 && quizLabels.length > 0) {
        new Chart(ctxQuiz, {
            type: 'line',
            data: {
                labels: quizLabels,
                datasets: quizDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Student Quiz Scores Over Time (Moving Average)'
                    },
                    tooltip: { // Add tooltip for better interaction
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Quiz'
                        },
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Score'
                        },
                        min: 0,
                        max: 10
                    }
                }
            }
        });
    } else {
        ctxQuiz.canvas.parentNode.innerHTML = "<p style='text-align: center; margin-top: 20px;'>No quiz data available to display trends for selected students.</p>";
    }
}

// === BẮT ĐẦU THÊM MỚI HÀM CHO BIỂU ĐỒ ASSIGNMENT ===
function initAssignmentGradeChart(studentAssignmentTrendsData) {
    const ctxAssignment = document.getElementById('studentAssignmentGradeChart').getContext('2d');

    // Destroy existing chart instance if it exists
    const existingChart = Chart.getChart('studentAssignmentGradeChart');
    if (existingChart) {
        existingChart.destroy();
    }

    const assignmentDatasets = [];
    let assignmentLabels = [];

    // Lấy labels từ sinh viên đầu tiên trong dữ liệu đã lọc (hoặc toàn bộ dữ liệu)
    if (studentAssignmentTrendsData.length > 0 && studentAssignmentTrendsData[0].assignment_titles) {
        assignmentLabels = studentAssignmentTrendsData[0].assignment_titles;
    } else {
        console.warn("No assignment data available or assignment_titles not found for the selected student(s). Chart may not display correctly.");
    }

    studentAssignmentTrendsData.forEach(student => {
        const dataPoints = student.moving_average_grades.slice(0, assignmentLabels.length);
        console.log(`Student: ${student.student_name}, Data Points (Assignment):`, dataPoints);

        assignmentDatasets.push({
            label: student.student_name,
            data: dataPoints,
            borderColor: getRandomColor(),
            fill: false,
            tension: 0.4
        });
    });

    if (assignmentDatasets.length > 0 && assignmentLabels.length > 0) {
        new Chart(ctxAssignment, {
            type: 'line',
            data: {
                labels: assignmentLabels,
                datasets: assignmentDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Student Assignment Grades Over Time (Moving Average)'
                    },
                    tooltip: { // Add tooltip for better interaction
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Assignment'
                        },
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Grade'
                        },
                        min: 0,
                        max: 10 // Giả sử điểm assignment từ 0-10, điều chỉnh nếu thang điểm khác (ví dụ 0-100)
                    }
                }
            }
        });
    } else {
        ctxAssignment.canvas.parentNode.innerHTML = "<p style='text-align: center; margin-top: 20px;'>No assignment data available to display trends for selected students.</p>";
    }
}
// === KẾT THÚC THÊM MỚI HÀM CHO BIỂU ĐỒ ASSIGNMENT ===