<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #e0e7ff 100%);
            color: #222;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            transition: background 0.4s;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2563eb 60%, #fbbf24 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            box-shadow: 2px 0 20px rgba(37,99,235,0.10);
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: background 0.4s;
        }
        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%;
        }
        .sidebar .logo img {
            display: block;
            width: 70%;
            max-width: 150px;
            height: auto;
            margin: auto;
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
        }
        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            border-radius: 12px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #1e40af;
            border: 2px solid #fbbf24;
        }
        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #fde68a;
            transition: color 0.2s;
        }
        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #2563eb;
        }
        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
            background: transparent;
            transition: background 0.4s;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            border-radius: 16px 16px 0 0;
            box-shadow: 0 2px 8px rgba(251,191,36,0.08);
            padding: 20px 30px;
        }
        .dashboard-header h1 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .dashboard-header .user-info {
            font-size: 1.1em;
            color: #1e293b; /* Changed to a darker blue for readability */
            font-weight: 700;
            text-shadow: 0 1px 4px #fff, 0 1px 8px #fbbf24; /* Added text shadow for prominence */
        }
        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fde68a;
            color: #2563eb;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }
        .toggle-mode-btn:hover {
            background: #2563eb;
            color: #fde68a;
        }
        .notifications-section {
            background-color: #fef9c3;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(251,191,36,0.04);
            margin-bottom: 30px;
            border: 1px solid #fde68a;
        }
        .notifications-section h3 {
            font-size: 1.3em;
            color: #2563eb;
            margin-bottom: 15px;
            border-bottom: 1px solid #fde68a;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .notifications-section h3 i {
            color: #fbbf24;
            margin-right: 10px;
        }
        .notifications-section ul li {
            padding: 10px 0;
            border-bottom: 1px dashed #fde68a;
            font-size: 0.95em;
            color: #1e293b;
        }
        .notifications-section ul li strong {
            color: #2563eb;
            font-weight: 600;
            margin-right: 5px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 28px;
            margin-top: 35px;
        }
        .module-card {
            background: #fff;
            padding: 32px 24px 24px 24px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            text-align: left;
            transition:
                transform 0.25s cubic-bezier(.17,.67,.83,.67),
                box-shadow 0.25s,
                background 0.25s;
            border: 2px solid #fde68a;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .module-card:hover {
            transform: translateY(-10px) scale(1.06) rotate(-1deg);
            box-shadow: 0 16px 40px rgba(37,99,235,0.18);
            background: linear-gradient(120deg, #fde68a 0%, #2563eb 100%);
        }
        .module-card img {
            width: 54px;
            height: 54px;
            margin-bottom: 18px;
            filter: drop-shadow(0 2px 8px rgba(251,191,36,0.10));
            transition: transform 0.2s;
        }
        .module-card:hover img {
            transform: scale(1.12) rotate(-6deg);
        }
        .module-card h4 {
            font-size: 1.25em;
            color: #2563eb;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .module-card p {
            color: #1e293b;
            font-size: 1em;
            margin-bottom: 18px;
        }
        .module-card a {
            display: inline-block;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            border: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            font-size: 1em;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .module-card a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
        }
        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.85em;
            color: #2563eb;
            background-color: #fde68a;
            border-top: 1px solid #fbbf24;
            border-radius: 0 0 12px 12px;
        }
        footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 8px;
        }
        footer a:hover {
            text-decoration: underline;
            color: #fbbf24;
        }
        footer p { margin: 5px 0; }
        .contact-info { margin-top: 15px; }
        .contact-info p { margin: 3px 0; }

        /* Styles for the new content blocks */
        .dashboard-content-card {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee; /* Added a subtle border */
        }
        .dashboard-content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .dashboard-content-card h2 {
            color: #2563eb; /* Match the main color scheme */
            font-size: 1.6em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-weight: 700;
        }
        .dashboard-content-card h2 i {
            margin-right: 10px;
            color: #fbbf24; /* Accent color */
        }
        .dashboard-content-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .dashboard-content-card ul li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 1.05em;
            color: #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-content-card ul li:last-child {
            border-bottom: none;
        }
        .dashboard-content-card ul li .date {
            font-size: 0.9em;
            color: #777;
        }
        .dashboard-content-card p {
            font-size: 1.1em;
            color: #444;
            margin-bottom: 10px;
        }
        .dashboard-content-card .no-data {
            color: #777;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
        .progress-bar-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 20px;
            background-color: #28a745;
            border-radius: 5px;
            text-align: center;
            color: white;
            line-height: 20px;
            font-size: 0.85em;
            transition: width 0.5s ease-in-out;
        }

        /* Dark Mode */
        .dark-mode {
            background-color: #1e293b;
            color: #fde68a;
        }
        .dark-mode .sidebar {
            background: linear-gradient(135deg, #1e293b 60%, #fbbf24 100%);
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }
        .dark-mode .main-wrapper {
            background-color: #1e293b;
        }
        .dark-mode .main-content {
            background-color: #1e293b;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        .dark-mode .dashboard-header h1,
        .dark-mode .notifications-section h3,
        .dark-mode .module-card h4,
        .dark-mode .dashboard-content-card h2 { /* Added for new cards */
            color: #fbbf24;
        }
        .dark-mode .dashboard-header .user-info {
            color: #fde68a;
            text-shadow: 0 1px 4px #1e293b, 0 1px 8px #2563eb;
        }
        .dark-mode .notifications-section,
        .dark-mode .module-card,
        .dark-mode .dashboard-content-card { /* Added for new cards */
            background-color: #1e293b;
            border-color: #fbbf24;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .dark-mode .notifications-section h3 i,
        .dark-mode .dashboard-content-card h2 i { /* Added for new cards */
            color: #fbbf24;
        }
        .dark-mode .notifications-section ul li,
        .dark-mode .dashboard-content-card ul li { /* Added for new cards */
            border-bottom-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .notifications-section ul li strong,
        .dark-mode .dashboard-content-card p,
        .dark-mode .dashboard-content-card ul li .date { /* Added for new cards */
            color: #fde68a;
        }
        .dark-mode .module-card a,
        .dark-mode .dashboard-content-card .btn { /* Added for new cards */
            color: #fbbf24;
            background: #1e293b;
            border: 1px solid #fbbf24;
        }
        .dark-mode .module-card a:hover,
        .dark-mode .dashboard-content-card .btn:hover { /* Added for new cards */
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode footer {
            background-color: #1e293b;
            color: #fde68a;
            border-top-color: #fbbf24;
        }
        .dark-mode footer a {
            color: #fbbf24;
        }
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .dashboard-header h1 { font-size: 1.8em; }
            .module-card h4 { font-size: 1.3em; }
            .dashboard-content-card h2 { font-size: 1.4em; } /* Adjust for new cards */
        }
        @media (max-width: 768px) {
            body { flex-direction: column; } /* Changed to column for small screens */
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo { padding: 15px 0; }
            .sidebar .logo img { width: 50%; max-width: 120px; }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li { width: 48%; margin-bottom: 5px; }
            .sidebar ul li a {
                justify-content: center;
                padding: 10px;
                text-align: center;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span { display: block; font-size: 0.8em; }
            .main-wrapper { margin-left: 0; padding: 20px; }
            .dashboard-header { flex-direction: column; align-items: flex-start; }
            .dashboard-header h1 { margin-bottom: 10px; font-size: 1.8em; }
            .dashboard-header .user-info { font-size: 0.9em; }
            .toggle-mode-btn { top: 10px; right: 20px; } /* Adjust button position */
            .module-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
            .module-card { padding: 20px; }
            .module-card h4 { font-size: 1.2em; }
            .module-card p { font-size: 0.85em; }
            .module-card a { font-size: 0.85em; padding: 6px 12px; }
            .dashboard-content-card { padding: 20px; } /* Adjust for new cards */
            .dashboard-content-card h2 { font-size: 1.2em; } /* Adjust for new cards */
            .dashboard-content-card p, .dashboard-content-card ul li { font-size: 0.9em; } /* Adjust for new cards */
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
            .module-grid { grid-template-columns: 1fr; } /* Single column for small screens */
            .dashboard-content-card { margin-bottom: 15px; } /* Spacing for new cards */
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_forum_courses.php"><i class="fas fa-comments"></i> <span>Course Forum</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Welcome, <?= $fullname; ?></h1>
                <div class="user-info">
                    <i class="fas fa-user-graduate"></i> Role: <?= $role; ?>
                </div>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <?php if ($sys_notif_result->num_rows > 0): ?>
                <section class="notifications-section">
                    <h3><i class="fas fa-bullhorn"></i> Announcements</h3>
                    <ul>
                        <?php while ($notif = $sys_notif_result->fetch_assoc()): ?>
                            <li><strong><?= date('M d, Y', strtotime($notif['created_at'])); ?></strong> - <?= htmlspecialchars($notif['message']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                    <?php
                    // Reset the pointer for sys_notif_result to reuse it
                    $sys_notif_result->data_seek(0);
                    if ($conn->query("SELECT message FROM system_notifications")->num_rows > 5): ?>
                        <p style="margin-top: 10px; text-align: right;"><a href="notifications.php">View All Announcements</a></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="module-grid">
                <div class="dashboard-content-card">
                    <h2><i class="fas fa-book-open"></i> Enrolled Courses</h2>
                    <?php if (!empty($courses_in_progress)): ?>
                        <ul>
                            <?php foreach ($courses_in_progress as $course): ?>
                                <li>
                                    <span><?= htmlspecialchars($course['title']); ?></span>
                                    <a href="course_detail.php?course_id=<?= $course['id']; ?>" class="btn btn-sm btn-info">Details</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data">You are not enrolled in any courses yet.</p>
                    <?php endif; ?>
                </div>

                <div class="dashboard-content-card">
                    <h2><i class="fas fa-question-circle"></i> Quizzes</h2>
                    <p>Upcoming Quizzes: <strong><?= $upcoming_quizzes_count; ?></strong></p>
                    <p>Unattempted Quizzes: <strong><?= $unattempted_quizzes_count; ?></strong></p>
                    <p class="no-data">Check the Quizzes page for more details.</p>
                </div>

                <div class="dashboard-content-card">
                    <h2><i class="fas fa-clipboard-list"></i> Upcoming Assignment Deadlines</h2>
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

                 <div class="dashboard-content-card">
                    <h2><i class="fas fa-chart-line"></i> Learning Progress</h2>
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
                                    <small>(<?= $progress['completed_lessons']; ?>/<?= $progress['total_lessons']; ?> lessons)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data">No learning progress available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr style ="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
        <footer>
            <a href="https://www.facebook.com/btecfptdn/?locale=vi_VN" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
            |
            <a href="https://international.fpt.edu.vn/" target="_blank"><i class="fas fa-globe"></i> Website</a>
            |
            <a href="tel:02473099588"><i class="fas fa-phone"></i> 024 730 99 588</a>
            <br>
            <p>Address: 66 Vo Van Tan, Thanh Khe District, Da Nang</p>
            <div class="contact-info">
                <p>Email:</p>
                <p>Academic Department: <a href="mailto:Academic.btec.dn@fe.edu.vn">Academic.btec.dn@fe.edu.vn</a></p>
                <p>SRO Department: <a href="mailto:sro.btec.dn@fe.edu.vn">sro.btec.dn@fe.edu.vn</a></p>
                <p>Finance Department: <a href="mailto:accounting.btec.dn@fe.edu.vn">accounting.btec.dn@fe.edu.vn</a></p>
            </div>
            <p>&copy; <?= date('Y'); ?> BTEC FPT - Learning Management System.</p>
            <small>Powered by Innovation in Education</small>
        </footer>
    </div>
    <script>
        // Toggle dark/light mode
        const btn = document.getElementById('toggleModeBtn');
        btn.onclick = function() {
            document.body.classList.toggle('dark-mode');
            btn.innerHTML = document.body.classList.contains('dark-mode')
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };
    </script>
</body>
</html>