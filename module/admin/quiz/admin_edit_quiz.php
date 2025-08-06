<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$action = $_GET['action'] ?? 'add';
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

$quiz_title = '';
$course_id = 0;
$deadline = '';
$error = "";

// Lấy danh sách tất cả courses để chọn
$courses = $conn->query("SELECT id, title FROM courses ORDER BY title ASC");

// Nếu sửa, lấy dữ liệu quiz hiện tại
if ($action === 'edit' && $quiz_id > 0) {
    $stmt = $conn->prepare("SELECT title, course_id, deadline FROM quizzes WHERE id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $stmt->bind_result($quiz_title, $course_id, $deadline);
    if (!$stmt->fetch()) {
        echo "Quiz not found.";
        exit;
    }
    $stmt->close();
    if ($deadline) {
        $deadline = date('Y-m-d\TH:i', strtotime($deadline));
    }
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_title = trim($_POST['title']);
    $course_id = intval($_POST['course_id']);
    $deadline = $_POST['deadline'] ?? '';

    if (empty($quiz_title) || $course_id <= 0 || empty($deadline)) {
        $error = "Please enter quiz title, select a course and set a deadline.";
    } else {
        $deadline_mysql = date('Y-m-d H:i:s', strtotime($deadline));
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, deadline) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $course_id, $quiz_title, $deadline_mysql);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE quizzes SET title = ?, course_id = ?, deadline = ? WHERE id = ?");
            $stmt->bind_param("sisi", $quiz_title, $course_id, $deadline_mysql, $quiz_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: admin_quizzes.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= ($action === 'add') ? 'Add New Quiz' : 'Edit Quiz' ?> | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_edit_quiz.css">
   
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-edit"></i> <?= ($action === 'add') ? 'Add New Quiz' : 'Edit Quiz' ?></h2>
    </div>

    <div class="quiz-management-overview"> <h3><i class="fas fa-clipboard"></i> Quiz Details</h3> <div class="quiz-management-content"> <?php if ($action === 'edit'): ?>
                <p class="quiz-title-info"><i class="fas fa-info-circle"></i> Editing quiz: <strong><?= htmlspecialchars($quiz_title) ?></strong></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error-message"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="form-group">
                    <label for="title"><i class="fas fa-signature"></i> Quiz Title:</label><br>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($quiz_title) ?>" required>
                </div>
                <div class="form-group">
                    <label for="course_id"><i class="fas fa-book"></i> Assign to Course:</label><br>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php
                        $courses->data_seek(0);
                        while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?= $course['id'] ?>" <?= ($course['id'] == $course_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deadline"><i class="fas fa-clock"></i> Deadline:</label><br>
                    <input type="datetime-local" id="deadline" name="deadline" value="<?= htmlspecialchars($deadline) ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit">
                        <i class="fas fa-save"></i>
                        <?= ($action === 'add') ? 'Add Quiz' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="back-link">
        <a href="admin_quizzes.php"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>