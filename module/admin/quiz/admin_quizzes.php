<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Lấy danh sách quizzes + tên khóa học
$keyword = $_GET['keyword'] ?? "";

if (!empty($keyword)) {
    $stmt = $conn->prepare("
        SELECT q.id, q.title AS quiz_title, c.title AS course_title
        FROM quizzes q
        LEFT JOIN courses c ON q.course_id = c.id
        WHERE q.title LIKE ? OR c.title LIKE ?
        ORDER BY q.id DESC
    ");
    $like = "%" . $keyword . "%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("
        SELECT q.id, q.title AS quiz_title, c.title AS course_title
        FROM quizzes q
        LEFT JOIN courses c ON q.course_id = c.id
        ORDER BY q.id DESC
    ");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Quizzes (Admin) | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../../css/style.css"> 
<link rel="stylesheet" href="../../../css/admin/admin_quizzes.css"> 
    
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-question-circle"></i> Manage Quizzes</h2>
    </div>

    <div class="course-management-overview">
        <h3><i class="fas fa-tasks"></i> Quiz Management Actions</h3>
        <div class="course-management-content">
            <form method="get" class="search-form">
                <input type="text" name="keyword" placeholder="Search by quiz title or course..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($keyword)): ?>
                    <a href="admin_quizzes.php" class="reset-link"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
            <a href="admin_edit_quiz.php" class="add-new-course-link">
                <i class="fas fa-plus-circle"></i> Add New Quiz
            </a>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="courses-table"> <thead>
                <tr>
                    <th>ID</th>
                    <th>Quiz Title</th>
                    <th>Course</th>
                    <th>Actions</th>
                    <th>Questions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= $row['id'] ?></td>
                        <td data-label="Quiz Title"><?= htmlspecialchars($row['quiz_title']) ?></td>
                        <td data-label="Course"><?= htmlspecialchars($row['course_title']) ?></td>
                        <td data-label="Actions" class="course-actions"> <a href="admin_edit_quiz.php?action=edit&quiz_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                             <form action="admin_delete_quiz.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
                                <input type="hidden" name="quiz_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                        <td data-label="Questions" class="course-actions">
    <a href="admin_quiz_questions.php?quiz_id=<?= $row['id'] ?>"><i class="fas fa-list"></i> Manage Questions</a>
</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-courses"><i class="fas fa-exclamation-circle"></i> No quizzes found.</p> <?php endif; ?>

    <div class="back-to-dashboard">
        <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>

</div>
<script src="../../../js/sidebar.js"></script>
</body>
</html>