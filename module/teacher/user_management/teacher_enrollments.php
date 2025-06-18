<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
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

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You are not allowed to manage enrollments for this course.";
    exit;
}
$stmt->bind_result($course_title);
$stmt->fetch();
$stmt->close();

// Xóa enrollment nếu bấm remove
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    $conn->query("DELETE FROM enrollments WHERE id = $remove_id AND course_id = $course_id");
    header("Location: teacher_enrollments.php?course_id=$course_id");
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

// Initialize $message if not set by PHP logic, to avoid errors in HTML
$message = $message ?? '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollments - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/teacher/teacher_enrollments.css">
</head>
<body>

    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">

        <div class="admin-page-header">
            <h2><i class="fas fa-users"></i> Enrollments for: <?= htmlspecialchars($course_title) ?></h2>
            </div>

        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <div class="enrollments-overview">
            <h3><i class="fas fa-list-alt"></i> Enrolled Students Overview</h3>
            <div class="enrollments-content">
                </div>
        </div>

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
                            <td data-label="Full Name"><?= htmlspecialchars($row['fullname']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td data-label="Enrolled At"><?= htmlspecialchars($row['enrolled_at']) ?></td>
                            <td data-label="Action" class="enrollment-actions">
                                <a href="teacher_enrollments.php?course_id=<?= htmlspecialchars($course_id) ?>&remove_id=<?= htmlspecialchars($row['enroll_id']) ?>" onclick="return confirm('Are you sure you want to remove this enrollment?')"><i class="fas fa-trash-alt"></i> Remove</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php $result->free(); ?> <?php else: ?>
            <p class="no-enrollments"><i class="fas fa-info-circle"></i> No students have enrolled in this course yet.</p>
        <?php endif; ?>

        <div class="back-to-courses">
            <a href="teacher_courses.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            <a href="teacher_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
<script src="js/teacher_sidebar.js"></script>
</body>
</html>