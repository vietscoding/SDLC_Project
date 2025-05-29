<?php
session_start();
include "includes/db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Fetch user by email
        $stmt = $conn->prepare("SELECT id, fullname, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullname, $hashedPassword, $role);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashedPassword)) {
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            /* Option 1: Soft Gradient Background */
             background: linear-gradient(135deg, #e0f2f7, #bbdefb); 

            /* Option 2: Subtle Pattern Background (replace URL) */
            /* background: url('path/to/your/subtle-pattern.png'); */

            /* Option 3: Educational Image Background (replace URL) */
            /*background: url('https://images.unsplash.com/photo-1519681393784-d1202a9c2313?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed;*/
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            max-width: 90%;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

         h2 {
            color: #1976d2; /* A more standard blue */
            margin-bottom: 30px;
            font-size: 2.5em;
            font-weight: 600;
        }

        .error-message {
            color: #d32f2f; /* A clearer red for errors */
            background-color: #ffebee; /* Light red background for errors */
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a; /* Light red border for errors */
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #424242; /* Darker gray for labels */
            font-weight: bold;
            text-align: left;
        }

       input[type="email"],
        input[type="password"] {
            width: calc(100% - 20px);
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #9e9e9e; /* Medium gray border for inputs */
            border-radius: 5px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

       input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #1976d2; /* Focus color matches heading */
            outline: none;
            box-shadow: 0 0 5px rgba(25, 118, 210, 0.5); /* Focus shadow matches heading */
        }

       button[type="submit"] {
            background-color: #1976d2; /* Primary blue for button */
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background-color: #1565c0; /* Darker shade of blue on hover */
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

       p {
            margin-top: 20px;
            color: #616161; /* Medium gray for paragraph text */
        }

        p a {
            color: #2196f3; /* A brighter blue for links */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        p a:hover {
            color: #1976d2; /* Hover color matches heading and focus */
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-graduation-cap"></i> Log In</h2>

        <?php if ($error): ?>
            <p class="error-message"><?= $error ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="email"><i class="fas fa-envelope"></i> Email:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <label for="password"><i class="fas fa-lock"></i> Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <button type="submit"><i class="fas fa-sign-in-alt"></i> Log In</button>
        </form>

        <p><i class="fas fa-question-circle"></i> <a href="register.php">Don't have an account? Register here</a></p>
    </div>
</body>
</html>