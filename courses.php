<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Fetch courses from database
$sql = "SELECT id, title, description FROM courses";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Courses | [Your University Name]</title>
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
            background-color: #0056b3; /* University blue (example) */
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
            width: 80%; /* Make it span 80% of the width */
            height: auto; /* Maintain aspect ratio */
            margin: auto; /* Center the image horizontally */
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
            border-left-color: #fbc531; /* Accent color */
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
        }

        .course-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .course-list-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
        }

        .course-list-container {
            list-style: none;
            padding: 0;
        }

        .course-item {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid #0056b3; /* Accent border */
        }

        .course-item strong {
            font-size: 1.4em;
            color: #333;
            display: block;
            margin-bottom: 10px;
        }

        .course-item p {
            color: #666;
            margin-bottom: 15px;
        }

        .course-item a {
            display: inline-block;
            text-decoration: none;
            color: #0056b3;
            font-weight: 600;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #0056b3;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .course-item a:hover {
            background-color: #0056b3;
            color: white;
        }

        .navigation-links {
            margin-top: 30px;
            font-size: 0.9em;
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 15px;
        }

        .navigation-links a:hover {
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
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .course-list-header h2,
        .dark-mode .course-item strong {
            color: #f8f9fa;
        }

        .dark-mode .course-item {
            background-color: #444;
            color: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border-left-color: #fbc531;
        }

        .dark-mode .course-item p {
            color: #ccc;
        }

        .dark-mode .course-item a,
        .dark-mode .navigation-links a {
            color: #fbc531;
            border-color: #fbc531;
        }

        .dark-mode .course-item a:hover,
        .dark-mode .navigation-links a:hover {
            background-color: #fbc531;
            color: #222;
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
            <li><a href="courses.php" class="active"><i class="fas fa-book"></i> Courses</a></li>
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
        <div class="course-list-header">
            <h2>Available Courses</h2>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <ul class="course-list-container">
                <?php while($row = $result->fetch_assoc()): ?>
                    <li class="course-item">
                        <strong><?= htmlspecialchars($row['title']); ?></strong>
                        <p><?= htmlspecialchars($row['description']); ?></p>
                        <a href="course_detail.php?course_id=<?= $row['id'] ?>">View Details</a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No courses available at the moment.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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
