<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Lấy danh sách assignments của các khóa học giáo viên phụ trách
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.due_date, c.title AS course_title, c.id AS course_id
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY a.due_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments | [Your University Name]</title>
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
            background-color: #f4f6f8; /* Light background */
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* Sidebar (Fixed Width and Style) */
        .sidebar {
            width: 280px; /* Match previous sidebars */
            background-color: #2c3e50; /* Teacher-specific dark blue */
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
            margin-bottom: 30px; /* More margin to match */
        }

        .sidebar .logo img {
            display: block;
            width: 80%; /* Match previous logos */
            height: auto;
            margin: 0 auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex; /* Use flex to align icon and text */
            align-items: center; /* Vertically align icon and text */
            padding: 15px 20px; /* Match previous padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent; /* Indicator */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #f39c12; /* Teacher-specific accent */
        }

        .sidebar ul li a i {
            margin-right: 15px; /* Spacing for the icon */
            font-size: 1.2em; /* Icon size */
        }

        /* Main Content */
        .main-content {
            margin-left: 280px; /* Match sidebar width */
            padding: 30px;
            flex-grow: 1; /* Allow main content to take up remaining vertical space */
        }

        .assignments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .assignments-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .assignments-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .add-assignment-link {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745; /* Green add button */
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background-color 0.2s ease;
        }

        .add-assignment-link:hover {
            background-color: #1e7e34;
        }

        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden; /* To contain the border-radius for thead/tbody */
        }

        .assignments-table thead {
            background-color: #f8f9fa;
            color: #555;
            font-weight: bold;
        }

        .assignments-table th, .assignments-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .assignments-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .assignments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .assignment-actions a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.2s ease;
        }

        .assignment-actions a:last-child {
            margin-right: 0;
        }

        .assignment-actions a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .assignment-actions a.delete-link {
            color: #dc3545; /* Red delete link */
        }

        .assignment-actions a.delete-link:hover {
            color: #c82333;
        }

        .no-assignments {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            color: #777;
            font-style: italic;
            margin-top: 20px;
        }

        .back-to-dashboard {
            margin-top: 20px;
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

        .dark-mode .assignments-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .add-assignment-link {
            background-color: #28a745;
            color: #fff;
        }

        .dark-mode .add-assignment-link:hover {
            background-color: #1e7e34;
        }

        .dark-mode .assignments-table {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .assignments-table thead {
            background-color: #495057;
            color: #eee;
        }

        .dark-mode .assignments-table th, .dark-mode .assignments-table td {
            border-bottom-color: #555;
        }

        .dark-mode .assignments-table tbody tr:nth-child(even) {
            background-color: #343a40;
        }

        .dark-mode .assignment-actions a {
            color: #007bff;
        }

        .dark-mode .assignment-actions a.delete-link {
            color: #dc3545;
        }

        .dark-mode .no-assignments {
            background-color: #343a40;
            color: #ccc;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .back-to-dashboard a {
            color: #007bff;
        }

        .dark-mode footer {
            color: #ccc;
            border-top-color: #555;
            background-color: #343a40;
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
            <li><a href="teacher_assignments.php" class="active"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="assignments-header">
            <h2><i class="fas fa-tasks"></i> Manage Assignments</h2>
        </div>

        <a href="teacher_assignment_edit.php?action=add" class="add-assignment-link"><i class="fas fa-plus"></i> Add New Assignment</a>

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
                                <a href="teacher_assignment_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a> |
                                <a href="teacher_assignment_submissions.php?assignment_id=<?= $row['id'] ?>"><i class="fas fa-list-alt"></i> View Submissions</a> |
                                <a href="teacher_assignments.php?delete_id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this assignment?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-assignments"><i class="fas fa-exclamation-circle"></i> No assignments found.</p>
        <?php endif; ?>

        <p class="back-to-dashboard"><a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>

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

<?php
// Xóa assignment
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Kiểm tra quyền sở hữu assignment
    $stmt_check = $conn->prepare("
        SELECT a.id FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt_check->bind_param("ii", $delete_id, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_delete = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }

    $stmt_check->close();
    header("Location: teacher_assignments.php");
    exit;
}
?>
