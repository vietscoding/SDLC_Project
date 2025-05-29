<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['quiz_id'])) {
    echo "Quiz ID missing.";
    exit;
}

$quiz_id = intval($_GET['quiz_id']);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu quiz
$stmt = $conn->prepare("
    SELECT q.title, c.id, c.title
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE q.id = ? AND c.teacher_id = ?
");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You do not have permission to manage this quiz.";
    exit;
}
$stmt->bind_result($quiz_title, $course_id, $course_title);
$stmt->fetch();
$stmt->close();

// Xử lý thêm câu hỏi mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    $question = trim($_POST['question']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = $_POST['correct_option'];

    if ($question && $option_a && $option_b && $option_c && $option_d && in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $quiz_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_option);
        $stmt->execute();
        $stmt->close();
        header("Location: teacher_quiz_questions.php?quiz_id=$quiz_id");
        exit;
    } else {
        $error = "Please fill all fields correctly.";
    }
}

// Xử lý xóa câu hỏi
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM quiz_questions WHERE id = $delete_id AND quiz_id = $quiz_id");
    header("Location: teacher_quiz_questions.php?quiz_id=$quiz_id");
    exit;
}

// Lấy danh sách câu hỏi
$result = $conn->query("SELECT id, question, option_a, option_b, option_c, option_d, correct_option FROM quiz_questions WHERE quiz_id = $quiz_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions - <?= htmlspecialchars($quiz_title) ?> | BTEC</title>
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
            background-color: #2c3e50;
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
            border-left-color: #e67e22;
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

        .manage-questions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        .manage-questions-header h2 {
            font-size: 2.0em;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .manage-questions-header h2 i {
            margin-right: 10px;
            color: #007bff;
        }

        .quiz-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            color: #555;
            font-style: italic;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            font-style: italic;
        }

        .add-question-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .add-question-container h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .add-question-container h3 i {
            margin-right: 10px;
            color: #00c853; /* Green color for add */
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

        .form-group textarea,
        .form-group input[type="text"],
        .form-group select {
            width: calc(100% - 16px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group select option {
            padding: 8px;
        }

        .form-actions button[type="submit"] {
            background-color: #00c853; /* Green color for add button */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease;
        }

        .form-actions button[type="submit"]:hover {
            background-color: #009640;
        }

        .question-list-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
        }

        .question-list-container h3 {
            font-size: 1.6em;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .question-list-container h3 i {
            margin-right: 10px;
            color: #007bff; /* Blue color for list */
        }

        .question-list {
            list-style: none;
            padding-left: 0;
        }

        .question-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .question-item:last-child {
            border-bottom: none;
        }

        .question-text {
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }

        .options {
            margin-left: 20px;
            color: #666;
        }

        .correct-answer {
            margin-top: 10px;
            font-weight: bold;
            color: #2196f3; /* Blue color for correct answer */
        }

        .question-actions {
            margin-top: 10px;
        }

        .question-actions a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.2s ease;
        }

        .question-actions a:hover {
            color: #0056b3;
            text-decoration: underline;
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

        .dark-mode .manage-questions-header h2 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .quiz-info {
            background-color: #343a40;
            color: #bbb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
            border-color: #555;
        }

        .dark-mode .error-message {
            color: #ffc107;
        }

        .dark-mode .add-question-container {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
            border-color: #555;
        }

        .dark-mode .add-question-container h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .form-group label {
            color: #ccc;
        }

        .dark-mode .form-group textarea,
        .dark-mode .form-group input[type="text"],
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
            background-color: #00c853;
            color: #fff;
        }

        .dark-mode .question-list-container {
            background-color: #343a40;
            color: #eee;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
            border-color: #555;
        }

        .dark-mode .question-list-container h3 {
            color: #fff;
            border-bottom-color: #555;
        }

        .dark-mode .question-text {
            color: #eee;
        }

        .dark-mode .options {
            color: #ccc;
        }

        .dark-mode .correct-answer {
            color: #ffc107;
        }

        .dark-mode .question-actions a {
            color: #007bff;
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
        <li><a href="teacher_courses.php"><i class="fas fa-book"></i> My Courses</a></li>
        <li><a href="teacher_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
        <li><a href="teacher_quiz_results.php"><i class="fas fa-chart-bar"></i> View Quiz Results</a></li>
        <li><a href="teacher_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
        <li><a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send Notifications</a></li>
        <li><a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Notifications</a></li>
        <li><a href="teacher_quizzes.php" class="active"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
         <li><a href="teacher_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a></li>
    </ul>
</div>

<div class="main-content">
    <div class="manage-questions-header">
        <h2><i class="fas fa-list-ul"></i> Manage Questions for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
    </div>

    <div class="quiz-info">
        <i class="fas fa-info-circle"></i> Course: <?= htmlspecialchars($course_title) ?>
    </div>

    <?php if (!empty($error)): ?>
        <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="add-question-container">
        <h3><i class="fas fa-plus-circle"></i> Add New Question</h3>
        <form method="post" class="add-question-form">
            <div class="form-group">
                <label for="question"><i class="fas fa-question"></i> Question:</label><br>
                <textarea name="question" id="question" rows="3" cols="60" required></textarea>
            </div>
            <div class="form-group">
                <label for="option_a"><i class="fas fa-check-square"></i> Option A:</label><br>
                <input type="text" id="option_a" name="option_a" required>
            </div>
            <div class="form-group">
                <label for="option_b"><i class="fas fa-check-square"></i> Option B:</label><br>
                <input type="text" id="option_b" name="option_b" required>
            </div>
            <div class="form-group">
                <label for="option_c"><i class="fas fa-check-square"></i> Option C:</label><br>
                <input type="text" id="option_c" name="option_c" required>
            </div>
            <div class="form-group">
                <label for="option_d"><i class="fas fa-check-square"></i> Option D:</label><br>
                <input type="text" id="option_d" name="option_d" required>
            </div>
            <div class="form-group">
                <label for="correct_option"><i class="fas fa-flag-checkered"></i> Correct Option:</label><br>
                <select name="correct_option" id="correct_option" required>
                    <option value="">-- Select Correct Option --</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit"><i class="fas fa-plus"></i> Add Question</button>
            </div>
        </form>
    </div>

    <div class="question-list-container">
        <h3><i class="fas fa-list"></i> Question List</h3>
        <?php if ($result->num_rows > 0): ?>
            <ol class="question-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li class="question-item">
                        <p class="question-text"><?= htmlspecialchars($row['question']) ?></p>
                        <div class="options">
                            A. <?= htmlspecialchars($row['option_a']) ?><br>
                            B. <?= htmlspecialchars($row['option_b']) ?><br>
                            C. <?= htmlspecialchars($row['option_c']) ?><br>
                            D. <?= htmlspecialchars($row['option_d']) ?>
                        </div>
                        <p class="correct-answer"><i class="fas fa-check"></i> Correct Answer: <?= $row['correct_option'] ?></p>
                        <div class="question-actions">
                            <a href="teacher_quiz_questions_edit.php?quiz_id=<?= $quiz_id ?>&question_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="teacher_quiz_questions.php?quiz_id=<?= $quiz_id ?>&delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this question?')"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ol>
        <?php else: ?>
            <p>No questions yet.</p>
        <?php endif; ?>
    </div>

    <div class="back-link">
        <a href="teacher_quizzes.php"><i class="fas fa-arrow-left"></i> Back to Quiz List</a>
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
