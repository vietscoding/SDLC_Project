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

$error = ""; // Initialize error variable

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
    <title>Edit Quiz Question - <?= htmlspecialchars($quiz_title) ?> | BTEC FPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_quiz_questions_edit.css">
   
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-edit"></i> Edit Question for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
    </div>

    <div class="quiz-management-overview">
        <h3><i class="fas fa-list-alt"></i> Question Management</h3>
        <div class="quiz-management-content">
            <p class="quiz-info">
                <i class="fas fa-book"></i> Course: <strong><?= htmlspecialchars($course_title) ?></strong>
            </p>
            <?php if (!empty($error)): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="post" autocomplete="off">
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

    <?php include "includes/footer.php"; ?>
</div>

<script src="js/sidebar.js"></script>
</body>
</html>