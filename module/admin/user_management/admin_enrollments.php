<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";

// Lấy course_id từ URL
if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}
$course_id = intval($_GET['course_id']);

// Lấy tên khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "Course not found.";
    exit;
}
$stmt->bind_result($course_title);
$stmt->fetch();
$stmt->close();

// Xóa enrollment nếu bấm remove
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    // Sanitize $remove_id to prevent SQL injection, although intval helps,
    // a prepared statement is safer for deletes too.
    $delete_stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ? AND course_id = ?");
    $delete_stmt->bind_param("ii", $remove_id, $course_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: admin_enrollments.php?course_id=$course_id");
    exit;
}

// Lấy danh sách học viên đã enroll
$result = $conn->query("
  SELECT e.id AS enroll_id, u.fullname, u.email, e.enrolled_at
  FROM enrollments e
  JOIN users u ON e.user_id = u.id
  WHERE e.course_id = $course_id
  ORDER BY e.enrolled_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollments - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_enrollments.css">
   
</head>
<body>
    <?php include "includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-users"></i> Enrollments for: <?= htmlspecialchars($course_title) ?></h2>
        </div>

        <div class="course-management-overview">
            <h3><i class="fas fa-list-alt"></i> Enrolled Students</h3>
            <div class="course-management-content">
                <?php if ($result->num_rows > 0): ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Enrolled At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['enrolled_at']) ?></td>
                                    <td class="enrollment-actions">
                                        <a href="admin_enrollments.php?course_id=<?= $course_id ?>&remove_id=<?= $row['enroll_id'] ?>" onclick="return confirm('Are you sure you want to remove this enrollment?')"><i class="fas fa-trash-alt"></i> Remove</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-enrollments"><i class="fas fa-info-circle"></i> No students have enrolled in this course yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="admin_courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/sidebar.js"></script>
</body>
</html>