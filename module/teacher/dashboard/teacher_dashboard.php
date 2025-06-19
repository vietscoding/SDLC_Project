<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";

$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
$teacher_id = $_SESSION['user_id'];

// Fetch courses taught by the teacher
$courses_taught_query = "SELECT id, title FROM courses WHERE teacher_id = ?";
$stmt = $conn->prepare($courses_taught_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses_taught_result = $stmt->get_result();
$courses_taught = $courses_taught_result->fetch_all(MYSQLI_ASSOC);

// Fetch assignments to grade
$assignments_to_grade_query = "
    SELECT COUNT(DISTINCT asub.id) AS count_assignments
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ? AND asub.grade IS NULL;
";
$stmt = $conn->prepare($assignments_to_grade_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$assignments_to_grade_result = $stmt->get_result();
$assignments_to_grade = $assignments_to_grade_result->fetch_assoc();

// Fetch quizzes to grade (assuming quizzes need manual grading if 'score' is null or a specific status)
$quizzes_to_grade_query = "
    SELECT COUNT(DISTINCT qs.id) AS count_quizzes
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE c.teacher_id = ? AND qs.score IS NULL;
";
$stmt = $conn->prepare($quizzes_to_grade_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$quizzes_to_grade_result = $stmt->get_result();
$quizzes_to_grade = $quizzes_to_grade_result->fetch_assoc();

// Fetch quick stats: total students enrolled in teacher's courses
$total_students_query = "
    SELECT COUNT(DISTINCT e.user_id) AS total_students
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE c.teacher_id = ?;
";
$stmt = $conn->prepare($total_students_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$total_students_result = $stmt->get_result();
$total_students = $total_students_result->fetch_assoc();

// Calculate average progress of students in teacher's courses
$avg_progress_query = "
    SELECT AVG(course_progress) AS avg_overall_progress
    FROM (
        SELECT
            p.user_id,
            p.course_id,
            (COUNT(DISTINCT p.lesson_id) * 100.0 / total_lessons.count) AS course_progress
        FROM
            progress p
        JOIN
            courses c ON p.course_id = c.id
        JOIN
            (SELECT course_id, COUNT(id) AS count FROM lessons GROUP BY course_id) AS total_lessons
            ON p.course_id = total_lessons.course_id
        WHERE
            c.teacher_id = ? AND p.is_completed = 1
        GROUP BY
            p.user_id, p.course_id
    ) AS student_course_progress;
";
$stmt = $conn->prepare($avg_progress_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$avg_progress_result = $stmt->get_result();
$avg_progress = $avg_progress_result->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_dashboard.css">
</head>
<body>

    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="teacher-dashboard-header">
            <h2><i class="fas fa-tachometer-alt"></i> Teacher Dashboard</h2>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-chart-line"></i> Quick Stats</h3>
            <div class="quick-stats-grid">
                <div class="overview-item">
                    <i class="fas fa-book-open"></i>
                    <span>Active Courses</span>
                    <strong><?= count($courses_taught) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Total Students Enrolled</span>
                    <strong><?= htmlspecialchars($total_students['total_students']) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments to Grade</span>
                    <strong class="<?= ($assignments_to_grade['count_assignments'] > 0) ? 'danger' : ''; ?>">
                        <?= htmlspecialchars($assignments_to_grade['count_assignments']) ?>
                    </strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Quizzes to Grade</span>
                    <strong class="<?= ($quizzes_to_grade['count_quizzes'] > 0) ? 'danger' : ''; ?>">
                        <?= htmlspecialchars($quizzes_to_grade['count_quizzes']) ?>
                    </strong>
                </div>
                 <div class="overview-item">
                    <i class="fas fa-spinner"></i>
                    <span>Avg Student Progress</span>
                    <strong><?= round(htmlspecialchars($avg_progress['avg_overall_progress'] ?? 0), 2) ?>%</strong>
                </div>
            </div>
        </div>

        <div class="teacher-actions">
           
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-book-open"></i> Courses You Teach</h3>
            <ul class="course-list">
                <?php if ($courses_taught): ?>
                    <?php foreach ($courses_taught as $course): ?>
                        <li><?= htmlspecialchars($course['title']) ?></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No courses assigned yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            <div class="notification-list">
                <?php if ($sys_notif_result->num_rows > 0): ?>
                    <?php while($notification = $sys_notif_result->fetch_assoc()): ?>
                        <div class="notification-item system-notification">
                            <strong>System Notification:</strong> <?= htmlspecialchars($notification['message']) ?>
                            <small><?= date("M d, Y H:i", strtotime($notification['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notification-item">
                        <p>No system notifications.</p>
                    </div>
                <?php endif; ?>
                
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