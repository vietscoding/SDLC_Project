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
$error = "";

// Lấy danh sách teacher
$teacher_result = $conn->query("SELECT id, fullname FROM users WHERE role = 'teacher'");

// Lấy thông tin course cần sửa
$stmt = $conn->prepare("SELECT title, department, description, teacher_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($title, $department, $description, $teacher_id);
if (!$stmt->fetch()) {
    echo "Course not found.";
    exit;
}
$stmt->close();

// Cập nhật course khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_title = trim($_POST['title']);
    $new_department = trim($_POST['department']);
    $new_description = trim($_POST['description']);
    $new_teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (!empty($new_title) && !empty($new_department)) {
        $stmt = $conn->prepare("UPDATE courses SET title = ?, department = ?, description = ?, teacher_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $new_title, $new_department, $new_description, $new_teacher_id, $course_id);
        if ($stmt->execute()) {
            header("Location: admin_courses.php");
            exit;
        } else {
            $error = "Failed to update course.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in at least Title and Department.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Course | BTEC</title>
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

        .edit-course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .edit-course-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .edit-course-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue for edit */
        }

        .edit-course-form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 16px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group textarea {
            resize: vertical;
        }

        .form-group select option {
            padding: 8px;
        }

        .form-actions button[type="submit"] {
            background-color: #007bff; /* Blue for save changes */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease;
        }

        .form-actions button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-style: italic;
        }

        .back-link {
            margin-top: 20px;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
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

        .dark-mode .edit-course-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .edit-course-form-container {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .form-group label {
            color: #ccc;
        }

        .dark-mode .form-group input[type="text"],
        .dark-mode .form-group textarea,
        .dark-mode .form-group select {
            background-color: #495057;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .form-group select option {
            color: #eee;
            background-color: #343a40;
        }

        .dark-mode .form-actions button[type="submit"] {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .error-message {
            color: #ffc107;
        }

        .dark-mode .back-link a {
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
    <div class="edit-course-header">
        <h2><i class="fas fa-edit"></i> Edit Course</h2>
    </div>

    <div class="edit-course-form-container">
        <?php if ($error): ?>
            <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="title"><i class="fas fa-signature"></i> Course Title:</label><br>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
            </div>

            <div class="form-group">
                <label for="department"><i class="fas fa-tag"></i> Department:</label><br>
                <input type="text" id="department" name="department" value="<?= htmlspecialchars($department) ?>" required>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-file-alt"></i> Description:</label><br>
                <textarea id="description" name="description" rows="4" cols="50"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="form-group">
                <label for="teacher_id"><i class="fas fa-chalkboard-teacher"></i> Instructor (optional):</label><br>
                <select id="teacher_id" name="teacher_id">
                    <option value="">-- None --</option>
                    <?php while ($teacher = $teacher_result->fetch_assoc()): ?>
                        <option value="<?= $teacher['id'] ?>" <?= ($teacher['id'] == $teacher_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['fullname']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <div class="back-link">
        <a href="admin_courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
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