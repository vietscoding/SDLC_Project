<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Existing statistics (keep them as they are)
// Thống kê số lượng học viên
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

// Thống kê tổng số khóa học
$total_courses = $conn->query("SELECT COUNT(*) AS total FROM courses")->fetch_assoc()['total'];

// Thống kê tổng số quiz
$total_quizzes = $conn->query("SELECT COUNT(*) AS total FROM quizzes")->fetch_assoc()['total'];

// Thống kê tổng số bài nộp quiz
$total_submissions = $conn->query("SELECT COUNT(*) AS total FROM quiz_submissions")->fetch_assoc()['total'];

// Thống kê tổng số assignments
$total_assignments = $conn->query("SELECT COUNT(*) AS total FROM assignments")->fetch_assoc()['total'];

// Tổng số bài nộp assignment
$total_assignment_submissions = $conn->query("SELECT COUNT(*) AS total FROM assignment_submissions")->fetch_assoc()['total'];

// Thống kê tiến độ trung bình sinh viên (% lessons đã hoàn thành trên tổng bài học)
$progress_result = $conn->query("
    SELECT
        ROUND(AVG(completed.lesson_completed / total.total_lessons) * 100, 2) AS avg_progress
    FROM
        (SELECT user_id, COUNT(*) AS lesson_completed FROM progress WHERE is_completed = 1 GROUP BY user_id) AS completed
    JOIN
        (SELECT COUNT(*) AS total_lessons FROM lessons) AS total
");
$avg_progress = $progress_result->fetch_assoc()['avg_progress'] ?? 0;

// Quiz Performance Statistics for Table and Chart
$quiz_stats_query = "
    SELECT
        q.title,
        AVG(qs.score) AS avg_score,
        COUNT(qs.id) AS submission_count
    FROM quizzes q
    LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id
    GROUP BY q.id
    ORDER BY avg_score DESC
";
$quiz_stats = $conn->query($quiz_stats_query);

// Assignment Performance Statistics for Table and Chart
$assignment_stats_query = "
    SELECT
        a.title,
        AVG(asub.grade) AS avg_grade,
        COUNT(asub.id) AS submission_count
    FROM assignments a
    LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
    GROUP BY a.id
    ORDER BY avg_grade DESC
";
$assignment_stats = $conn->query($assignment_stats_query);

// --- Dữ liệu cho BIỂU ĐỒ QUIZ Performance ---
$quiz_chart_data = [];
if ($quiz_stats->num_rows > 0) {
    // Reset internal pointer to reuse data for chart
    $quiz_stats->data_seek(0);
    while ($row = $quiz_stats->fetch_assoc()) {
        $quiz_chart_data[] = [
            'title' => htmlspecialchars($row['title']),
            'avg_score' => $row['avg_score'] !== null ? round($row['avg_score'], 2) : 'N/A'
        ];
    }
}

// --- Dữ liệu cho BIỂU ĐỒ ASSIGNMENT Performance ---
$assignment_chart_data = [];
if ($assignment_stats->num_rows > 0) {
    // Reset internal pointer to reuse data for chart
    $assignment_stats->data_seek(0);
    while ($row = $assignment_stats->fetch_assoc()) {
        $assignment_chart_data[] = [
            'title' => htmlspecialchars($row['title']),
            'avg_grade' => $row['avg_grade'] !== null ? round($row['avg_grade'], 2) : 'N/A'
        ];
    }
}

// === START: TOP K STUDENTS FOR ALL COURSES ===
$all_courses_top_students = [];
$top_k_limit = 5; // Define K

// Get all course IDs and titles
$courses_query = $conn->query("SELECT id, title FROM courses");
while ($course = $courses_query->fetch_assoc()) {
    $course_id = $course['id'];
    $course_title = $course['title'];

    $student_average_scores_for_course = [];

    // Get all quiz scores for students in this course
    $stmt_quiz = $conn->prepare("
        SELECT
            qs.user_id,
            u.fullname AS student_name,
            AVG(qs.score) AS average_quiz_score
        FROM
            quiz_submissions qs
        JOIN
            quizzes q ON qs.quiz_id = q.id
        JOIN
            users u ON qs.user_id = u.id
        WHERE
            q.course_id = ? AND qs.score IS NOT NULL
        GROUP BY
            qs.user_id, u.fullname
    ");
    $stmt_quiz->bind_param("i", $course_id);
    $stmt_quiz->execute();
    $result_quiz = $stmt_quiz->get_result();
    while ($row = $result_quiz->fetch_assoc()) {
        $student_average_scores_for_course[$row['user_id']]['student_name'] = $row['student_name'];
        $student_average_scores_for_course[$row['user_id']]['quiz_scores'] = $row['average_quiz_score'];
    }
    $stmt_quiz->close();

    // Get all assignment grades for students in this course
    $stmt_assignment = $conn->prepare("
        SELECT
            asa.user_id,
            u.fullname AS student_name,
            AVG(asa.grade) AS average_assignment_grade
        FROM
            assignment_submissions asa
        JOIN
            assignments a ON asa.assignment_id = a.id
        JOIN
            users u ON asa.user_id = u.id
        WHERE
            a.course_id = ? AND asa.grade IS NOT NULL
        GROUP BY
            asa.user_id, u.fullname
    ");
    $stmt_assignment->bind_param("i", $course_id);
    $stmt_assignment->execute();
    $result_assignment = $stmt_assignment->get_result();
    while ($row = $result_assignment->fetch_assoc()) {
        // Ensure student_name is set even if they only have assignment grades
        $student_average_scores_for_course[$row['user_id']]['student_name'] = $row['student_name'];
        $student_average_scores_for_course[$row['user_id']]['assignment_grades'] = $row['average_assignment_grade'];
    }
    $stmt_assignment->close();

    // Calculate overall average for each student in the current course
    $final_student_scores_for_course = [];
    foreach ($student_average_scores_for_course as $user_id => $data) {
        $total_score = 0;
        $count = 0;
        if (isset($data['quiz_scores'])) {
            $total_score += $data['quiz_scores'];
            $count++;
        }
        if (isset($data['assignment_grades'])) {
            $total_score += $data['assignment_grades'];
            $count++;
        }

        if ($count > 0) {
            $final_student_scores_for_course[$user_id] = [
                'student_name' => $data['student_name'],
                'average_overall_score' => round($total_score / $count, 2)
            ];
        }
    }

    // Sort students by average score in descending order
    uasort($final_student_scores_for_course, function($a, $b) {
        return $b['average_overall_score'] <=> $a['average_overall_score'];
    });

    // Get the top K students for the current course
    $top_k_students_for_course = array_slice($final_student_scores_for_course, 0, $top_k_limit, true);

    $all_courses_top_students[] = [
        'course_id' => $course_id,
        'course_title' => $course_title,
        'top_students' => $top_k_students_for_course
    ];
}
// === END: TOP K STUDENTS FOR ALL COURSES ===

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_reports.css">
</head>
<body>
    <?php include "../../../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-reports-header">
            <h2><i class="fas fa-chart-line"></i> Admin Reports</h2>
        </div>
 <div class="dashboard-section chart-section">
            <h3><i class="fas fa-chart-bar"></i> Quiz Performance Overview</h3>
            <div class="chart-container">
                <canvas id="quizPerformanceChart"></canvas>
            </div>
        </div>

        <div class="dashboard-section chart-section">
            <h3><i class="fas fa-chart-line"></i> Assignment Performance Overview</h3>
            <div class="chart-container">
                <canvas id="assignmentPerformanceChart"></canvas>
            </div>
        </div>
        <div class="dashboard-section">
            <h3><i class="fas fa-tachometer-alt"></i> System Overview</h3>
            <div class="stats-cards-container">
            <div class="stat-card">
                <i class="fas fa-user-graduate icon"></i>
                <span class="label">Total Students</span>
                <strong class="value"><?= $total_students ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-book icon"></i>
                <span class="label">Total Courses</span>
                <strong class="value"><?= $total_courses ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-question-circle icon"></i>
                <span class="label">Total Quizzes</span>
                <strong class="value"><?= $total_quizzes ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt icon"></i>
                <span class="label">Total Quiz Submissions</span>
                <strong class="value"><?= $total_submissions ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks icon"></i>
                <span class="label">Total Assignments</span>
                <strong class="value"><?= $total_assignments ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-upload icon"></i>
                <span class="label">Total Assignment Submissions</span>
                <strong class="value"><?= $total_assignment_submissions ?></strong>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner icon"></i>
                <span class="label">Average Student Progress</span>
                <strong class="value"><?= $avg_progress ?>%</strong>
            </div>

        </div>
        </div>


        <div class="report-section">
            <h3><i class="fas fa-clipboard-question"></i> Quiz Performance Statistics</h3>
            <?php if ($quiz_stats->num_rows > 0): ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Average Score</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset internal pointer for table display as it was used for chart data
                        $quiz_stats->data_seek(0);
                        while ($row = $quiz_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= $row['avg_score'] !== null ? round($row['avg_score'], 2) : 'N/A' ?></td>
                                <td><?= $row['submission_count'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-records"><i class="fas fa-exclamation-circle"></i> No quiz records found.</p>
            <?php endif; ?>
        </div>

        <div class="report-section">
            <h3><i class="fas fa-clipboard-check"></i> Assignment Performance Statistics</h3>
            <?php if ($assignment_stats->num_rows > 0): ?>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Assignment Title</th>
                            <th>Average Grade</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset internal pointer for table display as it was used for chart data
                        $assignment_stats->data_seek(0);
                        while ($row = $assignment_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= $row['avg_grade'] !== null ? round($row['avg_grade'], 2) : 'N/A' ?></td>
                                <td><?= $row['submission_count'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-records"><i class="fas fa-exclamation-circle"></i> No assignment records found.</p>
            <?php endif; ?>
        </div>

        <div class="report-section">
            <h3><i class="fas fa-medal"></i> Top <?= $top_k_limit ?> Students Per Course (Overall Average)</h3>
            <?php if (!empty($all_courses_top_students)): ?>
                <?php foreach ($all_courses_top_students as $course_data): ?>
                    <div class="course-top-students">
                        <h4>Course: <?= htmlspecialchars($course_data['course_title']) ?></h4>
                        <?php if (!empty($course_data['top_students'])): ?>
                            <table class="stats-table top-students-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student Name</th>
                                        <th>Average Overall Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($course_data['top_students'] as $student): ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= htmlspecialchars($student['student_name']) ?></td>
                                            <td><?= htmlspecialchars($student['average_overall_score']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-records"><i class="fas fa-info-circle"></i> No student performance data for this course yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-records"><i class="fas fa-exclamation-circle"></i> No courses found or no student data available.</p>
            <?php endif; ?>
        </div>
        <div class="back-link">
            <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../../js/admin_reports_charts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quizChartData = <?= json_encode($quiz_chart_data) ?>;
            const assignmentChartData = <?= json_encode($assignment_chart_data) ?>;

            console.log("Quiz Chart Data:", quizChartData); // Debugging
            console.log("Assignment Chart Data:", assignmentChartData); // Debugging

            // Gọi hàm khởi tạo biểu đồ
            initQuizPerformanceChart(quizChartData);
            initAssignmentPerformanceChart(assignmentChartData);
        });
    </script>
</body>
</html>