<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$courses = $conn->query("SELECT id, title FROM courses");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Forum by Course | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_manage_forum.css">
   
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="inner-content-wrapper">
        <div class="admin-dashboard-header">
            <h2><i class="fas fa-list-alt"></i> Manage Forum by Course</h2>
            </div>

        <div class="forum-posts-section">
            <h3><i class="fas fa-book"></i> Select a Course to Manage Posts</h3>
            <div class="posts-container">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <a href="admin_manage_course_posts.php?course_id=<?= $course['id'] ?>" class="course-item-link">
                            <div class="course-item-content">
                                <h4><?= htmlspecialchars($course['title']) ?></h4>
                                <p>Click to manage posts for this course.</p>
                            </div>
                            <div class="course-item-icon">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-medium);">No courses found to manage.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="navigation-links">
            <a href="admin_forum.php" class="view-all-posts-btn"><i class="fas fa-hourglass-half"></i> View Pending Posts</a>
            <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    <?php include "../../../includes/footer.php"; ?>
</div>
<script src="../../../js/sidebar.js"></script>
</body>
</html>