<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$message = "";
$error = "";

// Gửi thông báo khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = trim($_POST['content']);
    $target_role = $_POST['target_role'];

    if (!empty($content)) {
        // Prepare the statement outside the loop to avoid re-preparing multiple times
        $insert_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");

        // Get user IDs based on target role
        if ($target_role === 'all') {
            $users_query = $conn->query("SELECT id FROM users");
        } else {
            $stmt_get_users = $conn->prepare("SELECT id FROM users WHERE role = ?");
            $stmt_get_users->bind_param("s", $target_role);
            $stmt_get_users->execute();
            $users_query = $stmt_get_users->get_result();
            $stmt_get_users->close();
        }

        $notification_sent_count = 0;
        // Add notification for each user
        if ($users_query) {
            while ($user = $users_query->fetch_assoc()) {
                $insert_stmt->bind_param("is", $user['id'], $content);
                if ($insert_stmt->execute()) {
                    $notification_sent_count++;
                }
            }
        }
        $insert_stmt->close();

        if ($notification_sent_count > 0) {
            $message = "Notification sent successfully to " . $notification_sent_count . " user(s).";
        } else {
            $error = "No users found for the selected role, or failed to send notification.";
        }

    } else {
        $error = "Message content cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send System Notification | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_send_notification.css">
    
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-bell"></i> Send System Notification</h2>
    </div>

    <div class="notification-card">
        <h3><i class="fas fa-paper-plane"></i> Create New Notification</h3>
        <?php if ($message): ?>
            <p class="success-message"><i class="fas fa-check-circle"></i> <?= $message ?></p>
        <?php elseif ($error): ?>
            <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="content"><i class="fas fa-envelope-open-text"></i> Notification Content:</label>
                <textarea id="content" name="content" rows="6" required placeholder="Enter your notification message here..."></textarea>
            </div>

            <div class="form-group">
                <label for="target_role"><i class="fas fa-users"></i> Send To:</label>
                <select id="target_role" name="target_role" required>
                    <option value="all">All Users</option>
                    <option value="student">Students</option>
                    <option value="teacher">Teachers</option>
                    <option value="admin">Admins</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit"><i class="fas fa-paper-plane"></i> Send Notification</button>
            </div>
        </form>
    </div>

    <div class="back-to-users">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>