<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$pending_posts = $conn->query("
    SELECT p.*, u.fullname, c.title AS course_title
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Forum Posts | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_forum.css">
   
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="inner-content-wrapper"> <div class="admin-dashboard-header">
            <h2><i class="fas fa-comments"></i> Manage Forum Posts</h2>
            <div class="header-actions">
                <a href="admin_manage_forum.php" class="view-all-posts-btn"><i class="fas fa-eye"></i> View All Posts</a>
            </div>
        </div>

        <div class="forum-posts-section">
            <h3><i class="fas fa-hourglass-half"></i> Pending Forum Posts</h3>
            <div class="posts-container">
                <?php if ($pending_posts->num_rows > 0): ?>
                    <?php while ($post = $pending_posts->fetch_assoc()): ?>
                        <div class="forum-post-item">
                            <div class="post-meta">
                                <strong>Course:</strong> <?= htmlspecialchars($post['course_title']) ?><br>
                                <strong>By:</strong> <?= htmlspecialchars($post['fullname']) ?><br>
                                <strong>At:</strong> <?= $post['created_at'] ?>
                            </div>
                            <div class="post-content">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </div>
                            <?php if ($post['media_url']): ?>
                                <div class="post-media">
                                    <?php
                                    $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                    ?>
                                        <img src="<?= $post['media_url'] ?>" alt="Post Media">
                                    <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                        <video src="<?= $post['media_url'] ?>" controls></video>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($post['attachment']): ?>
                                <div class="post-attachment">
                                    <a href="<?= $post['attachment'] ?>" download><i class="fas fa-paperclip"></i> Download Attachment</a>
                                </div>
                            <?php endif; ?>
                            <div class="post-actions">
                                <a href="admin_approve_post.php?post_id=<?= $post['id'] ?>" class="approve-btn"><i class="fas fa-check-circle"></i> Approve</a>
                                <a href="admin_reject_post.php?post_id=<?= $post['id'] ?>" class="reject-btn"><i class="fas fa-times-circle"></i> Reject</a>
                                <a href="#" class="delete-btn" data-post-id="<?= $post['id'] ?>"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-medium);">No pending forum posts.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="navigation-links">
            <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div> <?php include "../../../includes/footer.php"; ?>
</div>
<script src="../../../js/sidebar.js"></script>
<script src="../../../js/delete_post.js"></script>
</body>
</html>