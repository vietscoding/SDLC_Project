<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$title = $description = $department = ""; // Initialize variables
$error_message = "";

if (!isset($_GET['course_id'])) {
    $error_message = "Course ID missing.";
} else {
    $course_id = intval($_GET['course_id']);
    $teacher_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT title, description, department FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $course_id, $teacher_id);
    $stmt->execute();
    $stmt->bind_result($title, $description, $department);
    if (!$stmt->fetch()) {
        $error_message = "You are not allowed to view this course.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Details (Teacher) | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_course_detail.css">
</head>
<body>

    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-info-circle"></i> Course Details</h2>
        </div>

        <?php if (isset($error_message) && !empty($error_message)): ?>
            <div class="error-message">
                <h2><i class="fas fa-exclamation-triangle"></i> Error</h2>
                <p><?= $error_message ?></p>
            </div>
            <div class="navigation-links">
                <a href="teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            </div>
        <?php else: ?>
            <div class="progress-overview course-info-section">
                <h3><i class="fas fa-book-open"></i> Course Information</h3>
                <div class="progress-content">
                    <ul class="course-details-list">
                        <li><strong>Title:</strong> <?= htmlspecialchars($title) ?></li>
                        <li><strong>Department:</strong> <?= htmlspecialchars($department) ?></li>
                    </ul>
                    <?php if ($description): ?>
                        <p class="course-description"><?= nl2br(htmlspecialchars($description)) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="progress-overview manage-course-section">
                <h3><i class="fas fa-cog"></i> Manage Course</h3>
                <div class="progress-content">
                    <ul class="manage-links">
                        <li><a href="teacher_lessons.php?course_id=<?= $course_id ?>"><i class="fas fa-list-ol"></i> Manage Lessons</a></li>
                        <li><a href="teacher_quizzes.php?course_id=<?= $course_id ?>"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
                        <li><a href="teacher_enrollments.php?course_id=<?= $course_id ?>"><i class="fas fa-users"></i> View Enrollments</a></li>
                        <li><a href="teacher_analytics.php?course_id=<?= $course_id ?>"><i class="fas fa-chart-bar"></i> View Analytics</a></li>
                        <li><a href="teacher_progress.php?course_id=<?= $course_id ?>"><i class="fas fa-chart-line"></i> Track Student Progress</a></li>
                        <li><a href="teacher_assignments.php?course_id=<?= $course_id ?>"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
                    </ul>
                </div>
            </div>
            <div class="navigation-links">
                <a href="teacher_search_courses.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Search Courses</a>
            </div>
        <?php endif; ?>

        <?php include "includes/footer.php"; ?>
    </div>

<script src="js/teacher_sidebar.js"></script>
</body>
</html>