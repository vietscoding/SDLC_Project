<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? 0;

// Lấy tên khóa học
$course = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();
if (!$course) {
    echo "Invalid Course ID";
    exit;
}

// Lấy danh sách bài post kèm like_count và trạng thái like của người dùng hiện tại
$posts = $conn->query("
    SELECT p.*, u.fullname,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = {$teacher_id}) AS is_liked_by_user
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.course_id = $course_id AND p.status = 'approved'
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forum - <?= htmlspecialchars($course['title']) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <style>
         body {
            font-family: 'Poppins', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s, color 0.4s;
        }

        .main-content {
            margin-left: 280px; /* Must match sidebar width */
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--background-light);
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
        }

        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-page-header h2 {
            font-size: 2.2em;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .admin-page-header h2 i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        .create-post-section {
            background-color: var(--background-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px var(--shadow-light);
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .create-post-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .create-post-btn i {
            margin-right: 10px;
            font-size: 1.1em;
        }

        .create-post-btn:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .forum-posts-container {
            display: flex; /* Stack posts vertically */
            flex-direction: column;
            align-items: center; /* Center posts horizontally */
            gap: 25px;
        }

        .post-card {
            background-color: var(--background-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px var(--shadow-light);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%; /* Take full width of its container */
            max-width: 800px; /* Limit the maximum width of the post card */
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }

        .post-author-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .post-author-info strong {
            font-size: 1.1em;
            color: var(--text-dark);
            margin-right: 10px;
        }

        .post-author-info span {
            font-size: 0.85em;
            color: var(--text-medium);
        }

        .post-content {
            font-size: 1em;
            color: var(--text-dark);
            margin-bottom: 15px;
            line-height: 1.7;
        }

        .post-media {
            margin: 20px 0;
            text-align: center;
        }
        .post-media img, .post-media video {
            max-width: 100%; /* Make media responsive */
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 400px; /* Limit height for large media */
            object-fit: contain; /* Ensure media is contained within its bounds */
        }

        .post-attachment {
            margin-bottom: 20px;
        }
        .post-attachment a {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .post-attachment a i {
            margin-right: 8px;
        }
        .post-attachment a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .post-actions {
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Align items to the start */
            gap: 15px; /* Space between buttons */
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            margin-top: 20px;
        }

        button.like-btn {
            background: #e9f7ff; /* Light primary background for like button */
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            /* Removed hover transition properties */
        }

        button.like-btn i {
            margin-right: 8px;
        }

        /* Removed button.like-btn:hover rule entirely */

        /* Style for liked state */
        button.like-btn.liked {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .post-comments-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background-color: #f1f1f1; /* Neutral background for comments link */
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .post-comments-link i {
            margin-right: 8px;
        }

        .post-comments-link:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }

        .no-posts-message {
            background-color: var(--background-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px var(--shadow-light);
            margin-top: 30px;
            color: var(--text-medium);
            font-style: italic;
            text-align: center;
            font-size: 1.1em;
            border: 1px dashed var(--border-color);
        }
        .no-posts-message i {
            margin-right: 10px;
            color: var(--accent-color);
        }

        .back-button-container {
            margin-top: 40px;
            text-align: center;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }

        .back-button i {
            margin-right: 10px;
            font-size: 1.1em;
        }

        .back-button:hover {
            background-color: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 600px) {
            .admin-page-header h2 {
                font-size: 1.8em;
            }

            .create-post-btn {
                width: 100%;
                justify-content: center;
            }

            .post-card {
                padding: 20px;
            }

            .post-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            button.like-btn, .post-comments-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-comments"></i> Forum: <?= htmlspecialchars($course['title']) ?></h2>
        </div>

        <div class="create-post-section">
            <a href="teacher_create_post.php?course_id=<?= $course_id ?>" class="create-post-btn">
                <i class="fas fa-plus-circle"></i> Create New Post
            </a>
        </div>

        <div class="forum-posts-container">
            <?php if ($posts->num_rows > 0): ?>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-author-info">
                            <strong><?= htmlspecialchars($post['fullname']) ?></strong>
                            <span>(<?= $post['created_at'] ?>)</span>
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
                                    <img src="<?= $post['media_url'] ?>" alt="Image">
                                <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                    <video src="<?= $post['media_url'] ?>" controls></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($post['attachment']): ?>
                            <div class="post-attachment">
                                <a href="<?= $post['attachment'] ?>" download>
                                    <i class="fas fa-paperclip"></i> Download Attachment
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="post-actions">
                            <button class="like-btn <?= $post['is_liked_by_user'] > 0 ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
                                <i class="fas fa-thumbs-up"></i> Like (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>)
                            </button>
                            <a href="teacher_view_post.php?post_id=<?= $post['id'] ?>" class="post-comments-link">
                                <i class="fas fa-comment"></i> View & Comment
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts-message"><i class="fas fa-exclamation-circle"></i> No posts found for this course yet. Be the first to create one!</p>
            <?php endif; ?>
        </div>

        <div class="back-button-container">
            <a href="teacher_forum_courses.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Course Forums
            </a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
    <script src="js/like.js?v=<?= filemtime('js/like.js') ?>"></script>
    <script>
window.addEventListener('pageshow', function (event) {
    // If the page was restored from the BFcache
    if (event.persisted) {
        console.log('Page restored from BFcache, forcing reload.');
        window.location.reload();
    }
});
</script>
</body>
</html>