<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";
$user_id = $_SESSION['user_id'];

// Lấy thông báo hệ thống
$sys_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC");

// Lấy thông báo cá nhân (thông báo giáo viên đã gửi)
// Giả sử 'sender_id' trong bảng notifications lưu trữ id của giáo viên đã gửi
$personal_result = $conn->query("SELECT n.message, n.created_at, c.title as course_title
                                FROM notifications n
                                LEFT JOIN courses c ON n.course_id = c.id
                                WHERE n.sender_id = $user_id AND n.type = 'teacher_notification'
                                ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/teacher/teacher_view_notifications.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-envelope-open-text"></i> My Notifications</h2>
        </div>

        <?php if ($sys_result && $sys_result->num_rows > 0): ?>
            <div class="notification-section">
                <h3><i class="fas fa-bullhorn"></i> Important Announcements</h3>
                <ul>
                    <?php while ($sys = $sys_result->fetch_assoc()): ?>
                        <li><strong>[<?= $sys['created_at'] ?>]</strong> <?= htmlspecialchars($sys['message']) ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="notification-section">
            <h3><i class="fas fa-paper-plane"></i> Sent Notifications</h3>
            <?php if ($personal_result && $personal_result->num_rows > 0): ?>
                <ul>
                    <?php while ($notif = $personal_result->fetch_assoc()): ?>
                        <li><strong>[<?= $notif['created_at'] ?>]</strong>
                            <?php if (!empty($notif['course_title'])): ?>
                                (Course: <?= htmlspecialchars($notif['course_title']) ?>)
                            <?php endif; ?>
                            <?= htmlspecialchars($notif['message']) ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No notifications sent by you yet.</p>
            <?php endif; ?>
        </div>

        <div class="back-buttons">
            <a href="../dashboard/teacher_dashboard.php" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="teacher_notifications.php"><i class="fas fa-bell"></i> Send New Notification</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>