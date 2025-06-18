<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$comment_id = $_GET['comment_id'] ?? 0;
$post_id = $_GET['post_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Lấy comment về để kiểm tra quyền sở hữu
$stmt = $conn->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Comment not found or permission denied.");
}
$comment = $result->fetch_assoc();

// Xử lý update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
    $stmt->bind_param("si", $content, $comment_id);
    $stmt->execute();
    header("Location: teacher_view_post.php?post_id=$post_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Comment</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_edit_comment.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Edit Comment</h2>
        </div>

        <div class="post-create-container">
            <form method="POST">
                <label for="content">Comment Content:</label>
                <textarea name="content" id="content" rows="5" cols="60" required><?= htmlspecialchars($comment['content']) ?></textarea><br><br>
                <div class="button-group">
                    <button type="submit"><i class="fas fa-save"></i> Update Comment</button>
                    <a href="teacher_view_post.php?post_id=<?= $post_id ?>" class="cancel-button"><i class="fas fa-arrow-left"></i> Cancel</a>
                </div>
            </form>
        </div>
        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
</body>
</html>