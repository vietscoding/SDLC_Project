<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
// Generate and store CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include "../../../includes/db_connect.php";

$error = "";

// Lấy danh sách teacher để chọn
$teacher_result = $conn->query("SELECT id, fullname FROM users WHERE role = 'teacher'");

// Thêm mới course
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token validation failed. Please try again.";
        // Consider logging this or taking other security actions
        exit; // Stop execution if token is invalid
    }
    $title = trim($_POST['title']);
    $department = trim($_POST['department']);
    $description = trim($_POST['description']);
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (!empty($title) && !empty($department)) {
        $stmt = $conn->prepare("INSERT INTO courses (title, department, description, teacher_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $department, $description, $teacher_id);
        if ($stmt->execute()) {
            // Regenerate CSRF token after successful submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: admin_courses.php");
            exit;
        } else {
            $error = "Failed to add course.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in at least Title and Department.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Course | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_add_course.css">
    
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-plus-circle"></i> Add New Course</h2>
    </div>

    <div class="course-management-overview">
        <h3><i class="fas fa-pencil-alt"></i> Course Details</h3>
        <div class="course-management-content">
            <?php if ($error): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="title"><i class="fas fa-signature"></i> Course Title:</label><br>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="department"><i class="fas fa-tag"></i> Department:</label><br>
                    <input type="text" id="department" name="department" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description:</label><br>
                    <textarea id="description" name="description" rows="4" cols="50"></textarea>
                </div>

                <div class="form-group">
                    <label for="teacher_id"><i class="fas fa-chalkboard-teacher"></i> Instructor (optional):</label><br>
                    <select id="teacher_id" name="teacher_id">
                        <option value="">-- None --</option>
                        <?php while ($teacher = $teacher_result->fetch_assoc()): ?>
                            <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['fullname']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit"><i class="fas fa-plus"></i> Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <div class="back-link">
        <a href="admin_courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>