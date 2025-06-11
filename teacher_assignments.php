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
    <title>Manage Assignments | BTEC FPT</title>
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
        .assignments-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
            justify-content: space-between;
        }
        .assignments-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
            display: flex;
            align-items: center;
        }
        .assignments-header h2 i {
            margin-right: 10px;
            color: #f39c12;
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
            box-shadow: 0 2px 8px rgba(41,128,185,0.10);
            font-size: 1em;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            border: none;
            outline: none;
            gap: 8px;
        }
        .add-assignment-link i {
            margin-right: 8px;
        }
        .add-assignment-link:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
            transform: translateY(-2px) scale(1.04);
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
        .assignments-table tbody tr:last-child td {
            border-bottom: none;
        }
        .assignments-table tbody tr:hover {
            background: #fffbe6;
            transition: background 0.2s;
        }
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
        .assignment-actions a i {
            margin-right: 6px;
            font-size: 1em;
        }
        .assignment-actions a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #2c3e50;
            box-shadow: 0 4px 16px rgba(243,156,18,0.13);
        }
        .assignment-actions a:last-child:hover {
            background: #dc3545;
            color: #fff;
        }
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
        .no-assignments i {
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
        .dark-mode .assignments-header {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
        }
        .dark-mode .assignments-header h2,
        .dark-mode .assignments-header h2 i {
            color: #181e29;
        }
        .dark-mode .add-assignment-link {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .add-assignment-link:hover {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .assignments-table {
            background: #23272f;
            color: #eee;
        }
        .dark-mode .assignments-table thead {
            background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
            color: #181e29;
        }
        .dark-mode .assignments-table tbody tr:hover {
            background: #2c3e50;
        }
        .dark-mode .assignment-actions a {
            background: #23272f;
            color: #ffe082;
        }
        .dark-mode .assignment-actions a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .assignment-actions a:last-child {
            background: #3a2323;
            color: #ffb3b3;
        }
        .dark-mode .assignment-actions a:last-child:hover {
            background: #dc3545;
            color: #fff;
        }
        .dark-mode .no-assignments {
            background-color: #333;
            color: #ccc;
            border-color: #444;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .dark-mode .no-assignments i {
            color: #f39c12;
        }
        .dark-mode .navigation-links a {
            background: linear-gradient(90deg, #23272f 0%, #22304a 100%);
            color: #ffe082;
        }
        .dark-mode .navigation-links a:hover {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode footer {
            background: #23272f;
            color: #aaa;
        }
        .dark-mode footer a {
            color: #ffe082;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .assignments-header h2 { font-size: 1.8em; }
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
            .assignments-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .assignments-header h2 { margin-bottom: 10px; font-size: 1.8em; }
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
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="assignments-header">
                <h2><i class="fas fa-tasks"></i> Manage Assignments</h2>
                <a href="teacher_assignment_edit.php?action=add" class="add-assignment-link"><i class="fas fa-plus-circle"></i> Add New Assignment</a>
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
                                    <a href="teacher_assignment_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="teacher_assignment_submissions.php?assignment_id=<?= $row['id'] ?>"><i class="fas fa-list-alt"></i> View Submissions</a>
                                    <a href="teacher_assignments.php?delete_id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this assignment?')"><i class="fas fa-trash-alt"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-assignments"><i class="fas fa-exclamation-circle"></i> No assignments found.</p>
            <?php endif; ?>
            <div class="navigation-links">
                <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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
