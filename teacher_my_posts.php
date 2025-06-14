<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

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
</head>
<body>
<h2>My Posts</h2>

<?php if ($posts->num_rows > 0): ?>
    <ul>
        <?php while($row = $posts->fetch_assoc()): ?>
            <li>
                <strong>Course:</strong> <?= htmlspecialchars($row['course_title']) ?><br>
                <strong>Posted On:</strong> <?= $row['created_at'] ?><br>
                <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                <?php if ($row['media_url']): ?>
                    <?php
                    $ext = strtolower(pathinfo($row['media_url'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])):
                    ?>
                        <p><img src="<?= htmlspecialchars($row['media_url']) ?>" style="max-width:300px"></p>
                    <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                        <p><video src="<?= htmlspecialchars($row['media_url']) ?>" controls style="max-width:300px"></video></p>
                    <?php else: ?>
                        <p><a href="<?= htmlspecialchars($row['media_url']) ?>" target="_blank">View Media</a></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($row['attachment']): ?>
                    <p><a href="<?= htmlspecialchars($row['attachment']) ?>" download>Download Attachment</a></p>
                <?php endif; ?>
                <a href="teacher_edit_post.php?post_id=<?= $row['id'] ?>">Edit</a> |
                <a href="teacher_my_posts.php?action=delete&post_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                <hr>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>You haven't made any posts yet.</p>
<?php endif; ?>

<a href="teacher_forum_courses.php">← Back to Course Forums</a>
</body>
</html>