<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$courses = $conn->query("SELECT id, title FROM courses");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Forum by Course</title>
</head>
<body>
<h2>📚 Forum Management - Choose a Course</h2>

<ul>
<?php while ($course = $courses->fetch_assoc()): ?>
    <li>
        <a href="admin_manage_course_posts.php?course_id=<?= $course['id'] ?>">
            <?= htmlspecialchars($course['title']) ?>
        </a>
    </li>
<?php endwhile; ?>
</ul>
<a href="admin_forum.php">View All Pending Posts</a><br>
<a href="admin_dashboard.php">← Back to Dashboard</a>
</body>
</html>
