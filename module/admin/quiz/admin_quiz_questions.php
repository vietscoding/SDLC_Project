<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

if (!isset($_GET['quiz_id'])) {
    echo "Quiz ID missing.";
    exit;
}
$quiz_id = intval($_GET['quiz_id']);

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

$error = ""; // Initialize error variable

// Thêm câu hỏi mới
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
        header("Location: admin_quiz_questions.php?quiz_id=$quiz_id");
        exit;
    } else {
        $error = "Please fill all fields correctly.";
    }
}

// Xóa câu hỏi
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Prepare and bind for deletion
    $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
    $stmt->bind_param("ii", $delete_id, $quiz_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_quiz_questions.php?quiz_id=$quiz_id");
    exit;
}

// Lấy danh sách câu hỏi
$result = $conn->query("SELECT id, question, option_a, option_b, option_c, option_d, correct_option FROM quiz_questions WHERE quiz_id = $quiz_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions - <?= htmlspecialchars($quiz_title) ?> | BTEC FPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_quiz_questions.css">
   
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-question-circle"></i> Manage Questions for Quiz: <?= htmlspecialchars($quiz_title) ?></h2>
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

            <div class="add-question-section">
                <h3><i class="fas fa-plus-circle"></i> Add New Question</h3>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <label for="question"><i class="fas fa-question"></i> Question:</label>
                        <textarea name="question" id="question" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="option_a"><i class="fas fa-check-square"></i> Option A:</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>
                    <div class="form-group">
                        <label for="option_b"><i class="fas fa-check-square"></i> Option B:</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>
                    <div class="form-group">
                        <label for="option_c"><i class="fas fa-check-square"></i> Option C:</label>
                        <input type="text" id="option_c" name="option_c" required>
                    </div>
                    <div class="form-group">
                        <label for="option_d"><i class="fas fa-check-square"></i> Option D:</label>
                        <input type="text" id="option_d" name="option_d" required>
                    </div>
                    <div class="form-group">
                        <label for="correct_option"><i class="fas fa-flag-checkered"></i> Correct Option:</label>
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

            <div class="question-list-section">
                <h3><i class="fas fa-list"></i> Question List</h3>
                <?php if ($result->num_rows > 0): ?>
                    <ol class="question-list">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <li class="question-item">
                                <p class="question-text"><?= htmlspecialchars($row['question']) ?></p>
                                <div class="options">
                                    <span>A. <?= htmlspecialchars($row['option_a']) ?></span>
                                    <span>B. <?= htmlspecialchars($row['option_b']) ?></span>
                                    <span>C. <?= htmlspecialchars($row['option_c']) ?></span>
                                    <span>D. <?= htmlspecialchars($row['option_d']) ?></span>
                                </div>
                                <p class="correct-answer"><i class="fas fa-check"></i> Correct Answer: <?= $row['correct_option'] ?></p>
                                <div class="question-actions">
                                    <a href="admin_quiz_questions_edit.php?quiz_id=<?= $quiz_id ?>&question_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="admin_quiz_questions.php?quiz_id=<?= $quiz_id ?>&delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this question?')"><i class="fas fa-trash-alt"></i> Delete</a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ol>
                <?php else: ?>
                    <p class="error-message"><i class="fas fa-exclamation-circle"></i> No questions yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="back-link">
        <a href="admin_quizzes.php"><i class="fas fa-arrow-left"></i> Back to Quiz List</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>

<script src="js/sidebar.js"></script>
</body>
</html>