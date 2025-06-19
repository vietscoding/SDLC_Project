<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Xử lý tìm kiếm
$keyword = $_GET['keyword'] ?? '';
$sql = "
    SELECT c.id, c.title, c.description, c.department, u.fullname AS instructor
    FROM courses c
    JOIN users u ON c.teacher_id = u.id
    WHERE c.title LIKE ? OR c.department LIKE ? OR u.fullname LIKE ?
";
$stmt = $conn->prepare($sql);
$search_keyword = '%' . $keyword . '%';
$stmt->bind_param("sss", $search_keyword, $search_keyword, $search_keyword);
$stmt->execute();
$result = $stmt->get_result();

$enrolled_courses = [];
$user_id = $_SESSION['user_id'];
$enroll_result = $conn->query("SELECT course_id FROM enrollments WHERE user_id = $user_id");
while ($en = $enroll_result->fetch_assoc()) {
    $enrolled_courses[] = $en['course_id'];
}

$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Courses | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/student/student_search_courses.css"> 
   
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?> <div class="main-content">
        <div class="courses-header">
            <h2><i class="fas fa-search"></i> Search Courses</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
           
        </div>

        <form class="search-form" method="get">
            <input type="text" name="keyword" placeholder="Enter course, department, or instructor name" value="<?= htmlspecialchars($keyword) ?>" required>
            <button type="submit">Search</button>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <ul class="course-list-container">
                <?php while ($course = $result->fetch_assoc()): ?>
                    <li class="course-item">
                        <strong><?= htmlspecialchars($course['title']) ?></strong>
                        <p><?= htmlspecialchars($course['description'] ?? 'No description available.'); ?></p>
                        <div class="course-meta">
                            Department: <?= htmlspecialchars($course['department']) ?> <br>
                            Instructor: <?= htmlspecialchars($course['instructor']) ?>
                            <?php if (in_array($course['id'], $enrolled_courses)): ?>
                                <br><span style="color: var(--primary-color); font-weight: bold;">[Enrolled]</span>
                            <?php endif; ?>
                        </div>
                        <a href="course_detail.php?course_id=<?= $course['id'] ?>">View Details <i class="fas fa-arrow-right"></i></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-results">No matching courses found.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="courses.php"><i class="fas fa-book"></i> My Courses</a>
        </div>
        
        <?php include "../../../includes/footer.php"; ?>
    </div>
    
<script src="../../../js/student_sidebar.js"></script>
</body>
</html>