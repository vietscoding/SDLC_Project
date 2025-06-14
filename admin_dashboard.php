<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Thống kê số liệu
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$total_teachers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'teacher'")->fetch_assoc()['total'];
$total_quizzes  = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];
$total_submissions = $conn->query("SELECT COUNT(*) AS total FROM quiz_submissions")->fetch_assoc()['total'];
$total_assignment_submissions = $conn->query("SELECT COUNT(*) AS total FROM assignment_submissions")->fetch_assoc()['total'];

$progress_result = $conn->query("
    SELECT 
        ROUND(AVG(completed.lesson_completed / total.total_lessons) * 100, 2) AS avg_progress
    FROM 
        (SELECT user_id, COUNT(*) AS lesson_completed FROM progress WHERE is_completed = 1 GROUP BY user_id) AS completed
    JOIN 
        (SELECT COUNT(*) AS total_lessons FROM lessons) AS total
");
$avg_progress = $progress_result->fetch_assoc()['avg_progress'] ?? 0;

$total_lessons_res = $conn->query("SELECT COUNT(*) AS total FROM lessons");
$total_lessons = $total_lessons_res->fetch_assoc()['total'] ?? 0;

$completed_students = 0;
$total_students_in_progress = 0;

if ($total_lessons > 0) {
    $students_progress = $conn->query("
        SELECT user_id, COUNT(*) AS completed
        FROM progress
        WHERE is_completed = 1
        GROUP BY user_id
    ");
    while ($row = $students_progress->fetch_assoc()) {
        $total_students_in_progress++;
        if ($row['completed'] == $total_lessons) {
            $completed_students++;
        }
    }
    $completion_rate = $total_students_in_progress > 0 ? round(($completed_students / $total_students_in_progress) * 100, 2) : 0;
} else {
    $completion_rate = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    
    <style>
    /* CSS ĐẶC TRƯNG CHO ADMIN DASHBOARD */
    /* Basic Reset & Base Styles (chỉ giữ lại những gì đặc trưng cho main-content) */
    /* Lưu ý: Các biến :root, body, * đã được định nghĩa trong style.css.
        Bạn chỉ cần các quy tắc cho main-content và các phần tử con của nó. */

    /* Main Content Area */
    .main-content {
        margin-left: 280px; /* Phải khớp với sidebar width trong style.css */
        padding: 30px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background-color: var(--background-light);
        transition: margin-left 0.3s ease;
    }

    /* Admin Dashboard Header */
    .admin-dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color); /* Sử dụng biến từ style.css */
    }

    .admin-dashboard-header h2 {
        font-size: 2.2em;
        color: var(--text-dark);
        margin: 0;
        display: flex;
        align-items: center;
        font-weight: 600;
    }

    .admin-dashboard-header h2 i {
        margin-right: 12px;
        color: var(--primary-color);
        font-size: 1.1em;
    }

    /* System Overview Grid */
    .system-overview {
        background-color: var(--background-card);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 20px var(--shadow-light);
        margin-bottom: 30px;
        /* display: grid;  Không cần grid ở đây nữa vì đã có system-overview-content */
        /* grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); Không cần ở đây nữa */
        gap: 25px; /* Giữ gap chung cho các phần tử con */
        position: relative;
    }

    .system-overview > h3 {
        position: absolute;
        top: 25px;
        left: 30px;
        font-size: 1.6em;
        color: var(--text-dark);
        font-weight: 600;
        display: flex;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
        width: calc(100% - 60px);
        margin-bottom: 20px;
    }

    .system-overview > h3 i {
        margin-right: 10px;
        color: var(--primary-color);
        font-size: 1em;
    }

    .system-overview-content {
        margin-top: 60px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 25px;
        width: 100%;
    }

    .overview-item {
        background-color: var(--background-light);
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
        border: 1px solid var(--border-color);
    }

    .overview-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px var(--shadow-medium);
    }

    .overview-item i {
        font-size: 3em;
        margin-bottom: 15px;
        color: var(--primary-color);
        transition: color 0.3s ease;
    }
    
    .overview-item:hover i {
        color: var(--accent-color);
    }

    .overview-item span {
        display: block;
        font-size: 1em;
        color: var(--text-medium);
        margin-bottom: 8px;
        font-weight: 500;
    }

    .overview-item strong {
        display: block;
        font-size: 2em;
        font-weight: 700;
        color: var(--text-dark);
        letter-spacing: -0.5px;
    }

    /* Admin Actions */
    .admin-actions {
        background-color: var(--background-card);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 20px var(--shadow-light);
        margin-bottom: 30px;
    }

    .admin-actions h3 {
        font-size: 1.8em;
        color: var(--text-dark);
        margin-top: 0;
        margin-bottom: 25px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
        display: flex;
        align-items: center;
        font-weight: 600;
    }

    .admin-actions h3 i {
        margin-right: 12px;
        color: var(--accent-color);
        font-size: 1.1em;
    }

    .admin-actions ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .admin-actions li {
        background-color: var(--background-light);
        padding: 15px 20px;
        border-radius: 8px;
        transition: background-color 0.3s ease, transform 0.2s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
    }

    .admin-actions li:hover {
        background-color: #e6f7ff;
        transform: translateY(-3px);
    }

    .admin-actions li a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .admin-actions li a:hover {
        color: var(--primary-color);
    }

    .admin-actions li a i {
        margin-right: 10px;
        font-size: 1.1em;
        color: var(--primary-color);
        transition: color 0.3s ease;
    }

    .admin-actions li a:hover i {
        color: var(--primary-color);
    }

    /* Logout Link */
    .logout-link {
        margin-top: 20px;
        text-align: center;
    }

    .logout-link a {
        display: inline-flex;
        align-items: center;
        padding: 12px 30px;
        background-color: var(--accent-color);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
    }

    .logout-link a:hover {
        background-color: #c82333;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
    }

    .logout-link a i {
        margin-right: 10px;
        font-size: 1.1em;
    }

    /* Dark Mode (Specific to Dashboard elements) */
    body.dark-mode { /* Cần định nghĩa lại nếu muốn ghi đè màu nền chính cho trang cụ thể */
        background-color: #2c3e50; /* Ghi đè màu nền body cho dark mode của trang dashboard */
        color: #ecf0f1;
    }
    
    .dark-mode .main-content {
        background-color: #2c3e50;
    }

    .dark-mode .admin-dashboard-header h2 {
        color: #ecf0f1;
    }

    .dark-mode .admin-dashboard-header h2 i {
        color: #f1c40f; /* Yellow accent in dark mode */
    }

    .dark-mode .system-overview {
        background-color: #34495e;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        border-color: #444;
    }

    .dark-mode .system-overview > h3 {
        color: #ecf0f1;
        border-bottom-color: #444;
    }

    .dark-mode .system-overview > h3 i {
        color: #f1c40f;
    }

    .dark-mode .overview-item {
        background-color: #495e74;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        border-color: #555;
    }

    .dark-mode .overview-item i {
        color: #f1c40f;
    }

    .dark-mode .overview-item span {
        color: #bdc3c7;
    }

    .dark-mode .overview-item strong {
        color: #ecf0f1;
    }

    .dark-mode .admin-actions {
        background-color: #34495e;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .dark-mode .admin-actions h3 {
        color: #ecf0f1;
        border-bottom-color: #444;
    }

    .dark-mode .admin-actions h3 i {
        color: #f1c40f;
    }

    .dark-mode .admin-actions li {
        background-color: #495e74;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .dark-mode .admin-actions li:hover {
        background-color: #5d748f;
    }

    .dark-mode .admin-actions li a {
        color: #85c1e9;
    }
    .dark-mode .admin-actions li a i {
        color: #85c1e9;
    }

    .dark-mode .logout-link a {
        background-color: #e74c3c;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.2);
    }

    .dark-mode .logout-link a:hover {
        background-color: #c0392b;
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
    }

    /* Responsive Adjustments (Specific to Dashboard elements) */
    /* Các quy tắc responsive ở đây sẽ áp dụng riêng cho Dashboard,
        còn sidebar và footer sẽ dùng responsive từ style.css */
    @media (max-width: 1024px) {
        /* .main-content margin-left đã được xử lý bởi responsive của sidebar trong style.css */
        .system-overview-content {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .admin-actions ul {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Giữ cho action items lớn hơn một chút */
        }
        .overview-item i {
            font-size: 2.5em; /* Giảm kích thước icon */
        }
        .overview-item strong {
            font-size: 1.8em; /* Giảm kích thước số liệu */
        }
        .overview-item span {
            font-size: 0.9em; /* Giảm kích thước chữ mô tả */
        }
        .admin-actions li a {
            font-size: 0.9em; /* Giảm kích thước chữ trong nút hành động */
        }
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0; /* Loại bỏ margin khi sidebar ẩn */
            padding: 20px; /* Giảm padding tổng thể của main-content */
            padding-top: 80px; /* Vẫn giữ khoảng trống cho nút burger */
        }
        /* Dashboard Header */
        .admin-dashboard-header {
            flex-direction: column; /* Stack header elements */
            align-items: flex-start; /* Align title to start */
            margin-bottom: 20px;
        }
        .admin-dashboard-header h2 {
            font-size: 1.8em;
            margin-bottom: 10px; /* Khoảng cách với các phần tử khác nếu có */
            text-align: left;
        }
        /* .admin-dashboard-header .some-button-group { */ /* Nếu có nhóm nút ở header */
            /* width: 100%;
            display: flex;
            justify-content: flex-start;
            gap: 10px; */
        /* } */


        /* System Overview */
        .system-overview {
            padding: 15px; /* Giảm padding */
            margin-bottom: 20px;
        }
        .system-overview > h3 {
            position: static; /* Đặt lại vị trí header */
            text-align: left; /* Căn trái tiêu đề */
            width: auto;
            margin-bottom: 15px;
            padding-bottom: 8px;
            font-size: 1.4em; /* Giảm kích thước tiêu đề */
        }
        .system-overview-content {
            margin-top: 0; /* Đặt lại margin-top */
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); /* Giảm min-width */
            gap: 15px; /* Giảm khoảng cách */
        }
        .overview-item {
            padding: 12px; /* Giảm padding */
        }
        .overview-item i {
            font-size: 2em; /* Giảm kích thước icon */
            margin-bottom: 10px;
        }
        .overview-item span {
            font-size: 0.8em; /* Giảm kích thước chữ mô tả */
        }
        .overview-item strong {
            font-size: 1.3em; /* Giảm kích thước số liệu */
        }

        /* Admin Actions */
        .admin-actions {
            padding: 15px; /* Giảm padding */
            margin-bottom: 20px;
        }
        .admin-actions h3 {
            font-size: 1.4em;
            margin-bottom: 15px;
            padding-bottom: 8px;
        }
        .admin-actions ul {
            grid-template-columns: 1fr; /* Stack các nút hành động */
            gap: 10px; /* Giảm khoảng cách */
        }
        .admin-actions li {
            padding: 12px 15px; /* Giảm padding */
        }
        .admin-actions li a {
            font-size: 0.9em; /* Giảm kích thước chữ */
        }
        .admin-actions li a i {
            font-size: 1em; /* Giảm kích thước icon */
        }

        /* Logout Link */
        .logout-link a {
            padding: 10px 20px; /* Giảm padding */
            font-size: 0.95em; /* Giảm font-size */
            width: auto; /* Để nút không chiếm toàn bộ chiều rộng */
        }
    }

    @media (max-width: 480px) {
        .admin-dashboard-header h2 {
            font-size: 1.4em;
        }
        .system-overview-content {
            grid-template-columns: 1fr; /* Mỗi item một hàng */
        }
        .overview-item {
            padding: 10px;
        }
        .overview-item i {
            font-size: 1.8em;
        }
        .overview-item strong {
            font-size: 1.2em;
        }
        .overview-item span {
            font-size: 0.75em;
        }
        .admin-actions h3 {
            font-size: 1.3em;
        }
        .admin-actions li a {
            font-size: 0.85em;
        }
        .logout-link a {
            font-size: 0.9em;
            padding: 8px 15px;
        }
    }
