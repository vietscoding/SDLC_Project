<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];

$courses = $conn->query("SELECT * FROM courses WHERE teacher_id = $teacher_id");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Courses' Forums</title>
</head>
<body>
<h2>Your Courses' Forums</h2>
<?php while ($course = $courses->fetch_assoc()): ?>
    <p>
        <a href="teacher_forum.php?course_id=<?= $course['id'] ?>">
            <?= htmlspecialchars($course['title']) ?>
        </a>
    </p>
<?php endwhile; ?>
<a href="teacher_my_posts.php">View and Manage My Posts</a><br><br> <a href="teacher_dashboard.php">← Back to Dashboard</a>
</body>
</html>
