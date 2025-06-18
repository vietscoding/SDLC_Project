<?php
session_start();
include "includes/db_connect.php";

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
            header("Location: student_dashboard.php");
            exit;
        } elseif ($role == 'teacher') {
            header("Location: teacher_dashboard.php");
            exit;
        } elseif ($role == 'admin') {
            header("Location: admin_dashboard.php");
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
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* Đổi màu nền phía sau form */
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 50%, #fbc2eb 100%);
        }

        .login-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            width: 800px; /* Adjust as needed */
            max-width: 95%;
        }

        .login-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            color: #525252;
            font-weight: 500;
            font-size: 1.2rem;
        }

        .logo img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            /* Style your logo image */
        }

        h2 {
            color: #262626;
            margin-bottom: 20px;
            font-size: 2rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #525252;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d4d4d4;
            border-radius: 6px;
            font-size: 1rem;
            color: #262626;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6; /* Blue focus color */
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9rem;
            color: #525252;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .forgot-password a {
            color: #3b82f6;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        button.login-btn {
            background-color: #3b82f6;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        button.login-btn:hover {
            background-color: #2563eb;
        }

        .signup-link {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #525252;
        }

        .signup-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .illustration-panel {
            flex: 1;
            background-color: #e0f2fe; /* Light blue for illustration */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .illustration-panel img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 100%;
                max-width: 100%;
            }

            .illustration-panel {
                display: none; /* Hide illustration on smaller screens */
            }

            .login-panel {
                padding: 30px;
            }
        }
    </style>
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