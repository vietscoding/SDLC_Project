<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$courses = $conn->query("
    SELECT c.id, c.title, COUNT(e.id) AS pending_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'pending'
    GROUP BY c.id
    HAVING pending_count > 0
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Enrollments by Course | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_course_enrollments.css">
  
</head>
<body>
    <?php include "../../../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-hourglass-half"></i> Pending Enrollment Requests</h2>
        </div>

        <div class="course-management-overview">
            <h3><i class="fas fa-list-alt"></i> Courses with Pending Enrollments</h3>
            <div class="course-management-content">
                <?php if ($courses->num_rows > 0): ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Pending Requests</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['title']) ?></td>
                                    <td><?= $course['pending_count'] ?></td>
                                    <td class="enrollment-actions">
                                        <a href="admin_enrollment_approval.php?course_id=<?= $course['id'] ?>"><i class="fas fa-eye"></i> View Requests</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-enrollments"><i class="fas fa-info-circle"></i> No courses currently have pending enrollment requests.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/sidebar.js"></script>
</body>
</html>