</style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-dashboard-header">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
    </div>

    <div class="system-overview">
        <h3><i class="fas fa-chart-pie"></i> System Overview</h3>
        <div class="system-overview-content">
            <div class="overview-item">
                <i class="fas fa-book"></i>
                <span>Total Courses</span>
                <strong><?= $total_courses ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-user-graduate"></i>
                <span>Total Students</span>
                <strong><?= $total_students ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Total Teachers</span>
                <strong><?= $total_teachers ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-question-circle"></i>
                <span>Total Quizzes</span>
                <strong><?= $total_quizzes ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-file-alt"></i>
                <span>Quiz Submissions</span>
                <strong><?= $total_submissions ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-upload"></i>
                <span>Assignment Submissions</span>
                <strong><?= $total_assignment_submissions ?></strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-spinner"></i>
                <span>Avg Progress</span>
                <strong><?= $avg_progress ?>%</strong>
            </div>
            <div class="overview-item">
                <i class="fas fa-check-circle"></i>
                <span>Completion Rate</span>
                <strong><?= $completion_rate ?>%</strong>
            </div>
        </div>
    </div>

    <div class="admin-actions">
        <h3><i class="fas fa-tools"></i> Admin Actions</h3>
        <ul>
            <li><a href="admin_courses.php"><i class="fas fa-book"></i> Manage Courses</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
            <li><a href="admin_quizzes.php"><i class="fas fa-question-circle"></i> Manage Quizzes</a></li>
            <li><a href="admin_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a></li>
            <li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> View Reports</a></li>
            <li><a href="admin_forum.php"><i class="fas fa-comments"></i> Manage Forum</a></li>
            <li><a href="admin_send_notification.php"><i class="fas fa-bell"></i> Post Notifications</a></li>
        </ul>
    </div>

    <div class="logout-link">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>

    <?php include "includes/footer.php"; ?>
</div>
<script src="js/main.js"></script>
</body>
</html>