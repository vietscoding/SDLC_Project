<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

if (!isset($_GET['assignment_id'])) {
    echo "Assignment ID missing.";
    exit;
}

$assignment_id = intval($_GET['assignment_id']);
$user_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu assignment
$stmt = $conn->prepare("
    SELECT a.title, c.id, c.title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.teacher_id = ?
");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You do not have permission to view submissions for this assignment.";
    exit;
}
$stmt->bind_result($assignment_title, $course_id, $course_title);
$stmt->fetch();
$stmt->close();

// Xử lý chấm điểm và feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = floatval($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $update_stmt = $conn->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ? WHERE id = ?");
    $update_stmt->bind_param("dsi", $grade, $feedback, $submission_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Thêm ?saved=1 vào URL khi quay lại trang
header("Location: teacher_assignment_submissions.php?assignment_id=$assignment_id&saved=1");
exit;

}

// Lấy danh sách bài nộp
$result = $conn->prepare("
    SELECT s.id, u.fullname, s.submitted_text, s.submitted_file, s.submitted_at, s.grade, s.feedback
    FROM assignment_submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
$result->bind_param("i", $assignment_id);
$result->execute();
$submissions = $result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions for <?= htmlspecialchars($assignment_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/teacher/teacher_assignment_submissions.css">
</head>
<body>
    <?php include "includes/teacher_sidebar.php"; ?>

    <div class="main-content">

        <div class="admin-page-header">
            <h2><i class="fas fa-list-alt"></i> Submissions for: <?= htmlspecialchars($assignment_title) ?></h2>
        </div>

        <div class="my-courses-overview">
            <h3><i class="fas fa-file-alt"></i> Submission Management</h3>
            <div class="my-courses-content">
                <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> Grade and feedback saved successfully!
                    </div>
                <?php endif; ?>
                <div class="submissions-info">
                    <p><i class="fas fa-book-open"></i> Course: <?= htmlspecialchars($course_title) ?></p>
                </div>

                <?php if ($submissions->num_rows > 0): ?>
                    <table class="courses-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Submitted Text</th>
                                <th>Submitted File</th>
                                <th>Submitted At</th>
                                <th colspan="3">Grade & Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $submissions->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Student"><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td data-label="Submitted Text"><?= nl2br(htmlspecialchars($row['submitted_text'])) ?></td>
                                    <td data-label="Submitted File">
                                        <?php if ($row['submitted_file']): ?>
                                            <a href="<?= htmlspecialchars($row['submitted_file']) ?>" target="_blank" class="download-link"><i class="fas fa-download"></i> Download</a>
                                        <?php else: ?>
                                            No file
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Submitted At"><?= $row['submitted_at'] ?></td>
                                    <td data-label="Grade & Feedback" colspan="3" class="submission-actions">
                                        <form method="post">
                                            <input type="hidden" name="submission_id" value="<?= $row['id'] ?>">
                                            <label for="grade_<?= $row['id'] ?>">Grade:</label>
                                            <input type="number" id="grade_<?= $row['id'] ?>" name="grade" value="<?= $row['grade'] ?? '' ?>" step="0.01" min="0" max="100">
                                            <label for="feedback_<?= $row['id'] ?>">Feedback:</label>
                                            <textarea id="feedback_<?= $row['id'] ?>" name="feedback" rows="3"><?= htmlspecialchars($row['feedback']) ?></textarea>
                                            <button type="submit"><i class="fas fa-save"></i> Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-courses"><i class="fas fa-inbox"></i> No submissions yet for this assignment.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-to-dashboard">
            <a href="teacher_assignments.php"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>
<script src="js/teacher_sidebar.js"></script>
</body>
</html>