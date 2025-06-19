<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$user_id = $_SESSION['user_id'];

// Get the list of courses the student is enrolled in
$stmt = $conn->prepare("
    SELECT DISTINCT c.id, c.title
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();

$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Assignments | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css"> 
    <link rel="stylesheet" href="../../../css/student/student_assignments.css"> 
  
</head>
<body>
    <?php include "../../../includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="courses-header">
            <h2><i class="fas fa-tasks"></i> My Assignments</h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>

      

        <?php if ($courses_result->num_rows > 0): ?>
            <ul class="assignment-course-container">
                <?php while ($course = $courses_result->fetch_assoc()): ?>
                    <li class="assignment-course-item">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <?php
                        $course_id = $course['id'];
                        $assign_stmt = $conn->prepare("SELECT id, title, description, due_date, file_path FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
                        $assign_stmt->bind_param("i", $course_id);
                        $assign_stmt->execute();
                        $assignments = $assign_stmt->get_result();
                        ?>
                        <?php if ($assignments->num_rows > 0): ?>
                            <ul class="assignment-list">
                                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                                    <li class="assignment-item">
                                        <strong><?= htmlspecialchars($assignment['title']) ?></strong>
                                        <span class="assignment-due-date">Due: <?= date('Y-m-d H:i', strtotime($assignment['due_date'])) ?></span>
                                        <p class="assignment-description"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                        <?php if (!empty($assignment['file_path'])): ?>
                                            <a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank" class="assignment-file-link">
                                                <i class="fas fa-download"></i> Download Assignment File
                                            </a>
                                        <?php endif; ?>
                                        <a href="submit_assignment.php?assignment_id=<?= $assignment['id'] ?>" class="submit-assignment-link">Submit Assignment <i class="fas fa-paper-plane"></i></a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="no-assignments">No assignments for this course.</p>
                        <?php endif; ?>
                        <?php $assign_stmt->close(); ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="no-courses-message">You are not enrolled in any courses with assignments at the moment.</p>
        <?php endif; ?>

        <div class="navigation-links">
            <a href="../dashboard/student_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/student_sidebar.js"></script>

</body>
</html>