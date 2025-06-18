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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Enrollments for <?= htmlspecialchars($course['title']) ?> | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_enrollment_approval.css">
   
</head>
<body>
    <?php include "includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-user-graduate"></i> Enrollments for <?= htmlspecialchars($course['title']) ?></h2>
        </div>

        <div class="course-management-overview">
            <h3><i class="fas fa-hourglass-half"></i> Pending Enrollment Requests</h3>
            <div class="course-management-content">
                <?php if ($pending_enrollments->num_rows > 0): ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Enrolled At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending_enrollments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= $row['enrolled_at'] ?></td>
                                    <td class="enrollment-actions">
                                        <form method="post">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="approve"><i class="fas fa-check-circle"></i> Approve</button>
                                            <button type="submit" name="action" value="reject" class="reject"><i class="fas fa-times-circle"></i> Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-enrollments"><i class="fas fa-info-circle"></i> No pending enrollment requests for this course.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="admin_course_enrollments.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/sidebar.js"></script>
</body>
</html>