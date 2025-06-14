<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$course_id = $_GET['course_id'];

$course = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();

$pending_enrollments = $conn->query("
    SELECT e.id, u.fullname, u.email, e.enrolled_at
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    WHERE e.course_id = $course_id AND e.status = 'pending'
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enrollment_id = $_POST['enrollment_id'];
    $action = $_POST['action'];

    $new_status = $action == 'approve' ? 'approved' : 'rejected';
    $conn->query("UPDATE enrollments SET status='$new_status' WHERE id = $enrollment_id");
    
    header("Location: admin_enrollment_approval.php?course_id=$course_id");
    exit;
}
?>
<h2>Pending Enrollments for <?= htmlspecialchars($course['title']) ?></h2>
<table border="1" cellpadding="8">
    <tr>
        <th>Student Name</th>
        <th>Email</th>
        <th>Enrolled At</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $pending_enrollments->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['fullname']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= $row['enrolled_at'] ?></td>
        <td>
            <form method="post" style="display:inline">
                <input type="hidden" name="enrollment_id" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="approve">✔️ Approve</button>
                <button type="submit" name="action" value="reject">❌ Reject</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<a href="admin_course_enrollments.php">⬅ Back to Courses</a>
