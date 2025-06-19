<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

// Lấy thông báo hệ thống
$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC");
$important_announcements_html = "";
if ($sys_notif_result->num_rows > 0) {
    $important_announcements_html .= "<div class='important-announcements-container'>"; // Reusing or adapting existing class
    $important_announcements_html .= "<h3><i class='fas fa-bullhorn'></i> Important Announcements</h3><ul>";
    while ($notif = $sys_notif_result->fetch_assoc()) {
        $important_announcements_html .= "<li><strong>[{$notif['created_at']}]</strong> " . htmlspecialchars($notif['message']) . "</li>";
    }
    $important_announcements_html .= "</ul></div><br>";
}

// Lấy danh sách khóa học giáo viên phụ trách
$courses = $conn->query("SELECT id, title FROM courses WHERE teacher_id = {$_SESSION['user_id']}");
$notification_success = false;
$message_status = ''; // To hold success/error messages

// Xử lý gửi thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id']) && isset($_POST['message'])) {
    $course_id = intval($_POST['course_id']);
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $result = $conn->query("SELECT user_id FROM enrollments WHERE course_id = $course_id");
        $teacher_id = $_SESSION['user_id'];
        $course_info = $conn->query("SELECT title FROM courses WHERE id = $course_id")->fetch_assoc();
        $teacher_info = $conn->query("SELECT fullname FROM users WHERE id = $teacher_id")->fetch_assoc();
        $course_title = $course_info['title'];
        $teacher_name = $teacher_info['fullname'];

        $full_message = "From $teacher_name (Course: $course_title): $message";
        $sent_count = 0;
        while ($row = $result->fetch_assoc()) {
            $student_id = $row['user_id'];
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, sender_id, course_id, type, created_at) VALUES (?, ?, ?, ?, 'teacher_notification', NOW())");
            $stmt->bind_param("isii", $student_id, $full_message, $teacher_id, $course_id);
            if ($stmt->execute()) {
                $sent_count++;
            }
            $stmt->close();
        }
        if ($sent_count > 0) {
            $notification_success = true;
            $message_status = '<div class="success-message"><i class="fas fa-check-circle"></i> Notification sent successfully to ' . $sent_count . ' enrolled students!</div>';
        } else {
            $message_status = '<div class="warning-message"><i class="fas fa-exclamation-triangle"></i> No students found for this course or no notification sent.</div>';
        }
    } else {
        $message_status = '<div class="error-message"><i class="fas fa-times-circle"></i> Message cannot be empty.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> <
    <link rel="stylesheet" href="../../../css/teacher/teacher_notifications.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-bell"></i> Send Notifications to Students</h2>
        </div>

        <?php if (!empty($message_status)): ?>
            <?= $message_status ?>
        <?php endif; ?>

        <?= $important_announcements_html ?>

        <div class="form-overview">
            <h3><i class="fas fa-paper-plane"></i> Send New Notification</h3>
            <div class="form-content">
                <form method="post" class="edit-lesson-form-style">
                    <label for="course_id"><i class="fas fa-book"></i> Select Course:</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Choose a Course --</option>
                        <?php $courses->data_seek(0); // Reset pointer for second use ?>
                        <?php while ($row = $courses->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label for="message"><i class="fas fa-envelope"></i> Notification Message:</label>
                    <textarea name="message" id="message" rows="6" placeholder="Enter your message here..." required></textarea>
                    
                    <button type="submit"><i class="fas fa-paper-plane"></i> Send Notification</button>
                </form>
            </div>
        </div>

        <div class="back-buttons">
            <a href="../dashboard/teacher_dashboard.php" class="primary-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="teacher_view_notifications.php"><i class="fas fa-envelope-open-text"></i> View Sent Notifications</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>