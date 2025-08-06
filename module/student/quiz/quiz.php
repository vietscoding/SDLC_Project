<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

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
    $course_title_for_teacher_notif = $info['course_title'];
    $result_teacher->close();

    // Gửi notification cho giáo viên
    $notif_msg = $_SESSION['fullname'] . " has submitted a quiz in your course: " . $course_title_for_teacher_notif;
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/quiz.css">
   
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-question-circle"></i> Take Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($_SESSION['fullname']); ?> (<?= htmlspecialchars($_SESSION['role']); ?>)
            </div>
        </div>
       
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
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>
        
        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/student_sidebar.js"></script>
 
</body>
</html>