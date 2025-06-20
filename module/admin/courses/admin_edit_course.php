<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
// Thêm đoạn code này để tạo hoặc kiểm tra CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include "../../../includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);
$error = "";

// Lấy danh sách teacher
$teacher_result = $conn->query("SELECT id, fullname FROM users WHERE role = 'teacher'");

// Lấy thông tin course cần sửa
$stmt = $conn->prepare("SELECT title, department, description, teacher_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($title, $department, $description, $teacher_id);
if (!$stmt->fetch()) {
    echo "Course not found.";
    exit;
}
$stmt->close();

// Cập nhật course khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Thêm đoạn kiểm tra CSRF token ở đây
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF token validation failed. Please try again.";
        exit;
    }

    $new_title = trim($_POST['title']);
    $new_department = trim($_POST['department']);
    $new_description = trim($_POST['description']);
    $new_teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (!empty($new_title) && !empty($new_department)) {
        $stmt = $conn->prepare("UPDATE courses SET title = ?, department = ?, description = ?, teacher_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $new_title, $new_department, $new_description, $new_teacher_id, $course_id);
        if ($stmt->execute()) {
            // Regenerate CSRF token after successful submission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: admin_courses.php");
            exit;
        } else {
            $error = "Failed to update course.";
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
    <title>Edit Course | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_edit_course.css">
     
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-edit"></i> Edit Course</h2>
    </div>

    <div class="course-management-overview">
        <h3><i class="fas fa-clipboard"></i> Course Details</h3>
        <div class="course-management-content">
            <?php if ($error): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="title"><i class="fas fa-signature"></i> Course Title:</label><br>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="form-group">
                    <label for="department"><i class="fas fa-tag"></i> Department:</label><br>
                    <input type="text" id="department" name="department" value="<?= htmlspecialchars($department) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description:</label><br>
                    <textarea id="description" name="description" rows="4" cols="50"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="teacher_id"><i class="fas fa-chalkboard-teacher"></i> Instructor (optional):</label><br>
                    <select id="teacher_id" name="teacher_id">
                        <option value="">-- None --</option>
                        <?php while ($teacher = $teacher_result->fetch_assoc()): ?>
                            <option value="<?= $teacher['id'] ?>" <?= ($teacher['id'] == $teacher_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($teacher['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
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