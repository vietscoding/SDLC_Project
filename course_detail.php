<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

include "includes/db_connect.php";
$course_id = $_GET['course_id'];
if (!isset($_GET['course_id'])) {
    echo "Missing course ID.";
    exit;
}
$course_id = intval($_GET['course_id']); // đảm bảo là số




// Lấy tên khóa học
$stmt = $conn->prepare("SELECT title, teacher_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($course_title, $teacher_id);
$stmt->fetch();
$stmt->close();

// Handle enrollment
if (isset($_POST['enroll'])) {
    $insert_enrollment = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
    $insert_enrollment->bind_param("ii", $_SESSION['user_id'], $course_id);
    if ($insert_enrollment->execute()) {
        // Sau khi enroll thành công → chèn notification cho học viên
        $message = "You have successfully enrolled in the course: '$course_title'.";
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify_stmt->bind_param("is", $_SESSION['user_id'], $message);
        $notify_stmt->execute();
        $notify_stmt->close();

        // Tạo thông báo cho giáo viên
        $notif_msg = $_SESSION['fullname'] . " has enrolled in your course: " . $course_title;
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $teacher_id, $notif_msg);
        $stmt->execute();
        $stmt->close();

        // Reload page to update enrollment status
        header("Location: course_detail.php?course_id=$course_id");
        exit;
    } else {
        echo "Enrollment failed.";
    }
    $insert_enrollment->close();
}


// Check if user already enrolled
$enrolled = false;
$check_enrollment = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
$check_enrollment->bind_param("ii", $_SESSION['user_id'], $course_id);
$check_enrollment->execute();
$check_enrollment->store_result();

if ($check_enrollment->num_rows > 0) {
        $enrolled = true;
}
$check_enrollment->close();

// Fetch course info
$stmt = $conn->prepare("SELECT title, description FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Course not found.";
    exit;
}

$stmt->bind_result($title, $description);
$stmt->fetch();
$stmt->close();

