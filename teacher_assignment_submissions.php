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
    <title>Submissions for <?= htmlspecialchars($assignment_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(44,62,80,0.15);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background 0.4s;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }
        .sidebar .logo img {
            display: block;
            width: 70%;
            max-width: 150px;
            height: auto;
            margin: auto;
        }
        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 8px;
            margin-bottom: 10px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            transform: translateX(8px) scale(1.05);
            box-shadow: 0 2px 8px rgba(243,156,18,0.15);
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #f1c40f;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2c3e50;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fff;
            color: #2c3e50;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #f1c40f;
            color: #fff;
        }
        .submissions-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
        }
        .submissions-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
            display: flex;
            align-items: center;
        }
        .submissions-header h2 i {
            margin-right: 10px;
            color: #f39c12;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #155724;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .submissions-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            color: #555;
            border: 1px solid #eee;
        }
        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .submissions-table thead th {
            background-color: #ffe082;
            color: #2c3e50;
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f1c40f33;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .submissions-table tbody td {
            padding: 15px 20px;
            border-bottom: 1px solid #f2f2f2;
            color: #444;
            vertical-align: middle;
        }
        .submissions-table tbody tr:last-child td {
            border-bottom: none;
        }
        .submissions-table tbody tr:hover {
            background: #fffbe6;
            transition: background 0.2s;
        }
        .submission-actions form {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .submission-actions label {
            font-weight: 500;
            color: #555;
            white-space: nowrap;
        }
        .submission-actions input[type="number"],
        .submission-actions textarea {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9em;
            flex-grow: 1;
            max-width: 150px;
        }
        .submission-actions input[type="number"] {
            width: 60px;
            flex-grow: 0;
        }
        .submission-actions textarea {
            min-width: 180px;
            height: 60px;
            resize: vertical;
        }
        .submission-actions button[type="submit"] {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.97em;
            font-weight: 600;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        .submission-actions button[type="submit"]:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .download-link {
            color: #28a745;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .download-link:hover {
            color: #1e7e34;
            text-decoration: underline;
        }
        .no-submissions {
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            color: #777;
            font-style: italic;
            margin-top: 25px;
            text-align: center;
            border: 1px solid #eee;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .no-submissions i {
            color: #f39c12;
        }
        .navigation-links {
            margin-top: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .navigation-links a {
            display: inline-flex;
            align-items: center;
            color: #fff;
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            border-radius: 6px;
            padding: 12px 26px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            border: none;
            outline: none;
            gap: 8px;
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }
        .navigation-links a i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }
        footer a {
            color: #3498db;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }
        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1a1a1a;
        }
        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .submissions-header {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
        }
        .dark-mode .submissions-header h2,
        .dark-mode .submissions-header h2 i {
            color: #181e29;
        }
        .dark-mode .submissions-info {
            background-color: #2a2a2a;
            color: #eee;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            border-color: #444;
        }
        .dark-mode .submissions-table {
            background-color: #2a2a2a;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .dark-mode .submissions-table thead th {
            background-color: #3e3e3e;
            color: #ccc;
            border-bottom-color: #555;
        }
        .dark-mode .submissions-table tbody td {
            border-bottom-color: #3a3a3a;
        }
        .dark-mode .submissions-table tbody tr:hover {
            background-color: #3a3a3a;
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
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .submission-actions button[type="submit"]:hover {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .download-link {
            color: #81c784;
        }
        .dark-mode .no-submissions {
            background-color: #2a2a2a;
            color: #bbb;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-color: #444;
        }
        .dark-mode .no-submissions i {
            color: #f39c12;
        }
        .dark-mode .navigation-links a {
            color: #fbc531;
        }
        .dark-mode .navigation-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }
        .dark-mode footer a {
            color: #fbc531;
        }
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .submissions-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo { padding: 15px 0; }
            .sidebar .logo img { width: 50%; max-width: 120px; }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li { width: 48%; margin-bottom: 5px; }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
                flex-direction: column;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span {
                display: block;
                font-size: 0.8em;
            }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .main-content { padding: 20px; }
            .submissions-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .submissions-header h2 { margin-bottom: 10px; font-size: 1.8em; }
            .navigation-links { flex-direction: column; gap: 15px; }
            footer { margin-top: 25px; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; flex-direction: row; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php" class="active"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
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
                                <td colspan="3" class="submission-actions">
                                    <form method="post">
                                        <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                        <label for="grade_<?= $row['id'] ?>">Grade:</label>
                                        <input type="number" id="grade_<?= $row['id'] ?>" name="grade" value="<?= $row['grade'] ?? '' ?>" step="0.01" min="0" max="100">
                                        <label for="feedback_<?= $row['id'] ?>">Feedback:</label>
                                        <textarea id="feedback_<?= $row['id'] ?>" name="feedback" rows="3"><?= htmlspecialchars($row['feedback']) ?></textarea>
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
            <div class="navigation-links">
                <a href="teacher_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
                <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
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
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
    </script>
</body>
</html>