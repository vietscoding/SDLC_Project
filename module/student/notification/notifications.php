<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];
// Fetch system notifications
$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC");

// Fetch user-specific notifications (teacher notifications in this context)
$stmt = $conn->prepare("SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? AND type = 'teacher_notification' ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark user notifications as read when the page is accessed
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

// Get user fullname and role for the header (assuming these are in $_SESSION from login)
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Notifications | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/notifications.css">
 
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="notifications-header">
            <h2><i class="fas fa-bell"></i> Your Notifications</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

    

        <div class="notification-section system-announcements">
            <h3><i class="fas fa-bullhorn"></i> Important Announcements</h3>
            <?php if ($sys_notif_result->num_rows > 0): ?>
                <ul class="notification-list-container">
                    <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                        <li class="notification-item">
                            <span class="notification-date"><?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></span>
                            <p class="notification-message"><?= htmlspecialchars($notif['message']) ?></p>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-notifications">No system announcements at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="notification-section user-notifications">
            <h3><i class="fas fa-envelope"></i> Your Recent Notifications</h3>
            <?php if ($result->num_rows > 0): ?>
                <ul class="notification-list-container">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li class="notification-item">
                            <span class="notification-date"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></span>
                            <p class="notification-message"><?= htmlspecialchars($row['message']) ?></p>
                            <?php if ($row['is_read'] == 0): ?>
                                <span class="notification-status">New</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-notifications">No new notifications.</p>
            <?php endif; ?>
        </div>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>

</body>
</html>