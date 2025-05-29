<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$keyword = $_GET['keyword'] ?? "";

// Query lấy các courses mà teacher này đang dạy
if (!empty($keyword)) {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor 
                            FROM courses c 
                            JOIN users u ON c.teacher_id = u.id 
                            WHERE c.teacher_id = ? 
                              AND (c.title LIKE ? OR c.department LIKE ?) 
                            ORDER BY c.id DESC");
    $like = "%" . $keyword . "%";
    $stmt->bind_param("iss", $teacher_id, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor 
                            FROM courses c 
                            JOIN users u ON c.teacher_id = u.id 
                            WHERE c.teacher_id = ? 
                            ORDER BY c.id DESC");
    $stmt->bind_param("i", $teacher_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search My Courses | [Your University Name]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f6f8; /* Light background */
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column; /* Make body a flex container, stacking items vertically */
        }

        /* Sidebar (Fixed Width and Style) */
        .sidebar {
            width: 280px; /* Match previous sidebars */
            background-color: #2c3e50; /* Teacher-specific dark blue */
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px; /* More margin to match */
        }

        .sidebar .logo img {
            display: block;
            width: 80%; /* Match previous logos */
            height: auto;
            margin: 0 auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex; /* Use flex to align icon and text */
            align-items: center; /* Vertically align icon and text */
            padding: 15px 20px; /* Match previous padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent; /* Indicator */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #f39c12; /* Teacher-specific accent */
        }

        .sidebar ul li a i {
            margin-right: 15px; /* Spacing for the icon */
            font-size: 1.2em; /* Icon size */
        }

        /* Main Content */
        .main-content {
            margin-left: 280px; /* Match sidebar width */
            padding: 30px;
            flex-grow: 1; /* Allow main content to take up remaining vertical space */
        }

        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .search-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .search-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            flex-grow: 1;
            font-size: 1em;
        }

        .search-form button[type="submit"] {
            background-color: #007bff; /* Blue button */
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .search-form button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .search-form a {
            color: #6c757d; /* Grey reset link */
            text-decoration: none;
            font-size: 0.9em;
        }

        .search-form a:hover {
            text-decoration: underline;
        }

        .course-list {
            list-style: none;
            padding-left: 0;
        }

        .course-list li {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #28a745; /* Green course indicator */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .course-info {
            flex-grow: 1;
        }

        .course-info strong {
            font-size: 1.1em;
            color: #333;
        }

        .course-details-link a {
            color: #007bff; /* Blue link */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .course-details-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .no-courses {
            font-style: italic;
            color: #777;
        }

        .back-to-dashboard {
            margin-top: 20px;
        }

        .back-to-dashboard a {
            color: #007bff; /* Blue link */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-to-dashboard a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Footer */
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


        /* Dark Mode (Optional) */
        .dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #343a40;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .sidebar ul li a {
            color: #ddd;
        }

        .dark-mode .sidebar ul li a:hover,
        .dark-mode .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #ffc107;
        }

        .dark-mode .search-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .search-form input[type="text"] {
            background-color: #495057;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .search-form button[type="submit"] {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .course-list li {
            background-color: #343a40;
            color: #eee;
            border-left-color: #28a745;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .course-info strong {
            color: #f8f9fa;
        }

        .dark-mode .course-details-link a {
            color: #007bff;
        }

        .dark-mode .no-courses {
            color: #ccc;
        }

        .dark-mode .back-to-dashboard a {
            color: #007bff;
        }

        .dark-mode footer {
            color: #ccc;
            border-top-color: #555;
            background-color: #343a40;
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
        <div class="search-header">
            <h2><i class="fas fa-book"></i> My Courses</h2>
        </div>

        <form class="search-form" method="get">
            <input type="text" name="keyword" placeholder="Search by title or department..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <a href="teacher_courses.php"><i class="fas fa-undo"></i> Reset</a>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <ul class="course-list">
                <?php while ($course = $result->fetch_assoc()): ?>
                    <li>
                        <div class="course-info">
                            <strong><?= htmlspecialchars($course['title']) ?></strong>
                            (<?= htmlspecialchars($course['department']) ?>) - Instructor: <?= htmlspecialchars($course['instructor']) ?>
                        </div>
                        <div class="course-details-link">
                            <a href="teacher_course_detail.php?course_id=<?= $course['id'] ?>"><i class="fas fa-info-circle"></i> View Details</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-courses"><i class="fas fa-exclamation-circle"></i> You are not assigned to any courses.</p>
        <?php endif; ?>

        <p class="back-to-dashboard"><a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>
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


</body>
</html>