<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$success = "";
$error = "";

// Xử lý duyệt tài khoản nếu admin bấm approve
if (isset($_GET['approve_id'])) {
    $approve_id = intval($_GET['approve_id']);
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'teacher'");
    $stmt->bind_param("i", $approve_id);
    if ($stmt->execute()) {
        $success = "Teacher approved successfully.";
    } else {
        $error = "Failed to approve teacher.";
    }
    $stmt->close();
    // Redirect to clear the GET parameter and prevent re-approval on refresh
    header("Location: admin_approve_teachers.php?status=" . (empty($success) ? 'error' : 'success'));
    exit;
}

// Check for status messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success = "Teacher approved successfully.";
    } elseif ($_GET['status'] === 'error') {
        $error = "Failed to approve teacher.";
    }
}

// Lấy danh sách teacher pending
$result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'teacher' AND status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Teachers | BTEC FPT</title>
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
            display: flex; /* Make body a flex container */
            min-height: 100vh; /* Ensure body takes full viewport height */
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: #34495e; /* Darker blue for admin */
            color: white;
            position: fixed; /* Keep sidebar fixed */
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto; /* Enable scrolling for long menus */
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

        /* Content Wrapper for main content and footer */
        .content-wrapper {
            margin-left: 260px; /* Offset for fixed sidebar */
            flex-grow: 1; /* Allow content wrapper to take remaining horizontal space */
            display: flex; /* Make it a flex container */
            flex-direction: column; /* Stack main content and footer vertically */
            min-height: 100vh; /* Ensure it takes full viewport height */
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            flex-grow: 1; /* Allow main content to push footer down */
            display: flex;
            flex-direction: column;
            background-color: #fff; /* Added background for main content area */
            box-shadow: 0 0 20px rgba(0,0,0,0.05); /* Added shadow */
            border-radius: 8px; /* Added border-radius */
            margin-bottom: 20px; /* Space before footer if it's not pushed down */
        }

        .approve-teachers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .approve-teachers-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .approve-teachers-header h2 i {
            margin-right: 10px;
            color: #e74c3c; /* Red icon for admin */
        }

        .approve-teachers-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .approve-teachers-container h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .approve-teachers-container h3 i {
            margin-right: 10px;
            color: #f39c12; /* Orange icon */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden; /* Ensures rounded corners apply to table content */
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0; /* Lighter border */
            text-align: left;
        }

        th {
            background: #34495e; /* Darker blue for table headers */
            color: #fff;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f0f0; /* Subtle hover effect */
        }

        .approve-button {
            display: inline-flex; /* Use flex for icon alignment */
            align-items: center;
            gap: 5px; /* Space between icon and text */
            padding: 8px 15px;
            background: #28a745; /* Green for approve */
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s ease, transform 0.2s ease;
            font-size: 0.9em;
        }

        .approve-button:hover {
            background: #218838;
            transform: translateY(-1px); /* Subtle lift on hover */
        }

        .no-pending-teachers {
            padding: 20px;
            background-color: #f0f8ff; /* Light blue background */
            border: 1px solid #b0e0e6;
            border-radius: 8px;
            text-align: center;
            color: #555;
            font-style: italic;
            margin-top: 20px;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #e6ffe6;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #a3e6a3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e6a3a3;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link {
            margin-top: 20px;
            text-align: center;
        }

        .back-link a {
            color: #6c757d;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link a:hover {
            color: #495057;
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 30px;
            margin-top: auto; /* Push footer to the bottom of the flex column */
            font-size: 0.9em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            /* Removed border-radius as it's now full width */
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

        /* Dark Mode */
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

        .dark-mode .content-wrapper {
            background-color: #343a40; /* Apply dark mode background to wrapper */
        }

        .dark-mode .main-content {
            background-color: #343a40;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .approve-teachers-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .approve-teachers-container {
            background-color: #495057;
            color: #eee;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .dark-mode .approve-teachers-container h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode table {
            background-color: #495057;
        }

        .dark-mode th {
            background: #2c3e50;
            border-color: #6c757d;
        }

        .dark-mode td {
            border-color: #6c757d;
        }

        .dark-mode tr:nth-child(even) {
            background: #5a6268;
        }

        .dark-mode tr:hover {
            background-color: #6c757d;
        }

        .dark-mode .approve-button {
            background: #28a745;
            color: #fff;
        }

        .dark-mode .approve-button:hover {
            background: #218838;
        }

        .dark-mode .no-pending-teachers {
            background-color: #343a40;
            border-color: #555;
            color: #ccc;
        }

        .dark-mode .success-message {
            background-color: #28a745;
            border-color: #1a6f2b;
            color: #fff;
        }

        .dark-mode .error-message {
            background-color: #dc3545;
            border-color: #a71d2a;
            color: #fff;
        }

        .dark-mode .back-link a {
            color: #a7b1b8;
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
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
        <li><a href="admin_approve_teachers.php" class="active"><i class="fas fa-user-check"></i> User Authorization</a></li>
        <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
        <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
        <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
        <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="content-wrapper">
    <div class="main-content">
        <div class="approve-teachers-header">
            <h2><i class="fas fa-user-check"></i> Approve Pending Teachers</h2>
        </div>

        <div class="approve-teachers-container">
            <h3><i class="fas fa-list-alt"></i> Pending Teacher Accounts</h3>
            <?php if (!empty($success)): ?>
                <p class='success-message'><i class='fas fa-check-circle'></i> <?= $success ?></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class='error-message'><i class='fas fa-exclamation-triangle'></i> <?= $error ?></p>
            <?php endif; ?>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <a class="approve-button" href="admin_approve_teachers.php?approve_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to approve this teacher?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-pending-teachers"><i class="fas fa-info-circle"></i> No pending teacher accounts at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="back-link">
            <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
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
        <p>&copy; 2025 BTEC FPT - Learning Management System.</p>
        <small>Powered by Innovation in Education</small>
    </footer>
</div>

</body>
</html>
