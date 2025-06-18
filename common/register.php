<?php
include "includes/db_connect.php";

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
    <style>
        /* Basic Reset */
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
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 50%, #fbc2eb 100%);
            padding: 20px; /* Add some padding for smaller screens */
        }

        .registration-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            width: 800px; /* Match login container width */
            max-width: 95%; /* Ensure responsiveness */
            animation: slideIn 0.5s ease-out; /* Keep existing animation */
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .registration-panel {
            flex: 1;
            padding: 40px; /* Match login panel padding */
            display: flex;
            flex-direction: column;
            text-align: left; /* Ensure text alignment */
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
            width: 24px; /* Adjust logo size */
            height: 24px;
            margin-right: 10px;
        }

        h2 {
            font-size: 2rem; /* Match login h2 size */
            font-weight: 700; /* Match login h2 weight */
            margin-bottom: 20px;
            color: #262626; /* Match login h2 color */
            text-align: left; /* Align to left */
        }

        .success-message,
        .error-message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: center;
            font-size: 0.9em;
        }

        .success-message {
            background-color: #e6ffe6;
            color: #28a745;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px; /* Match login form-group margin */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px; /* Match login label margin */
            color: #525252; /* Match login label color */
            font-weight: 500; /* Match login label weight */
            font-size: 0.9rem; /* Match login label size */
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%; /* Full width */
            padding: 12px 16px; /* Match login input padding */
            border: 1px solid #d4d4d4; /* Match login input border */
            border-radius: 6px; /* Match login input border-radius */
            font-size: 1rem; /* Match login input font size */
            color: #262626; /* Match login input color */
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #3b82f6; /* Blue focus color from login */
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25); /* Blue shadow from login */
        }

        select {
            appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg fill="%23495057" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 16px center; /* Adjust position for wider padding */
            background-size: 16px;
        }

        button[type="submit"] {
            background-color: #3b82f6; /* Match login button color */
            color: #fff;
            padding: 12px 24px; /* Match login button padding */
            border: none;
            border-radius: 6px; /* Match login button border-radius */
            cursor: pointer;
            font-size: 1rem; /* Match login button font size */
            font-weight: 500; /* Match login button font weight */
            transition: background-color 0.3s ease;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background-color: #2563eb; /* Match login button hover color */
            transform: translateY(-1px); /* Keep subtle hover effect */
        }

        p.link-text { /* New class for descriptive paragraph links */
            margin-top: 30px; /* Match signup-link margin-top */
            text-align: center;
            font-size: 0.9rem; /* Match signup-link font size */
            color: #525252; /* Match signup-link color */
        }

        p.link-text a {
            color: #3b82f6; /* Match signup-link a color */
            text-decoration: none;
            font-weight: 500; /* Match signup-link a font weight */
        }

        p.link-text a:hover {
            text-decoration: underline;
        }

        .illustration-panel {
            flex: 1;
            background-color: #e0f2fe; /* Light blue for illustration */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px; /* Add padding to illustration */
        }

        .illustration-panel img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .registration-container {
                flex-direction: column;
                width: 100%;
                max-width: 100%;
            }

            .illustration-panel {
                display: none; /* Hide illustration on smaller screens */
            }

            .registration-panel {
                padding: 30px; /* Adjust padding for smaller screens */
            }
        }
    </style>
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