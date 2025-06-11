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
    <title><?= htmlspecialchars($course_title) ?> Forum | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #e0e7ff 100%);
            color: #222;
            line-height: 1.7;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2563eb 60%, #fbbf24 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(37,99,235,0.10);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background 0.4s;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }
        .sidebar .logo img {
            display: block;
            width: 70%;
            max-width: 150px;
            height: auto;
            margin: auto;
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
        }
        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #1e40af;
            border: 2px solid #fbbf24;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #fde68a;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2563eb;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
            width: 100%;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .forum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 2px 8px rgba(251,191,36,0.08);
            padding: 20px 30px;
        }
        .forum-header h2 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .toggle-mode-btn {
            background: #fde68a;
            color: #2563eb;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #2563eb;
            color: #fde68a;
        }
        .post-form {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1.5px solid #fde68a;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .post-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: 'Roboto', 'Open Sans', sans-serif;
            font-size: 1.1em;
            line-height: 1.6;
        }
        .post-form button[type="submit"] {
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            color: #fff;
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.10);
        }
        .post-form button[type="submit"]:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 8px rgba(251,191,36,0.18);
        }
        .forum-posts-container {
            margin-bottom: 30px;
        }
        .forum-posts-container h3 {
            font-size: 1.4em;
            color: #2563eb;
            margin-bottom: 20px;
            border-bottom: 2px solid #fde68a;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .forum-post {
            border: 1.5px solid #fde68a;
            padding: 18px;
            margin-bottom: 18px;
            border-radius: 10px;
            background-color: #f8f9fa;
            box-shadow: 0 2px 8px rgba(251,191,36,0.05);
        }
        .post-author {
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        .post-date {
            font-style: italic;
            color: #777;
            font-size: 0.95em;
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
            display: flex;
            gap: 18px;
        }
        .navigation-links a {
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.18s;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
            transform: translateY(-3px) scale(1.06);
            text-decoration: none;
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #2563eb;
            background-color: #fde68a;
            border-top: 1px solid #fbbf24;
            border-radius: 0 0 12px 12px;
        }
        footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
            color: #fbbf24;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1e293b;
            color: #fde68a;
        }
        .dark-mode .sidebar {
            background: linear-gradient(135deg, #1e293b 60%, #fbbf24 100%);
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1e293b;
        }
        .dark-mode .main-content {
            background-color: #1e293b;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .forum-header h2 {
            color: #fbbf24;
        }
        .dark-mode .forum-header {
            background: linear-gradient(90deg, #2563eb 0%, #1e293b 100%);
        }
        .dark-mode .post-form {
            background-color: #1e293b;
            border-color: #fbbf24;
        }
        .dark-mode .post-form textarea {
            background-color: #222;
            color: #fde68a;
            border-color: #fbbf24;
        }
        .dark-mode .post-form button[type="submit"] {
            background: #1e293b;
            color: #fbbf24;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .post-form button[type="submit"]:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .forum-posts-container h3 {
            color: #fbbf24;
            border-bottom: 2px solid #fbbf24;
        }
        .dark-mode .forum-post {
            background-color: #222;
            border-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .post-author {
            color: #fbbf24;
        }
        .dark-mode .post-date {
            color: #fde68a;
        }
        .dark-mode .post-content {
            color: #fde68a;
        }
        .dark-mode .no-posts {
            color: #fde68a;
        }
        .dark-mode .navigation-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .navigation-links a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode footer {
            background-color: #1e293b;
            color: #fde68a;
            border-top-color: #fbbf24;
        }
        .dark-mode footer a {
            color: #fbbf24;
        }
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .forum-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo { padding: 15px 0; }
            .sidebar .logo img { width: 50%; max-width: 120px; }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li { width: 48%; margin-bottom: 5px; }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { display: block; font-size: 0.8em; }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .forum-header { flex-direction: column; align-items: flex-start; }
            .forum-header h2 { margin-bottom: 10px; font-size: 1.8em; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="forum-header">
                <h2><?= htmlspecialchars($course_title) ?> Forum</h2>
                <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <div class="post-form">
                <form method="post">
                    <textarea name="content" rows="4" placeholder="Write your message here..." required></textarea><br>
                    <button type="submit">Post</button>
                </form>
            </div>
            <div class="forum-posts-container">
                <h3><i class="fas fa-comments"></i> Posts:</h3>
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
            <hr style="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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
    </div>
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
    </script>
</body>
</html>