<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

// Lấy danh sách tất cả assignments
$sql = "
    SELECT a.id, a.title, a.due_date, c.title AS course_title, c.id AS course_id
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    ORDER BY a.due_date DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
 <link rel="stylesheet" href="css/admin/admin_assignments.css">
   
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-tasks"></i> Manage Assignments</h2>
    </div>

    <div class="course-management-overview">
        <h3><i class="fas fa-edit"></i> Assignment Management Actions</h3>
        <div class="course-management-content">
            <a href="admin_assignment_edit.php?action=add" class="add-new-course-link">
                <i class="fas fa-plus-circle"></i> Add New Assignment
            </a>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="courses-table">
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
                        <td data-label="Actions" class="course-actions">
                            <a href="admin_assignment_edit.php?action=edit&id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <form action="admin_assignments.php" method="GET" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-courses"><i class="fas fa-exclamation-circle"></i> No assignments found.</p>
    <?php endif; ?>

    <div class="back-to-dashboard">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>
<script src="js/sidebar.js"></script>
</body>
</html>
<?php
// Xóa assignment
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt_delete = $conn->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    header("Location: admin_assignments.php");
    exit;
}
?>