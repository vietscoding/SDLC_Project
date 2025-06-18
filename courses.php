<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}
include "includes/db_connect.php";
$user_id = $_SESSION['user_id'];
$sql = "SELECT c.id, c.title, c.description
        FROM courses c
        INNER JOIN enrollments e ON c.id = e.course_id
        WHERE e.user_id = $user_id";
$result = $conn->query($sql);
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/student/courses.css">
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="courses-header">
            <h2><i class="fas fa-book"></i> My Courses</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
            
        </div>

        <?php if ($result->num_rows > 0): ?>
            <ul class="course-list-container">
                <?php while($row = $result->fetch_assoc()): ?>
                    <li class="course-item">
                        <strong><?= htmlspecialchars($row['title']); ?></strong>
                        <p><?= htmlspecialchars($row['description']); ?></p>
                        <a href="course_detail.php?course_id=<?= $row['id'] ?>">View Details <i class="fas fa-arrow-right"></i></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-courses-message">You are not enrolled in any courses at the moment. Please search for available courses.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a>
        </div>
        
        <?php include "includes/footer.php"; ?>
    </div>
    
    <script src="js/student_sidebar.js"></script> 
</body>
</html>