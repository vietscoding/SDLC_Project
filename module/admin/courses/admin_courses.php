<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// **********************************************************


// Xử lý search nếu có
$keyword = $_GET['keyword'] ?? "";

// Query lấy courses
$stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS teacher_name 
                        FROM courses c 
                        LEFT JOIN users u ON c.teacher_id = u.id
                        ORDER BY c.id DESC");

$stmt->execute();
$result = $stmt->get_result();
$matched_courses = [];

while ($row = $result->fetch_assoc()) {
    if (!empty($keyword)) {
        // Tách từng từ trong title và department
        $title_words = explode(" ", $row['title']);
        $department_words = explode(" ", $row['department']);

        // Tính khoảng cách nhỏ nhất với từng từ
        $distance_title = PHP_INT_MAX;
        foreach ($title_words as $word) {
            $distance = levenshtein(strtolower(trim($keyword)), strtolower(trim($word)));
            if ($distance < $distance_title) {
                $distance_title = $distance;
            }
        }

        $distance_department = PHP_INT_MAX;
        foreach ($department_words as $word) {
            $distance = levenshtein(strtolower(trim($keyword)), strtolower(trim($word)));
            if ($distance < $distance_department) {
                $distance_department = $distance;
            }
        }

        // Nếu khoảng cách <= 3 hoặc chứa keyword thì thêm vào danh sách
        if ($distance_title <= 3 || $distance_department <= 3 
            || stripos(strtolower($row['title']), strtolower($keyword)) !== false 
            || stripos(strtolower($row['department']), strtolower($keyword)) !== false) {
            
            // Thêm score để sắp xếp
            $row['score'] = min($distance_title, $distance_department);
            $matched_courses[] = $row;
        }
    } else {
        // Nếu không search thì score mặc định 0
        $row['score'] = 0;
        $matched_courses[] = $row;
    }
}

// Sắp xếp kết quả theo score tăng dần
usort($matched_courses, function($a, $b) {
    return $a['score'] <=> $b['score'];
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Courses (Admin) | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../../../css/style.css"> 
<link rel="stylesheet" href="../../../css/admin/admin_courses.css"> 
    
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

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

    <?php if (count($matched_courses) > 0): ?>
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
            <?php foreach ($matched_courses as $row): ?>
                <tr>
                    <td data-label="ID"><?= $row['id'] ?></td>
                    <td data-label="Title"><?= htmlspecialchars($row['title']) ?></td>
                    <td data-label="Department"><?= htmlspecialchars($row['department']) ?></td>
                    <td data-label="Teacher"><?= htmlspecialchars($row['teacher_name']) ?></td>
                    <td data-label="Actions" class="course-actions">
                        <a href="admin_edit_course.php?course_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                        <a href="../user_management/admin_enrollments.php?course_id=<?= $row['id'] ?>"><i class="fas fa-users"></i> Enrollments</a>
                        <a href="../user_management/admin_progress.php?course_id=<?= $row['id'] ?>"><i class="fas fa-tasks"></i> Progress</a>
                        <form action="admin_delete_course.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                            <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="no-courses"><i class="fas fa-exclamation-circle"></i> No courses found.</p>
<?php endif; ?>


    <div class="back-to-dashboard">
        <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>

</div>
<script src="../../../js/sidebar.js"></script>
</body>
</html>