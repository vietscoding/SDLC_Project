<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy tất cả quiz thuộc các khóa học giáo viên quản lý
$stmt = $conn->prepare("
    SELECT q.id, q.title, c.title AS course_title, c.id AS course_id
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY c.title, q.title
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Quizzes | BTEC</title>
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
            background-color: #f4f6f8;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
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

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            width: 70%;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
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
            border-left-color: #e67e22;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.1em;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .manage-quizzes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .manage-quizzes-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .manage-quizzes-header h2 i {
            margin-right: 10px;
            color: #007bff;
        }

        .add-quiz-link {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .add-quiz-link:hover {
            background-color: #0056b3;
        }

        .quizzes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .quizzes-table thead th {
            background-color: #f8f9fa;
            color: #555;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .quizzes-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .quizzes-table tbody tr:last-child td {
            border-bottom: none;
        }

        .quizzes-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .quiz-actions a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.2s ease;
        }

        .quiz-actions a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .quiz-actions a:last-child {
            margin-right: 0;
        }

        .no-quizzes {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
            color: #777;
            font-style: italic;
        }

        .back-to-dashboard {
            margin-top: 30px;
        }

        .back-to-dashboard a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-to-dashboard a:hover {
            color: #0056b3;
            text-decoration: underline;
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

        .dark-mode .manage-quizzes-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .add-quiz-link {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .quizzes-table {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .quizzes-table thead th {
            background-color: #495057;
            color: #ccc;
            border-bottom-color: #555;
        }

        .dark-mode .quizzes-table tbody td {
            border-bottom-color: #495057;
        }

        .dark-mode .quizzes-table tbody tr:nth-child(even) {
            background-color: #495057;
        }

        .dark-mode .quiz-actions a {
            color: #007bff;
        }

        .dark-mode .no-quizzes {
            background-color: #343a40;
            color: #bbb;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .back-to-dashboard a {
            color: #007bff;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
        <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
        <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
        <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
        <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
        <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
        <li><a href="teacher_quizzes.php" class="active"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
         <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="manage-quizzes-header">
        <h2><i class="fas fa-question-circle"></i> Manage Quizzes</h2>
        <a href="teacher_quiz_edit.php?action=add" class="add-quiz-link"><i class="fas fa-plus-circle"></i> Add New Quiz</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="quizzes-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Quiz Title</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td class="quiz-actions">
                            <a href="teacher_quiz_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="teacher_quiz_questions.php?quiz_id=<?= $row['id'] ?>"><i class="fas fa-list-ul"></i> Manage Questions</a>
                            <a href="teacher_quizzes.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this quiz?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-quizzes"><i class="fas fa-exclamation-circle"></i> No quizzes found.</p>
    <?php endif; ?>

    <div class="back-to-dashboard">
        <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
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
