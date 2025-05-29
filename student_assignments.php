<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Lấy danh sách khóa học học viên đã enroll
$stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.title
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Assignments | [Your University Name]</title>
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

        .assignments-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .assignments-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .course-assignments {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .course-assignments h3 {
            font-size: 1.8em;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }

        .assignment-list {
            list-style: none;
            padding-left: 0;
        }

        .assignment-item {
            background-color: white;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .assignment-item strong {
            font-size: 1.1em;
            color: #333;
        }

        .assignment-due-date {
            color: #d35400;
            font-weight: bold;
            margin-left: 10px;
        }

        .assignment-description {
            color: #555;
            margin-top: 8px;
            margin-bottom: 12px;
            white-space: pre-line; /* Preserve line breaks */
        }

        .submit-assignment-link {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .submit-assignment-link:hover {
            background-color: #218838;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .no-assignments {
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

        .dark-mode .assignments-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .course-assignments {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .course-assignments h3 {
            color: #fbc531;
            border-bottom-color: #fbc531;
        }

        .dark-mode .assignment-item {
            background-color: #333;
            border-color: #555;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .dark-mode .assignment-item strong {
            color: #a7f3d0;
        }

        .dark-mode .assignment-due-date {
            color: #ff8a65;
        }

        .dark-mode .assignment-description {
            color: #ccc;
        }

        .dark-mode .submit-assignment-link {
            background-color: #a7f3d0;
            color: #222;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .submit-assignment-link:hover {
            background-color: #86efac;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .no-assignments {
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
            <li><a href="student_assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="assignments-header">
            <h2>My Assignments</h2>
        </div>

        <?php while ($course = $courses_result->fetch_assoc()): ?>
            <div class="course-assignments">
                <h3><?= htmlspecialchars($course['title']) ?></h3>
                <?php
                $course_id = $course['id'];
                $assign_stmt = $conn->prepare("SELECT id, title, description, due_date FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
                $assign_stmt->bind_param("i", $course_id);
                $assign_stmt->execute();
                $assignments = $assign_stmt->get_result();
                ?>

                <?php if ($assignments->num_rows > 0): ?>
                    <ul class="assignment-list">
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <li class="assignment-item">
                                <strong><?= htmlspecialchars($assignment['title']) ?></strong>
                                <span class="assignment-due-date">(Due: <?= date('Y-m-d H:i', strtotime($assignment['due_date'])) ?>)</span><br>
                                <p class="assignment-description"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                <a href="submit_assignment.php?assignment_id=<?= $assignment['id'] ?>" class="submit-assignment-link">Submit Assignment</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-assignments">No assignments for this course.</p>
                <?php endif; ?>
                <?php $assign_stmt->close(); ?>
            </div>
        <?php endwhile; ?>

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