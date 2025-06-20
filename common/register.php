<?php
include "../includes/db_connect.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'student' or 'teacher'
    $status = ($role == 'teacher') ? 'pending' : 'approved';
    if (!empty($fullname) && !empty($email) && !empty($password)) {
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
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $hashedPassword, $role, $status);


            if ($stmt->execute()) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Failed to register. Please try again.";
            }
        }
        $check->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
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
            <h2><i class="fas fa-user-plus"></i> Create Account</h2>

            <?php
            // Example PHP logic for success/error messages
            $success = null;
            $error = null;

            // This part would typically come from your server-side PHP processing
            // For demonstration, let's simulate a success or error
            // if (isset($_POST['submit_registration'])) {
            //     // Simulate successful registration
            //     $success = "Account registered successfully!";
            //     // Simulate an error
            //     // $error = "Email already exists. Please try another.";
            // }
            ?>

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