<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$courses = $conn->query("
    SELECT c.id, c.title, COUNT(e.id) AS pending_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'pending'
    GROUP BY c.id
    HAVING pending_count > 0
");

?>
<h2>Pending Enrollment Requests by Course</h2>
<ul>
    <?php while ($course = $courses->fetch_assoc()): ?>
        <li>
            <a href="admin_enrollment_approval.php?course_id=<?= $course['id'] ?>">
                <?= htmlspecialchars($course['title']) ?> (<?= $course['pending_count'] ?> pending)
            </a>
        </li>
    <?php endwhile; ?>
</ul>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>