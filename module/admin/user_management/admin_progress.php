<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);

// Lấy tên khóa học và tên giáo viên
$stmt = $conn->prepare("SELECT c.title, u.fullname AS teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($course_title, $teacher_name);
if (!$stmt->fetch()) {
    echo "Course not found.";
    exit;
}
$stmt->close();

// Lấy tổng số bài học trong khóa
$result = $conn->query("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = $course_id");
$total_lessons = $result->fetch_assoc()['total_lessons'];

// Lấy danh sách học viên đã enroll
$stmt = $conn->prepare("
    SELECT u.id, u.fullname
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$students_result = $stmt->get_result();

// Lấy điểm trung bình quiz từng học viên
$avg_scores = [];
$quiz_scores_res = $conn->query("
    SELECT qs.user_id, AVG(qs.score) AS avg_score
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    WHERE q.course_id = $course_id
    GROUP BY qs.user_id
");
while ($row = $quiz_scores_res->fetch_assoc()) {
    $avg_scores[$row['user_id']] = round($row['avg_score'], 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Tracking - <?= htmlspecialchars($course_title) ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_progress.css">
  
</head>
<body>
    <?php include "../../../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-chart-line"></i> Progress Tracking for: <?= htmlspecialchars($course_title) ?></h2>
        </div>

        <div class="course-management-overview">
            <h3><i class="fas fa-user-tie"></i> Student Progress</h3>
            <div class="course-management-content">
                <p class="teacher-info"><strong>Teacher:</strong> <?= htmlspecialchars($teacher_name) ?></p>
                <?php if ($students_result->num_rows === 0): ?>
                    <p class="no-data-message"><i class="fas fa-info-circle"></i> No students enrolled in this course yet.</p>
                <?php elseif ($total_lessons == 0): ?>
                    <p class="no-data-message"><i class="fas fa-info-circle"></i> No lessons available for this course to track progress.</p>
                <?php else: ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Lesson Completion</th>
                                <th>Average Quiz Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students_result->fetch_assoc()): ?>
                                <?php
                                    $student_id = $student['id'];
                                    // Đếm số bài học đã hoàn thành
                                    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND course_id = ? AND is_completed = 1");
                                    $stmt2->bind_param("ii", $student_id, $course_id);
                                    $stmt2->execute();
                                    $stmt2->bind_result($completed_lessons);
                                    $stmt2->fetch();
                                    $stmt2->close();

                                    $completion_rate = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
                                    $avg_quiz_score = $avg_scores[$student_id] ?? 'N/A';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['fullname']) ?></td>
                                    <td><?= $completion_rate ?>%</td>
                                    <td><?= $avg_quiz_score ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="../courses/admin_courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/sidebar.js"></script>
</body>
</html>