// Fetch lessons in this course
$lesson_query = $conn->prepare("SELECT id, title FROM lessons WHERE course_id = ?");
$lesson_query->bind_param("i", $course_id);
$lesson_query->execute();
$lessons_result = $lesson_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title; ?> | [Your University Name]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa; /* Light grey background */
            color: #333;
            line-height: 1.7; /* Increased line height for better readability */
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 280px;
            background-color: #0056b3;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 60px;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .sidebar .logo img {
            display: block;
            width: 80%;
            height: auto;
            margin:auto;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-left: 5px solid transparent;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #fbc531;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            font-size: 1.2em;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
        }

        .course-detail-header {
            margin-bottom: 40px; /* Increased spacing */
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .course-detail-header h2 {
            font-size: 2.5em; /* Larger heading */
            color: #2c3e50; /* Darker heading color */
            margin: 0;
            font-weight: 600; /* Make heading bold */
        }

        .course-description {
            font-size: 1.15em; /* Slightly larger font */
            color: #555;
            line-height: 1.8; /* Increased line height */
            margin-bottom: 40px; /* Increased spacing */
        }

        .lessons-section {
            background-color: #f8f9fa; /* Lighter background for lessons */
            padding: 30px;
            margin-bottom: 40px; /* Increased spacing */
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #eee; /* Added border */
        }

        .lessons-section h3 {
            font-size: 1.8em; /* Larger heading */
            color: #0056b3;
            margin-bottom: 25px; /* Increased spacing */
            border-bottom: 2px solid #d3d3d3; /* Lighter border */
            padding-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .lessons-section h3 i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .lessons-list {
            list-style: none;
            padding-left: 0;
        }

        .lessons-list li {
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0; /* Lighter border */
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1em; /* Slightly larger font */
        }

        .lessons-list li:last-child {
            border-bottom: none;
        }

        .lessons-list li a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .lessons-list li a:hover {
            color: #004080;
            text-decoration: underline;
        }

        .enroll-section {
            margin-bottom: 40px; /* Increased spacing */
        }

        .enroll-section p {
            font-size: 1.2em; /* Larger font */
            color: #28a745;
            margin-bottom: 20px; /* Increased spacing */
            font-weight: 600;
        }

        .enroll-section form button {
            background-color: #0056b3;
            color: white;
            padding: 15px 30px; /* Increased padding */
            border: none;
            border-radius: 8px; /* More rounded corners */
            font-size: 1.2em; /* Larger font */
            cursor: pointer;
            transition: background-color 0.2s ease, box-shadow 0.2s ease; /* Added box-shadow transition */
        }

        .enroll-section form button:hover {
            background-color: #004080;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow on hover */
        }

        .navigation-links {
            margin-top: 40px; /* Increased spacing */
            font-size: 1.1em; /* Larger font */
        }

        .navigation-links a {
            color: #0056b3;
            text-decoration: none;
            margin-right: 25px; /* Increased spacing */
            font-weight: 600; /* Make links bold */
        }

        .navigation-links a:hover {
            text-decoration: underline;
            color: #004080;
        }

       footer{
        text-align: center;
            padding: 30px;
            margin-top: 40px;
            font-size: 0.9em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px;
        }

        footer a {
            color: #0056b3;
            text-decoration: none;
            margin: 0 5px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer p {
            margin: 5px 0;
        }

        footer .contact-info {
            margin-top: 15px;
        }

        footer .contact-info p {
            margin: 3px 0;
        }

        /* Dark Mode Footer */
        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }

        .dark-mode footer a {
            color: #fbc531;
        }


        .quiz-forum-links {
            margin-top: 30px;
            display: flex;
            gap: 20px;
        }

        .quiz-forum-links a {
            color: #0056b3;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
            font-size: 1.1em;
        }

        .quiz-forum-links a:hover {
            color: #004080;
            text-decoration: underline;
        }

        /* Dark Mode (Optional - Add a class 'dark-mode' to the body) */
        .dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #222;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-content {
            background-color: #333;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .course-detail-header h2,
        .dark-mode .lessons-section h3 {
            color: #f8f9fa;
        }

        .dark-mode .course-description {
            color: #ccc;
        }

        .dark-mode .lessons-section {
            background-color: #444;
            border-color: #555;
        }

        .dark-mode .lessons-list li {
            border-bottom-color: #555;
            color: #eee;
        }

        .dark-mode .lessons-list li a {
            color: #fbc531;
        }

        .dark-mode .lessons-list li a:hover {
            color: #fbb003;
        }

        .dark-mode .enroll-section p {
            color: #a7f3d0;
        }

        .dark-mode .enroll-section form button {
            background-color: #fbc531;
            color: #222;
        }

        .dark-mode .enroll-section form button:hover {
            background-color: #fbb003;
            color: #222;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #fbb003;
            text-decoration: underline;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
        }

        .dark-mode .quiz-forum-links a {
            color: #fbc531;
        }

        .dark-mode .quiz-forum-links a:hover {
            color: #fbb003;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="courses.php" class="active"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> Academic Progress</a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> Grades & Results</a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="course-detail-header">
            <h2><?= htmlspecialchars($title); ?></h2>
        </div>
        <p class="course-description"><?= htmlspecialchars($description); ?></p>

        <section class="lessons-section">
            <h3><i class="fas fa-list-alt"></i> Lessons:</h3>
            <?php if ($lessons_result->num_rows > 0): ?>
                <ul class="lessons-list">
                    <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
                        <li>
                            <?= htmlspecialchars($lesson['title']); ?>
                            <a href="lesson.php?id=<?= $lesson['id']; ?>">View Lesson</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No lessons available in this course.</p>
            <?php endif; ?>
        </section>

        <section class="enroll-section">
            <?php if ($enrolled): ?>
                <p><strong>You are already enrolled in this course.</strong></p>
            <?php else: ?>
                <form method="post" action="">
                    <button type="submit" name="enroll">Enroll in this course</button>
                </form>
            <?php endif; ?>
        </section>

        <div class="quiz-forum-links">
            <a href="quiz_list.php?course_id=<?= $course_id ?>">View Quizzes</a>
            <a href="forum.php?course_id=<?= $course_id ?>">Go to Course Forum</a>
        </div>

        <div class="navigation-links">
            <a href="courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <hr style ="margin-top:30px; ">
            <footer>
            <a href="https://www.facebook.com/btecfptdn/?locale=vi_VN" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
            |
            <a href="https://international.fpt.edu.vn/" target="_blank"><i class="fas fa-globe"></i> Website</a>
            |
            <a href="tel:02473099588"><i class="fas fa-phone"></i> 024 730 99 588</a>
<br>
            <p>Address: 66 Võ Văn Tần, Quận Thanh Khê, Đà Nẵng</p>
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

</body>
</html>
