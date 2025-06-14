<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);

// Lấy tên khóa học và tên giáo viên
$stmt = $conn->prepare("SELECT c.title, u.fullname AS teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($course_title, $teacher_name);
if (!$stmt->fetch()) {
    echo "Course not found.";
    exit;
}
$stmt->close();

// Lấy tổng số bài học trong khóa
$result = $conn->query("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = $course_id");
$total_lessons = $result->fetch_assoc()['total_lessons'];

// Lấy danh sách học viên đã enroll
$stmt = $conn->prepare("
    SELECT u.id, u.fullname
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$students_result = $stmt->get_result();

// Lấy điểm trung bình quiz từng học viên
$avg_scores = [];
$quiz_scores_res = $conn->query("
    SELECT qs.user_id, AVG(qs.score) AS avg_score
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    WHERE q.course_id = $course_id
    GROUP BY qs.user_id
");
while ($row = $quiz_scores_res->fetch_assoc()) {
    $avg_scores[$row['user_id']] = round($row['avg_score'], 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Tracking - <?= htmlspecialchars($course_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .sidebar {
            width: 260px;
            background-color: #34495e;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 40px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }
        .sidebar .logo {
            text-align: center;
            padding: 10px 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar .logo img {
            width: 70%;
            max-width: 110px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 10px 10px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 4px solid transparent;
            font-size: 0.85em;
            white-space: normal;
            word-break: break-word;
            min-height: 38px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #e74c3c;
        }
        .sidebar ul li a i { margin-right: 10px; font-size: 1em; }
        .main-content {
            margin-left: 260px;
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .admin-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .admin-dashboard-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }
        .admin-dashboard-header h2 i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .progress-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px #0001;
        }
        .progress-table th, .progress-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .progress-table th {
            background: #f8f9fa;
            color: #34495e;
            font-weight: 600;
        }
        .progress-table tbody tr:nth-child(even) { background: #f9f9f9; }
        .back-link {
            margin-top: 30px;
            display: inline-block;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        footer {
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
        footer a:hover { text-decoration: underline; }
        footer p { margin: 5px 0; }
        footer .contact-info { margin-top: 15px; }
        footer .contact-info p { margin: 3px 0; }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 10px; }
            .sidebar { position: static; width: 100%; height: auto; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php" class="active"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_approve_teachers.php"><i class="fas fa-user-check"></i> User authorization</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="admin-dashboard-header">
        <h2><i class="fas fa-tasks"></i> Progress Tracking for: <?= htmlspecialchars($course_title) ?></h2>
    </div>
    <p><strong>Teacher:</strong> <?= htmlspecialchars($teacher_name) ?></p>
    <table class="progress-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Lesson Completion</th>
                <th>Average Quiz Score</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($students_result->num_rows === 0): ?>
                <tr>
                    <td colspan="3" style="text-align:center; color:#888;">No students enrolled in this course.</td>
                </tr>
            <?php elseif ($total_lessons == 0): ?>
                <tr>
                    <td colspan="3" style="text-align:center; color:#888;">No lessons available for this course.</td>
                </tr>
            <?php else: ?>
                <?php while ($student = $students_result->fetch_assoc()): ?>
                    <?php
                        $student_id = $student['id'];
                        // Đếm số bài học đã hoàn thành
                        $stmt2 = $conn->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND course_id = ? AND is_completed = 1");
                        $stmt2->bind_param("ii", $student_id, $course_id);
                        $stmt2->execute();
                        $stmt2->bind_result($completed_lessons);
                        $stmt2->fetch();
                        $stmt2->close();

                        $completion_rate = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
                        $avg_quiz_score = $avg_scores[$student_id] ?? 'N/A';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($student['fullname']) ?></td>
                        <td><?= $completion_rate ?>%</td>
                        <td><?= $avg_quiz_score ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <a href="admin_courses.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Courses</a>
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