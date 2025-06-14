<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

// Lấy danh sách tất cả assignments
$sql = "
    SELECT a.id, a.title, a.due_date, c.title AS course_title, c.id AS course_id
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    ORDER BY a.due_date DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments | Admin</title>
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
            width: 200px;
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
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 10px 10px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 4px solid transparent;
            font-size: 0.95em;
            white-space: normal;
            word-break: break-word;
            min-height: 38px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #e74c3c;
        }
        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 1em;
        }
        .main-content {
            margin-left: 200px;
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .assignments-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
            justify-content: space-between;
        }
        .assignments-header h2 {
            font-size: 2em;
            color: #333;
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .assignments-header h2 i {
            margin-right: 10px;
            color: #e74c3c;
        }
        .add-assignment-link {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            font-size: 1em;
            gap: 8px;
        }
        .add-assignment-link i { margin-right: 8px; }
        .add-assignment-link:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
        }
        .assignments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(44,62,80,0.06);
        }
        .assignments-table thead {
            background: linear-gradient(90deg, #ffe082 0%, #ffe0b2 100%);
            color: #2c3e50;
            font-weight: bold;
        }
        .assignments-table th, .assignments-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #f1c40f33;
        }
        .assignments-table tbody tr:last-child td { border-bottom: none; }
        .assignments-table tbody tr:hover { background: #fffbe6; }
        .assignment-actions a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #3498db;
            background: #f8f9fa;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 0.97em;
            font-weight: 500;
            margin-right: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 4px rgba(41,128,185,0.07);
        }
        .assignment-actions a:last-child {
            margin-right: 0;
            color: #dc3545;
            background: #fff0f0;
        }
        .assignment-actions a i { margin-right: 6px; font-size: 1em; }
        .assignment-actions a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
        }
        .assignment-actions a:last-child:hover { background: #dc3545; color: #fff; }
        .no-assignments {
            background-color: #fdfdfd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-align: center;
            color: #777;
            font-style: italic;
            font-size: 1em;
            border: 1px solid #e0e0e0;
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .no-assignments i { color: #f39c12; }
        .logout-link {
            margin-top: 20px;
        }
        .logout-link a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }
        .logout-link a:hover {
            color: #c0392b;
            text-decoration: underline;
        }
        hr { margin-top: 30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)); }
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
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 20px; }
            .sidebar { position: static; width: 100%; height: auto; }
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
        <li><a href="admin_approve_teachers.php"><i class="fas fa-user-check"></i> User authorization</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_assignments.php" class="active"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>
<div class="main-content">
    <div class="assignments-header">
        <h2><i class="fas fa-tasks"></i> Manage Assignments</h2>
        <a href="admin_assignment_edit.php?action=add" class="add-assignment-link"><i class="fas fa-plus-circle"></i> Add New Assignment</a>
    </div>
    <?php if ($result->num_rows > 0): ?>
        <table class="assignments-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Title</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['course_title']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= $row['due_date'] ?></td>
                        <td class="assignment-actions">
                            <a href="admin_assignment_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="admin_assignments.php?delete_id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this assignment?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-assignments"><i class="fas fa-exclamation-circle"></i> No assignments found.</p>
    <?php endif; ?>
    <div class="logout-link">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
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
<?php
// Xóa assignment
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt_delete = $conn->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    header("Location: admin_assignments.php");
    exit;
}
?>