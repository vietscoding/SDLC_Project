<?php
include "includes/db_connect.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'student' or 'teacher'

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
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $hashedPassword, $role);

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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #e0f2f7, #81d4fa); /* Light blue gradient */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
        }

        .registration-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 360px;
            text-align: left;
            animation: slideIn 0.5s ease-out;
            position: relative;
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

        h2 {
            font-size: 2em;
            margin-bottom: 20px;
            color: #0056b3;
            text-align: center;
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
            margin-bottom: 18px; /* Slightly increased margin */
            position: relative;
            display: flex; /* Use flexbox to align label and input */
            flex-direction: column; /* Stack label above input */
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: bold;
            font-size: 0.95em;
        }

        .input-wrapper {
            position: relative; /* Wrapper for input and icon */
        }

        .input-wrapper i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #0056b3;
            opacity: 0.7;
            pointer-events: none;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: calc(100% - 30px); /* Adjusted width for icon spacing */
            padding: 8px 10px 8px 35px; /* More left padding for icon */
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1em;
            color: #495057;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: #0056b3;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        select {
            appearance: none;
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg fill="%23495057" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            padding-left: 35px; /* Match input padding */
        }

        button[type="submit"] {
            background-color: #0056b3;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease-in-out;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background-color: #004494;
            transform: translateY(-1px);
        }

        p {
            margin-top: 15px;
            color: #6c757d;
            text-align: center;
            font-size: 0.9em;
        }

        p a {
            color: #0056b3;
            text-decoration: none;
            font-weight: bold;
        }

        p a:hover {
            text-decoration: underline;
        }

        /* Floating BTEC logo */
        .floating-logo {
            position: fixed;
            top: 15px;
            left: 15px;
            width: 60px;
            height: auto;
            opacity: 0.7;
            animation: floatLogo 4s infinite alternate ease-in-out;
        }

        @keyframes floatLogo {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-8px);
            }
        }

        /* Subtle background pattern */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg id="slash" fill="%23e1f5fe" fill-opacity="0.4"%3E%3Cpath d="M0 0l60 60-10 10L-10 0z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
            opacity: 0.6;
            z-index: -1;
        }
    </style>
</head>
<body>
    <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo" class="floating-logo">

    <div class="registration-container">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>

        <?php if ($success): ?>
            <p class="success-message"><i class="fas fa-check-circle"></i> <?= $success ?></p>
        <?php elseif ($error): ?>
            <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="fullname"><i class="fas fa-user"></i> Full Name:</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="fullname" name="fullname" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="role"><i class="fas fa-graduation-cap"></i> Role:</label>
                <div class="input-wrapper">
                    <i class="fas fa-graduation-cap"></i>
                    <select id="role" name="role" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <button type="submit"><i class="fas fa-sign-up-alt"></i> Register</button>
        </form>

        <p><i class="fas fa-sign-in-alt"></i> Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>