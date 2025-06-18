<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$comment_id = $_GET['comment_id'];
$post_id = $_GET['post_id'];

// Kiểm tra quyền
$check = $conn->query("SELECT * FROM comments WHERE id=$comment_id AND user_id={$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo "Permission denied.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_content = $_POST['new_content'];
    $conn->query("UPDATE comments SET content='$new_content' WHERE id=$comment_id");
    header("Location: student_view_post.php?post_id=$post_id");
    exit;
}

$comment = $check->fetch_assoc();

// For the header, we'll display the Post ID directly since 'title' might not exist in 'posts'
$header_text = "Editing Comment for Post ID: " . htmlspecialchars($post_id);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Comment | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/student_edit_comment.css">
   
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comment-dots"></i> <?= $header_text ?></h2>
        </div>

        <div class="comment-form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="new_content">Comment Content:</label>
                    <textarea name="new_content" id="new_content" rows="8" required><?= htmlspecialchars($comment['content']) ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Comment</button>
                    <a href="student_view_post.php?post_id=<?= $post_id ?>" class="cancel-btn"><i class="fas fa-times-circle"></i> Cancel</a>
                </div>
            </form>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/student_sidebar.js"></script>
</body>
</html>