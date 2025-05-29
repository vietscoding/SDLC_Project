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
        return str_replace("watch?v=", "embed/", $url);
    }
    return $url;
}

// Lấy thông tin bài học
$stmt = $conn->prepare("SELECT title, content, video_link, course_id FROM lessons WHERE id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Lesson not found.";
    exit;
}

$stmt->bind_result($title, $content, $video_link, $course_id);
$stmt->fetch();
$stmt->close();

$user_id = $_SESSION['user_id'];

// Xử lý khi học viên bấm “Mark as Completed”
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    // Kiểm tra đã có record trong progress chưa
    $check = $conn->prepare("SELECT id FROM progress WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
    $check->bind_param("iii", $user_id, $course_id, $lesson_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Cập nhật lại trạng thái hoàn thành
        $check->bind_result($progress_id);
        $check->fetch();
        $update = $conn->prepare("UPDATE progress SET is_completed = 1, completed_at = NOW() WHERE id = ?");
        $update->bind_param("i", $progress_id);
        $update->execute();
        $update->close();
    } else {
        // Thêm mới record hoàn thành
        $insert = $conn->prepare("INSERT INTO progress (user_id, course_id, lesson_id, is_completed, completed_at) VALUES (?, ?, ?, 1, NOW())");
        $insert->bind_param("iii", $user_id, $course_id, $lesson_id);
        $insert->execute();
        $insert->close();
    }
    $check->close();

    // Reload trang để cập nhật trạng thái
    header("Location: lesson.php?id=$lesson_id");
    exit;
}

// Kiểm tra trạng thái hoàn thành bài học
$completed = false;
$check_status = $conn->prepare("SELECT is_completed FROM progress WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
$check_status->bind_param("iii", $user_id, $course_id, $lesson_id);
$check_status->execute();
$check_status->bind_result($is_completed);
if ($check_status->fetch()) {
    $completed = ($is_completed == 1);
}
$check_status->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($title); ?> | [Your University Name]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.7;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: #0056b3;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            display: block;
            width: 80%;
            height: auto;
            margin-left: 30px;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #fbc531;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .lesson-header {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .lesson-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .lesson-content {
            font-size: 1.15em;
            color: #555;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .lesson-video {
            margin-bottom: 40px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden; /* Ensure video doesn't overflow rounded corners */
        }

        .lesson-video iframe {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9; /* Maintain aspect ratio */
        }

        .mark-complete-section {
            margin-bottom: 40px;
        }

        .mark-complete-section p {
            font-size: 1.2em;
            color: #28a745;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .mark-complete-section form button {
            background-color: #0056b3;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .mark-complete-section form button:hover {
            background-color: #004080;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navigation-links {
            margin-top: 40px;
            font-size: 1.1em;
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 25px;
            font-weight: 600;
        }

        .navigation-links a:hover {
            text-decoration: underline;
            color: #004080;
        }

        footer{
        text-align: center;
            padding: 30px;
            margin-top: 40px;
            font-size: 0.9em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }

        footer a {
            color: #0056b3;
            text-decoration: none;
            margin: 0 5px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer p {
            margin: 5px 0;
        }

        footer .contact-info {
            margin-top: 15px;
        }

        footer .contact-info p {
            margin: 3px 0;
        }

        /* Dark Mode Footer */
        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }

        .dark-mode footer a {
            color: #fbc531;
        }


        /* Dark Mode (Optional - Add a class 'dark-mode' to the body) */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .lesson-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .lesson-content {
            color: #ccc;
        }

        .dark-mode .lesson-video {
            border-color: #555;
        }

        .dark-mode .mark-complete-section p {
            color: #a7f3d0;
        }

        .dark-mode .mark-complete-section form button {
            background-color: #fbc531;
            color: #222;
        }

        .dark-mode .mark-complete-section form button:hover {
            background-color: #fbb003;
            color: #222;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #fbb003;
            text-decoration: underline;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="lesson-header">
            <h2><?= htmlspecialchars($title); ?></h2>
        </div>

        <div class="lesson-content">
            <p><?= nl2br(htmlspecialchars($content)); ?></p>
        </div>

        <?php if (!empty($video_link)): ?>
    <div class="lesson-video">
        <iframe src="<?= htmlspecialchars(convertYoutubeLink($video_link)); ?>" frameborder="0" allowfullscreen></iframe>
    </div>
<?php endif; ?>


        <section class="mark-complete-section">
            <?php if ($completed): ?>
                <p><strong>Lesson completed!</strong></p>
            <?php else: ?>
                <form method="post">
                    <button type="submit" name="mark_complete">Mark as Completed</button>
                </form>
            <?php endif; ?>
        </section>

        <div class="navigation-links">
            <a href="course_detail.php?course_id=<?= $course_id; ?>"><i class="fas fa-arrow-left"></i> Back to Course Details</a>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <hr style ="margin-top:30px; ">
            <footer>
            <a href="https://www.facebook.com/btecfptdn/?locale=vi_VN" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
            |
            <a href="https://international.fpt.edu.vn/" target="_blank"><i class="fas fa-globe"></i> Website</a>
            |
            <a href="tel:02473099588"><i class="fas fa-phone"></i> 024 730 99 588</a>
<br>
            <p>Address: 66 Võ Văn Tần, Quận Thanh Khê, Đà Nẵng</p>
            <div class="contact-info">
                <p>Email:</p>
                <p>Academic Department: <a href="mailto:Academic.btec.dn@fe.edu.vn">Academic.btec.dn@fe.edu.vn</a></p>
                <p>SRO Department: <a href="mailto:sro.btec.dn@fe.edu.vn">sro.btec.dn@fe.edu.vn</a></p>
                <p>Finance Department: <a href="mailto:accounting.btec.dn@fe.edu.vn">accounting.btec.dn@fe.edu.vn</a></p>
            </div>
            <p>&copy; <?= date('Y'); ?> BTEC FPT - Learning Management System.</p>
            <small>Powered by Innovation in Education</small>
        </footer>

    </div>
</body>
</html>
