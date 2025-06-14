<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

// Lấy course_id từ URL
if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}
$course_id = intval($_GET['course_id']);

// Lấy tên khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "Course not found.";
    exit;
}
$stmt->bind_result($course_title);
$stmt->fetch();
$stmt->close();

// Xóa enrollment nếu bấm remove
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    $conn->query("DELETE FROM enrollments WHERE id = $remove_id AND course_id = $course_id");
    header("Location: admin_enrollments.php?course_id=$course_id");
    exit;
}

// Lấy danh sách học viên đã enroll
$result = $conn->query("
  SELECT e.id AS enroll_id, u.fullname, u.email, e.enrolled_at
  FROM enrollments e
  JOIN users u ON e.user_id = u.id
  WHERE e.course_id = $course_id
  ORDER BY e.enrolled_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollments - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
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
        .sidebar {
            width: 260px;
            background-color: #34495e;
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
            border-left-color: #e74c3c;
        }
        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.1em;
        }
        .main-content {
            margin-left: 260px;
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .manage-courses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        .manage-courses-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }
        .manage-courses-header h2 i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .enrollment-table-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .enrollment-table-section h3 {
            font-size: 1.3em;
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            font-weight: 500;
        }
        .enrollment-table-section h3 i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .enrollment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: #fff;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }
        .enrollment-table thead th {
            background-color: #f8f9fa;
            color: #555;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .enrollment-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .enrollment-table tbody tr:last-child td {
            border-bottom: none;
        }
        .enrollment-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .enrollment-actions a {
            color: #e74c3c;
            text-decoration: none;
            font-size: 0.95em;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
            padding: 6px 12px;
            border-radius: 5px;
            border: 1px solid #e74c3c;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }
        .enrollment-actions a i {
            margin-right: 5px;
        }
        .enrollment-actions a:hover {
            background-color: #e74c3c;
            color: white;
        }
        .no-enrollments {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
            color: #777;
            font-style: italic;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .no-enrollments i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .back-link {
            margin-top: 30px;
        }
        .back-link a {
            color: #fff;
            background: linear-gradient(90deg, #e74c3c 0%, #f1c40f 100%);
            text-decoration: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 12px 26px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .back-link a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
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
            <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
            <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
            <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
            <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="manage-courses-header">
            <h2><i class="fas fa-users"></i> Enrollments for: <?= htmlspecialchars($course_title) ?></h2>
            
        </div>
        <div class="enrollment-table-section">
            <h3><i class="fas fa-list-alt"></i> Enrolled Students</h3>
            <?php if ($result->num_rows > 0): ?>
                <table class="enrollment-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Enrolled At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['enrolled_at']) ?></td>
                                <td class="enrollment-actions">
                                    <a href="admin_enrollments.php?course_id=<?= $course_id ?>&remove_id=<?= $row['enroll_id'] ?>" onclick="return confirm('Are you sure you want to remove this enrollment?')"><i class="fas fa-trash-alt"></i> Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-enrollments"><i class="fas fa-info-circle"></i> No students have enrolled in this course yet.</p>
            <?php endif; ?>
        </div>
        <div class="back-link">
            <a href="admin_courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
        </div>
        <hr>
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