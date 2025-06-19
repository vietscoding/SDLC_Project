<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

// Lấy tất cả quiz thuộc các khóa học giáo viên quản lý
$stmt = $conn->prepare("
    SELECT q.id, q.title, c.title AS course_title, c.id AS course_id
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY c.title, q.title
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Handle quiz deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        header("Location: teacher_quizzes.php?success=Quiz deleted successfully!");
        exit();
    } else {
        header("Location: teacher_quizzes.php?error=Failed to delete quiz.");
        exit();
    }
    $delete_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Quizzes | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/teacher/teacher_quizzes.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-question-circle"></i> Manage Quizzes</h2>
            <a href="teacher_quiz_edit.php?action=add" class="add-new-button"><i class="fas fa-plus-circle"></i> Add New Quiz</a>
        </div>

        <div class="my-quizzes-overview">
            <h3><i class="fas fa-clipboard-list"></i> Quiz Management Overview</h3>
            <div class="my-quizzes-content">
                </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table class="quizzes-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Quiz Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Course"><?= htmlspecialchars($row['course_title']) ?></td>
                            <td data-label="Quiz Title"><?= htmlspecialchars($row['title']) ?></td>
                            <td data-label="Actions" class="quiz-actions">
                                <a href="teacher_quiz_results.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> View Quiz's Results</a>
                                <a href="teacher_quiz_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="teacher_quiz_questions.php?quiz_id=<?= $row['id'] ?>"><i class="fas fa-list-ul"></i> Manage Questions</a>
                                <a href="teacher_quizzes.php?delete_id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this quiz?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-quizzes"><i class="fas fa-exclamation-circle"></i> No quizzes found.</p>
        <?php endif; ?>

        <div class="back-to-dashboard">
            <a href="../dashboard/teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
<script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>