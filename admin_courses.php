<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "includes/db_connect.php";

// Xử lý search nếu có
$keyword = $_GET['keyword'] ?? "";

// Query lấy courses
if (!empty($keyword)) {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS teacher_name 
                            FROM courses c 
                            LEFT JOIN users u ON c.teacher_id = u.id
                            WHERE c.title LIKE ? OR c.department LIKE ?
                            ORDER BY c.id DESC");
    $like = "%" . $keyword . "%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS teacher_name 
                            FROM courses c 
                            LEFT JOIN users u ON c.teacher_id = u.id
                            ORDER BY c.id DESC");
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses (Admin) | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css"> 

    <style>
        /* CSS ĐẶC TRƯNG CHO ADMIN COURSES */
        /* Main Content Area (phải khớp với sidebar width) */
        .main-content {
            margin-left: 280px; /* Phải khớp với sidebar width trong style.css */
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--background-light);
            /* Thêm transition cho main-content nếu bạn muốn nó di chuyển khi sidebar mở */
            transition: margin-left 0.3s ease;
        }

        /* Responsive cho main-content */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0; /* Loại bỏ margin khi sidebar ẩn */
                padding-top: 80px; /* Tạo khoảng trống cho nút burger */
            }
            /* Nếu bạn muốn main-content dịch chuyển khi sidebar mở, dùng cái này */
            /* body.sidebar-open .main-content {
                transform: translateX(280px);
            } */
        }

        /* Header tương tự admin-dashboard-header */
        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-page-header h2 {
            font-size: 2.2em;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .admin-page-header h2 i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.1em;
        }

        /* Container cho phần search và thêm mới, tương tự system-overview */
        .course-management-overview {
            background-color: var(--background-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px var(--shadow-light);
            margin-bottom: 30px;
            position: relative;
        }

        .course-management-overview > h3 {
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
            margin-bottom: 20px; /* Adjust spacing below heading */
        }

        .course-management-overview > h3 i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1em;
        }

        .course-management-content {
            margin-top: 60px; /* Để tạo khoảng trống cho tiêu đề h3 */
            display: flex;
            flex-direction: column; /* Hoặc grid nếu muốn xếp các phần tử con */
            gap: 20px; /* Khoảng cách giữa form search và nút thêm mới */
        }

        .search-form {
            display: flex;
            gap: 10px; /* Khoảng cách giữa input và button */
            align-items: center;
            padding-bottom: 20px; /* Dưới form search */
            border-bottom: 1px dashed var(--border-color); /* Đường kẻ nhẹ */
            margin-bottom: 20px; /* Khoảng cách với nút Add New */
        }

        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .search-form input[type="text"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .search-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .search-form button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .search-form .reset-link { /* Đổi tên class để tránh trùng với các a khác */
            display: inline-flex; /* Use inline-flex for icon and text alignment */
            align-items: center;
            color: var(--text-medium);
            text-decoration: none;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .search-form .reset-link:hover {
            background-color: var(--background-light);
            color: var(--text-dark);
            border-color: var(--primary-color);
        }
        .search-form .reset-link i {
            margin-right: 8px;
        }

        .add-new-course-link {
            background-color: #28a745;
            color: white;
            padding: 10px 20px; /* Giảm padding để nút nhỏ lại */
            border-radius: 8px;
            text-decoration: none;
            font-size: 1em; /* Giảm font-size */
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
            width: fit-content;
            margin-top: 10px;
        }

        .add-new-course-link:hover {
            background-color: #218838;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }
        .add-new-course-link i {
            margin-right: 10px;
        }


        /* Bảng Course vẫn giữ nguyên nhưng với style tốt hơn */
        .courses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px; /* Tăng khoảng cách từ phần trên */
            background-color: var(--background-card);
            box-shadow: 0 5px 20px var(--shadow-light);
            border-radius: 12px;
            overflow: hidden; /* Đảm bảo góc bo tròn cho bảng */
        }

        .courses-table thead th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 1em;
            text-transform: uppercase;
        }

        .courses-table tbody td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 0.95em;
        }

        .courses-table tbody tr:last-child td {
            border-bottom: none;
        }

        .courses-table tbody tr:nth-child(even) {
            background-color: #f9fbfd; /* Màu nền nhẹ hơn cho hàng chẵn */
        }

        .courses-table tbody tr:hover {
            background-color: #e9f7ff; /* Hover effect */
            transition: background-color 0.2s ease;
        }

        .course-actions a {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 12px; /* Tăng khoảng cách giữa các action */
            transition: color 0.2s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            font-size: 0.9em;
        }

        .course-actions a:hover {
            color: var(--accent-color);
            transform: translateY(-2px);
        }

        .course-actions a i {
            margin-right: 5px;
            font-size: 1.1em;
        }

        .course-actions a:last-child {
            margin-right: 0;
        }

        .no-courses {
            background-color: var(--background-card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px var(--shadow-light);
            margin-top: 30px;
            color: var(--text-medium);
            font-style: italic;
            text-align: center;
            font-size: 1.1em;
            border: 1px dashed var(--border-color);
        }
        .no-courses i {
            margin-right: 10px;
            color: var(--accent-color);
        }

        .back-to-dashboard {
            margin-top: 40px;
            text-align: center;
        }

        .back-to-dashboard a {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            background-color: #6c757d; /* Gray color for back button */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
        }

        .back-to-dashboard a:hover {
            background-color: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        .back-to-dashboard a i {
            margin-right: 10px;
            font-size: 1.1em;
        }

        /* Dark Mode (Specific to Courses elements) */
        body.dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }

        .dark-mode .main-content {
            background-color: #212529;
        }

        .dark-mode .admin-page-header h2 {
            color: #ecf0f1;
        }

        .dark-mode .admin-page-header h2 i {
            color: #f1c40f;
        }

        .dark-mode .course-management-overview {
            background-color: #34495e;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border-color: #444;
        }

        .dark-mode .course-management-overview > h3 {
            color: #ecf0f1;
            border-bottom-color: #444;
        }

        .dark-mode .course-management-overview > h3 i {
            color: #f1c40f;
        }

        .dark-mode .search-form input[type="text"] {
            background-color: #495057;
            color: #eee;
            border-color: #555;
        }
        .dark-mode .search-form input[type="text"]:focus {
            border-color: #f1c40f;
            box-shadow: 0 0 0 3px rgba(241, 196, 15, 0.25);
        }

        .dark-mode .search-form button {
            background-color: #007bff; /* Giữ nguyên màu xanh nếu muốn, hoặc đổi sang vàng */
            color: #fff;
        }

        .dark-mode .search-form .reset-link {
            color: #bdc3c7;
            border-color: #555;
        }
        .dark-mode .search-form .reset-link:hover {
            background-color: #495057;
            color: #ecf0f1;
            border-color: #f1c40f;
        }

        .dark-mode .add-new-course-link {
            background-color: #27ae60; /* Darker green */
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.2);
        }
        .dark-mode .add-new-course-link:hover {
            background-color: #2ecc71; /* Lighter green on hover */
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }

        .dark-mode .courses-table {
            background-color: #34495e;
            color: #ecf0f1;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .courses-table thead th {
            background-color: #2980b9; /* Darker blue for table header */
            color: #fff;
        }

        .dark-mode .courses-table tbody td {
            border-bottom-color: #444;
        }

        .dark-mode .courses-table tbody tr:nth-child(even) {
            background-color: #3a536b;
        }

        .dark-mode .courses-table tbody tr:hover {
            background-color: #495e74;
        }

        .dark-mode .course-actions a {
            color: #85c1e9; /* Light blue for actions */
        }
        .dark-mode .course-actions a:hover {
            color: #f1c40f; /* Yellow on hover */
        }

        .dark-mode .no-courses {
            background-color: #34495e;
            color: #bdc3c7;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            border-color: #555;
        }

        .dark-mode .no-courses i {
            color: #e74c3c; /* Red icon in dark mode */
        }

        .dark-mode .back-to-dashboard a {
            background-color: #7f8c8d; /* Darker gray for back button */
            box-shadow: 0 4px 15px rgba(127, 140, 141, 0.2);
        }
        .dark-mode .back-to-dashboard a:hover {
            background-color: #95a5a6;
            box-shadow: 0 6px 20px rgba(127, 140, 141, 0.3);
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            /* .main-content margin-left đã được xử lý bởi responsive của sidebar trong style.css */
            .course-management-overview {
                padding: 25px;
            }
            .course-management-overview > h3 {
                top: 20px;
                left: 25px;
                font-size: 1.4em;
                width: calc(100% - 50px);
            }
            .course-management-content {
                margin-top: 50px;
            }
            .courses-table thead th, .courses-table tbody td {
                padding: 12px 15px;
            }
            .course-actions a {
                margin-right: 8px;
            }
        }

        @media (max-width: 768px) {
            /* .main-content margin-left đã được xử lý bởi responsive của sidebar trong style.css */
            .admin-page-header h2 {
                font-size: 1.8em;
            }
            .course-management-overview > h3 {
                position: static; /* Header becomes static */
                text-align: center;
                width: auto;
                margin-bottom: 25px;
                padding-bottom: 10px;
            }
            .course-management-content {
                margin-top: 0; /* No need for top margin if h3 is static */
            }
            .search-form {
                flex-direction: column; /* Stack search elements */
                gap: 15px;
                border-bottom: none; /* Remove border if stacked */
                margin-bottom: 15px;
            }
            .search-form input[type="text"],
            .search-form button,
            .search-form .reset-link {
                width: 100%; /* Make them full width */
                text-align: center;
                justify-content: center; /* Center content for buttons/links */
            }
            .search-form .reset-link {
                margin-left: 0; /* Remove margin-left */
            }

            .add-new-course-link {
                width: 100%;
                margin-top: 0; /* Remove margin-top as it's part of the stack */
            }

            /* Responsive table for smaller screens */
            .courses-table, .no-courses {
                margin-top: 20px;
            }
            .courses-table thead, .courses-table tbody, .courses-table th, .courses-table td, .courses-table tr {
                display: block;
            }
            .courses-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .courses-table tr {
                border: 1px solid var(--border-color);
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
            }
            .courses-table tbody td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
                display: flex; /* Use flex to align label and value */
                align-items: center;
                justify-content: flex-end; /* Align value to the right */
                min-height: 40px; /* Ensure enough height for content */
            }
            .courses-table tbody tr:nth-child(even) {
                background-color: var(--background-card); /* Reset background for stacked rows */
            }
            .courses-table tbody tr:nth-child(odd) {
                background-color: var(--background-light); /* Apply to odd rows */
            }
            .courses-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px; /* Adjust left position for label */
                width: calc(50% - 30px); /* Adjust width to fit */
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--text-dark);
            }
            .course-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end; /* Align actions to the right */
                gap: 8px; /* Khoảng cách giữa các nút action */
                padding-top: 10px; /* Khoảng cách từ nội dung trên */
                border-top: 1px dashed var(--border-color); /* Đường kẻ nhẹ */
            }
            .course-actions a {
                margin-right: 0 !important; /* Override previous margin-right */
                padding: 8px 12px;
                font-size: 0.85em;
            }
        }

        @media (max-width: 480px) {
            .admin-page-header h2 {
                font-size: 1.5em;
            }
            .courses-table tbody td:before {
                font-size: 0.85em;
            }
        }
    </style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-book"></i> Manage Courses</h2>
    </div>

    <div class="course-management-overview">
        <h3><i class="fas fa-tasks"></i> Course Management Actions</h3>
        <div class="course-management-content">
            <form method="get" class="search-form">
                <input type="text" name="keyword" placeholder="Search by title or department..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($keyword)): ?>
                    <a href="admin_courses.php" class="reset-link"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
            <a href="admin_add_course.php" class="add-new-course-link"><i class="fas fa-plus-circle"></i> Add New Course</a>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="courses-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Teacher</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= $row['id'] ?></td>
                        <td data-label="Title"><?= htmlspecialchars($row['title']) ?></td>
                        <td data-label="Department"><?= htmlspecialchars($row['department']) ?></td>
                        <td data-label="Teacher"><?= htmlspecialchars($row['teacher_name']) ?></td>
                        <td data-label="Actions" class="course-actions">
                            <a href="admin_edit_course.php?course_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                            <a href="admin_delete_course.php?course_id=<?= $row['id'] ?>" onclick="return confirm('Delete this course?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            <a href="admin_enrollments.php?course_id=<?= $row['id'] ?>"><i class="fas fa-users"></i> Enrollments</a>
                            <a href="admin_progress.php?course_id=<?= $row['id'] ?>"><i class="fas fa-tasks"></i> Progress</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-courses"><i class="fas fa-exclamation-circle"></i> No courses found.</p>
    <?php endif; ?>

    <div class="back-to-dashboard">
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "includes/footer.php"; ?>

</div>
<script src="js/main.js"></script>
</body>
</html>