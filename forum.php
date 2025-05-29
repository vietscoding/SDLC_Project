<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Lấy course_id nếu có truyền GET, nếu không lấy khóa học đầu tiên mà user tham gia
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
} else {
    // Lấy khóa học đầu tiên user tham gia
    $stmt = $conn->prepare("SELECT course_id FROM enrollments WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($course_id);
    $stmt->fetch();
    $stmt->close();

    if (!$course_id) {
        echo "You are not enrolled in any course yet.";
        exit;
    }
}

// Xử lý gửi bài mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO forum_posts (user_id, course_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $course_id, $content);
        $stmt->execute();
        $stmt->close();
        // Reload trang để hiển thị bài mới
        header("Location: forum.php?course_id=$course_id");
        exit;
    }
}

// Get course title
$course_stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_stmt->bind_result($course_title);
$course_stmt->fetch();
$course_stmt->close();

// Lấy danh sách bài viết forum theo khóa học
$stmt = $conn->prepare("SELECT f.id, f.content, f.posted_at, u.fullname FROM forum_posts f JOIN users u ON f.user_id = u.id WHERE f.course_id = ? ORDER BY f.posted_at DESC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forum: <?= htmlspecialchars($course_title) ?> | [Your University Name]</title>
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
            margin:auto;
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

        .forum-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .forum-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .post-form {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .post-form textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: 'Open Sans', sans-serif;
            font-size: 1.1em;
            line-height: 1.6;
        }

        .post-form button[type="submit"] {
            background-color: #0056b3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .post-form button[type="submit"]:hover {
            background-color: #004080;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .forum-posts-container {
            margin-bottom: 30px;
        }

        .forum-post {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: white;
        }

        .post-author {
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 5px;
            font-size: 1.1em;
        }

        .post-date {
            font-style: italic;
            color: #777;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .post-content {
            line-height: 1.6;
            color: #333;
        }

        .no-posts {
            font-style: italic;
            color: #777;
        }

        .navigation-links {
            margin-top: 30px;
            font-size: 1.1em;
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 20px;
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

        .dark-mode .forum-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .post-form {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .post-form textarea {
            background-color: #333;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .post-form button[type="submit"] {
            background-color: #fbc531;
            color: #222;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .post-form button[type="submit"]:hover {
            background-color: #fbb003;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .forum-posts-container .forum-post {
            background-color: #444;
            border-color: #555;
            color: #eee;
        }

        .dark-mode .forum-posts-container .post-author {
            color: #fbc531;
        }

        .dark-mode .forum-posts-container .post-date {
            color: #ccc;
        }

        .dark-mode .forum-posts-container .post-content {
            color: #eee;
        }

        .dark-mode .no-posts {
            color: #ccc;
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #fbb003;
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
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="forum-header">
            <h2><?= htmlspecialchars($course_title) ?> Forum</h2>
        </div>

        <div class="post-form">
            <form method="post">
                <textarea name="content" rows="4" placeholder="Write your message here..." required></textarea><br>
                <button type="submit">Post</button>
            </form>
        </div>

        <div class="forum-posts-container">
            <h3>Posts:</h3>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="forum-post">
                        <p class="post-author"><?= htmlspecialchars($row['fullname']) ?></p>
                        <p class="post-date"><?= $row['posted_at'] ?></p>
                        <p class="post-content"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-posts">No posts yet in this course forum.</p>
            <?php endif; ?>
        </div>

        <div class="navigation-links">
            <a href="course_detail.php?course_id=<?= $course_id ?>"><i class="fas fa-arrow-left"></i> Back to Course</a>
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