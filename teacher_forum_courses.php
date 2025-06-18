<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];

$courses_query = $conn->query("SELECT id, title FROM courses WHERE teacher_id = $teacher_id ORDER BY id DESC"); // Fetching only what's needed for this page

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Courses' Forums | BTEC FPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_forum_courses.css">
</head>
<body>

    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Your Courses' Forums</h2>
        </div>

        <?php if ($courses_query->num_rows > 0): ?>
            <div class="progress-overview">
                <h3><i class="fas fa-book-reader"></i> Available Forums</h3>
                <div class="progress-content">
                    <ul class="course-list">
                        <?php while ($course = $courses_query->fetch_assoc()): ?>
                            <li class="course-item">
                                <div class="course-item-image" style="background-image: url('https://source.unsplash.com/random/800x400?forum,discussion&sig=<?= $course['id'] ?>');">
                                    <span class="course-icon"><i class="fas fa-comment-alt"></i></span>
                                </div>
                                <div class="course-item-content">
                                    <h3 class="course-item-title"><?= htmlspecialchars($course['title']) ?> Forum</h3>
                                    <p class="course-item-description">Access the discussion forum for this course.</p>
                                    <div class="course-item-actions">
                                        <a href="teacher_forum.php?course_id=<?= $course['id'] ?>"><i class="fas fa-sign-in-alt"></i> Go to Forum</a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <p class="no-results"><i class="fas fa-exclamation-circle"></i> You are not currently teaching any courses with associated forums.</p>
        <?php endif; ?>

        <div class="back-to-courses" style="margin-top: 30px; text-align: center;">
            <a href="teacher_my_posts.php" class="back-button" style="background-color: var(--accent-color);"><i class="fas fa-clipboard-list"></i> View and Manage My Posts</a>
            <a href="teacher_dashboard.php" class="back-button" style="margin-left: 15px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>

</body>
</html>