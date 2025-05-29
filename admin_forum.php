<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy danh sách forum posts
$result = $conn->query("
    SELECT f.id, f.content, f.posted_at, u.fullname, c.title AS course_title
    FROM forum_posts f
    JOIN users u ON f.user_id = u.id
    JOIN courses c ON f.course_id = c.id
    ORDER BY f.posted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Forum (Admin) | BTEC</title>
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
            background-color: #34495e; /* Darker blue for admin */
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
            border-left-color: #e74c3c; /* Red accent for admin */
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

        .manage-forum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .manage-forum-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .manage-forum-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue for forum */
        }

        .forum-posts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .forum-posts-table thead th {
            background-color: #f8f9fa;
            color: #555;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .forum-posts-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .forum-posts-table tbody tr:last-child td {
            border-bottom: none;
        }

        .forum-posts-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .forum-post-content {
            max-width: 400px; /* Adjust as needed */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .forum-actions a {
            color: #e74c3c; /* Red for delete */
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.2s ease;
        }

        .forum-actions a:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        .no-forum-posts {
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
            background-color: #2c3e50;
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

        .dark-mode .manage-forum-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .forum-posts-table {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .forum-posts-table thead th {
            background-color: #495057;
            color: #ccc;
            border-bottom-color: #555;
        }

        .dark-mode .forum-posts-table tbody td {
            border-bottom-color: #495057;
        }

        .dark-mode .forum-posts-table tbody tr:nth-child(even) {
            background-color: #495057;
        }

        .dark-mode .forum-actions a {
            color: #ff4d4d;
        }

        .dark-mode .no-forum-posts {
            background-color: #343a40;
            color: #bbb;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .back-to-dashboard a {
            color: #007bff;
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

    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php" class="active"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="manage-forum-header">
        <h2><i class="fas fa-comments"></i> Manage Forum Posts</h2>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="forum-posts-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Content</th>
                    <th>User</th>
                    <th>Course</th>
                    <th>Posted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td class="forum-post-content"><?= htmlspecialchars($row['content']) ?></td>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= $row['posted_at'] ?></td>
                        <td class="forum-actions">
                            <a href="admin_delete_post.php?post_id=<?= $row['id'] ?>" onclick="return confirm('Delete this post?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-forum-posts"><i class="fas fa-exclamation-circle"></i> No forum posts found.</p>
    <?php endif; ?>

    <div class="back-to-dashboard">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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