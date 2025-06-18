<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

include "includes/db_connect.php";
$course_id = $_GET['course_id'];
if (!isset($_GET['course_id'])) {
    echo "Missing course ID.";
    exit;
}
$course_id = intval($_GET['course_id']); // đảm bảo là số




// Lấy tên khóa học
$stmt = $conn->prepare("SELECT title, teacher_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->bind_result($course_title, $teacher_id);
$stmt->fetch();
$stmt->close();

// Handle enrollment
if (isset($_POST['enroll'])) {
    $insert_enrollment = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'pending')");
    $insert_enrollment->bind_param("ii", $_SESSION['user_id'], $course_id);
    if ($insert_enrollment->execute()) {
        // Sau khi enroll thành công → chèn notification cho học viên
        $message = "You have successfully enrolled in the course: '$course_title'.";
        $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify_stmt->bind_param("is", $_SESSION['user_id'], $message);
        $notify_stmt->execute();
        $notify_stmt->close();

        // Tạo thông báo cho giáo viên
        $notif_msg = $_SESSION['fullname'] . " has enrolled in your course: " . $course_title;
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $teacher_id, $notif_msg);
        $stmt->execute();
        $stmt->close();

        // Reload page to update enrollment status
        header("Location: course_detail.php?course_id=$course_id");
        exit;
    } else {
        echo "Enrollment failed.";
    }
    $insert_enrollment->close();
}


// Check if user already enrolled
$enrolled_status = null;
$check_enrollment = $conn->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?");
$check_enrollment->bind_param("ii", $_SESSION['user_id'], $course_id);
$check_enrollment->execute();
$check_enrollment->store_result();

if ($check_enrollment->num_rows > 0) {
    $check_enrollment->bind_result($enrolled_status);
    $check_enrollment->fetch();
}


// Fetch course info
$stmt = $conn->prepare("SELECT title, description FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Course not found.";
    exit;
}

$stmt->bind_result($title, $description);
$stmt->fetch();
$stmt->close();

// Fetch lessons in this course
$lesson_query = $conn->prepare("SELECT id, title FROM lessons WHERE course_id = ?");
$lesson_query->bind_param("i", $course_id);
$lesson_query->execute();
$lessons_result = $lesson_query->get_result();
$fullname = htmlspecialchars($_SESSION['fullname']);
$role = htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title; ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/student/course_detail.css">
   
</head>
<body>
    <?php include "includes/student_sidebar.php"; ?>

    <div class="main-content">
        <div class="course-detail-header">
            <h2><i class="fas fa-book-open"></i> <?= htmlspecialchars($title); ?></h2>
            <div class="user-info">
                <i class="fas fa-user-graduate"></i> <?= $fullname; ?> (<?= $role; ?>)
            </div>
        </div>



        <p class="course-description"><?= htmlspecialchars($description); ?></p>

        <section class="lessons-section">
            <h3><i class="fas fa-list-alt"></i> Lessons:</h3>
            <?php if ($lessons_result->num_rows > 0): ?>
                <ul class="lessons-list">
                    <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
                        <li>
                            <?= htmlspecialchars($lesson['title']); ?>
                            <?php if ($enrolled_status == 'approved'): ?>
                                <a href="lesson.php?id=<?= $lesson['id']; ?>">View Lesson <i class="fas fa-arrow-right"></i></a>
                            <?php else: ?>
                                <span>(Enroll approved to access)</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No lessons available in this course.</p>
            <?php endif; ?>
        </section>

        <div class="course-actions">
            <div class="enroll-section">
                <?php if ($enrolled_status == 'approved'): ?>
                    <p class="enrolled-message">
                        <i class="fas fa-check-circle"></i> You are enrolled in this course.
                    </p>
                    <div class="quick-actions-row">
                        <a class="quiz-forum-btn" href="quiz_list.php?course_id=<?= $course_id ?>"><i class="fas fa-question-circle"></i> View Quizzes</a>
                        </div>

                <?php elseif ($enrolled_status == 'pending'): ?>
                    <p class="pending-message">
                        <i class="fas fa-clock"></i> Enrollment pending approval.
                    </p>

                <?php else: ?>
                    <form method="post" action="" style="display:inline;">
                        <button type="submit" name="enroll" class="quiz-forum-btn">
                            <i class="fas fa-user-plus"></i> Enroll in this course
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="navigation-links">
            <a href="courses.php"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
            <a href="student_search_courses.php"><i class="fas fa-search"></i> Search Courses</a>
        </div>

        <?php include "includes/footer.php"; ?>
    </div>

    <script src="js/student_sidebar.js"></script>

</body>
</html>