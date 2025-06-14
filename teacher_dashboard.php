<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
$teacher_id = $_SESSION['user_id'];

// Fetch courses taught by the teacher
$courses_taught_query = "SELECT id, title FROM courses WHERE teacher_id = ?";
$stmt = $conn->prepare($courses_taught_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses_taught_result = $stmt->get_result();
$courses_taught = $courses_taught_result->fetch_all(MYSQLI_ASSOC);

// Fetch assignments to grade
$assignments_to_grade_query = "
    SELECT COUNT(DISTINCT asub.id) AS count_assignments
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ? AND asub.grade IS NULL;
";
$stmt = $conn->prepare($assignments_to_grade_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assignments_to_grade_result = $stmt->get_result();
$assignments_to_grade = $assignments_to_grade_result->fetch_assoc();

// Fetch quizzes to grade (assuming quizzes need manual grading if 'score' is null or a specific status)
$quizzes_to_grade_query = "
    SELECT COUNT(DISTINCT qs.id) AS count_quizzes
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE c.teacher_id = ? AND qs.score IS NULL;
";
$stmt = $conn->prepare($quizzes_to_grade_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$quizzes_to_grade_result = $stmt->get_result();
$quizzes_to_grade = $quizzes_to_grade_result->fetch_assoc();

// Fetch quick stats: total students enrolled in teacher's courses
$total_students_query = "
    SELECT COUNT(DISTINCT e.user_id) AS total_students
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE c.teacher_id = ?;
";
$stmt = $conn->prepare($total_students_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$total_students_result = $stmt->get_result();
$total_students = $total_students_result->fetch_assoc();

// Calculate average progress of students in teacher's courses
// This query calculates (completed lessons / total lessons) * 100 for each student in a course, then averages it.
$avg_progress_query = "
    SELECT AVG(course_progress) AS avg_overall_progress
    FROM (
        SELECT
            p.user_id,
            p.course_id,
            (COUNT(DISTINCT p.lesson_id) * 100.0 / total_lessons.count) AS course_progress
        FROM
            progress p
        JOIN
            courses c ON p.course_id = c.id
        JOIN
            (SELECT course_id, COUNT(id) AS count FROM lessons GROUP BY course_id) AS total_lessons
            ON p.course_id = total_lessons.course_id
        WHERE
            c.teacher_id = ? AND p.is_completed = 1
        GROUP BY
            p.user_id, p.course_id
    ) AS student_course_progress;
";
$stmt = $conn->prepare($avg_progress_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$avg_progress_result = $stmt->get_result();
$avg_progress = $avg_progress_result->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .header {
            background-color: #004a8f;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header .logo {
            font-size: 24px;
            font-weight: 700;
        }

        .header .user-info {
            font-size: 16px;
        }

        .main-wrapper {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 250px;
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            padding-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: block;
            padding: 15px 30px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #e9f5ff;
            color: #004a8f;
            border-left: 5px solid #004a8f;
        }

        .sidebar ul li a i {
            margin-right: 10px;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid for content cards */
            gap: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            padding: 25px;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h2 {
            color: #004a8f;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 2px solid #e0e7ef;
            padding-bottom: 10px;
        }

        .card ul {
            list-style: none;
        }

        .card ul li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card ul li:last-child {
            border-bottom: none;
        }

        .card .course-item {
            font-weight: 500;
            color: #555;
        }

        .card .count {
            font-size: 28px;
            font-weight: 700;
            color: #28a745; /* Green for positive numbers */
            text-align: center;
            margin-top: 10px;
        }

        .card .count.danger {
            color: #dc3545; /* Red for urgent numbers */
        }

        .notification-item {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-size: 14px;
        }
        .notification-item strong {
            color: #004a8f;
        }
        .notification-item small {
            display: block;
            color: #888;
            margin-top: 5px;
        }
        
        .stats-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }
        .stats-item:last-child {
            border-bottom: none;
        }
        .stats-item span:first-child {
            font-weight: 500;
            color: #555;
        }
        .stats-item span:last-child {
            font-size: 18px;
            font-weight: 700;
            color: #007bff;
        }
        .stats-item span.green {
            color: #28a745;
        }


        .footer {
            background-color: #004a8f;
            color: white;
            text-align: center;
            padding: 20px 0;
            font-size: 14px;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }

        .footer a {
            color: #a2d2ff;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: white;
        }

        .footer .contact-info {
            margin-top: 10px;
            font-size: 13px;
        }
        .footer .contact-info p {
            margin-bottom: 5px;
        }
        .footer .contact-info a {
            color: #a2d2ff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">BTEC FPT LMS</div>
            <div class="user-info">
                Welcome, <?= $fullname ?> (<?= ucfirst($role) ?>) | <a href="logout.php" style="color: white;">Logout</a>
            </div>
        </header>

        <div class="main-wrapper">
            <aside class="sidebar">
                <nav>
                    <ul>
                        <li><a href="teacher_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="teacher_courses.php"><i class="fas fa-book"></i> Courses</a></li>
                        <li><a href="teacher_search_courses.php"><i class="fas fa-book"></i>Search Courses</a></li>
                        <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
                        <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a></li>
                        <li><a href="teacher_notifications.php"><i class="fas fa-users"></i> Send Notifications</a></li>
                        <li><a href="teacher_view_notifications.php"><i class="fas fa-users"></i> View Notifications</a></li>
                        <li><a href="teacher_forum_courses.php"><i class="fas fa-clipboard-list"></i> Course Forum</a></li>
                        <li><a href="teacher_profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                        <li><a href="logout.php"><i class="fas fa-user-circle"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="main-content">
                <div class="card">
                    <h2><i class="fas fa-book-open"></i> Courses You Teach</h2>
                    <ul>
                        <?php if ($courses_taught): ?>
                            <?php foreach ($courses_taught as $course): ?>
                                <li><span class="course-item"><?= htmlspecialchars($course['title']) ?></span></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No courses assigned yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <h2><i class="fas fa-clipboard-check"></i> To Grade</h2>
                    <ul>
                        <li>
                            Assignments:
                            <span class="count <?= ($assignments_to_grade['count_assignments'] > 0) ? 'danger' : ''; ?>">
                                <?= htmlspecialchars($assignments_to_grade['count_assignments']) ?>
                            </span>
                        </li>
                        <li>
                            Quizzes:
                            <span class="count <?= ($quizzes_to_grade['count_quizzes'] > 0) ? 'danger' : ''; ?>">
                                <?= htmlspecialchars($quizzes_to_grade['count_quizzes']) ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <h2><i class="fas fa-bell"></i> Notifications</h2>
                    <?php if ($sys_notif_result->num_rows > 0): ?>
                        <?php while($notification = $sys_notif_result->fetch_assoc()): ?>
                            <div class="notification-item">
                                <strong>System Notification:</strong> <?= htmlspecialchars($notification['message']) ?>
                                <small><?= date("M d, Y H:i", strtotime($notification['created_at'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No system notifications.</p>
                    <?php endif; ?>
                    <div class="notification-item" style="border-left: 3px solid #007bff;">
                        <strong>Student Notification:</strong> You have a new message from John Doe in "Math 101".
                        <small>Jun 12, 2025 10:30 AM</small>
                    </div>
                </div>

                <div class="card">
                    <h2><i class="fas fa-chart-line"></i> Quick Stats</h2>
                    <div class="stats-item">
                        <span>Total Students Enrolled:</span>
                        <span class="green"><?= htmlspecialchars($total_students['total_students']) ?></span>
                    </div>
                    <div class="stats-item">
                        <span>Average Student Progress:</span>
                        <span class="green"><?= round(htmlspecialchars($avg_progress['avg_overall_progress'] ?? 0), 2) ?>%</span>
                    </div>
                    <div class="stats-item">
                        <span>Active Courses:</span>
                        <span class="green"><?= count($courses_taught) ?></span>
                    </div>
                    </div>
            </main>
        </div>

        <footer class="footer">
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

    <script>
        // JavaScript for sidebar active link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar ul li a');

            sidebarLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>