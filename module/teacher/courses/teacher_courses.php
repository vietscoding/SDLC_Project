<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Lấy danh sách khóa học giáo viên phụ trách
$stmt = $conn->prepare("SELECT id, title, description FROM courses WHERE teacher_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_courses.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-graduation-cap"></i> My Courses</h2>
        </div>

        <div class="my-courses-overview">
            <h3><i class="fas fa-tasks"></i> Course Management Overview</h3>
            <div class="my-courses-content">
                </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table class="courses-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="ID"><?= $row['id'] ?></td>
                            <td data-label="Title"><?= htmlspecialchars($row['title']) ?></td>
                            <td data-label="Description"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                            <td data-label="Actions" class="course-actions">
                                <a href="teacher_lessons.php?course_id=<?= $row['id'] ?>"><i class="fas fa-list-ul"></i> Lessons</a>
                                <a href="../user_management/teacher_enrollments.php ?= $row['id'] ?>"><i class="fas fa-users"></i> Enrollments</a>
                                <a href="../user_management/ teacher_progress.php?course_id=<?= $row['id'] ?>"><i class="fas fa-chart-line"></i> Progress</a>
                                <a href="../user_management/ teacher_enroll_approval.php?course_id=<?= $row['id'] ?>"><i class="fas fa-user-check"></i> Approve</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-courses"><i class="fas fa-exclamation-triangle"></i> You are not assigned to any courses yet.</p>
        <?php endif; ?>

        <div class="back-to-dashboard">
            <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>