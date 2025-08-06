<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Get the list of courses the student is enrolled in
$stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.title
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();

// Get user fullname and role for the header (assuming these are in $_SESSION from login)
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grades & Results | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_view_assignments.css">
  
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="grades-header">
            <h2><i class="fas fa-check-circle"></i> Grades & Results</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

        <?php if ($courses_result->num_rows > 0): ?>
            <?php while ($course = $courses_result->fetch_assoc()): ?>
                <div class="course-section">
                    <h3><i class="fas fa-book-open"></i> <?= htmlspecialchars($course['title']) ?></h3>
                    <?php
                    $course_id = $course['id'];
                    $assign_stmt = $conn->prepare("
                        SELECT a.id, a.title, a.due_date, s.submitted_text, s.submitted_file, s.grade, s.feedback, s.submitted_at
                        FROM assignments a
                        LEFT JOIN assignment_submissions s
                            ON a.id = s.assignment_id AND s.user_id = ?
                        WHERE a.course_id = ?
                        ORDER BY a.due_date ASC
                    ");
                    $assign_stmt->bind_param("ii", $user_id, $course_id);
                    $assign_stmt->execute();
                    $assignments = $assign_stmt->get_result();
                    ?>
                    <?php if ($assignments->num_rows > 0): ?>
                        <ul class="assignment-list-container">
                        <?php while ($a = $assignments->fetch_assoc()): ?>
                            <li class="assignment-item">
                                <span class="assignment-title"><?= htmlspecialchars($a['title']) ?></span>
                                <span class="assignment-due-date">Due: <?= date('Y-m-d H:i', strtotime($a['due_date'])) ?></span>
                                
                                <?php if ($a['submitted_text'] || $a['submitted_file']): ?>
                                    <div class="submission-info">
                                        <strong>Your Submission:</strong>
                                        <?php if ($a['submitted_text']): ?>
                                            <p><?= nl2br(htmlspecialchars($a['submitted_text'])) ?></p>
                                        <?php endif; ?>
                                        <?php if ($a['submitted_file']): ?>
                                            <p><i class="fas fa-file"></i> <a href="<?= htmlspecialchars($a['submitted_file']) ?>" target="_blank">Download Submission</a></p>
                                        <?php endif; ?>
                                        <p>Submitted at: <?= $a['submitted_at'] ? date('Y-m-d H:i', strtotime($a['submitted_at'])) : 'N/A' ?></p>
                                    </div>
                                    <div class="grade-feedback">
                                        <strong>Grade:</strong>
                                        <span class="grade-value"><?= $a['grade'] !== null ? $a['grade'] : '<span class="no-grade">Not graded yet</span>' ?></span>
                                        <strong>Feedback:</strong>
                                        <span class="feedback-text"><?= $a['feedback'] ? nl2br(htmlspecialchars($a['feedback'])) : '<span class="no-feedback">No feedback yet</span>' ?></span>
                                    </div>
                                <?php else: ?>
                                    <p class="no-submission"><em>You have not submitted this assignment.</em></p>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-assignments">No assignments in this course.</p>
                    <?php endif; ?>
                    <?php $assign_stmt->close(); ?>

                    <div class="quiz-scores">
                        <h4><i class="fas fa-clipboard-list"></i> Quiz Scores</h4>
                        <?php
                        // Fetch quiz scores for the current course and user
                        $quiz_stmt = $conn->prepare("
                            SELECT q.title, qs.score
                            FROM quizzes q
                            LEFT JOIN quiz_submissions qs
                                ON q.id = qs.quiz_id AND qs.user_id = ?
                            WHERE q.course_id = ?
                            ORDER BY q.title ASC
                        ");
                        $quiz_stmt->bind_param("ii", $user_id, $course_id);
                        $quiz_stmt->execute();
                        $quizzes = $quiz_stmt->get_result();
                        ?>
                        <?php if ($quizzes->num_rows > 0): ?>
                            <ul>
                            <?php while ($q = $quizzes->fetch_assoc()): ?>
                                <li>
                                    <span class="quiz-title"><?= htmlspecialchars($q['title']) ?>:</span>
                                    <span class="quiz-score"><?= $q['score'] !== null ? htmlspecialchars($q['score']) : '<span class="no-quiz-score">N/A</span>' ?></span>
                                </li>
                            <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="no-quiz-score">No quizzes for this course yet.</p>
                        <?php endif; ?>
                        <?php $quiz_stmt->close(); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-courses">You are not enrolled in any courses yet.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>
  
</body>
</html>