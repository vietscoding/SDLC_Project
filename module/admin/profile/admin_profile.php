<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/admin/admin_profile.css"> 
  
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-user-cog"></i> Admin Profile</h2>
    </div>

    <div class="profile-card">
        <h3><i class="fas fa-info-circle"></i> Profile Information</h3>
        <div class="user-details-section">
            <p><strong><i class="fas fa-user"></i> Name:</strong> <?= htmlspecialchars($fullname) ?></p>
            <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?= htmlspecialchars($email) ?></p>
        </div>

        <h3><i class="fas fa-wrench"></i> Actions</h3>
        <div class="profile-actions">
            <a href="admin_change_email.php"><i class="fas fa-envelope"></i> Change Email</a>
            <a href="admin_change_password.php"><i class="fas fa-key"></i> Change Password</a>
        </div>
    </div>

    <div class="back-to-users">
        <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>