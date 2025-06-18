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

$message = ''; // For success/error messages

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

    if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || !in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Please fill all fields correctly.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $quiz_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_option);
        if ($stmt->execute()) {
            $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Question added successfully!</div>';
        } else {
            $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Error adding question: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}

// Xử lý xóa câu hỏi
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Ensure the question belongs to the current quiz and teacher
    $check_stmt = $conn->prepare("
        SELECT qq.id FROM quiz_questions qq
        JOIN quizzes q ON qq.quiz_id = q.id
        JOIN courses c ON q.course_id = c.id
        WHERE qq.id = ? AND qq.quiz_id = ? AND c.teacher_id = ?
    ");
    $check_stmt->bind_param("iii", $delete_id, $quiz_id, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $conn->query("DELETE FROM quiz_questions WHERE id = $delete_id");
        $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Question deleted successfully!</div>';
    } else {
        $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Question not found or you do not have permission to delete it.</div>';
    }
    $check_stmt->close();
    // Redirect to clear GET parameters
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions for Quiz: <?= htmlspecialchars($quiz_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_quiz_questions.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-list-ul"></i> Manage Questions for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
        </div>

        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <div class="form-overview">
            <h3><i class="fas fa-info-circle"></i> Quiz Information</h3>
            <div class="form-content">
                <p><strong>Course:</strong> <?= htmlspecialchars($course_title) ?></p>
            </div>
        </div>

        <div class="form-overview">
            <h3><i class="fas fa-plus-circle"></i> Add New Question</h3>
            <div class="form-content">
                <form method="post" class="edit-quiz-form-style">
                    <label for="question"><i class="fas fa-question"></i> Enter Question Text:</label>
                    <textarea name="question" id="question" rows="3" required></textarea>

                    <label for="option_a"><i class="fas fa-check-square"></i> Option A Text:</label>
                    <input type="text" id="option_a" name="option_a" required>

                    <label for="option_b"><i class="fas fa-check-square"></i> Option B Text:</label>
                    <input type="text" id="option_b" name="option_b" required>

                    <label for="option_c"><i class="fas fa-check-square"></i> Option C Text:</label>
                    <input type="text" id="option_c" name="option_c" required>

                    <label for="option_d"><i class="fas fa-check-square"></i> Option D Text:</label>
                    <input type="text" id="option_d" name="option_d" required>

                    <label for="correct_option"><i class="fas fa-flag-checkered"></i> Select Correct Option (A, B, C, or D):</label>
                    <select name="correct_option" id="correct_option" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>

                    <button type="submit"><i class="fas fa-plus"></i> Add Question</button>
                </form>
            </div>
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
                                <a href="teacher_quiz_questions_edit.php?quiz_id=<?= $quiz_id ?>&question_id=<?= $row['id'] ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                <a href="teacher_quiz_questions.php?quiz_id=<?= $quiz_id ?>&delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this question?')" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ol>
            <?php else: ?>
                <p class="warning-message" style="margin-top: 20px;"><i class="fas fa-exclamation-circle"></i> No questions have been added to this quiz yet.</p>
            <?php endif; ?>
        </div>

        <div class="back-buttons">
            <a href="teacher_quizzes.php" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Quiz List</a>
            <a href="teacher_dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>

</body>
</html>