<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

if (!isset($_GET['quiz_id']) || !isset($_GET['question_id'])) {
    echo "Missing quiz or question ID.";
    exit;
}
$quiz_id = intval($_GET['quiz_id']);
$question_id = intval($_GET['question_id']);

// Lấy thông tin quiz và course
$stmt = $conn->prepare("
    SELECT q.title, c.id, c.title
    FROM quizzes q
    LEFT JOIN courses c ON q.course_id = c.id
    WHERE q.id = ?
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "Quiz not found.";
    exit;
}
$stmt->bind_result($quiz_title, $course_id, $course_title);
$stmt->fetch();
$stmt->close();

// Lấy dữ liệu câu hỏi
$stmt = $conn->prepare("SELECT question, option_a, option_b, option_c, option_d, correct_option FROM quiz_questions WHERE id = ? AND quiz_id = ?");
$stmt->bind_param("ii", $question_id, $quiz_id);
$stmt->execute();
$stmt->bind_result($question, $option_a, $option_b, $option_c, $option_d, $correct_option);
if (!$stmt->fetch()) {
    echo "Question not found.";
    exit;
}
$stmt->close();

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_question = trim($_POST['question']);
    $new_option_a = trim($_POST['option_a']);
    $new_option_b = trim($_POST['option_b']);
    $new_option_c = trim($_POST['option_c']);
    $new_option_d = trim($_POST['option_d']);
    $new_correct_option = $_POST['correct_option'];

    if ($new_question && $new_option_a && $new_option_b && $new_option_c && $new_option_d && in_array($new_correct_option, ['A','B','C','D'])) {
        $stmt = $conn->prepare("
            UPDATE quiz_questions 
            SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?
            WHERE id = ? AND quiz_id = ?
        ");
        $stmt->bind_param("ssssssii", $new_question, $new_option_a, $new_option_b, $new_option_c, $new_option_d, $new_correct_option, $question_id, $quiz_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_quiz_questions.php?quiz_id=$quiz_id");
        exit;
    } else {
        $error = "Please fill all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Quiz Question | BTEC FPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7ef 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 260px;
            background-color: #34495e;
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
            background: linear-gradient(90deg, #e74c3c 0%, #f1c40f 100%);
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
            margin-left: 260px;
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
            background: #e74c3c;
            color: #fff;
        }
        .edit-question-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            background: linear-gradient(90deg, #f1c40f 0%, #e74c3c 100%);
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(243,156,18,0.08);
            padding: 20px 30px;
            justify-content: space-between;
        }
        .edit-question-header h2 {
            font-size: 2em;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(241,196,15,0.08);
            display: flex;
            align-items: center;
        }
        .edit-question-header h2 i {
            margin-right: 10px;
            color: #007bff;
        }
        .edit-question-form-container {
            background: #f9f9f9;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(41,128,185,0.07);
            padding: 30px 24px 24px 24px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 1.05em;
        }
        .form-group label i {
            margin-right: 8px;
            color: #666;
        }
        .form-group textarea,
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1.05em;
            color: #495057;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        .form-group textarea:focus,
        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.13);
        }
        .form-actions {
            text-align: right;
            padding-top: 10px;
        }
        .form-actions button[type="submit"] {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: background 0.3s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .form-actions button[type="submit"]:hover {
            background: linear-gradient(90deg, #e74c3c 0%, #f1c40f 100%);
            color: #2c3e50;
            transform: translateY(-1px) scale(1.04);
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-style: normal;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-message i {
            color: #dc3545;
        }
        .back-link {
            margin-top: 30px;
            text-align: center;
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
        .dark-mode .edit-question-header {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
        }
        .dark-mode .edit-question-header h2,
        .dark-mode .edit-question-header h2 i {
            color: #181e29;
        }
        .dark-mode .edit-question-form-container {
            background: #23272f;
        }
        .dark-mode .form-group label {
            color: #ffe082;
        }
        .dark-mode .form-group input[type="text"],
        .dark-mode .form-group textarea,
        .dark-mode .form-group select {
            background-color: #23272f;
            color: #ffe082;
            border-color: #444;
        }
        .dark-mode .form-group input[type="text"]:focus,
        .dark-mode .form-group textarea:focus,
        .dark-mode .form-group select:focus {
            border-color: #6a9ac9;
            box-shadow: 0 0 0 0.2rem rgba(106,154,201,0.15);
        }
        .dark-mode .form-actions button[type="submit"] {
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            color: #181e29;
        }
        .dark-mode .form-actions button[type="submit"]:hover {
            background: linear-gradient(90deg, #28a745 0%, #6dd5fa 100%);
            color: #fff;
        }
        .dark-mode .error-message {
            background: #3a2323;
            color: #ffb3b3;
            border-color: #ffb3b3;
        }
        .dark-mode .error-message i {
            color: #ffb3b3;
        }
        .dark-mode .back-link a {
            background: linear-gradient(90deg, #23272f 0%, #22304a 100%);
            color: #ffe082;
        }
        .dark-mode .back-link a:hover {
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

        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .edit-question-header h2 { font-size: 1.5em; }
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
            .edit-question-header { flex-direction: column; align-items: flex-start; margin-bottom: 20px; }
            .edit-question-header h2 { margin-bottom: 10px; font-size: 1.2em; }
            .back-link { margin-top: 25px; }
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
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
            <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
            <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
            <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
            <li><a href="admin_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="edit-question-header">
                <h2><i class="fas fa-edit"></i> Edit Question for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
            </div>
            <div class="edit-question-form-container">
                <?php if (!empty($error)): ?>
                    <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label for="question"><i class="fas fa-question"></i> Question:</label>
                        <textarea name="question" id="question" rows="3" required><?= htmlspecialchars($question) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="option_a"><i class="fas fa-check-square"></i> Option A:</label>
                        <input type="text" id="option_a" name="option_a" value="<?= htmlspecialchars($option_a) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="option_b"><i class="fas fa-check-square"></i> Option B:</label>
                        <input type="text" id="option_b" name="option_b" value="<?= htmlspecialchars($option_b) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="option_c"><i class="fas fa-check-square"></i> Option C:</label>
                        <input type="text" id="option_c" name="option_c" value="<?= htmlspecialchars($option_c) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="option_d"><i class="fas fa-check-square"></i> Option D:</label>
                        <input type="text" id="option_d" name="option_d" value="<?= htmlspecialchars($option_d) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="correct_option"><i class="fas fa-flag-checkered"></i> Correct Option:</label>
                        <select name="correct_option" id="correct_option" required>
                            <option value="">-- Select Correct Option --</option>
                            <option value="A" <?= ($correct_option === 'A') ? 'selected' : '' ?>>A</option>
                            <option value="B" <?= ($correct_option === 'B') ? 'selected' : '' ?>>B</option>
                            <option value="C" <?= ($correct_option === 'C') ? 'selected' : '' ?>>C</option>
                            <option value="D" <?= ($correct_option === 'D') ? 'selected' : '' ?>>D</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="back-link">
            <a href="admin_quiz_questions.php?quiz_id=<?= $quiz_id ?>"><i class="fas fa-arrow-left"></i> Back to Questions List</a>
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