<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($lesson_id <= 0) {
    echo "Invalid lesson ID.";
    exit;
}
function convertYoutubeLink($url) {
    if (strpos($url, 'watch?v=') !== false) {
        $url = str_replace("watch?v=", "embed/", $url);
    }
    if (strpos($url, 'enablejsapi=1') === false) {
        if (strpos($url, '?') !== false) {
            $url .= '&enablejsapi=1';
        } else {
            $url .= '?enablejsapi=1';
        }
    }
    return $url;
}

$stmt = $conn->prepare("SELECT title, content, video_link, file_path, course_id FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Lesson not found.";
    exit;
}

$stmt->bind_result($title, $content, $video_link, $file_path, $course_id);
$stmt->fetch();
$stmt->close();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $check = $conn->prepare("SELECT id FROM progress WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
    $check->bind_param("iii", $user_id, $course_id, $lesson_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($progress_id);
        $check->fetch();
        $update = $conn->prepare("UPDATE progress SET is_completed = 1, completed_at = NOW() WHERE id = ?");
        $update->bind_param("i", $progress_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO progress (user_id, course_id, lesson_id, is_completed, completed_at) VALUES (?, ?, ?, 1, NOW())");
        $insert->bind_param("iii", $user_id, $course_id, $lesson_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();

    header("Location: lesson.php?id=$lesson_id");
    exit;
}

$completed = false;
$check_status = $conn->prepare("SELECT is_completed FROM progress WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
$check_status->bind_param("iii", $user_id, $course_id, $lesson_id);
$check_status->execute();
$check_status->bind_result($is_completed);
if ($check_status->fetch()) {
    $completed = ($is_completed == 1);
}
$check_status->close();

$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($title); ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/lesson.css">
    
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-file-alt"></i> Lesson: <?= htmlspecialchars($title); ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>
       

        <div class="lesson-container">
            <div class="lesson-content">
                <p><?= nl2br(htmlspecialchars($content)); ?></p>
            </div>

            <?php if (!empty($video_link)): ?>
                <div class="lesson-video">
                    <iframe id="youtube-player" src="<?= htmlspecialchars(convertYoutubeLink($video_link)); ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>

            <?php if (!empty($file_path)): ?>
                <div class="attached-file">
                    <i class="fas fa-file-download"></i> Attached Document:
                    <a href="<?= htmlspecialchars($file_path); ?>" target="_blank">
                        View / Download
                    </a>
                </div>
            <?php endif; ?>

            <section class="mark-complete-section">
                <?php if ($completed): ?>
                    <p><i class="fas fa-check-circle"></i> <strong>Lesson completed!</strong></p>
                <?php else: ?>
                    <form method="post" id="mark-complete-form" style="display: none;">
                        <button type="submit" name="mark_complete"><i class="fas fa-check"></i> Mark as Completed</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>

        <div class="navigation-links">
            <a href="course_detail.php?course_id=<?= $course_id; ?>"><i class="fas fa-arrow-left"></i> Back to Course Details</a>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script>
       


        document.addEventListener('DOMContentLoaded', function() {
            const markCompleteForm = document.getElementById('mark-complete-form');
            const videoPlayer = document.getElementById('youtube-player');
            const hasVideo = <?= !empty($video_link) ? 'true' : 'false'; ?>;
            const isCompleted = <?= $completed ? 'true' : 'false'; ?>;

            if (isCompleted) {
                markCompleteForm.style.display = 'none';
                return;
            }

            if (hasVideo && videoPlayer) {
                let player;
                // Load the YouTube IFrame Player API asynchronously.
                const tag = document.createElement('script');
                tag.src = "http://www.youtube.com/iframe_api"; // Corrected YouTube API URL
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

                window.onYouTubeIframeAPIReady = function() {
                    player = new YT.Player('youtube-player', {
                        events: {
                            'onStateChange': onPlayerStateChange
                        }
                    });
                }

                function onPlayerStateChange(event) {
                    if (event.data === YT.PlayerState.ENDED) {
                        markCompleteForm.style.display = 'block';
                    }
                }
            } else {
                // If no video, show "Mark as Completed" button after 60 seconds
                setTimeout(function() {
                    markCompleteForm.style.display = 'block';
                }, 60000); // 60 seconds
            }
        });
    </script>
    <script src="js/student_sidebar.js"></script>
</body>
</html>