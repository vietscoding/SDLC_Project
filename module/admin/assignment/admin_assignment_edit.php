<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

$action = $_GET['action'] ?? 'add';  // 'add' hoặc 'edit'
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$title = '';
$description = '';
$due_date = '';
$course_id = 0;
$error = '';
$file_path = '';
$current_file = '';

// Lấy danh sách tất cả khóa học
$courses_result = $conn->query("SELECT id, title FROM courses");

// Nếu là sửa, lấy dữ liệu bài tập hiện tại
if ($action === 'edit' && $assignment_id > 0) {
    $stmt = $conn->prepare("SELECT title, description, due_date, course_id, file_path FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $stmt->bind_result($title, $description, $due_date, $course_id, $file_path);
    if (!$stmt->fetch()) {
        echo "Assignment not found.";
        exit;
    }
    $current_file = $file_path;
    $stmt->close();
    if ($due_date) {
        $due_date = date('Y-m-d', strtotime($due_date)); // Ensure date format for input type="date"
    }
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $course_id = intval($_POST['course_id']);
    $upload_path = $current_file;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $file_name;

        move_uploaded_file($file_tmp, $target_file);
        $upload_path = $target_file;
    }

    if (empty($title) || empty($description) || $course_id <= 0) {
        $error = "Please fill all required fields.";
    } else {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $course_id, $title, $description, $due_date, $upload_path);
            $stmt->execute();
            $stmt->close();
        } else if ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, course_id = ?, file_path = ? WHERE id = ?");
            $stmt->bind_param("sssisi", $title, $description, $due_date, $course_id, $upload_path, $assignment_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: admin_assignments.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?> | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin/admin_assignment_edit.css">
  
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-edit"></i> <?= ($action === 'add') ? 'Add New Assignment' : 'Edit Assignment' ?></h2>
    </div>

    <div class="assignment-management-overview">
        <h3><i class="fas fa-tasks"></i> Assignment Details</h3>
        <div class="assignment-management-content">
            <?php if ($action === 'edit'): ?>
                <p class="assignment-title-info"><i class="fas fa-info-circle"></i> Editing assignment: <strong><?= htmlspecialchars($title) ?></strong></p>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="form-group">
                    <label for="title"><i class="fas fa-signature"></i> Assignment Title:</label><br>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                </div>
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description:</label><br>
                    <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($description) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="due_date"><i class="fas fa-calendar-alt"></i> Due Date:</label><br>
                    <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>">
                </div>
                <div class="form-group">
                    <label for="course_id"><i class="fas fa-book"></i> Select Course:</label><br>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php
                        // Reset pointer nếu đã fetch ở trên
                        $courses_result->data_seek(0);
                        while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?= $course['id'] ?>" <?= ($course['id'] == $course_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="file"><i class="fas fa-paperclip"></i> Attachment (optional, PDF/DOCX/PPT...):</label><br>
                    <input type="file" id="file" name="file">
                </div>
                <?php if (!empty($current_file)): ?>
                    <div class="current-file">
                        <i class="fas fa-file-alt"></i> Current file: <a href="<?= htmlspecialchars($current_file) ?>" target="_blank">Download</a>
                    </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit">
                        <i class="fas fa-save"></i>
                        <?= ($action === 'add') ? 'Add Assignment' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="back-link">
        <a href="admin_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments List</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>

<script src="js/sidebar.js"></script>
</body>
</html>