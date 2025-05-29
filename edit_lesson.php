<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy lesson_id và course_id từ URL
if (!isset($_GET['lesson_id']) || !isset($_GET['course_id'])) {
    echo "Missing lesson or course ID.";
    exit;
}

$lesson_id = intval($_GET['lesson_id']);
$course_id = intval($_GET['course_id']);

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You are not allowed to edit lessons for this course.";
    exit;
}
$stmt->close();

// Lấy thông tin bài giảng cần sửa
$stmt = $conn->prepare("SELECT title, content, video_link FROM lessons WHERE id = ? AND course_id = ?");
$stmt->bind_param("ii", $lesson_id, $course_id);
$stmt->execute();
$stmt->bind_result($title, $content, $video);
if (!$stmt->fetch()) {
    echo "Lesson not found.";
    exit;
}
$stmt->close();

// Cập nhật bài giảng khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = trim($_POST['title']);
    $new_content = trim($_POST['content']);
    $new_video = trim($_POST['video_link']);

    if (!empty($new_title) && !empty($new_content)) {
        $stmt = $conn->prepare("UPDATE lessons SET title = ?, content = ?, video_link = ? WHERE id = ? AND course_id = ?");
        $stmt->bind_param("sssii", $new_title, $new_content, $new_video, $lesson_id, $course_id);
        $stmt->execute();
        $stmt->close();

        header("Location: teacher_lessons.php?course_id=$course_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Lesson | [Your University Name]</title>
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
            background-color: #f8f9fa; /* Light grey background */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px; /* Slightly wider sidebar */
            background-color: #2c3e50; /* Teacher-specific dark blue */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px; /* More top padding */
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100; /* Ensure it's above other content */
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        /* Logo image spanning the width */
        .sidebar .logo img {
            display: block;
            width: 80%; /* Make it span the width */
            height: auto; /* Maintain aspect ratio */
            margin:auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px; /* Adjust padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent; /* Indicator for active/hover */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active { /* You'd need JavaScript to add 'active' class */
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #f39c12; /* Teacher-specific accent color */
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px; /* Match sidebar width */
            padding: 30px; /* Adjust padding */
            flex-grow: 1;
            background-color: #fff; /* White main content background */
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }

        .edit-lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .edit-lesson-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .edit-lesson-form {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-left: 5px solid #f39c12; /* Teacher-specific accent border */
        }

        .edit-lesson-form h3 {
            font-size: 1.6em;
            color: #2c3e50; /* Teacher-specific heading color */
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .edit-lesson-form h3 i {
            margin-right: 10px;
        }

        .edit-lesson-form label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 0.95em;
        }

        .edit-lesson-form input[type="text"],
        .edit-lesson-form textarea {
            width: calc(100% - 16px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }

        .edit-lesson-form input[type="text"]:focus,
        .edit-lesson-form textarea:focus {
            border-color: #2c3e50;
            outline: none;
            box-shadow: 0 0 5px rgba(44, 62, 80, 0.5);
        }

        .edit-lesson-form button[type="submit"] {
            background-color: #27ae60; /* Success green */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .edit-lesson-form button[type="submit"]:hover {
            background-color: #219b56;
        }

        .back-to-lessons {
            margin-top: 20px;
        }

        .back-to-lessons a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-to-lessons a:hover {
            color: #1a252f;
            text-decoration: underline;
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
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .edit-lesson-header h2,
        .dark-mode .edit-lesson-form h3 {
            color: #f8f9fa;
        }

        .dark-mode .edit-lesson-form {
            background-color: #444;
            border-left-color: #f39c12;
            color: #eee;
        }

        .dark-mode .edit-lesson-form label,
        .dark-mode .edit-lesson-form input[type="text"],
        .dark-mode .edit-lesson-form textarea {
            color: #eee;
            border-color: #555;
            background-color: #333;
        }

        .dark-mode .edit-lesson-form button[type="submit"] {
            background-color: #2c3e50;
            color: #fff;
        }

        .dark-mode .edit-lesson-form button[type="submit"]:hover {
            background-color: #1a252f;
        }

        .dark-mode .back-to-lessons a {
            color: #f39c12;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
            border-radius: 0 0 8px 8px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_courses.php" class="active"><i class="fas fa-book"></i> My Courses</a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
            <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="edit-lesson-header">
            <h2><i class="fas fa-edit"></i> Edit Lesson</h2>
        </div>

        <div class="edit-lesson-form">
            <h3><i class="fas fa-info-circle"></i> Editing Lesson</h3>
            <form method="post">
                <label for="title">Lesson Title:</label><br>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required><br><br>

                <label for="content">Lesson Content:</label><br>
                <textarea name="content" id="content" rows="7" cols="60" required><?= htmlspecialchars($content) ?></textarea><br><br>

                <label for="video_link">Video Link (optional):</label><br>
                <input type="text" name="video_link" id="video_link" value="<?= htmlspecialchars($video) ?>"><br><br>

                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>

        <div class="back-to-lessons">
            <a href="teacher_lessons.php?course_id=<?= $course_id ?>"><i class="fas fa-arrow-left"></i> Back to Lessons</a>
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
