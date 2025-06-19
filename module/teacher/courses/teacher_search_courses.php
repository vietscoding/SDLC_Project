<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$teacher_id = $_SESSION['user_id'];
$keyword = $_GET['keyword'] ?? "";

// Query lấy các courses mà teacher này đang dạy
if (!empty($keyword)) {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor
                            FROM courses c
                            JOIN users u ON c.teacher_id = u.id
                            WHERE c.teacher_id = ?
                              AND (c.title LIKE ? OR c.department LIKE ?)
                            ORDER BY c.id DESC");
    $like = "%" . $keyword . "%";
    $stmt->bind_param("iss", $teacher_id, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.department, u.fullname AS instructor
                            FROM courses c
                            JOIN users u ON c.teacher_id = u.id
                            WHERE c.teacher_id = ?
                            ORDER BY c.id DESC");
    $stmt->bind_param("i", $teacher_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search My Courses | BTEC FPT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel ="stylesheet" href="../../../css/teacher/teacher_search_courses.css">
</head>
<body>

    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">


        <div class="admin-page-header">
            <h2><i class="fas fa-search"></i> Search My Courses</h2>
        </div>

        <div class="progress-overview">
            <h3><i class="fas fa-filter"></i> Search Filters</h3>
            <div class="progress-content">
                <form class="search-form" method="get">
                    <input type="text" name="keyword" placeholder="Search by title or department..." value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <a href="teacher_search_courses.php" class="reset-button"><i class="fas fa-undo"></i> Reset</a>
                </form>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="progress-overview" style="margin-top: 20px;">
                <h3><i class="fas fa-book-reader"></i> Search Results</h3>
                <div class="progress-content">
                    <ul class="course-list">
                        <?php while ($course = $result->fetch_assoc()): ?>
                            <li class="course-item">
                                <div class="course-item-image" style="background-image: url('https://source.unsplash.com/random/800x400?education,coding&sig=<?= $course['id'] ?>');">
                                    <span class="course-icon"><i class="fas fa-book-open"></i></span>
                                </div>
                                <div class="course-item-content">
                                    <h3 class="course-item-title"><?= htmlspecialchars($course['title']) ?></h3>
                                    <p class="course-item-description">Department: <?= htmlspecialchars($course['department']) ?> | Instructor: <?= htmlspecialchars($course['instructor']) ?></p>
                                    <div class="course-item-actions">
                                        <a href="teacher_course_detail.php?course_id=<?= $course['id'] ?>"><i class="fas fa-info-circle"></i> View Details</a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <p class="no-results"><i class="fas fa-exclamation-circle"></i> No courses found matching your search criteria.</p>
        <?php endif; ?>

        <div class="back-to-courses" style="margin-top: 30px; text-align: center;">
            <a href="../dashboard/teacher_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>

</body>
</html>