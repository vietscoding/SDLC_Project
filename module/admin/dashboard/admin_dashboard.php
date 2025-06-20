<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Thống kê số liệu
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

$total_lessons_res = $conn->query("SELECT COUNT(*) AS total FROM lessons");
$total_lessons = $total_lessons_res->fetch_assoc()['total'] ?? 0;

$completed_students = 0;
$total_students_in_progress = 0;

if ($total_lessons > 0) {
    $students_progress = $conn->query("
        SELECT user_id, COUNT(*) AS completed
        FROM progress
        WHERE is_completed = 1
        GROUP BY user_id
    ");
    while ($row = $students_progress->fetch_assoc()) {
        $total_students_in_progress++;
        if ($row['completed'] == $total_lessons) {
            $completed_students++;
        }
    }
    $completion_rate = $total_students_in_progress > 0 ? round(($completed_students / $total_students_in_progress) * 100, 2) : 0;
} else {
    $completion_rate = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/admin/admin_dashboard.css"> 
   
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-dashboard-header">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
    </div>

    <div class="system-overview">
        <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
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
</body>
</html>