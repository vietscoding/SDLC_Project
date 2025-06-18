<?php
session_start();
include "../includes/db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Fetch user by email
        $stmt = $conn->prepare("SELECT id, fullname, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashedPassword, $role, $status);
            $stmt->fetch();

            // Verify password
           if (password_verify($password, $hashedPassword)) {
    // Check teacher approval
    if ($role == 'teacher' && $status != 'approved') {
        $error = "Your account is pending approval by admin.";
    } else {
        // Store user data in session
        $_SESSION['user_id'] = $id;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['role'] = $role;

        // Redirect based on role
        if ($role == 'student') {
            header("Location: ../module/student/dashboard/student_dashboard.php");
            exit;
        } elseif ($role == 'teacher') {
            header("Location: ../module/teacher/dashboard/teacher_dashboard.php");
            exit;
        } elseif ($role == 'admin') {
            header("Location: ../module/admin/dashboard/admin_dashboard.php");
            exit;
        }
    }
} else {
    $error = "Incorrect password.";
}

        } else {
            $error = "Account not found.";
        }
        $stmt->close();
    } else {
        $error = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log In | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/common/login.css">
</head>
<body>
    
    <div class="login-container">
        <div class="login-panel">
            <div class="logo">
                <img src="https://vinadesign.vn/uploads/images/2023/06/logo-fpt-vinadesign-03-14-38-27.jpg" alt="BTEC FPT Logo" width="200px">
                BTEC FPT
            </div>
            <h2>Log In</h2>

            <?php if (isset($error)): ?>
                <p style="color: #dc2626; margin-bottom: 15px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <div class="forgot-password">
                        <a href="#">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Log In</button>
            </form>

            <div class="signup-link">
                Don't have an account? <a href="register.php">Sign up</a>
            </div>
        </div>
        <div class="illustration-panel">
            <img src="https://cdn.tokyotechlab.com/Blog/Blog%202024/Blog%20T9/tai_sao_cac_doanh_nghiep_va_to_chuc_giao_duc_nen_su_dung_phan_mem_lms_6092bea1d6.webp" alt="Login Illustration">
        </div>
    </div>
</body>
</html>