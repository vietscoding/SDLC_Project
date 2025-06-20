<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);
$teacher_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($course_title);
if (!$stmt->fetch()) {
    echo "You are not allowed to access this course analytics.";
    exit;
}
$stmt->close();

// Tổng số học viên đã enroll
$stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM enrollments WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total_students'];
$stmt->close();

// Tổng số bài học
$stmt = $conn->prepare("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_lessons = $result->fetch_assoc()['total_lessons'];
$stmt->close();

// Tỉ lệ hoàn thành bài học trung bình (%)
$avg_progress = 0;
if ($total_lessons > 0 && $total_students > 0) {
    // Corrected query to avoid ONLY_FULL_GROUP_BY issue
    // This calculates each student's completion percentage and then averages those percentages.
    $avg_progress_query = "
        SELECT AVG(student_progress.completion_percentage) AS avg_completion
        FROM (
            SELECT
                p.user_id,
                (COUNT(DISTINCT p.lesson_id) * 100.0 / ?) AS completion_percentage
            FROM
                progress p
            WHERE
                p.course_id = ? AND p.is_completed = 1
            GROUP BY
                p.user_id
        ) AS student_progress;
    ";
    $stmt = $conn->prepare($avg_progress_query);
    // Bind $total_lessons for the division, and $course_id for filtering
    // 'd' for float/double (for division), 'i' for integer (for course_id)
    $stmt->bind_param("di", $total_lessons, $course_id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $avg_progress = round($result->fetch_assoc()['avg_completion'] ?? 0, 2);
    $stmt->close();
}


// Điểm quiz trung bình
$stmt = $conn->prepare("
    SELECT AVG(score) AS avg_score
    FROM quiz_submissions
    WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) AND score IS NOT NULL
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_score_row = $result->fetch_assoc();
$avg_score = $avg_score_row['avg_score'] !== null ? round($avg_score_row['avg_score'], 2) : 'N/A';
$stmt->close();

// Tổng số assignments trong khóa
$stmt = $conn->prepare("SELECT COUNT(*) AS total_assignments FROM assignments WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_assignments = $result->fetch_assoc()['total_assignments'];
$stmt->close();

// Tổng số bài nộp assignments
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_submissions FROM assignment_submissions
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_submissions = $result->fetch_assoc()['total_submissions'];
$stmt->close();

// Điểm trung bình assignments
$stmt = $conn->prepare("
    SELECT AVG(grade) AS avg_grade FROM assignment_submissions
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) AND grade IS NOT NULL
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_grade_row = $result->fetch_assoc();
$avg_grade = $avg_grade_row['avg_grade'] !== null ? round($avg_grade_row['avg_grade'], 2) : 'N/A';
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_analytics.css">
   
</head>
<body>

    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="teacher-dashboard-header">
            <h2><i class="fas fa-chart-line"></i> Analytics for: <?= htmlspecialchars($course_title) ?></h2>
            <a href="../courses/teacher_courses.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-chart-line"></i> Course Statistics</h3>
            <div class="quick-stats-grid">
                <div class="overview-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Total Students Enrolled</span>
                    <strong><?= htmlspecialchars($total_students) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-book-reader"></i>
                    <span>Average Lesson Completion</span>
                    <strong><?= htmlspecialchars($avg_progress) ?>%</strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Total Lessons</span>
                    <strong><?= htmlspecialchars($total_lessons) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-tasks"></i>
                    <span>Total Assignments</span>
                    <strong><?= htmlspecialchars($total_assignments) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Average Quiz Score</span>
                    <strong><?= htmlspecialchars($avg_score) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-marker"></i>
                    <span>Average Assignment Grade</span>
                    <strong><?= htmlspecialchars($avg_grade) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-upload"></i>
                    <span>Total Assignment Submissions</span>
                    <strong><?= htmlspecialchars($total_submissions) ?></strong>
                </div>
            </div>
        </div>
        
        <div class="logout-link">
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>

</body>
</html>