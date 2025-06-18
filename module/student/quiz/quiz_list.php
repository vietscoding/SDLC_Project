<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";
if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}
$course_id = intval($_GET['course_id']);
// Lấy course title
$course_stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_stmt->bind_result($course_title);
$course_stmt->fetch();
$course_stmt->close();
// Lấy danh sách quizzes kèm deadline
$stmt = $conn->prepare("SELECT id, title, deadline FROM quizzes WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
$user_id = $_SESSION['user_id']; // Lấy user_id để kiểm tra đã làm quiz chưa
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quizzes for <?= $course_title ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/quiz_list.css">
    
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-question-circle"></i> Quizzes for Course: <?= htmlspecialchars($course_title) ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>
       

        <?php if ($result->num_rows > 0): ?>
            <ul class="quiz-list-container">
                <?php while ($quiz = $result->fetch_assoc()): ?>
                    <?php
                        // Kiểm tra đã làm quiz chưa
                        $quiz_id = $quiz['id'];
                        $done_stmt = $conn->prepare("SELECT COUNT(*) FROM quiz_submissions WHERE quiz_id = ? AND user_id = ?");
                        $done_stmt->bind_param("ii", $quiz_id, $user_id);
                        $done_stmt->execute();
                        $done_stmt->bind_result($has_done);
                        $done_stmt->fetch();
                        $done_stmt->close();

                        // Kiểm tra deadline
                        $now = date('Y-m-d H:i:s');
                        $deadline = $quiz['deadline'];
                        $is_expired = ($deadline && $now > $deadline);
                    ?>
                    <li class="quiz-item">
                        <div>
                            <div class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></div>
                            <span>
                                <i class="fas fa-clock"></i>
                                Deadline:
                                <?= $quiz['deadline'] ? date('H:i d/m/Y', strtotime($quiz['deadline'])) : 'No deadline' ?>
                            </span>
                        </div>
                        <?php if ($is_expired): ?>
                            <span class="status-message expired">
                                <i class="fas fa-times-circle"></i> Deadline passed
                            </span>
                        <?php elseif ($has_done): ?>
                            <span class="status-message completed">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                        <?php else: ?>
                            <a href="quiz.php?id=<?= $quiz['id'] ?>&course_id=<?= $course_id ?>"><i class="fas fa-pen"></i> Take Quiz</a>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-quizzes-message">No quizzes available for this course.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="course_detail.php?course_id=<?= $course_id ?>"><i class="fas fa-arrow-left"></i> Back to Course</a>
            <a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>


    <script src="js/student_sidebar.js"></script>
</body>
</html>