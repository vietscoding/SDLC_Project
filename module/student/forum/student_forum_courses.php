<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Fetch enrolled courses
$courses = $conn->query("
    SELECT c.id, c.title, c.description
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = $user_id
");

// Get user info for header
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Forums | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/student/student_forum_courses.css">
   
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="courses-header">
            <h2><i class="fas fa-comments"></i> My Forums</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

        <?php if ($courses->num_rows > 0): ?>
            <ul class="course-list-container">
                <?php while($row = $courses->fetch_assoc()): ?>
                    <li class="course-item">
                        <strong><?= htmlspecialchars($row['title']); ?></strong>
                        <p><?= htmlspecialchars($row['description'] ?? 'No description available for this course forum.'); ?></p>
                        <a href="student_forum.php?course_id=<?= $row['id'] ?>">Go to Forum <i class="fas fa-arrow-right"></i></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-courses-message">You are not enrolled in any courses with active forums yet.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="student_my_posts.php"><i class="fas fa-file-alt"></i> View My Posts</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>
</body>
</html>