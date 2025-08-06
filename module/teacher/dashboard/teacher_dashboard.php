<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
$teacher_id = $_SESSION['user_id'];

// Fetch courses taught by the teacher
$courses_taught_query = "SELECT id, title FROM courses WHERE teacher_id = ?";
$stmt = $conn->prepare($courses_taught_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses_taught_result = $stmt->get_result();

// CHỈNH SỬA: Thay vì chỉ lưu IDs và Titles riêng biệt, tạo mảng $courses_taught
$courses_taught = [];
$courses_taught_ids = [];
$courses_taught_titles = [];
while($row = $courses_taught_result->fetch_assoc()){
    $courses_taught[] = $row; // Thêm toàn bộ hàng vào mảng $courses_taught
    $courses_taught_ids[] = $row['id'];
    $courses_taught_titles[$row['id']] = $row['title']; // Vẫn giữ để dùng cho summary chart
}
$stmt->close();

// Fetch assignments to grade
$assignments_to_grade_query = "
    SELECT COUNT(DISTINCT asub.id) AS count_assignments
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ? AND asub.grade IS NULL;
";
$stmt = $conn->prepare($assignments_to_grade_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assignments_to_grade_result = $stmt->get_result();
$assignments_to_grade_count = $assignments_to_grade_result->fetch_assoc()['count_assignments'];
$stmt->close();

// === BẮT ĐẦU THÊM MỚI TỪ YÊU CẦU CỦA BẠN ===

// Fetch quick stats: total students enrolled in teacher's courses
$total_students_query = "
    SELECT COUNT(DISTINCT e.user_id) AS total_students
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE c.teacher_id = ?;
";
$stmt = $conn->prepare($total_students_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$total_students_result = $stmt->get_result();
$total_students = $total_students_result->fetch_assoc()['total_students'] ?? 0; // Thêm ?? 0 để tránh lỗi nếu không có kết quả
$stmt->close();

// Calculate average progress of students in teacher's courses
$avg_progress_query = "
    SELECT AVG(course_progress) AS avg_overall_progress
    FROM (
        SELECT
            p.user_id,
            p.course_id,
            (COUNT(DISTINCT p.lesson_id) * 100.0 / total_lessons.count) AS course_progress
        FROM
            progress p
        JOIN
            courses c ON p.course_id = c.id
        JOIN
            (SELECT course_id, COUNT(id) AS count FROM lessons GROUP BY course_id) AS total_lessons
            ON p.course_id = total_lessons.course_id
        WHERE
            c.teacher_id = ? AND p.is_completed = 1
        GROUP BY
            p.user_id, p.course_id
    ) AS student_course_progress;
";
$stmt = $conn->prepare($avg_progress_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$avg_progress_result = $stmt->get_result();
$avg_overall_progress = round($avg_progress_result->fetch_assoc()['avg_overall_progress'] ?? 0, 2); // Thêm ?? 0 và làm tròn
$stmt->close();

// === KẾT THÚC THÊM MỚI ===

// Dữ liệu cho biểu đồ tổng quát (không đổi)
$course_summary_data = [];
if (!empty($courses_taught_ids)) {
    foreach ($courses_taught_ids as $course_id) {
        $course_title = htmlspecialchars($courses_taught_titles[$course_id]);

        // Average Quiz Score for this course
        $avg_quiz_score = 'N/A';
        $stmt = $conn->prepare("
            SELECT AVG(score) AS avg_score
            FROM quiz_submissions
            WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) AND score IS NOT NULL
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['avg_score'] !== null) {
            $avg_quiz_score = round($row['avg_score'], 2);
        }
        $stmt->close();

        // Average Assignment Grade for this course
        $avg_assignment_grade = 'N/A';
        $stmt = $conn->prepare("
            SELECT AVG(grade) AS avg_grade
            FROM assignment_submissions
            WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) AND grade IS NOT NULL
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['avg_grade'] !== null) {
            $avg_assignment_grade = round($row['avg_grade'], 2);
        }
        $stmt->close();

        $course_summary_data[] = [
            'course_title' => $course_title,
            'avg_quiz_score' => $avg_quiz_score,
            'avg_assignment_grade' => $avg_assignment_grade
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_dashboard.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="teacher-dashboard-header">
            <h2>Welcome, <?= $fullname ?>!</h2>
        </div>
<div class="dashboard-section chart-section">
            <h3><i class="fas fa-chart-bar"></i> Course Performance Overview</h3>
            <div class="chart-container">
                <canvas id="coursePerformanceChart"></canvas>
            </div>
        </div>
        <div class="dashboard-section quick-stats-grid">
            <div class="overview-item">
                <i class="fas fa-book"></i>
                <span>Courses Taught</span>
                <strong><?= count($courses_taught) ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-tasks"></i>
                <span>Assignments to Grade</span>
                <strong><?= htmlspecialchars($assignments_to_grade_count) ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-user-graduate"></i>
                <span>Total Students</span>
                <strong><?= htmlspecialchars($total_students) ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-chart-line"></i>
                <span>Avg. Overall Progress</span>
                <strong><?= htmlspecialchars($avg_overall_progress) ?>%</strong>
            </div>
            </div>

        

        <div class="dashboard-section">
            <h3><i class="fas fa-chalkboard-teacher"></i> Your Courses</h3>
            <ul class="course-list">
                <?php if (!empty($courses_taught)): ?>
                    <?php foreach ($courses_taught as $course): ?>
                        <li>
                            <a href="../report/teacher_analytics.php?course_id=<?= htmlspecialchars($course['id']) ?>">
                                <?= htmlspecialchars($course['title']) ?>
                            </a>
                            <span>(ID: <?= htmlspecialchars($course['id']) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No courses assigned yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            <div class="notification-list">
                <?php if ($sys_notif_result->num_rows > 0): ?>
                    <?php while($notification = $sys_notif_result->fetch_assoc()): ?>
                        <div class="notification-item system-notification">
                            <strong>System Notification:</strong> <?= htmlspecialchars($notification['message']) ?>
                            <small><?= date("M d, Y H:i", strtotime($notification['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-item">
                        <p>No system notifications.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="logout-link">
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../../js/teacher_dashboard_charts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courseSummaryData = <?= json_encode($course_summary_data) ?>;
            console.log("Course Summary Data:", courseSummaryData);
            initCoursePerformanceChart(courseSummaryData);
        });
    </script>
</body>
</html>