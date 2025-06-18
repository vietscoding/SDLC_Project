<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Xử lý cập nhật email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->close();

        $success = "Email updated successfully.";
    } else {
        $error = "Email cannot be empty.";
    }
}

// Lấy email hiện tại
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_email);
$stmt->fetch();
$stmt->close();

// Get user role for the header (assuming it's in $_SESSION from login)
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Email | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/student_change_email.css">
    
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="profile-header">
            <h2><i class="fas fa-envelope"></i> Change Email</h2>
            </div>
        

        <div class="profile-section">
            <h3><i class="fas fa-edit"></i> Update Your Email</h3>
            <?php if ($success) echo "<p class='success-message'>$success</p>"; ?>
            <?php if ($error) echo "<p class='error-message'>$error</p>"; ?>
            <form method="post" class="profile-actions">
                <label for="email">New Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_email) ?>" required>
                <button type="submit"><i class="fas fa-save"></i> Change Email</button>
            </form>
        </div>

        <div class="navigation-links">
            <a href="student_profile.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            <a href="logout.php" class="logout-button"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

  <script src="js/student_sidebar.js"></script>
</body>
</html>