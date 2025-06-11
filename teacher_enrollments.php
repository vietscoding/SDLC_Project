<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Lấy course_id từ URL
if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "You are not allowed to manage enrollments for this course.";
    exit;
}
$stmt->bind_result($course_title);
$stmt->fetch();
$stmt->close();

// Xóa enrollment nếu bấm remove
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    $conn->query("DELETE FROM enrollments WHERE id = $remove_id AND course_id = $course_id");
    header("Location: teacher_enrollments.php?course_id=$course_id");
    exit;
}

// Lấy danh sách học viên đã enroll
$result = $conn->query("
  SELECT e.id AS enroll_id, u.fullname, u.email, e.enrolled_at
  FROM enrollments e
  JOIN users u ON e.user_id = u.id
  WHERE e.course_id = $course_id
  ORDER BY e.enrolled_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollments - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reset some default styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif; /* Changed to Roboto */
            background-color: #f0f2f5; /* Lighter grey background for the whole page */
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden; /* Prevent horizontal scroll */
            flex-direction: column; /* Allows main-wrapper and footer to stack */
        }

        .sidebar {
            width: 250px; /* Adjusted width to match other pages */
            background: linear-gradient(135deg, #2c3e50 60%, #2980b9 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px; /* Adjusted padding */
            box-shadow: 2px 0 20px rgba(44,62,80,0.15); /* Adjusted shadow */
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-y: auto; /* Enable vertical scrolling for long menus */
            scrollbar-width: thin;
            scrollbar-color: #f1c40f33 #2c3e5000; /* thumb vàng nhạt, track trong suốt */
        }

        .sidebar .logo {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 30px;
            width: 100%; /* Ensure logo area takes full width */
        }

        .sidebar .logo img {
            display: block;
            width: 70%; /* Smaller logo to match other pages */
            max-width: 150px; /* Max size for logo */
            height: auto;
            margin: auto;
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
            padding: 0 15px; /* Padding for list items */
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px; /* Adjusted padding */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
            border-radius: 6px; /* Slightly rounded corners */
            margin-bottom: 8px; /* Space between items */
            border-left: 5px solid transparent; /* Added border for active/hover */
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #34495e; /* Slightly lighter dark blue on hover */
            color: #f39c12; /* Accent color for active/hover */
            border-left-color: #f39c12; /* Accent border color */
        }

        .sidebar ul li a i {
            margin-right: 12px; /* Adjusted icon spacing */
            font-size: 1.1em;
            color: #ecf0f1; /* Light grey for icons */
        }

        .sidebar ul li a:hover i,
        .sidebar ul li a.active i {
            color: #f39c12; /* Accent color for icons on hover/active */
        }
        
        .sidebar ul li a span {
            white-space: nowrap;
        }

        .main-wrapper {
            flex-grow: 1;
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            background-color: #f0f2f5; /* Match body background */
            display: flex;
            flex-direction: column; /* Allows content and footer to stack vertically */
            min-height: 100vh; /* Ensure it takes full height */
        }

        .main-content {
            background-color: #fff; /* White main content background */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* More subtle shadow */
            padding: 30px;
            flex-grow: 1; /* Allow content to grow and push footer down */
        }

        .toggle-mode-btn {
            position: absolute;
            top: 18px;
            right: 30px;
            background: #fff;
            color: #2c3e50;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            cursor: pointer;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s, color 0.3s;
            z-index: 10;
        }

        .toggle-mode-btn:hover {
            background: #f1c40f;
            color: #fff;
        }

        .enrollments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .enrollments-header h2 {
            font-size: 2em; /* Consistent font size */
            color: #2c3e50; /* Consistent color */
            margin: 0;
            font-weight: 500; /* Consistent font weight */
        }

        .enrollments-header h2 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        /* Styling for the "Back to My Courses" button */
        .enrollments-header .button {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #3498db; /* A blue color */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }

        .enrollments-header .button:hover {
            background-color: #2980b9; /* Darker blue on hover */
        }

        .enrollments-header .button i {
            margin-right: 8px;
        }

        .enrollment-table-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px; /* Consistent border-radius */
            box-shadow: 0 2px 10px rgba(0,0,0,0.06); /* Consistent shadow */
            margin-bottom: 30px;
            border: 1px solid #e0e0e0; /* Subtle border */
        }

        .enrollment-table-section h3 {
            font-size: 1.6em; /* Consistent font size */
            color: #2c3e50; /* Consistent heading color */
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee; /* Consistent border */
            padding-bottom: 10px;
            font-weight: 500;
        }

        .enrollment-table-section h3 i {
            margin-right: 10px;
            color: #f39c12; /* Accent color for icon */
        }

        .enrollment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 8px; /* Rounded corners for the table itself */
            overflow: hidden; /* Ensures borders/shadows are applied correctly */
        }

        .enrollment-table th, .enrollment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0; /* Lighter border */
            vertical-align: middle;
        }

        .enrollment-table th {
            background-color: #f8f8f8; /* Lighter header background */
            font-weight: 600; /* Bolder font weight */
            color: #555;
            text-transform: uppercase; /* Uppercase headers */
            font-size: 0.9em;
        }

        .enrollment-table tbody tr:nth-child(even) {
            background-color: #fefefe; /* Slightly whiter even rows */
        }

        .enrollment-table tbody tr:hover {
            background-color: #f5f5f5; /* Subtle hover effect */
        }

        .enrollment-actions a {
            color: #e74c3c; /* Danger red */
            text-decoration: none;
            font-size: 0.9em;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            padding: 6px 12px; /* Adjusted padding */
            border-radius: 5px; /* Consistent border-radius */
            border: 1px solid #e74c3c;
            display: inline-flex; /* Allows icon and text to be together */
            align-items: center;
            font-weight: 500;
        }

        .enrollment-actions a i {
            margin-right: 5px; /* Space between icon and text */
        }

        .enrollment-actions a:hover {
            background-color: #e74c3c;
            color: white;
        }

        .no-enrollments {
            background-color: #fdfae5; /* Lighter warning yellow */
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            color: #85640c; /* Darker yellow text */
            font-style: italic;
            font-size: 0.95em;
            border: 1px solid #ffeeba;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .no-enrollments i {
            margin-right: 10px;
            color: #f39c12; /* Accent color */
        }

        /* Message Box Styling (from My Courses) */
        .success-message, .error-message, .warning-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            border: 1px solid transparent; /* Default transparent border */
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .warning-message {
            background-color: #fff3cd;
            color: #85640c;
            border-color: #ffeeba;
        }

        .success-message i, .error-message i, .warning-message i {
            margin-right: 8px;
        }

        .navigation-links {
            margin-top: 40px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 20px;
            padding-bottom: 20px;
        }

        .navigation-links a {
            display: inline-flex;
            align-items: center;
            color: #fff;
            background: linear-gradient(90deg, #f39c12 0%, #f1c40f 100%);
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            border-radius: 6px;
            padding: 12px 26px;
            box-shadow: 0 2px 8px rgba(243,156,18,0.10);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.15s;
            border: none;
            outline: none;
            gap: 8px;
        }

        .navigation-links a:hover {
            background: linear-gradient(90deg, #2980b9 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(41,128,185,0.13);
            transform: translateY(-2px) scale(1.04);
        }

        .navigation-links a i {
            margin-right: 8px;
            font-size: 1.1em;
        }

        hr {
            margin-top: 30px;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
        }

        footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px; /* Adjusted margin-top to match your My Courses footer */
            font-size: 0.85em;
            color: #777;
            background-color: #f2f2f2;
            border-top: 1px solid #eee;
            border-radius: 0 0 8px 8px; /* Match border-radius of main-content */
            flex-shrink: 0; /* Prevent footer from shrinking */
        }

        footer a {
            color: #3498db; /* Consistent link color */
            text-decoration: none;
            margin: 0 8px;
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
            font-size: 0.85em;
        }

        /* Dark Mode */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #f8f9fa;
        }

        .dark-mode .sidebar {
            background-color: #333;
            box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        }

        .dark-mode .main-wrapper {
            background-color: #1a1a1a;
        }

        .dark-mode .main-content {
            background-color: #222;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .dark-mode .enrollments-header h2,
        .dark-mode .enrollment-table-section h3 {
            color: #f8f9fa;
        }
        
        .dark-mode .enrollments-header h2 i,
        .dark-mode .enrollment-table-section h3 i {
            color: #f39c12;
        }

        .dark-mode .enrollment-table-section {
            background-color: #2a2a2a;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border-color: #444;
        }

        .dark-mode .enrollment-table th {
            background-color: #3a3a3a; /* Darker header background */
            color: #eee;
            border-color: #555;
        }

        .dark-mode .enrollment-table td {
            color: #ddd;
            border-color: #444;
        }

        .dark-mode .enrollment-table tbody tr:nth-child(even) {
            background-color: #2f2f2f;
        }

        .dark-mode .enrollment-table tbody tr:hover {
            background-color: #383838;
        }

        .dark-mode .enrollment-actions a {
            color: #f39c12; /* Accent color for remove */
            border-color: #f39c12;
        }

        .dark-mode .enrollment-actions a:hover {
            background-color: #f39c12;
            color: #222;
        }

        .dark-mode .no-enrollments {
            background-color: #2f2f2f;
            color: #ccc;
            border-color: #444;
        }

        .dark-mode .no-enrollments i {
            color: #f39c12;
        }

        .dark-mode .navigation-links a {
            color: #fbc531;
        }

        .dark-mode .navigation-links a:hover {
            color: #f39c12;
        }

        .dark-mode footer {
            background-color: #333;
            color: #ccc;
            border-top-color: #555;
            border-radius: 0 0 8px 8px;
        }
        
        .dark-mode footer a {
            color: #fbc531;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            .main-wrapper {
                margin-left: 220px;
            }
            .enrollments-header h2 {
                font-size: 1.8em;
            }
            .enrollment-table-section h3 {
                font-size: 1.4em;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column; /* Stack sidebar and main content */
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                box-shadow: none;
                padding-top: 0;
            }
            .sidebar .logo {
                padding: 15px 0;
                margin-bottom: 0;
            }
            .sidebar .logo img {
                width: 50%;
                max-width: 120px;
            }
            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px 0;
            }
            .sidebar ul li {
                width: 48%; /* Two items per row */
                margin-bottom: 5px;
            }
            .sidebar ul li a {
                flex-direction: column; /* Stack icon and text */
                justify-content: center;
                padding: 10px;
                text-align: center;
            }
            .sidebar ul li a i {
                margin-right: 0;
                margin-bottom: 5px;
                font-size: 1em;
            }
            .sidebar ul li a span {
                display: block; /* Ensure text is on a new line */
                font-size: 0.8em;
            }

            .main-wrapper {
                margin-left: 0;
                padding: 20px;
            }

            .enrollments-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .enrollments-header h2 {
                margin-bottom: 10px;
                font-size: 1.8em;
            }
            .enrollments-header .button {
                width: 100%; /* Full width button */
                justify-content: center; /* Center button content */
            }
            .enrollment-table-section {
                padding: 20px;
            }
            .enrollment-table-section h3 {
                font-size: 1.3em;
            }
            .enrollment-table th, .enrollment-table td {
                padding: 10px;
                font-size: 0.9em;
            }
            .enrollment-actions a {
                padding: 5px 10px;
                font-size: 0.85em;
            }
            .navigation-links {
                margin-top: 30px;
            }
            .navigation-links a {
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .sidebar ul li {
                width: 95%; /* One item per row */
            }
            .sidebar ul li a {
                flex-direction: row; /* Back to row for very small screens if desired, or keep column */
                justify-content: flex-start;
            }
            .sidebar ul li a i {
                margin-right: 10px;
                margin-bottom: 0;
            }
            .sidebar ul li a span {
                display: inline; /* Display inline again */
            }
            .main-content {
                padding: 15px;
            }
            .enrollments-header h2 {
                font-size: 1.5em;
            }
            .enrollment-table-section h3 {
                font-size: 1.1em;
            }
            .enrollment-table th, .enrollment-table td {
                font-size: 0.8em;
            }
            .enrollment-actions a {
                font-size: 0.8em;
            }
            footer {
                padding: 15px;
                font-size: 0.8em;
            }
            footer .contact-info p {
                font-size: 0.75em;
            }
        }

        /* Chrome, Edge, Safari */
        .sidebar::-webkit-scrollbar {
            width: 6px;
            background: transparent;
            transition: opacity 0.2s;
            opacity: 0;
        }
        .sidebar:hover::-webkit-scrollbar {
            opacity: 1;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #f1c40f99 60%, #f39c1299 100%);
            border-radius: 6px;
            min-height: 30px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #f39c12cc 60%, #f1c40fcc 100%);
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
</head>
<body>

    <?php
    // KHÔNG THAY ĐỔI BẤT KỲ LOGIC PHP NÀO Ở ĐÂY.
    // PHẦN NÀY CHỈ ĐỂ ĐẢM BẢO CÁC BIẾN CẦN THIẾT TỒN TẠI ĐỂ TRÁNH LỖI "UNDEFINED VARIABLE"
    // KHI BẠN CHƯA KẾT NỐI DB THẬT VÀ CHẠY THỬ RIÊNG FILE NÀY.
    // TRONG MÔI TRƯỜNG PHP THẬT CỦA BẠN, CÁC BIẾN NÀY SẼ ĐƯỢC LẤY TỪ DATABASE HOẶC URL.

    if (!isset($course_id)) {
        $course_id = 123; // Placeholder course ID
    }
    if (!isset($course_title)) {
        $course_title = "Sample Course Title (Placeholder)";
    }
    
    // Giả lập dữ liệu từ database cho $result nếu chưa có
    if (!isset($result) || !($result instanceof mysqli_result)) {
        // Tạo một đối tượng giả để mô phỏng mysqli_result
        // Đây chỉ là để HTML có thể chạy mà không cần DB connection
        class MockMySQLiResult {
            private $data;
            private $pointer = 0;
            public $num_rows;

            public function __construct($mockData) {
                $this->data = $mockData;
                $this->num_rows = count($mockData);
            }

            public function fetch_assoc() {
                if ($this->pointer < $this->num_rows) {
                    return $this->data[$this->pointer++];
                }
                return null;
            }
        }

        // Dữ liệu giả định
        $mockEnrollments = [
            [
                'fullname' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'enrolled_at' => '2023-01-15 10:30:00',
                'enroll_id' => 1
            ],
            [
                'fullname' => 'Trần Thị B',
                'email' => 'tranhtb@example.com',
                'enrolled_at' => '2023-02-20 14:00:00',
                'enroll_id' => 2
            ],
            [
                'fullname' => 'Lê Văn C',
                'email' => 'levanc@example.com',
                'enrolled_at' => '2023-03-01 09:15:00',
                'enroll_id' => 3
            ],
        ];
        // Để thử trường hợp không có dữ liệu, bạn có thể uncomment dòng này:
        // $mockEnrollments = []; 
        $result = new MockMySQLiResult($mockEnrollments);
    }

    // Nếu bạn có biến $message cho thông báo thành công/lỗi, hãy thêm vào đây
    if (!isset($message)) {
        $message = '';
    }
    // Ví dụ về cách bạn sẽ thiết lập $message trong file PHP của mình:
    // $message = '<div class="success-message"><i class="fas fa-check-circle"></i> Sinh viên đã được xóa khỏi khóa học!</div>';
    // $message = '<div class="error-message"><i class="fas fa-times-circle"></i> Lỗi khi xóa sinh viên.</div>';
    ?>

    <div class="sidebar">
        <div class="logo">
            <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
        </div>
        <ul>
            <li><a href="teacher_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="teacher_courses.php" class="sidebar-link"><i class="fas fa-book"></i> <span>My Courses</span></a></li>
            <li><a href="teacher_search_courses.php" class="sidebar-link"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="teacher_quiz_results.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> <span>View Quiz Results</span></a></li>
            <li><a href="teacher_assignments.php" class="sidebar-link"><i class="fas fa-tasks"></i> <span>Manage Assignments</span></a></li>
            <li><a href="teacher_notifications.php" class="sidebar-link"><i class="fas fa-bell"></i> <span>Send Notifications</span></a></li>
            <li><a href="teacher_view_notifications.php" class="sidebar-link"><i class="fas fa-envelope-open-text"></i> <span>View Notifications</span></a></li>
            <li><a href="teacher_quizzes.php" class="sidebar-link"><i class="fas fa-question-circle"></i> <span>Manage Quizzes</span></a></li>
            <li><a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
            <li><a href="logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> <span>Log out</span></a></li>
        </ul>
    </div>

    <div class="main-wrapper">
        <div class="main-content">
            <button class="toggle-mode-btn" id="toggleModeBtn" title="Toggle dark/light mode">
                <i class="fas fa-moon"></i>
            </button>
            <div class="enrollments-header">
                <h2><i class="fas fa-users"></i> Enrollments for: <?= htmlspecialchars($course_title) ?></h2>
                <a href="teacher_courses.php" class="button"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            </div>

            <?php if (!empty($message)): ?>
                <?= $message ?>
            <?php endif; ?>

            <div class="enrollment-table-section">
                <h3><i class="fas fa-list-alt"></i> Enrolled Students</h3>
                <?php if ($result->num_rows > 0): ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Enrolled At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset pointer for mock result if it was already used
                            if (isset($result) && ($result instanceof MockMySQLiResult)) {
                                $result = new MockMySQLiResult($mockEnrollments); // Re-initialize for demonstration
                            }
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['enrolled_at']) ?></td>
                                    <td class="enrollment-actions">
                                        <a href="teacher_enrollments.php?course_id=<?= htmlspecialchars($course_id) ?>&remove_id=<?= htmlspecialchars($row['enroll_id']) ?>" onclick="return confirm('Are you sure you want to remove this enrollment?')"><i class="fas fa-trash-alt"></i> Remove</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-enrollments"><i class="fas fa-info-circle"></i> No students have enrolled in this course yet.</p>
                <?php endif; ?>
            </div>

            <div class="navigation-links">
                <a href="teacher_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </div>

        <hr>
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
    <script>
        // Toggle dark/light mode và lưu trạng thái vào localStorage
        const btn = document.getElementById('toggleModeBtn');
        function setDarkMode(on) {
            document.body.classList.toggle('dark-mode', on);
            btn.innerHTML = on
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
            localStorage.setItem('darkMode', on ? '1' : '0');
        }
        // Khởi tạo trạng thái từ localStorage
        document.addEventListener('DOMContentLoaded', function() {
            setDarkMode(localStorage.getItem('darkMode') === '1');
            btn.onclick = function() {
                setDarkMode(!document.body.classList.contains('dark-mode'));
            };
        });

        // JavaScript để highlight link sidebar tương ứng (giữ nguyên)
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            const sidebarLinks = document.querySelectorAll('.sidebar ul li a.sidebar-link');

            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                const linkHref = link.getAttribute('href');
                if (linkHref) {
                    const linkFileName = linkHref.split('/').pop();
                    if (linkFileName === 'teacher_courses.php' && 
                   (currentPath === 'teacher_courses.php' || 
                    currentPath.startsWith('teacher_lessons.php') || 
                    currentPath.startsWith('edit_lesson.php') ||
                    currentPath.startsWith('teacher_enrollments.php'))) {
                    link.classList.add('active');
                    } else if (linkFileName === currentPath) {
                    link.classList.add('active');
                    }
                }
            });
        });
    </script>
</body>
</html>