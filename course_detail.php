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
    $insert_enrollment = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'pending')");
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
$enrolled_status = null;
$check_enrollment = $conn->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?");
$check_enrollment->bind_param("ii", $_SESSION['user_id'], $course_id);
$check_enrollment->execute();
$check_enrollment->store_result();

if ($check_enrollment->num_rows > 0) {
    $check_enrollment->bind_result($enrolled_status);
    $check_enrollment->fetch();
}


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
    <title><?= $title; ?> | BTEC FPT</title>
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
            width: 100%;
        }
        .main-content {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.10);
            padding: 40px 30px 30px 30px;
            position: relative;
            overflow: hidden;
        }
        .course-detail-header {
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
        .course-detail-header h2 {
            font-size: 2em;
            color: #fff;
            margin: 0;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(251,191,36,0.18);
        }
        .course-detail-header .user-info {
            font-size: 1.1em;
            color: #1e293b;
            font-weight: 700;
            text-shadow: 0 1px 4px #fff, 0 1px 8px #fbbf24;
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
        .course-description {
            font-size: 1.15em;
            color: #555;
            line-height: 1.8;
            margin-bottom: 40px;
        }
        .lessons-section {
            background: #f8f9fa;
            padding: 30px;
            margin-bottom: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1.5px solid #fde68a;
        }
        .lessons-section h3 {
            font-size: 1.4em;
            color: #2563eb;
            margin-bottom: 20px;
            border-bottom: 2px solid #fde68a;
            padding-bottom: 10px;
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
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1em;
        }
        .lessons-list li:last-child {
            border-bottom: none;
        }
        .lessons-list li a,
        .quiz-forum-links a {
            color: #fff; /* Luôn trắng mặc định */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s, background 0.2s;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            padding: 7px 18px;
            border-radius: 8px;
            margin-left: 18px;
            font-size: 0.98em;
            border: none;
            display: inline-block;
        }
        .lessons-list li a:hover,
        .quiz-forum-links a:hover {
            color: #2563eb;
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
        }
        /* Dark mode fix */
        .dark-mode .lessons-list li a,
        .dark-mode .quiz-forum-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1px solid #fbbf24;
        }
        .dark-mode .lessons-list li a:hover,
        .dark-mode .quiz-forum-links a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .quiz-forum-links {
            margin-top: 30px;
            display: flex;
            gap: 20px;
        }
        .quiz-forum-links a {
            /* XÓA DÒNG NÀY: color: #2563eb; */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s, background 0.2s;
            font-size: 1.1em;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            padding: 10px 22px;
            border-radius: 8px;
        }
        .quiz-forum-links a:hover {
            color: #fff;
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
        }
        .navigation-links {
            margin-top: 40px;
            font-size: 1.1em;
            display: flex;
            gap: 18px;
        }
        .navigation-links a {
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.18s;
            box-shadow: 0 2px 8px rgba(251,191,36,0.10);
        }
        .navigation-links a:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
            transform: translateY(-3px) scale(1.06);
            text-decoration: none;
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
        .dark-mode .course-detail-header h2 {
            color: #fbbf24;
        }
        .dark-mode .course-detail-header {
            background: linear-gradient(90deg, #2563eb 0%, #1e293b 100%);
        }
        .dark-mode .course-description {
            color: #fde68a;
        }
        .dark-mode .lessons-section {
            background-color: #1e293b;
            border-color: #fbbf24;
        }
        .dark-mode .lessons-section h3 {
            color: #fbbf24;
            border-bottom: 2px solid #fbbf24;
        }
        .dark-mode .lessons-list li {
            border-bottom-color: #fbbf24;
            color: #fde68a;
        }
        .dark-mode .lessons-list li a {
            color: #fbbf24;
            background: #1e293b;
            border: 1px solid #fbbf24;
        }
        .dark-mode .lessons-list li a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .enroll-section p {
            color: #a7f3d0;
        }
        .dark-mode .enroll-section form button {
            background: #1e293b;
            color: #fbbf24;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .enroll-section form button:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .quiz-forum-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .quiz-forum-links a:hover {
            background: #fbbf24;
            color: #1e293b;
        }
        .dark-mode .navigation-links a {
            color: #fbbf24;
            background: #1e293b;
            border: 1.5px solid #fbbf24;
        }
        .dark-mode .navigation-links a:hover {
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
        @media (max-width: 992px) {
            .sidebar { width: 220px; }
            .main-wrapper { margin-left: 220px; }
            .course-detail-header h2 { font-size: 1.8em; }
        }
        @media (max-width: 768px) {
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
            .course-detail-header { flex-direction: column; align-items: flex-start; }
            .course-detail-header h2 { margin-bottom: 10px; font-size: 1.8em; }
        }
        @media (max-width: 480px) {
            .sidebar ul li { width: 95%; }
            .sidebar ul li a { justify-content: flex-start; }
            .sidebar ul li a i { margin-right: 10px; margin-bottom: 0; }
        }
        .course-actions {
            display: flex;
            gap: 18px;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .quiz-forum-btn {
            color: #fff;
            background: linear-gradient(90deg, #2563eb 0%, #fbbf24 100%);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            font-size: 1.1em;
            border: none;
            display: inline-block;
        }
        .quiz-forum-btn:hover {
            background: linear-gradient(90deg, #fbbf24 0%, #2563eb 100%);
            color: #2563eb;
            box-shadow: 0 4px 16px rgba(251,191,36,0.18);
        }
        .quick-actions-row {
            display: flex;
            gap: 18px;
            margin-top: 18px;
            flex-wrap: wrap;
        }
        .quick-actions-row .quiz-forum-btn {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC FPT Logo">
        </div>
        <ul>
            <li><a href="courses.php" class="active"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    <div class="main-wrapper">
        <div class="main-content">
            <div class="course-detail-header">
                <h2><?= htmlspecialchars($title); ?></h2>
            </div>
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <p class="course-description"><?= htmlspecialchars($description); ?></p>
            <section class="lessons-section">
    <h3><i class="fas fa-list-alt"></i> Lessons:</h3>
    <?php if ($lessons_result->num_rows > 0): ?>
        <ul class="lessons-list">
            <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
                <li>
                    <?= htmlspecialchars($lesson['title']); ?>
                    <?php if ($enrolled_status == 'approved'): ?>
                        <a href="lesson.php?id=<?= $lesson['id']; ?>">View Lesson</a>
                    <?php else: ?>
                        <span style="color: #ccc; margin-left:12px;">(Enroll approved to access)</span>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No lessons available in this course.</p>
    <?php endif; ?>
</section>

            <div class="course-actions">
                <div class="enroll-section">
                    <?php if ($enrolled_status == 'approved'): ?>
    <p class="enrolled-message" style="color: #22c55e; font-weight: 600;">
        <i class="fas fa-check-circle"></i> You are enrolled in this course.
    </p>
    <div class="quick-actions-row">
        <a class="quiz-forum-btn" href="quiz_list.php?course_id=<?= $course_id ?>">View Quizzes</a>
        
    </div>

<?php elseif ($enrolled_status == 'pending'): ?>
    <p style="color: orange; font-weight: 600;">
        <i class="fas fa-clock"></i> Enrollment pending approval.
    </p>

<?php else: ?>
    <form method="post" action="" style="display:inline;">
        <button type="submit" name="enroll" class="quiz-forum-btn" style="margin-right: 10px;">
            <i class="fas fa-user-plus"></i> Enroll in this course
        </button>
    </form>
<?php endif; ?>

                </div>
            </div>
            <!-- XÓA PHẦN NÀY: -->
            <!--
            <div class="navigation-links">
                <a href="courses.php"><i class="fas fa-arrow-left"></i> Back to Courses</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
            </div>
            -->
            <hr style ="margin-top:30px; border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));">
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
