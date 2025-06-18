<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";
$result = $conn->query("
  SELECT qs.id AS submission_id, u.fullname, c.title AS course_title, q.title AS quiz_title, qs.score, qs.submitted_at
  FROM quiz_submissions qs
  JOIN users u ON qs.user_id = u.id
  JOIN quizzes q ON qs.quiz_id = q.id
  JOIN courses c ON q.course_id = c.id
  WHERE c.teacher_id = {$_SESSION['user_id']}
  ORDER BY qs.submitted_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Quiz Results | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/teacher/teacher_quiz_results.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-chart-bar"></i> Student Quiz Results</h2>
        </div>

        <div class="quiz-results-overview">
            <h3><i class="fas fa-clipboard-list"></i> Latest Quiz Submissions</h3>
            <div class="quiz-results-content">
                <?php if ($result->num_rows > 0): ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Student"><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td data-label="Course"><?= htmlspecialchars($row['course_title']) ?></td>
                                    <td data-label="Quiz"><?= htmlspecialchars($row['quiz_title']) ?></td>
                                    <td data-label="Score"><?= $row['score'] ?></td>
                                    <td data-label="Submitted At"><?= $row['submitted_at'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-results"><i class="fas fa-exclamation-circle"></i> No quiz submissions found.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-to-dashboard">
            <a href="teacher_quizzes.php"><i class="fas fa-arrow-left"></i> Back to Quiz List</a>
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
 
</body>
</html>