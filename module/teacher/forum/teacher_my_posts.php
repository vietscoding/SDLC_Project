<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];

// Handle delete request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    // Start a transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // 1. Delete all comments associated with this post
        $stmt_comments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt_comments->bind_param("i", $post_id);
        $stmt_comments->execute();
        $stmt_comments->close();

        // 2. Delete any likes associated with this post
        $stmt_likes = $conn->prepare("DELETE FROM post_likes WHERE post_id = ?");
        $stmt_likes->bind_param("i", $post_id);
        $stmt_likes->execute();
        $stmt_likes->close();

        // 3. Delete the post itself
        // Ensure only the teacher's own posts can be deleted
        $stmt_post = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt_post->bind_param("ii", $post_id, $teacher_id);
        $stmt_post->execute();
        $stmt_post->close();

        // Commit the transaction if all operations were successful
        $conn->commit();
        header("Location: teacher_my_posts.php");
        exit;

    } catch (mysqli_sql_exception $e) {
        // Rollback the transaction in case of any error
        $conn->rollback();
        echo "Error deleting post: " . $e->getMessage();
        // For a production environment, you might log this error and show a user-friendly message
        exit;
    }
}

// Get all posts by the current teacher, including course title
$posts = $conn->query("
    SELECT p.id, p.content, p.media_url, p.attachment, p.created_at, c.title as course_title
    FROM posts p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = $teacher_id
    ORDER BY p.created_at DESC
");

?>
<!DOCTYPE html>
<html>
<head>
    <title>My Posts (Teacher)</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_my_posts.css">
    
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-file-alt"></i> My Posts</h2>
        </div>

        <div class="forum-posts-container">
            <?php if ($posts->num_rows > 0): ?>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-author-info">
                            <strong>Course: <?= htmlspecialchars($post['course_title']) ?></strong>
                            <span>Posted On: <?= $post['created_at'] ?></span>
                        </div>

                        <div class="post-content">
                            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        </div>

                        <?php if ($post['media_url']): ?>
                            <div class="post-media">
                                <?php
                                $ext = strtolower(pathinfo($post['media_url'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                                ?>
                                    <img src="<?= htmlspecialchars($post['media_url']) ?>" alt="Image">
                                <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                    <video src="<?= htmlspecialchars($post['media_url']) ?>" controls></video>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($post['media_url']) ?>" target="_blank"><i class="fas fa-file-image"></i> View Media</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($post['attachment']): ?>
                            <div class="post-attachment">
                                <a href="<?= htmlspecialchars($post['attachment']) ?>" download>
                                    <i class="fas fa-paperclip"></i> Download Attachment
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="post-actions-buttons">
                            <a href="teacher_edit_post.php?post_id=<?= $post['id'] ?>" class="action-button edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="teacher_my_posts.php?action=delete&post_id=<?= $post['id'] ?>" class="action-button delete-btn" onclick="return confirm('Are you sure you want to delete this post? This will also delete all associated comments and likes.');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts-message"><i class="fas fa-exclamation-circle"></i> You haven't made any posts yet.</p>
            <?php endif; ?>
        </div>

        <div class="back-button-container">
            <a href="teacher_forum_courses.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Course Forums
            </a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>