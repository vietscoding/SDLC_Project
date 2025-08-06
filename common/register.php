<?php
include "../includes/db_connect.php";
session_start(); // Start session for CSRF and success message

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $fullname = htmlspecialchars(trim($_POST['fullname']), ENT_QUOTES, 'UTF-8');
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm-password'];
        $role = $_POST['role'];

        // Validate inputs
        if (empty($fullname) || empty($email) || empty($password)) {
            $error = "Please fill in all required fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif (!in_array($role, ['student', 'teacher'])) {
            $error = "Invalid role selected.";
        } else {
            // Check if email already exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "This email is already registered.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $status = ($role == 'teacher') ? 'pending' : 'approved';
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $fullname, $email, $hashedPassword, $role, $status);

                try {
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Registration successful! You can now log in.";
                        header("Location: login.php?registered=1");
                        exit;
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register Account | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/common/register.css">
</head>

<body>
    <div class="registration-container">
        <div class="registration-panel">
            <div class="logo">
                <img src="https://vinadesign.vn/uploads/images/2023/06/logo-fpt-vinadesign-03-14-38-27.jpg" alt="BTEC FPT Logo">
                BTEC FPT
            </div>
            <h2> Create Account</h2>
            <?php if ($success): ?>
                <p class="success-message"><i class="fas fa-check-circle"></i> <?= $success ?></p>
            <?php elseif ($error): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
            <?php endif; ?>
            <form method="post" action="">

                <div class="form-group">
                    <label for="fullname"><i class="fas fa-user"></i> Full Name:</label>
                    <input type="text" id="fullname" name="fullname" required>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <!-- Thêm trường Confirm Password -->
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <div class="form-group">
                    <label for="role"><i class="fas fa-graduation-cap"></i> Role:</label>
                    <select id="role" name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                    </select>
                </div>

                <button type="submit"><i class="fas fa-sign-up-alt"></i> Register</button>
            </form>

            <p class="link-text">Already have an account? <a href="login.php">Log in</a></p>
        </div>
        <div class="illustration-panel">
            <img src="https://cdn.tokyotechlab.com/Blog/Blog%202024/Blog%20T9/tai_sao_cac_doanh_nghiep_va_to_chuc_giao_duc_nen_su_dung_phan_mem_lms_6092bea1d6.webp" alt="Registration Illustration">
        </div>
    </div>
</body>

</html>