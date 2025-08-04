<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

if (!isset($_GET['course_id'])) {
    echo "Course ID missing.";
    exit;
}

$course_id = intval($_GET['course_id']);
$teacher_id = $_SESSION['user_id'];

// Kiểm tra quyền sở hữu khóa học
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($course_title);
if (!$stmt->fetch()) {
    echo "You are not allowed to access this course analytics.";
    exit;
}
$stmt->close();

// Lấy danh sách tất cả học sinh đã enroll trong khóa học
$all_students_in_course = [];
$stmt = $conn->prepare("
    SELECT u.id, u.fullname
    FROM users u
    JOIN enrollments e ON u.id = e.user_id
    WHERE e.course_id = ? AND u.role = 'student'
    ORDER BY u.fullname ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_students_in_course[] = $row;
}
$stmt->close();


// Tổng số học viên đã enroll
$stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM enrollments WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total_students'];
$stmt->close();

// Tổng số bài học
$stmt = $conn->prepare("SELECT COUNT(*) AS total_lessons FROM lessons WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_lessons = $result->fetch_assoc()['total_lessons'];
$stmt->close();

// Tỉ lệ hoàn thành bài học trung bình (%)
$avg_progress = 0;
if ($total_lessons > 0 && $total_students > 0) {
    // Corrected query to avoid ONLY_FULL_GROUP_BY issue
    // This calculates each student's completion percentage and then averages those percentages.
    $avg_progress_query = "
        SELECT AVG(student_progress.completion_percentage) AS avg_completion
        FROM (
            SELECT
                p.user_id,
                (COUNT(DISTINCT p.lesson_id) * 100.0 / ?) AS completion_percentage
            FROM
                progress p
            WHERE
                p.course_id = ? AND p.is_completed = 1
            GROUP BY
                p.user_id
        ) AS student_progress;
    ";
    $stmt = $conn->prepare($avg_progress_query);
    // Bind $total_lessons for the division, and $course_id for filtering
    // 'd' for float/double (for division), 'i' for integer (for course_id)
    $stmt->bind_param("di", $total_lessons, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $avg_progress = round($result->fetch_assoc()['avg_completion'] ?? 0, 2);
    $stmt->close();
}


// Điểm quiz trung bình
$stmt = $conn->prepare("
    SELECT AVG(score) AS avg_score
    FROM quiz_submissions
    WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) AND score IS NOT NULL
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_score_row = $result->fetch_assoc();
$avg_score = $avg_score_row['avg_score'] !== null ? round($avg_score_row['avg_score'], 2) : 'N/A';
$stmt->close();

// Lấy tất cả điểm quiz của học viên trong khóa học
$all_quiz_scores = [];
$stmt = $conn->prepare("
    SELECT
        qs.user_id,
        u.fullname AS student_name,
        q.title AS quiz_title,
        qs.score,
        qs.submitted_at -- Giả sử bạn có cột submission_date trong quiz_submissions
    FROM
        quiz_submissions qs
    JOIN
        quizzes q ON qs.quiz_id = q.id
    JOIN
        users u ON qs.user_id = u.id
    WHERE
        q.course_id = ? AND qs.score IS NOT NULL
    ORDER BY
        qs.user_id, qs.submitted_at ASC, q.id ASC -- Sắp xếp để có thứ tự cho Moving Average
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$raw_quiz_data_by_student = [];
while ($row = $result->fetch_assoc()) {
    $raw_quiz_data_by_student[$row['user_id']][] = $row;
}
$stmt->close();

$student_quiz_trends = [];
$window_size = 3; // Kích thước cửa sổ Moving Average, ví dụ 3 bài quiz gần nhất

foreach ($raw_quiz_data_by_student as $user_id => $quiz_entries) {
    $scores = array_column($quiz_entries, 'score');
    $quiz_titles = array_column($quiz_entries, 'quiz_title'); // Dùng làm nhãn cho biểu đồ
    $moving_average_scores = [];

    if (count($scores) > 0) {
        for ($i = 0; $i < count($scores); $i++) {
            $sum = 0;
            $count = 0;
            for ($j = max(0, $i - $window_size + 1); $j <= $i; $j++) {
                $sum += $scores[$j];
                $count++;
            }
            $moving_average_scores[] = round($sum / $count, 2);
        }
    }

    $student_quiz_trends[] = [
        'user_id' => $user_id,
        'student_name' => $quiz_entries[0]['student_name'],
        'quiz_titles' => $quiz_titles,
        'raw_scores' => $scores, // Giữ lại để debug hoặc so sánh
        'moving_average_scores' => $moving_average_scores
    ];
}

// Tổng số assignments trong khóa
$stmt = $conn->prepare("SELECT COUNT(*) AS total_assignments FROM assignments WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_assignments = $result->fetch_assoc()['total_assignments'];
$stmt->close();

// Tổng số bài nộp assignments
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_submissions FROM assignment_submissions
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$total_submissions = $result->fetch_assoc()['total_submissions'];
$stmt->close();

// Điểm trung bình assignments
$stmt = $conn->prepare("
    SELECT AVG(grade) AS avg_grade FROM assignment_submissions
    WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?) AND grade IS NOT NULL
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$avg_grade_row = $result->fetch_assoc();
$avg_grade = $avg_grade_row['avg_grade'] !== null ? round($avg_grade_row['avg_grade'], 2) : 'N/A';
$stmt->close();


// === BẮT ĐẦU THÊM MỚI CHO ASSIGNMENT TRENDS ===

// Lấy tất cả điểm assignments của học viên trong khóa học, sắp xếp theo thứ tự
$all_assignment_grades = [];
$stmt = $conn->prepare("
    SELECT
        asa.user_id,
        u.fullname AS student_name,
        a.title AS assignment_title,
        asa.grade,
        asa.submitted_at -- Giả sử bạn có cột submission_date_time trong assignment_submissions
    FROM
        assignment_submissions asa
    JOIN
        assignments a ON asa.assignment_id = a.id
    JOIN
        users u ON asa.user_id = u.id
    WHERE
        a.course_id = ? AND asa.grade IS NOT NULL
    ORDER BY
        asa.user_id, asa.submitted_at ASC, a.id ASC -- Sắp xếp để có thứ tự cho Moving Average
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$raw_assignment_data_by_student = [];
while ($row = $result->fetch_assoc()) {
    $raw_assignment_data_by_student[$row['user_id']][] = $row;
}
$stmt->close();

$student_assignment_trends = [];
$assignment_window_size = 3; // Kích thước cửa sổ Moving Average cho assignment, ví dụ 3 bài gần nhất

foreach ($raw_assignment_data_by_student as $user_id => $assignment_entries) {
    $grades = array_column($assignment_entries, 'grade');
    $assignment_titles = array_column($assignment_entries, 'assignment_title'); // Dùng làm nhãn cho biểu đồ
    $moving_average_grades = [];

    if (count($grades) > 0) {
        for ($i = 0; $i < count($grades); $i++) {
            $sum = 0;
            $count = 0;
            for ($j = max(0, $i - $assignment_window_size + 1); $j <= $i; $j++) {
                $sum += $grades[$j];
                $count++;
            }
            $moving_average_grades[] = round($sum / $count, 2);
        }
    }

    $student_assignment_trends[] = [
        'user_id' => $user_id,
        'student_name' => $assignment_entries[0]['student_name'],
        'assignment_titles' => $assignment_titles,
        'raw_grades' => $grades, // Giữ lại để debug hoặc so sánh
        'moving_average_grades' => $moving_average_grades
    ];
}

// === KẾT THÚC THÊM MỚI CHO ASSIGNMENT TRENDS ===

// === BẮT ĐẦU THÊM MỚI CHO TOP 5 STUDENTS ===
$student_average_scores = [];

// Lấy tất cả điểm quiz của học viên
$stmt = $conn->prepare("
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
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $student_average_scores[$row['user_id']]['student_name'] = $row['student_name'];
    $student_average_scores[$row['user_id']]['quiz_scores'] = $row['average_quiz_score'];
}
$stmt->close();

// Lấy tất cả điểm assignment của học viên
$stmt = $conn->prepare("
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
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $student_average_scores[$row['user_id']]['student_name'] = $row['student_name'];
    $student_average_scores[$row['user_id']]['assignment_grades'] = $row['average_assignment_grade'];
}
$stmt->close();

// Tính điểm trung bình tổng thể và chuẩn bị cho Top-K Selection
$final_student_scores = [];
foreach ($student_average_scores as $user_id => $data) {
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
        $final_student_scores[$user_id] = [
            'student_name' => $data['student_name'],
            'average_overall_score' => round($total_score / $count, 2)
        ];
    }
}

// Sắp xếp giảm dần theo điểm trung bình tổng thể (Top-K Selection)
uasort($final_student_scores, function($a, $b) {
    return $b['average_overall_score'] <=> $a['average_overall_score'];
});

// Lấy top 5
$top_5_students = array_slice($final_student_scores, 0, 5, true);

// === KẾT THÚC THÊM MỚI CHO TOP 5 STUDENTS ===


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - <?= htmlspecialchars($course_title ?? 'Course') ?> | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/teacher/teacher_analytics.css">
   
</head>
<body>

    <?php include "../../../includes/teacher_sidebar.php"; ?>

    <div class="main-content">
        <div class="teacher-dashboard-header">
            <h2><i class="fas fa-chart-line"></i> Analytics for: <?= htmlspecialchars($course_title) ?></h2>
            <a href="../courses/teacher_courses.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
        </div>

        <div class="dashboard-section">
            <h3><i class="fas fa-chart-line"></i> Course Statistics</h3>

            <div class="dashboard-section chart-section">
                <h3><i class="fas fa-chart-line"></i> Student Quiz Score Trends</h3>
                <div class="chart-controls">
                    <label for="quizStudentSelect">Select Student(s):</label>
                    <select id="quizStudentSelect" multiple>
                        <option value="all">All Students</option>
                        <?php foreach ($all_students_in_course as $student): ?>
                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="applyStudentFilter('quiz')">Apply Filter</button>
                </div>
                <div class="chart-container">
                    <canvas id="studentQuizScoreChart"></canvas>
                </div>
            </div>
            <div class="dashboard-section chart-section">
                <h3><i class="fas fa-chart-line"></i> Student Assignment Grade Trends</h3>
                <div class="chart-controls">
                    <label for="assignmentStudentSelect">Select Student(s):</label>
                    <select id="assignmentStudentSelect" multiple>
                        <option value="all">All Students</option>
                        <?php foreach ($all_students_in_course as $student): ?>
                            <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="applyStudentFilter('assignment')">Apply Filter</button>
                </div>
                <div class="chart-container">
                    <canvas id="studentAssignmentGradeChart"></canvas>
                </div>
            </div>
            <div class="dashboard-section">
                <h3><i class="fas fa-trophy"></i> Top 5 Students by Average Score</h3>
                <?php if (!empty($top_5_students)): ?>
                    <table class="top-students-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student Name</th>
                                <th>Average Overall Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; ?>
                            <?php foreach ($top_5_students as $student): ?>
                                <tr>
                                    <td><?= $rank++ ?></td>
                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                    <td><?= htmlspecialchars($student['average_overall_score']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No student data available to determine top performers.</p>
                <?php endif; ?>
            </div>
            <div class="quick-stats-grid">
                <div class="overview-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Total Students Enrolled</span>
                    <strong><?= htmlspecialchars($total_students) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-book-reader"></i>
                    <span>Average Lesson Completion</span>
                    <strong><?= htmlspecialchars($avg_progress) ?>%</strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Total Lessons</span>
                    <strong><?= htmlspecialchars($total_lessons) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-tasks"></i>
                    <span>Total Assignments</span>
                    <strong><?= htmlspecialchars($total_assignments) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Average Quiz Score</span>
                    <strong><?= htmlspecialchars($avg_score) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-marker"></i>
                    <span>Average Assignment Grade</span>
                    <strong><?= htmlspecialchars($avg_grade) ?></strong>
                </div>
                <div class="overview-item">
                    <i class="fas fa-upload"></i>
                    <span>Total Assignment Submissions</span>
                    <strong><?= htmlspecialchars($total_submissions) ?></strong>
                </div>
            </div>
            
            
            </div>
        
        <div class="logout-link">
            <a href="../../../common/logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>
    <script src="../../../js/teacher_sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../../js/teacher_chart.js"></script>
    <script>
        // Store the full dataset globally or pass it to a chart manager
        const fullStudentQuizTrendsData = <?= json_encode($student_quiz_trends) ?>;
        const fullStudentAssignmentTrendsData = <?= json_encode($student_assignment_trends) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Initial chart load with all students
            initQuizScoreChart(fullStudentQuizTrendsData);
            initAssignmentGradeChart(fullStudentAssignmentTrendsData);
        });

        function applyStudentFilter(chartType) {
            let selectedStudentIds;
            let dataToRender;

            if (chartType === 'quiz') {
                const selectElement = document.getElementById('quizStudentSelect');
                selectedStudentIds = Array.from(selectElement.selectedOptions).map(option => option.value);
                
                if (selectedStudentIds.includes('all') || selectedStudentIds.length === 0) {
                    dataToRender = fullStudentQuizTrendsData;
                } else {
                    dataToRender = fullStudentQuizTrendsData.filter(student => 
                        selectedStudentIds.includes(String(student.user_id))
                    );
                }
                initQuizScoreChart(dataToRender);
            } else if (chartType === 'assignment') {
                const selectElement = document.getElementById('assignmentStudentSelect');
                selectedStudentIds = Array.from(selectElement.selectedOptions).map(option => option.value);

                if (selectedStudentIds.includes('all') || selectedStudentIds.length === 0) {
                    dataToRender = fullStudentAssignmentTrendsData;
                } else {
                    dataToRender = fullStudentAssignmentTrendsData.filter(student => 
                        selectedStudentIds.includes(String(student.user_id))
                    );
                }
                initAssignmentGradeChart(dataToRender);
            }
        }
    </script>
</body>
</html>