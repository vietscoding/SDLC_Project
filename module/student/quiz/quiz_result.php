<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['submission_id'])) {
    echo "Submission ID missing.";
    exit;
}

$submission_id = intval($_GET['submission_id']);
$user_id = $_SESSION['user_id']; // Added to get quiz_id later for navigation

// Lấy tổng điểm và quiz_id
$score_stmt = $conn->prepare("SELECT qs.score, qs.quiz_id, q.title as quiz_title FROM quiz_submissions qs JOIN quizzes q ON qs.quiz_id = q.id WHERE qs.id = ? AND qs.user_id = ?");
$score_stmt->bind_param("ii", $submission_id, $user_id);
$score_stmt->execute();
$score_stmt->bind_result($score, $quiz_id, $quiz_title);
$score_stmt->fetch();
$score_stmt->close();

if (!$quiz_id) {
    echo "Submission not found or you don't have permission to view it.";
    exit;
}

// Lấy thông tin kết quả
$stmt = $conn->prepare("
SELECT q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, a.selected_option
FROM quiz_answers a
JOIN quiz_questions q ON a.question_id = q.id
WHERE a.submission_id = ?
ORDER BY q.id ASC
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Result Details: <?= htmlspecialchars($quiz_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/quiz_result.css">
     
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-poll"></i> Quiz Result Details: <?= htmlspecialchars($quiz_title) ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= htmlspecialchars($_SESSION['fullname']); ?> (<?= htmlspecialchars($_SESSION['role']); ?>)
            </div>
        </div>

        <div class="quiz-results-container">
            <div class="score-summary">
                Your total score: <span><?= $score ?></span>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <ol>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <li>
                            <strong><?= htmlspecialchars($row['question']) ?></strong><br>
                            <span class="result-option <?= ($row['selected_option'] === 'A' ? 'selected' : '') ?> <?= ($row['correct_option'] === 'A' ? 'correct' : '') ?>">A. <?= htmlspecialchars($row['option_a']) ?></span>
                            <span class="result-option <?= ($row['selected_option'] === 'B' ? 'selected' : '') ?> <?= ($row['correct_option'] === 'B' ? 'correct' : '') ?>">B. <?= htmlspecialchars($row['option_b']) ?></span>
                            <span class="result-option <?= ($row['selected_option'] === 'C' ? 'selected' : '') ?> <?= ($row['correct_option'] === 'C' ? 'correct' : '') ?>">C. <?= htmlspecialchars($row['option_c']) ?></span>
                            <span class="result-option <?= ($row['selected_option'] === 'D' ? 'selected' : '') ?> <?= ($row['correct_option'] === 'D' ? 'correct' : '') ?>">D. <?= htmlspecialchars($row['option_d']) ?></span>

                            <br>
                            <strong>Your Answer:</strong> <span class="<?= ($row['selected_option'] === $row['correct_option']) ? 'correct-answer' : 'incorrect-answer' ?>"><?= htmlspecialchars($row['selected_option']) ?></span><br>
                            <strong>Correct Answer:</strong> <span class="correct-answer"><?= htmlspecialchars($row['correct_option']) ?></span>
                            <?php if ($row['selected_option'] === $row['correct_option']): ?>
                                <span class="correct-answer">✔ Correct</span>
                            <?php else: ?>
                                <span class="incorrect-answer">✘ Incorrect</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ol>
            <?php else: ?>
                <p>No result details found for this submission.</p>
            <?php endif; ?>
        </div>

        <div class="navigation-links">
            <?php if (isset($quiz_id)): ?>
                <a href="courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            <?php endif; ?>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/student_sidebar.js"></script>
</body>
</html>