<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['id'])) {
    echo "Quiz ID missing.";
    exit;
}

$quiz_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Lấy thông tin quiz để hiển thị tiêu đề
$quiz_title_stmt = $conn->prepare("SELECT title, course_id FROM quizzes WHERE id = ?");
$quiz_title_stmt->bind_param("i", $quiz_id);
$quiz_title_stmt->execute();
$quiz_title_stmt->bind_result($quiz_title, $course_id);
$quiz_title_stmt->fetch();
$quiz_title_stmt->close();

// Lấy câu hỏi quiz
$stmt = $conn->prepare("SELECT id, question, option_a, option_b, option_c, option_d FROM quiz_questions WHERE quiz_id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "No questions found for this quiz.";
    exit;
}

// Xử lý nộp bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    $total = 0;

    // Lấy tất cả câu hỏi để kiểm tra đáp án
    $stmt2 = $conn->prepare("SELECT id, correct_option FROM quiz_questions WHERE quiz_id = ?");
    $stmt2->bind_param("i", $quiz_id);
    $stmt2->execute();
    $answers = $stmt2->get_result();

    while ($row = $answers->fetch_assoc()) {
        $total++;
        $qid = $row['id'];
        $correct = $row['correct_option'];
        $user_answer = isset($_POST['answer'][$qid]) ? $_POST['answer'][$qid] : '';

        if ($user_answer === $correct) {
            $score++;
        }
    }
    $stmt2->close();

    // Lưu điểm vào quiz_submissions
    $stmt3 = $conn->prepare("INSERT INTO quiz_submissions (user_id, quiz_id, score) VALUES (?, ?, ?)");
    $stmt3->bind_param("iii", $user_id, $quiz_id, $score);
    $stmt3->execute();
    // Lấy ID vừa insert
    $submission_id = $stmt3->insert_id;
    $stmt3->close();


    // Lưu từng câu trả lời
    $stmt4 = $conn->prepare("INSERT INTO quiz_answers (submission_id, question_id, selected_option) VALUES (?, ?, ?)");
    foreach ($_POST['answer'] as $qid => $selected) {
        $stmt4->bind_param("iis", $submission_id, $qid, $selected);
        $stmt4->execute();
    }
    $stmt4->close();
    // Sau khi lưu kết quả quiz thành công
    $message = "You scored $score points on quiz '$quiz_title'!";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $stmt->close();
    // Tìm giáo viên của khóa học
    $result_teacher = $conn->query("
        SELECT c.teacher_id, c.title AS course_title
        FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = $quiz_id
    ");
    $info = $result_teacher->fetch_assoc();
    $teacher_id = $info['teacher_id'];
    $course_title = $info['course_title'];
    $result_teacher->close();

    // Gửi notification cho giáo viên
    $notif_msg = $_SESSION['fullname'] . " has submitted a quiz in your course: " . $course_title;
    $stmt_notify_teacher = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt_notify_teacher->bind_param("is", $teacher_id, $notif_msg);
    $stmt_notify_teacher->execute();
    $stmt_notify_teacher->close();

    // Chuyển tới trang kết quả chi tiết
    header("Location: quiz_result.php?submission_id=$submission_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Quiz: <?= htmlspecialchars($quiz_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #e0e7ff 100%);
            color: #222;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2563eb 60%, #fbbf24 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(37,99,235,0.10);
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
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
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
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #1e40af;
            border: 2px solid #fbbf24;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #fde68a;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2563eb;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
            width: 100%;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 2px 8px rgba(251,191,36,0.08);
            padding: 20px 30px;
        }
        .quiz-header h2 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .quiz-header .user-info {
            font-size: 1.1em;
            color: #1e293b;
            font-weight: 700;
            text-shadow: 0 1px 4px #fff, 0 1px 8px #fbbf24;
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fde68a;
            color: #2563eb;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #2563eb;
            color: #fde68a;
        }
        .quiz-form {
            margin-top: 30px;
        }
        fieldset {
            margin-bottom: 25px;
            padding: 20px;
            border: 2px solid #fde68a;
            border-radius: 12px;
            background-color: #f9f9f9;
            transition: background 0.2s;
        }
        legend {
            font-size: 1.15em;
            font-weight: bold;
            color: #2563eb;
            padding: 0 10px;
            margin-bottom: 15px;
            border-bottom: 2px solid #2563eb;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1em;
            color: #555;
        }
        input[type="radio"] {
            margin-right: 10px;
            vertical-align: middle;
        }
        button[type="submit"] {
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            color: #fff;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
        }
        .navigation-links {
            margin-top: 30px;
            font-size: 0.95em;
            display: flex;
            gap: 18px;
        }
        .navigation-links a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.18s;
            font-size: 1em;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            margin-top: 12px;
            align-self: flex-end;
            margin-right: 10px;
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
            transform: translateY(-3px) scale(1.06);
            text-decoration: none;
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #2563eb;
            background-color: #fde68a;
            border-top: 1px solid #fbbf24;
            border-radius: 0 0 12px 12px;
        }
        footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
            color: #fbbf24;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }
        /* Dark Mode */
        .dark-mode {
            background-color: #1e293b;
            color: #fde68a;
        }
        .dark-mode .sidebar {
            background: linear-gradient(135deg, #1e293b 60%, #fbbf24 100%);
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1e293b;
        }
        .dark-mode .main-content {
            background-color: #1e293b;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .quiz-header h2,
        .dark-mode legend {
            color: #fbbf24;
            border-bottom-color: #fbbf24;
        }
        .dark-mode .quiz-header {
            background: linear-gradient(90deg, #2563eb 0%, #1e293b 100%);
        }
        .dark-mode fieldset {
            background-color: #29304d;
            border-color: #fbbf24;
        }
        .dark-mode label {
            color: #fde68a;
        }
        .dark-mode button[type="submit"] {
            background: #1e293b;
            color: #fbbf24;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode button[type="submit"]:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .navigation-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .navigation-links a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode footer {
            background-color: #1e293b;
            color: #fde68a;
            border-top-color: #fbbf24;
        }
        .dark-mode footer a {
            color: #fbbf24;
        }
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .quiz-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
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
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { display: block; font-size: 0.8em; }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .quiz-header { flex-direction: column; align-items: flex-start; }
            .quiz-header h2 { margin-bottom: 10px; font-size: 1.8em; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="quiz-header">
                <h2><?= htmlspecialchars($quiz_title) ?></h2>
                <div class="user-info">
                    <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($_SESSION['fullname']); ?> (<?= htmlspecialchars($_SESSION['role']); ?>)
                </div>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <form method="post" class="quiz-form">
                <?php while($row = $result->fetch_assoc()): ?>
                    <fieldset>
                        <legend><?= htmlspecialchars($row['question']) ?></legend>
                        <label><input type="radio" name="answer[<?= $row['id'] ?>]" value="A" required> <?= htmlspecialchars($row['option_a']) ?></label>
                        <label><input type="radio" name="answer[<?= $row['id'] ?>]" value="B" required> <?= htmlspecialchars($row['option_b']) ?></label>
                        <label><input type="radio" name="answer[<?= $row['id'] ?>]" value="C" required> <?= htmlspecialchars($row['option_c']) ?></label>
                        <label><input type="radio" name="answer[<?= $row['id'] ?>]" value="D" required> <?= htmlspecialchars($row['option_d']) ?></label>
                    </fieldset>
                <?php endwhile; ?>
                <button type="submit"><i class="fas fa-paper-plane"></i> Submit Quiz</button>
            </form>
            <div class="navigation-links">
                <a href="quiz_list.php?course_id=<?= $course_id ?>"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
                <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
            <hr style="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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