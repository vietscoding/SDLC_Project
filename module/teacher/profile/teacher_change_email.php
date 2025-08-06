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
    $email = trim($_POST['email']);
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Email updated successfully.";
        }
    } else {
        $error = "Email cannot be empty.";
    }
}

$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_email);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Email | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/teacher/teacher_change_email.css">
</head>
<body>
    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
      
        <div class="admin-page-header">
            <h2><i class="fas fa-envelope"></i> Change Email</h2>
        </div>

        <div class="form-container">
            <?php if (!empty($success)): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label for="email">New Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_email ?? '') ?>" required>
                <div class="button-group">
                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="teacher_profile.php" class="cancel-button"><i class="fas fa-arrow-left"></i> Back to Profile</a>
                </div>
            </form>
        </div>
        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>

</body>
</html>