<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}
include "../../../includes/db_connect.php";
$user_id = $_SESSION['user_id'];
$sql = "
SELECT c.id, c.title,
    (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS total_lessons,
    (SELECT COUNT(*) FROM progress p
        WHERE p.course_id = c.id AND p.user_id = ? AND p.is_completed = 1) AS completed_lessons
FROM courses c
JOIN enrollments e ON c.id = e.course_id
WHERE e.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Learning Progress | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/student/progress.css"> 
 
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="student-dashboard-header">
            <h2><i class="fas fa-chart-line"></i> My Learning Progress</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

      

        <div class="dashboard-section">
            <h3><i class="fas fa-tasks"></i> Course Progress Overview</h3>
            <?php if ($result->num_rows > 0): ?>
                <div class="module-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $progress_percent = ($row['total_lessons'] > 0) ? round(($row['completed_lessons'] / $row['total_lessons']) * 100) : 0;
                        ?>
                        <div class="module-card progress-item">
                            <div class="course-info">
                                <h4 class="course-title"><?= htmlspecialchars($row['title']) ?></h4>
                                <p class="lessons-progress"><?= $row['completed_lessons'] ?> / <?= $row['total_lessons'] ?> Lessons Completed</p>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?= $progress_percent ?>%;"></div>
                                </div>
                                <span class="progress-percentage"><?= $progress_percent ?>%</span>
                            </div>
                            <a href="course_detail.php?course_id=<?= $row['id'] ?>" class="view-details-button"><i class="fas fa-arrow-right"></i> View Course</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-courses">You are not enrolled in any courses yet.</p>
            <?php endif; ?>
        </div>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/student_sidebar.js"></script>
  
</body>
</html>