<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Thống kê số lượng học viên
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

// Thống kê tổng số khóa học
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];

// Thống kê tổng số quiz
$total_quizzes = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];

// Thống kê tổng số bài nộp quiz
$total_submissions = $conn->query("SELECT COUNT(*) AS total FROM quiz_submissions")->fetch_assoc()['total'];

// Thống kê tổng số assignments
$total_assignments = $conn->query("SELECT COUNT(*) AS total FROM assignments")->fetch_assoc()['total'];

// Tổng số bài nộp assignment
$total_assignment_submissions = $conn->query("SELECT COUNT(*) AS total FROM assignment_submissions")->fetch_assoc()['total'];

// Thống kê tiến độ trung bình sinh viên (% lessons đã hoàn thành trên tổng bài học)
$progress_result = $conn->query("
    SELECT
        ROUND(AVG(completed.lesson_completed / total.total_lessons) * 100, 2) AS avg_progress
    FROM
        (SELECT user_id, COUNT(*) AS lesson_completed FROM progress WHERE is_completed = 1 GROUP BY user_id) AS completed
    JOIN
        (SELECT COUNT(*) AS total_lessons FROM lessons) AS total
");
$avg_progress = $progress_result->fetch_assoc()['avg_progress'] ?? 0;

// Thống kê trung bình điểm từng assignment
$assignment_stats = $conn->query("
    SELECT a.title, AVG(s.grade) AS avg_grade, COUNT(s.id) AS submission_count
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
    GROUP BY a.id
    ORDER BY a.id DESC
");

// Thống kê trung bình điểm quiz từng quiz
$quiz_stats = $conn->query("
    SELECT q.title, AVG(s.score) AS avg_score, COUNT(s.id) AS submission_count
    FROM quizzes q
    LEFT JOIN quiz_submissions s ON q.id = s.quiz_id
    GROUP BY q.id
    ORDER BY q.id DESC
");

// Tính Course Completion Rate (% học viên hoàn thành tất cả bài học)
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
    <title>System Reports (Admin) | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
 <link rel="stylesheet" href="css/admin/admin_reports.css">
 
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-dashboard-header">
        <h2><i class="fas fa-chart-line"></i> System Reports</h2>
    </div>

    <div class="system-overview">
        <h3><i class="fas fa-chart-pie"></i> Summary Statistics</h3>
        <div class="stats-cards-container">
            <div class="stat-card">
                <i class="fas fa-user-graduate icon"></i>
                <span class="label">Total Students</span>
                <strong class="value"><?= $total_students ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-book icon"></i>
                <span class="label">Total Courses</span>
                <strong class="value"><?= $total_courses ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-question-circle icon"></i>
                <span class="label">Total Quizzes</span>
                <strong class="value"><?= $total_quizzes ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt icon"></i>
                <span class="label">Total Quiz Submissions</span>
                <strong class="value"><?= $total_submissions ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks icon"></i>
                <span class="label">Total Assignments</span>
                <strong class="value"><?= $total_assignments ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-upload icon"></i>
                <span class="label">Total Assignment Submissions</span>
                <strong class="value"><?= $total_assignment_submissions ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner icon"></i>
                <span class="label">Average Student Progress</span>
                <strong class="value"><?= $avg_progress ?>%</strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle icon"></i>
                <span class="label">Course Completion Rate</span>
                <strong class="value"><?= $completion_rate ?>%</strong>
            </div>
        </div>
    </div>

    <div class="report-section">
        <h3><i class="fas fa-chart-bar"></i> Quiz Performance Statistics</h3>
        <?php if ($quiz_stats->num_rows > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Quiz Title</th>
                        <th>Average Score</th>
                        <th>Submissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $quiz_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['avg_score'] !== null ? round($row['avg_score'], 2) : 'N/A' ?></td>
                            <td><?= $row['submission_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-records"><i class="fas fa-exclamation-circle"></i> No quiz records found.</p>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h3><i class="fas fa-clipboard-check"></i> Assignment Performance Statistics</h3>
        <?php if ($assignment_stats->num_rows > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Assignment Title</th>
                        <th>Average Grade</th>
                        <th>Submissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $assignment_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['avg_grade'] !== null ? round($row['avg_grade'], 2) : 'N/A' ?></td>
                            <td><?= $row['submission_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-records"><i class="fas fa-exclamation-circle"></i> No assignment records found.</p>
        <?php endif; ?>
    </div>

    <div class="back-link">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>
<script src="js/sidebar.js"></script>
</body>
</html>