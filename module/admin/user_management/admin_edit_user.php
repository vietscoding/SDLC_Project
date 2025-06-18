<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['user_id'])) {
    echo "User ID missing.";
    exit;
}

$user_id = intval($_GET['user_id']);
$error = "";

// Lấy thông tin user
$stmt = $conn->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $role);
if (!$stmt->fetch()) {
    echo "User not found.";
    exit;
}
$stmt->close();

// Ngăn chặn chỉnh sửa người dùng có vai trò là 'admin'
if ($role == 'admin') {
    // Lưu thông báo lỗi vào session
    $_SESSION['error_message'] = "Bạn không được phép chỉnh sửa tài khoản Admin.";
    // Chuyển hướng người dùng về trang quản lý người dùng
    header("Location: admin_users.php");
    exit; // Rất quan trọng để dừng việc thực thi script sau khi chuyển hướng
}

// Xử lý cập nhật role khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_role = $_POST['role'];

    if (in_array($new_role, ['student', 'teacher'])) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        if ($stmt->execute()) {
            header("Location: admin_users.php");
            exit;
        } else {
            $error = "Failed to update role.";
        }
        $stmt->close();
    } else {
        $error = "Invalid role selected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_edit_user.css">
   
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>
    </div>

    <div class="edit-user-card">
        <h3><i class="fas fa-info-circle"></i> User Details</h3>
        <div class="user-details-section">
            <p><strong><i class="fas fa-id-card"></i> ID:</strong> <?= $user_id ?></p>
            <p><strong><i class="fas fa-user"></i> Full Name:</strong> <?= htmlspecialchars($fullname) ?></p>
            <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?= htmlspecialchars($email) ?></p>
            <p><strong><i class="fas fa-user-tag"></i> Current Role:</strong> <span style="text-transform: capitalize;"><?= htmlspecialchars($role) ?></span></p>
        </div>

        <h3><i class="fas fa-user-tag"></i> Change User Role</h3>
        <?php if ($error): ?>
            <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="role"><i class="fas fa-briefcase"></i> Select New Role:</label>
                <select id="role" name="role" required>
                    <option value="student" <?= ($role == 'student') ? 'selected' : '' ?>>Student</option>
                    <option value="teacher" <?= ($role == 'teacher') ? 'selected' : '' ?>>Teacher</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <div class="back-to-users">
        <a href="admin_users.php"><i class="fas fa-arrow-left"></i> Back to User Management</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>

<script src="js/sidebar.js"></script> </body>
</html>