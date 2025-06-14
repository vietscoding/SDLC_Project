<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy danh sách các khóa học học sinh đã enroll
$user_id = $_SESSION['user_id'];

$courses = $conn->query("
    SELECT c.id, c.title 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = $user_id
");

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Forums</title>
</head>
<body>
<h2>Forums for Your Courses</h2>

<?php if ($courses->num_rows > 0): ?>
    <ul>
        <?php while($row = $courses->fetch_assoc()): ?>
            <li>
                <a href="student_forum.php?course_id=<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['title']) ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>You have not enrolled in any courses yet.</p>
<?php endif; ?>

<a href="student_my_posts.php">View and Manage My Posts</a><br><br> <a href="student_dashboard.php">← Back to Dashboard</a>

</body>
</html>
