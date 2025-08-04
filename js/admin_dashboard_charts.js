// Hàm để khởi tạo biểu đồ tổng quan hiệu suất khóa học cho Admin
function initAdminCoursePerformanceChart(adminCoursePerformanceData) {
    const ctx = document.getElementById('adminCoursePerformanceChart').getContext('2d');

    const courseLabels = adminCoursePerformanceData.map(c => c.course_title);
    const avgQuizScores = adminCoursePerformanceData.map(c => c.avg_quiz_score === 'N/A' ? null : c.avg_quiz_score);
    const avgAssignmentGrades = adminCoursePerformanceData.map(c => c.avg_assignment_grade === 'N/A' ? null : c.avg_assignment_grade);

    // Tính toán chiều cao động cho container biểu đồ
    // Mỗi thanh cần khoảng 30-40px không gian, cộng thêm padding/margin
    const barHeight = 35; // Chiều cao ước tính cho mỗi thanh bar (bao gồm khoảng cách)
    const chartPadding = 150; // Khoảng đệm tổng cộng cho phần trên/dưới của biểu đồ
    const suggestedHeight = (courseLabels.length * barHeight) + chartPadding;

    // Đặt chiều cao tối thiểu để biểu đồ vẫn hiển thị tốt ngay cả khi có ít khóa học
    const minChartHeight = 200; 
    const actualChartHeight = Math.max(suggestedHeight, minChartHeight);

    // Cập nhật chiều cao của chart-container
    const chartContainer = document.querySelector('.chart-container');
    if (chartContainer) {
        chartContainer.style.height = `${actualChartHeight}px`;
    }

    new Chart(ctx, {
        type: 'bar', // Loại biểu đồ vẫn là 'bar'
        data: {
            labels: courseLabels,
            datasets: [
                {
                    label: 'Average Quiz Score',
                    data: avgQuizScores,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Average Assignment Grade',
                    data: avgAssignmentGrades,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // Đổi trục index sang 'y' để biểu đồ thành thanh ngang
            responsive: true,
            maintainAspectRatio: false, // Quan trọng để chiều cao biểu đồ có thể tự điều chỉnh
            plugins: {
                title: {
                    display: true,
                    text: 'Average Quiz Scores and Assignment Grades Across All Courses'
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
                x: { // Trục X bây giờ là trục giá trị (điểm số)
                    title: {
                        display: true,
                        text: 'Average Score/Grade'
                    },
                    min: 0,
                    max: 10 // Giả sử điểm từ 0-100
                },
                y: { // Trục Y bây giờ là trục danh mục (Course Titles)
                    title: {
                        display: true,
                        text: 'Course'
                    },
                    ticks: {
                        autoSkip: false, // Không tự động bỏ qua nhãn
                        maxRotation: 0, // Không xoay nhãn
                        minRotation: 0 // Đảm bảo nhãn luôn nằm ngang
                    }
                }
            }
        }
    });
}