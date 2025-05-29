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
    SELECT c.id, c.title
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
    <title>My Assignment Submissions | [Your University Name]</title>
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

        .submissions-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .submissions-header h2 {
            font-size: 2.5em;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .course-submissions {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .course-submissions h3 {
            font-size: 1.8em;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }

        .assignment-submission {
            background-color: white;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .assignment-title {
            font-size: 1.1em;
            color: #333;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .assignment-due {
            color: #d35400;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .submission-info {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #f0f0f0;
            border-radius: 5px;
            background-color: #fefefe;
        }

        .submission-info strong {
            font-weight: bold;
            color: #28a745;
            margin-right: 5px;
        }

        .submission-text {
            white-space: pre-line;
            color: #555;
            margin-bottom: 8px;
        }

        .submission-file a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .submission-file a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .submission-date {
            color: #777;
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .grade-feedback {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #d4edda; /* Light green border */
            border-radius: 5px;
            background-color: #f1fae5; /* Light green background */
        }

        .grade-feedback strong {
            font-weight: bold;
            color: #155724; /* Dark green text for label */
            margin-right: 5px;
        }

        .grade-value {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745; /* Bright green for the grade */
        }

        .no-grade {
            font-style: italic;
            color: #6c757d;
        }
        .feedback-text {
            color: #555;
            white-space: pre-line;
            margin-top: 8px;
        }

        .no-feedback {
            font-style: italic;
            color: #6c757d;
        }

        .no-submission {
            font-style: italic;
            color: #777;
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

        .dark-mode .submissions-header h2 {
            color: #f8f9fa;
        }

        .dark-mode .course-submissions {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .course-submissions h3 {
            color: #fbc531;
            border-bottom-color: #fbc531;
        }

        .dark-mode .assignment-submission {
            background-color: #333;
            border-color: #555;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .dark-mode .assignment-title {
            color: #a7f3d0;
        }

        .dark-mode .assignment-due {
            color: #ff8a65;
        }

        .dark-mode .submission-info {
            background-color: #444;
            border-color: #555;
            color: #ccc;
        }

        .dark-mode .submission-info strong {
            color: #a7f3d0;
        }

        .dark-mode .submission-file a {
            color: #86efac;
        }

        .dark-mode .submission-date {
            color: #999;
        }

        .dark-mode .grade-feedback {
            border-color: #5cb85c; /* Darker green border for dark mode */
            background-color: #38413b; /* Darker green background for dark mode */
            color: #eee;
        }

        .dark-mode .grade-feedback strong {
            color: #a7f3d0; /* Lighter green for label in dark mode */
        }
        .dark-mode .grade-value {
            color: #a7f3d0; /* Lighter green for grade in dark mode */
        }

        .dark-mode .no-grade,
        .dark-mode .no-feedback {
            color: #999;
        }

        .dark-mode .feedback-text {
            color: #ccc;
        }

        .dark-mode .no-submission,
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
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php" class="active"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="submissions-header">
            <h2>My Assignment Submissions</h2>
        </div>

        <?php while ($course = $courses_result->fetch_assoc()): ?>
            <div class="course-submissions">
                <h3><?= htmlspecialchars($course['title']) ?></h3>
                <?php
                $course_id = $course['id'];
                $assign_stmt = $conn->prepare("
                    SELECT a.id, a.title, a.due_date, s.submitted_text, s.submitted_file, s.grade, s.feedback, s.submitted_at
                    FROM assignments a
                    LEFT JOIN assignment_submissions s
                        ON a.id = s.assignment_id AND s.user_id = ?
                    WHERE a.course_id = ?
                    ORDER BY a.due_date ASC
                ");
                $assign_stmt->bind_param("ii", $user_id, $course_id);
                $assign_stmt->execute();
                $assignments = $assign_stmt->get_result();
                ?>

                <?php if ($assignments->num_rows > 0): ?>
                    <?php while ($a = $assignments->fetch_assoc()): ?>
                        <div class="assignment-submission">
                            <h4 class="assignment-title"><?= htmlspecialchars($a['title']) ?></h4>
                            <p class="assignment-due">Due Date: <?= date('Y-m-d H:i', strtotime($a['due_date'])) ?></p>

                            <?php if ($a['submitted_text'] || $a['submitted_file']): ?>
                                <div class="submission-info">
                                    <strong>Your Submission:</strong>
                                    <?php if ($a['submitted_text']): ?>
                                        <p class="submission-text"><?= nl2br(htmlspecialchars($a['submitted_text'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($a['submitted_file']): ?>
                                        <p class="submission-file"><i class="fas fa-file"></i> <a href="<?= htmlspecialchars($a['submitted_file']) ?>" target="_blank">Download Submission</a></p>
                                    <?php endif; ?>
                                    <p class="submission-date">Submitted at: <?= $a['submitted_at'] ? date('Y-m-d H:i', strtotime($a['submitted_at'])) : 'N/A' ?></p>
                                </div>

                                <div class="grade-feedback">
                                    <strong>Grade:</strong> <?= $a['grade'] !== null ? $a['grade'] : 'Not graded yet' ?><br>
                                    <strong>Feedback:</strong> <?= $a['feedback'] ? nl2br(htmlspecialchars($a['feedback'])) : 'No feedback yet' ?>
                                </div>
                            <?php else: ?>
                                <p class="no-submission"><em>You have not submitted this assignment.</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-assignments">No assignments in this course.</p>
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