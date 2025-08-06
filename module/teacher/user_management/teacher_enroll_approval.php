<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$course_id = $_GET['course_id'] ?? 0;

// Kiểm tra quyền course
$check = $conn->prepare("SELECT id, title FROM courses WHERE id = ? AND teacher_id = ?");
$check->bind_param("ii", $course_id, $_SESSION['user_id']);
$check->execute();
$check->store_result();
if ($check->num_rows == 0) {
    echo "Bạn không có quyền duyệt enroll cho khóa học này.";
    exit;
}
$check->bind_result($cid, $course_title);
$check->fetch();
$check->close();

// Duyệt hoặc từ chối enroll
if (isset($_GET['approve'])) {
    $enroll_id = intval($_GET['approve']);
    $stmt = $conn->prepare("UPDATE enrollments SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $stmt->close();
    header("Location: teacher_enroll_approval.php?course_id=$course_id");
    exit;
}

if (isset($_GET['reject'])) {
    $enroll_id = intval($_GET['reject']);
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $stmt->close();
    header("Location: teacher_enroll_approval.php?course_id=$course_id");
    exit;
}

// Lấy danh sách học sinh enroll đang pending
$pending = $conn->prepare("
    SELECT e.id, u.fullname, u.email, e.enrolled_at
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    WHERE e.course_id = ? AND e.status = 'pending'
");
$pending->bind_param("i", $course_id);
$pending->execute();
$result = $pending->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Enrollments for <?= htmlspecialchars($course_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_enroll_approval.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-user-check"></i> Approve Enrollments</h2>
        </div>

        <div class="enrollment-approval-overview">
            <h3><i class="fas fa-clipboard-list"></i> Pending Enrollments for "<?= htmlspecialchars($course_title) ?>"</h3>
            <div class="enrollment-approval-content">
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table class="enrollments-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Enrolled At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Student"><?= htmlspecialchars($row['fullname']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td data-label="Enrolled At"><?= $row['enrolled_at'] ?></td>
                            <td data-label="Actions" class="enrollment-actions">
                                <a href="?course_id=<?= $course_id ?>&approve=<?= $row['id'] ?>" class="approve"><i class="fas fa-check"></i> Approve</a>
                                <a href="?course_id=<?= $course_id ?>&reject=<?= $row['id'] ?>" class="reject"><i class="fas fa-times"></i> Reject</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-pending-enrollments"><i class="fas fa-info-circle"></i> No pending enrollment requests for this course.</p>
        <?php endif; ?>

        <div class="back-to-courses">
            <a href="../courses/teacher_courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>