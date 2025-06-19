<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $hashed_password)) {
        $error = "Incorrect current password.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        $stmt->close();
        $success = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/teacher/teacher_change_password.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
        </div>

        <div class="form-container">
            <?php if (!empty($success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <div class="button-group">
                    <button type="submit"><i class="fas fa-save"></i> Change Password</button>
                    <a href="teacher_profile.php" class="cancel-button"><i class="fas fa-arrow-left"></i> Back to Profile</a>
                </div>
            </form>
        </div>
        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
</body>
</html>