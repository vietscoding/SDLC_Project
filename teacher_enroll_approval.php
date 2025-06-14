<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

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
    <title>Approve Enrollments | <?= htmlspecialchars($course_title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f1c40f; color: #2c3e50; }
        a.button {
            padding: 6px 14px;
            border-radius: 5px;
            text-decoration: none;
            margin-right: 8px;
        }
        .approve { background: #27ae60; color: #fff; }
        .reject { background: #e74c3c; color: #fff; }
        .back { background: #3498db; color: #fff; padding: 8px 18px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

<h2>Pending Enrollments for "<?= htmlspecialchars($course_title) ?>"</h2>

<?php if ($result->num_rows > 0): ?>
    <table>
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
                <td><?= htmlspecialchars($row['fullname']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= $row['enrolled_at'] ?></td>
                <td>
                    <a href="?course_id=<?= $course_id ?>&approve=<?= $row['id'] ?>" class="button approve"><i class="fas fa-check"></i> Approve</a>
                    <a href="?course_id=<?= $course_id ?>&reject=<?= $row['id'] ?>" class="button reject"><i class="fas fa-times"></i> Reject</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No pending enrollment requests.</p>
<?php endif; ?>

<a href="teacher_courses.php" class="back"><i class="fas fa-arrow-left"></i> Back to My Courses</a>

</body>
</html>
