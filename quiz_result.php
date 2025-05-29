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

// Lấy tổng điểm
$score_stmt = $conn->prepare("SELECT score FROM quiz_submissions WHERE id = ?");
$score_stmt->bind_param("i", $submission_id);
$score_stmt->execute();
$score_stmt->bind_result($score);
$score_stmt->fetch();
$score_stmt->close();

// Lấy thông tin kết quả
$stmt = $conn->prepare("
SELECT q.question, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, a.selected_option
FROM quiz_answers a
JOIN quiz_questions q ON a.question_id = q.id
WHERE a.submission_id = ?
");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quiz Result Details</title>
</head>
<body>
  <h2>Quiz Result Details</h2>
<h3>Your total score: <?= $score ?></h3>
  <?php if ($result->num_rows > 0): ?>
    <ol>
      <?php while($row = $result->fetch_assoc()): ?>
        <li>
          <strong><?= htmlspecialchars($row['question']) ?></strong><br>
          A. <?= htmlspecialchars($row['option_a']) ?><br>
          B. <?= htmlspecialchars($row['option_b']) ?><br>
          C. <?= htmlspecialchars($row['option_c']) ?><br>
          D. <?= htmlspecialchars($row['option_d']) ?><br>
          <strong>Your Answer:</strong> <?= $row['selected_option'] ?><br>
          <strong>Correct Answer:</strong> <?= $row['correct_option'] ?><br>
          <?php if ($row['selected_option'] === $row['correct_option']): ?>
            <span style="color: green;">✔ Correct</span>
          <?php else: ?>
            <span style="color: red;">✘ Incorrect</span>
          <?php endif; ?>
        </li>
        <br>
      <?php endwhile; ?>
    </ol>
  <?php else: ?>
    <p>No result found for this submission.</p>
  <?php endif; ?>

  <br>
  <a href="student_dashboard.php">Back to Dashboard</a> |
  <a href="logout.php">Log out</a>
</body>
</html>
