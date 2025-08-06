<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);
            if ($stmt->execute()) {
                $success = "Email updated successfully.";
            } else {
                $error = "Failed to update email: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Invalid email format.";
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
    <link rel="stylesheet" href="../../../css/admin/admin_change_email.css">
    
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-envelope"></i> Change Email</h2>
    </div>

    <div class="change-email-container">
        <h3><i class="fas fa-edit"></i> Update Your Email</h3>
        <?php if ($success) echo "<p class='success-message'>" . htmlspecialchars($success) . "</p>"; ?>
        <?php if ($error) echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="post">
            <label for="email">New Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_email) ?>" required>
            <button type="submit"><i class="fas fa-save"></i> Change Email</button>
        </form>
    </div>

    <div class="back-to-users"> <a href="admin_profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>