<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Lấy danh sách assignments của các khóa học giáo viên phụ trách
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.due_date, c.title AS course_title, c.id AS course_id
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY a.due_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Xóa assignment
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Kiểm tra quyền sở hữu assignment
    $stmt_check = $conn->prepare("
        SELECT a.id FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt_check->bind_param("ii", $delete_id, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_delete = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    $stmt_check->close();
    header("Location: teacher_assignments.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_assignments.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-tasks"></i> Manage Assignments</h2>
        </div>

        <div class="assignments-overview">
            <h3><i class="fas fa-clipboard-list"></i> Assignment Management Overview</h3>
            <div class="assignments-content">
                <a href="teacher_assignment_edit.php?action=add" class="add-assignment-link"><i class="fas fa-plus-circle"></i> Add New Assignment</a>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table class="assignments-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Title</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Course"><?= htmlspecialchars($row['course_title']) ?></td>
                            <td data-label="Title"><?= htmlspecialchars($row['title']) ?></td>
                            <td data-label="Due Date"><?= $row['due_date'] ?></td>
                            <td data-label="Actions" class="assignment-actions">
                                <a href="teacher_assignment_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="teacher_assignment_submissions.php?assignment_id=<?= $row['id'] ?>"><i class="fas fa-list-alt"></i> View Submissions</a>
                                <a href="teacher_assignments.php?delete_id=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this assignment?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-assignments"><i class="fas fa-exclamation-triangle"></i> No assignments found.</p>
        <?php endif; ?>

        <div class="back-to-dashboard">
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
    <script src="js/teacher_sidebar.js"></script>
    <script>
        // Toggle dark/light mode - This should ideally be handled by a global script or teacher_sidebar.js
        // For now, including it here for self-containment if not already in sidebar.js
        const toggleModeBtn = document.getElementById('toggleModeBtn'); // Assuming this button is still present in the sidebar
        if (toggleModeBtn) {
            toggleModeBtn.onclick = function() {
                document.body.classList.toggle('dark-mode');
                toggleModeBtn.innerHTML = document.body.classList.contains('dark-mode')
                    ? '<i class="fas fa-sun"></i>'
                    : '<i class="fas fa-moon"></i>';
            };
        }
    </script>
</body>
</html>