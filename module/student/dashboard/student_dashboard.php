<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";
$user_id = $_SESSION['user_id']; // Get current user ID

// Fetch system notifications (already existing)
$sys_notif_result = $conn->query("SELECT message, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");

$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);

// --- Data for New Modules ---

// 1. Courses in Progress
$courses_in_progress = [];
$courses_query = "
    SELECT c.id, c.title, c.description
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ?
";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses_in_progress[] = $row;
}
$stmt->close();

// 2. Upcoming/Unattempted Quizzes
$upcoming_quizzes_count = 0;
$unattempted_quizzes_count = 0;
$current_date = date('Y-m-d H:i:s');

// Get all quizzes for enrolled courses
$quizzes_query = "
    SELECT q.id, q.title, q.deadline, c.title as course_title
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ?
";
$stmt = $conn->prepare($quizzes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_quizzes_result = $stmt->get_result();
$stmt->close();

$all_quizzes = [];
while ($row = $all_quizzes_result->fetch_assoc()) {
    $all_quizzes[] = $row;
}

foreach ($all_quizzes as $quiz) {
    // Check if quiz is upcoming
    if ($quiz['deadline'] && strtotime($quiz['deadline']) > strtotime($current_date)) {
        $upcoming_quizzes_count++;
    }

    // Check if quiz is unattempted
    $submission_check_query = "
        SELECT COUNT(*) as count
        FROM quiz_submissions
        WHERE user_id = ? AND quiz_id = ?
    ";
    $stmt_sub = $conn->prepare($submission_check_query);
    $stmt_sub->bind_param("ii", $user_id, $quiz['id']);
    $stmt_sub->execute();
    $sub_result = $stmt_sub->get_result()->fetch_assoc();
    $stmt_sub->close();

    if ($sub_result['count'] == 0) {
        $unattempted_quizzes_count++;
    }
}


// 3. Upcoming Assignment Deadlines
$upcoming_assignments = [];
$assignments_query = "
    SELECT a.id, a.title, a.due_date, c.title as course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ? AND a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 5
";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_assignments[] = $row;
}
$stmt->close();


// 5. Learning Progress
$learning_progress_data = [];
foreach ($courses_in_progress as $course) {
    $course_id = $course['id'];

    // Total lessons in the course
    $total_lessons_query = "
        SELECT COUNT(*) as total_lessons
        FROM lessons
        WHERE course_id = ?
    ";
    $stmt = $conn->prepare($total_lessons_query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $total_lessons = $stmt->get_result()->fetch_assoc()['total_lessons'];
    $stmt->close();

    // Completed lessons for the user in this course
    $completed_lessons_query = "
        SELECT COUNT(*) as completed_lessons
        FROM progress
        WHERE user_id = ? AND course_id = ? AND is_completed = 1
    ";
    $stmt = $conn->prepare($completed_lessons_query);
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $completed_lessons = $stmt->get_result()->fetch_assoc()['completed_lessons'];
    $stmt->close();

    $progress_percentage = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100) : 0;

    $learning_progress_data[] = [
        'course_title' => $course['title'],
        'total_lessons' => $total_lessons,
        'completed_lessons' => $completed_lessons,
        'progress_percentage' => $progress_percentage
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_dashboard.css">
</head>
<body>

    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="student-dashboard-header">
            <h2><i class="fas fa-tachometer-alt"></i> Welcome, <?= $fullname; ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> Role: <?= $role; ?>
            </div>
        </div>

        <div class="module-grid">
            <div class="module-card">
                <h4><i class="fas fa-book-open"></i> Enrolled Courses</h4>
                <?php if (!empty($courses_in_progress)): ?>
                    <ul>
                        <?php foreach ($courses_in_progress as $course): ?>
                            <li>
                                <span><?= htmlspecialchars($course['title']); ?></span>
                                <a href="../courses/course_detail.php?course_id=<?= $course['id']; ?>" class="btn">Details</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data">You are not enrolled in any courses yet.</p>
                <?php endif; ?>
            </div>

            <div class="module-card">
                <h4><i class="fas fa-clipboard-list"></i> Upcoming Assignment Deadlines</h4>
                <?php if (!empty($upcoming_assignments)): ?>
                    <ul>
                        <?php foreach ($upcoming_assignments as $assignment): ?>
                            <li>
                                <span><?= htmlspecialchars($assignment['title']); ?> (<?= htmlspecialchars($assignment['course_title']); ?>)</span>
                                <span class="date">Due: <?= date('d/m/Y', strtotime($assignment['due_date'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data">No upcoming assignments.</p>
                <?php endif; ?>
            </div>

            <div class="module-card">
                <h4><i class="fas fa-question-circle"></i> Quiz Overview</h4>
                <p><strong>Upcoming Quizzes:</strong> <?= $upcoming_quizzes_count; ?></p>
                <p><strong>Unattempted Quizzes:</strong> <?= $unattempted_quizzes_count; ?></p>
                <p class="no-data"></p>
            </div>

            <div class="module-card">
                <h4><i class="fas fa-chart-line"></i> Learning Progress</h4>
                <?php if (!empty($learning_progress_data)): ?>
                    <ul>
                        <?php foreach ($learning_progress_data as $progress): ?>
                            <li>
                                <span><?= htmlspecialchars($progress['course_title']); ?></span>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?= $progress['progress_percentage']; ?>%;">
                                        <?= $progress['progress_percentage']; ?>%
                                    </div>
                                </div>
                                <div class="progress-bar-text">
                                    <small>Completed: <?= $progress['completed_lessons']; ?></small>
                                    <small>Total: <?= $progress['total_lessons']; ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data">No learning progress available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($sys_notif_result->num_rows > 0): ?>
            <div class="dashboard-section">
                <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                <div class="notification-list">
                    <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                        <div class="notification-item system-notification">
                            <strong>System Notification:</strong> <?= htmlspecialchars($notif['message']); ?>
                            <small><?= date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php
                // Reset the pointer for sys_notif_result to reuse it
                $sys_notif_result->data_seek(0);
                if ($conn->query("SELECT message FROM system_notifications")->num_rows > 5): ?>
                    <p class="view-all-link"><a href="../notification/notifications.php">View All Announcements</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="logout-link">
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/student_sidebar.js"></script> </body>
</html>