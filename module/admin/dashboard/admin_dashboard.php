<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Thống kê số liệu hiện có
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$total_teachers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'")->fetch_assoc()['total'];
$total_quizzes  = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];
$total_submissions = $conn->query("SELECT COUNT(*) AS total FROM quiz_submissions")->fetch_assoc()['total'];
$total_assignment_submissions = $conn->query("SELECT COUNT(*) AS total FROM assignment_submissions")->fetch_assoc()['total'];

$progress_result = $conn->query("
    SELECT 
        ROUND(AVG(completed.lesson_completed / total.total_lessons) * 100, 2) AS avg_progress
    FROM 
        (SELECT user_id, COUNT(*) AS lesson_completed FROM progress WHERE is_completed = 1 GROUP BY user_id) AS completed
    JOIN 
        (SELECT COUNT(*) AS total_lessons FROM lessons) AS total
");
$avg_progress = $progress_result->fetch_assoc()['avg_progress'] ?? 0;

$completion_rate_result = $conn->query("
    SELECT 
        ROUND(AVG(CASE WHEN total_lessons.count > 0 THEN (completed_lessons.count * 100.0 / total_lessons.count) ELSE 0 END), 2) AS completion_rate
    FROM users u
    LEFT JOIN (
        SELECT user_id, COUNT(DISTINCT lesson_id) as count
        FROM progress
        WHERE is_completed = 1
        GROUP BY user_id
    ) AS completed_lessons ON u.id = completed_lessons.user_id
    LEFT JOIN (
        SELECT COUNT(DISTINCT id) as count FROM lessons
    ) AS total_lessons ON 1=1
    WHERE u.role = 'student' AND total_lessons.count > 0;
");
$completion_rate = $completion_rate_result->fetch_assoc()['completion_rate'] ?? 0;

// === BẮT ĐẦU THÊM MỚI CHO BIỂU ĐỒ TỔNG QUÁT GIỐNG TEACHER DASHBOARD ===

$admin_course_performance_data = [];

// Lấy tất cả các khóa học
$all_courses_query = "SELECT id, title FROM courses";
$all_courses_result = $conn->query($all_courses_query);

if ($all_courses_result->num_rows > 0) {
    while($course_row = $all_courses_result->fetch_assoc()){
        $course_id = $course_row['id'];
        $course_title = htmlspecialchars($course_row['title']);

        // Điểm trung bình Quiz cho khóa học này
        $avg_quiz_score = 'N/A';
        $stmt_quiz = $conn->prepare("
            SELECT AVG(score) AS avg_score
            FROM quiz_submissions
            WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) AND score IS NOT NULL
        ");
        $stmt_quiz->bind_param("i", $course_id);
        $stmt_quiz->execute();
        $quiz_result = $stmt_quiz->get_result();
        $quiz_row = $quiz_result->fetch_assoc();
        if ($quiz_row['avg_score'] !== null) {
            $avg_quiz_score = round($quiz_row['avg_score'], 2);
        }
        $stmt_quiz->close();

        // Điểm trung bình Assignment cho khóa học này
        $avg_assignment_grade = 'N/A';
        $stmt_assignment = $conn->prepare("
            SELECT AVG(grade) AS avg_grade
            FROM assignment_submissions
            WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) AND grade IS NOT NULL
        ");
        $stmt_assignment->bind_param("i", $course_id);
        $stmt_assignment->execute();
        $assignment_result = $stmt_assignment->get_result();
        $assignment_row = $assignment_result->fetch_assoc();
        if ($assignment_row['avg_grade'] !== null) {
            $avg_assignment_grade = round($assignment_row['avg_grade'], 2);
        }
        $stmt_assignment->close();

        $admin_course_performance_data[] = [
            'course_title' => $course_title,
            'avg_quiz_score' => $avg_quiz_score,
            'avg_assignment_grade' => $avg_assignment_grade
        ];
    }
}

// === KẾT THÚC THÊM MỚI ===

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_dashboard.css">
</head>
<body>
    <?php include "../../../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-dashboard-header">
            <h2>Welcome, Admin!</h2>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-tachometer-alt"></i> System Overview</h3>
            <div class="dashboard-section chart-section">
            <h3><i class="fas fa-chart-bar"></i> Overall Course Performance Overview</h3>
            <div class="chart-container">
                <canvas id="adminCoursePerformanceChart"></canvas>
            </div>
        </div>
            <div class="system-overview-content">
                <div class="overview-item">
                    <i class="fas fa-book"></i>
                    <span>Total Courses</span>
                    <strong><?= $total_courses ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Total Students</span>
                    <strong><?= $total_students ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Total Teachers</span>
                    <strong><?= $total_teachers ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Total Quizzes</span>
                    <strong><?= $total_quizzes ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Quiz Submissions</span>
                    <strong><?= $total_submissions ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-upload"></i>
                    <span>Assignment Submissions</span>
                    <strong><?= $total_assignment_submissions ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-spinner"></i>
                    <span>Avg Progress</span>
                    <strong><?= $avg_progress ?>%</strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Completion Rate</span>
                    <strong><?= $completion_rate ?>%</strong>
                </div>
            </div>
        </div>

        
        <div class="admin-actions">
          </div>

        <div class="logout-link">
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../../js/admin_dashboard_charts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminCoursePerformanceData = <?= json_encode($admin_course_performance_data) ?>;
            console.log("Admin Course Performance Data:", adminCoursePerformanceData);
            initAdminCoursePerformanceChart(adminCoursePerformanceData);
        });
    </script>
</body>
</html>