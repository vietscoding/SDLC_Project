<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get old password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Validate
    if (!password_verify($current_password, $hashed_password)) {
        $error = "Incorrect current password.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Update new password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        $success = "Password changed successfully.";
    }
}

// Get user role for the header (assuming it's in $_SESSION from login)
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_change_password.css">
  
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="profile-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
        </div>

        <div class="profile-section">
            <h3><i class="fas fa-lock"></i> Update Your Password</h3>
            <?php if (!empty($success)): ?>
                <p class='success-message'><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class='error-message'><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="post" class="profile-actions" autocomplete="off">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit"><i class="fas fa-save"></i> Change Password</button>
            </form>
        </div>

        <div class="navigation-links">
            <a href="student_profile.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            <a href="../../../common/logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>
</body>
</html>