<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

if (!isset($_GET['quiz_id']) || !isset($_GET['question_id'])) {
    echo "Missing quiz or question ID.";
    exit;
}

$quiz_id = intval($_GET['quiz_id']);
$question_id = intval($_GET['question_id']);
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
    echo "You do not have permission to edit this question.";
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

    if (empty($new_question) || empty($new_option_a) || empty($new_option_b) || empty($new_option_c) || empty($new_option_d) || !in_array($new_correct_option, ['A','B','C','D'])) {
        $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Please fill all fields correctly.</div>';
    } else {
        $stmt = $conn->prepare("
            UPDATE quiz_questions
            SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?
            WHERE id = ? AND quiz_id = ?
        ");
        $stmt->bind_param("ssssssii", $new_question, $new_option_a, $new_option_b, $new_option_c, $new_option_d, $new_correct_option, $question_id, $quiz_id);
        if ($stmt->execute()) {
            $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Question updated successfully!</div>';
            // Update current question data to reflect changes immediately
            $question = $new_question;
            $option_a = $new_option_a;
            $option_b = $new_option_b;
            $option_c = $new_option_c;
            $option_d = $new_option_d;
            $correct_option = $new_correct_option;
        } else {
            $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Error updating question: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz Question | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_quiz_questions_edit.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-edit"></i> Edit Question for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
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
            <h3><i class="fas fa-question"></i> Edit Question Details</h3>
            <div class="form-content">
                <form method="post" class="edit-quiz-form-style">
                    <label for="question"><i class="fas fa-question"></i> Question Text:</label>
                    <textarea name="question" id="question" rows="3" required><?= htmlspecialchars($question) ?></textarea>

                    <label for="option_a"><i class="fas fa-check-square"></i> Option A Text:</label>
                    <input type="text" id="option_a" name="option_a" value="<?= htmlspecialchars($option_a) ?>" required>

                    <label for="option_b"><i class="fas fa-check-square"></i> Option B Text:</label>
                    <input type="text" id="option_b" name="option_b" value="<?= htmlspecialchars($option_b) ?>" required>

                    <label for="option_c"><i class="fas fa-check-square"></i> Option C Text:</label>
                    <input type="text" id="option_c" name="option_c" value="<?= htmlspecialchars($option_c) ?>" required>

                    <label for="option_d"><i class="fas fa-check-square"></i> Option D Text:</label>
                    <input type="text" id="option_d" name="option_d" value="<?= htmlspecialchars($option_d) ?>" required>

                    <label for="correct_option"><i class="fas fa-flag-checkered"></i> Select Correct Option (A, B, C, or D):</label>
                    <select name="correct_option" id="correct_option" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="A" <?= ($correct_option === 'A') ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= ($correct_option === 'B') ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= ($correct_option === 'C') ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= ($correct_option === 'D') ? 'selected' : '' ?>>D</option>
                    </select>

                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <div class="back-buttons">
            <a href="teacher_quiz_questions.php?quiz_id=<?= $quiz_id ?>" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Questions List</a>
            <a href="teacher_quizzes.php"><i class="fas fa-list-alt"></i> Back to Quiz List</a>
            <a href="../dashboard/teacher_dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>