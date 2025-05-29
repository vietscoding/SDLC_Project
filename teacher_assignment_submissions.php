<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['assignment_id'])) {
    echo "Assignment ID missing.";
    exit;
}

$assignment_id = intval($_GET['assignment_id']);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu assignment
$stmt = $conn->prepare("
    SELECT a.title, c.id, c.title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You do not have permission to view submissions for this assignment.";
    exit;
}
$stmt->bind_result($assignment_title, $course_id, $course_title);
$stmt->fetch();
$stmt->close();

// Xử lý chấm điểm và feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = floatval($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $update_stmt = $conn->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ? WHERE id = ?");
    $update_stmt->bind_param("dsi", $grade, $feedback, $submission_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Thêm ?saved=1 vào URL khi quay lại trang
header("Location: teacher_assignment_submissions.php?assignment_id=$assignment_id&saved=1");
exit;

}

// Lấy danh sách bài nộp
$result = $conn->prepare("
    SELECT s.id, u.fullname, s.submitted_text, s.submitted_file, s.submitted_at, s.grade, s.feedback
    FROM assignment_submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
$result->bind_param("i", $assignment_id);
$result->execute();
$submissions = $result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions for <?= htmlspecialchars($assignment_title) ?> | [Your University Name]</title>
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

        .submissions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .submissions-header h2 {
            font-size: 2.2em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .submissions-header h2 i {
            margin-right: 10px;
            color: #007bff; /* Blue icon */
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #155724;
            font-weight: bold;
        }

        .submissions-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            color: #555;
        }

        .submissions-info p {
            margin-bottom: 5px;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden; /* To contain the border-radius for thead/tbody */
        }

        .submissions-table thead {
            background-color: #f8f9fa;
            color: #555;
            font-weight: bold;
        }

        .submissions-table th, .submissions-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .submissions-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .submissions-table tbody tr:last-child td {
            border-bottom: none;
        }

        .submission-actions form {
            margin: 0;
            display: grid; /* Use grid layout for better control */
            grid-template-columns: auto 1fr auto; /* Grade | Feedback | Save */
            align-items: center;
            gap: 10px;
        }

        .submission-actions label {
            font-weight: bold;
            color: #555;
            justify-self: start; /* Align label to the start of the grid cell */
        }

        .submission-actions input[type="number"],
        .submission-actions textarea {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .submission-actions input[type="number"] {
            width: 60px;
            justify-self: start; /* Align grade input to the start */
        }

        .submission-actions textarea {
            width: 100%; /* Make feedback textarea take full width of its grid cell */
            height: 60px; /* Increased height for more feedback space */
            resize: vertical;
            grid-column: 2 / 3; /* Span across the middle grid cell */
        }

        .submission-actions button[type="submit"] {
            background-color: #007bff; /* Blue save button */
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s ease;
            justify-self: end; /* Align save button to the end */
        }

        .submission-actions button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .download-link {
            color: #28a745; /* Green download link */
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .download-link:hover {
            color: #1e7e34;
            text-decoration: underline;
        }

        .no-submissions {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            color: #777;
            font-style: italic;
            margin-top: 20px;
        }

        .back-links {
            margin-top: 20px;
        }

        .back-links a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
            transition: color 0.2s ease;
        }

        .back-links a:hover {
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .sidebar ul li a {
            color: #ddd;
        }

        .dark-mode .sidebar ul li a:hover,
        .dark-mode .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #ffc107;
        }

        .dark-mode .submissions-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .success-message {
            background-color: #1e3625;
            color: #a7e0b5;
            border-left-color: #81c784;
        }

        .dark-mode .submissions-info {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .submissions-table {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .submissions-table thead {
            background-color: #495057;
            color: #eee;
        }

        .dark-mode .submissions-table th, .dark-mode .submissions-table td {
            border-bottom-color: #555;
        }

        .dark-mode .submissions-table tbody tr:nth-child(even) {
            background-color: #343a40;
        }

        .dark-mode .submission-actions label {
            color: #ccc;
        }

        .dark-mode .submission-actions input[type="number"],
        .dark-mode .submission-actions textarea {
            background-color: #495057;
            color: #eee;
            border-color: #555;
        }

        .dark-mode .submission-actions button[type="submit"] {
            background-color: #007bff;
            color: #fff;
        }

        .dark-mode .download-link {
            color: #81c784;
        }

        .dark-mode .no-submissions {
            background-color: #343a40;
            color: #ccc;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .back-links a {
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
        <div class="submissions-header">
            <h2><i class="fas fa-list-alt"></i> Submissions for: <?= htmlspecialchars($assignment_title) ?></h2>
        </div>

        <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Grade and feedback saved successfully!
            </div>
        <?php endif; ?>

        <div class="submissions-info">
            <p><i class="fas fa-graduation-cap"></i> Course: <?= htmlspecialchars($course_title) ?></p>
        </div>

        <?php if ($submissions->num_rows > 0): ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Submitted Text</th>
                        <th>Submitted File</th>
                        <th>Submitted At</th>
                        <th>Grade</th>
                        <th>Feedback</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $submissions->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['submitted_text'])) ?></td>
                            <td>
                                <?php if ($row['submitted_file']): ?>
                                    <a href="<?= htmlspecialchars($row['submitted_file']) ?>" target="_blank" class="download-link"><i class="fas fa-download"></i> Download</a>
                                <?php else: ?>
                                    No file
                                <?php endif; ?>
                            </td>
                            <td><?= $row['submitted_at'] ?></td>
                            <td class="submission-actions">
                                <form method="post">
                                    <label for="grade_<?= $row['id'] ?>">Grade:</label>
                                    <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                    <input type="number" id="grade_<?= $row['id'] ?>" name="grade" value="<?= $row['grade'] ?? '' ?>" step="0.01" min="0" max="100">
                                </td>
                            <td class="submission-actions">
                                <label for="feedback_<?= $row['id'] ?>">Feedback:</label>
                                <textarea id="feedback_<?= $row['id'] ?>" name="feedback" rows="3"><?= htmlspecialchars($row['feedback']) ?></textarea>
                            </td>
                            <td class="submission-actions">
                                <button type="submit"><i class="fas fa-save"></i> Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-submissions"><i class="fas fa-inbox"></i> No submissions yet for this assignment.</p>
        <?php endif; ?>

        <div class="back-links">
            <a href="teacher_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
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
