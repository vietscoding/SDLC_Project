<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Xử lý search nếu có
$keyword = $_GET['keyword'] ?? "";

// Hàm lấy user theo role + keyword
function getUsersByRole($conn, $role, $keyword) {
    if (!empty($keyword)) {
        $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE role = ? AND (id LIKE ? OR fullname LIKE ? OR email LIKE ?) ORDER BY id DESC");
        $like = '%' . $keyword . '%';
        $stmt->bind_param("ssss", $role, $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE role = ? ORDER BY id DESC");
        $stmt->bind_param("s", $role);
    }
    $stmt->execute();
    return $stmt->get_result();
}

$students = getUsersByRole($conn, 'student', $keyword);
$teachers = getUsersByRole($conn, 'teacher', $keyword);
$admins   = getUsersByRole($conn, 'admin', $keyword);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users (Admin) | BTEC</title>
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

        .manage-users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .manage-users-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .manage-users-header h2 i {
            margin-right: 10px;
            color: #e74c3c; /* Red icon for admin users */
        }

        .search-container {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form {
            flex-grow: 1;
            display: flex;
        }

        .search-form input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px 0 0 5px;
            flex-grow: 1;
            font-size: 1em;
        }

        .search-form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease;
        }

        .search-form button:hover {
            background-color: #0056b3;
        }

        .search-form a {
            display: inline-block;
            margin-left: 10px;
            color: #007bff;
            text-decoration: none;
            padding: 10px 15px;
            border: 1px solid #007bff;
            border-radius: 5px;
            font-size: 1em;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .search-form a:hover {
            background-color: #007bff;
            color: white;
        }

        .user-list-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .user-list-section h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .user-list-section h3 i {
            margin-right: 10px;
            color: #f39c12; /* Orange for user sections */
        }

        .user-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .user-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-actions a {
            color: #007bff;
            text-decoration: none;
            margin-left: 15px;
            transition: color 0.2s ease;
        }

        .user-actions a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .no-users {
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

        .dark-mode .manage-users-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .search-form input[type="text"] {
            background-color: #495057;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .search-form button {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .search-form a {
            color: #007bff;
            border-color: #007bff;
        }

        .dark-mode .search-form a:hover {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .user-list-section {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .user-list-section h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .user-item {
            border-bottom-color: #495057;
        }

        .dark-mode .user-info {
            color: #ccc;
        }

        .dark-mode .user-actions a {
            color: #007bff;
        }

        .dark-mode .no-users {
            color: #bbb;
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
        <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="manage-users-header">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
    </div>

    <div class="search-container">
        <form method="get" class="search-form">
            <input type="text" name="keyword" placeholder="Search by ID, name or email..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($keyword)): ?>
                <a href="admin_users.php"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="user-list-section">
        <h3><i class="fas fa-user-graduate"></i> Students</h3>
        <?php if ($students->num_rows > 0): ?>
            <ul class="user-list">
                <?php while ($row = $students->fetch_assoc()): ?>
                    <li class="user-item">
                        <div class="user-info">
                            <?= $row['id'] ?> - <?= htmlspecialchars($row['fullname']) ?> (<?= htmlspecialchars($row['email']) ?>)
                        </div>
                        <div class="user-actions">
                            <a href="admin_edit_user.php?user_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="admin_delete_user.php?user_id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-users"><i class="fas fa-exclamation-circle"></i> No students found.</p>
        <?php endif; ?>
    </div>

    <div class="user-list-section">
        <h3><i class="fas fa-chalkboard-teacher"></i> Teachers</h3>
        <?php if ($teachers->num_rows > 0): ?>
            <ul class="user-list">
                <?php while ($row = $teachers->fetch_assoc()): ?>
                    <li class="user-item">
                        <div class="user-info">
                            <?= $row['id'] ?> - <?= htmlspecialchars($row['fullname']) ?> (<?= htmlspecialchars($row['email']) ?>)
                        </div>
                        <div class="user-actions">
                            <a href="admin_edit_user.php?user_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="admin_delete_user.php?user_id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-users"><i class="fas fa-exclamation-circle"></i> No teachers found.</p>
        <?php endif; ?>
    </div>

    <div class="user-list-section">
        <h3><i class="fas fa-user-cog"></i> Admins</h3>
        <?php if ($admins->num_rows > 0): ?>
            <ul class="user-list">
                <?php while ($row = $admins->fetch_assoc()): ?>
                    <li class="user-item">
                        <div class="user-info">
                            <?= $row['id'] ?> - <?= htmlspecialchars($row['fullname']) ?> (<?= htmlspecialchars($row['email']) ?>)
                        </div>
                        <div class="user-actions">
                            <a href="admin_edit_user.php?user_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="admin_delete_user.php?user_id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-users"><i class="fas fa-exclamation-circle"></i> No admins found.</p>
        <?php endif; ?>
    </div>

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
