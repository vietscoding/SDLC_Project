<?php
// includes/sidebar.php
// Đảm bảo các biến session và đường dẫn được xử lý đúng nếu cần
?>
<div class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="../dashboard/teacher_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="../courses/teacher_courses.php"><i class="fas fa-book"></i> Courses</a></li>
        <li><a href="../courses/teacher_search_courses.php"><i class="fas fa-book"></i>Search Courses</a></li>
        <li><a href="../assignment/teacher_assignments.php"><i class="fas fa-tasks"></i> Assignments</a></li>
        <li><a href="../quiz/teacher_quizzes.php"><i class="fas fa-question-circle"></i> Quizzes</a></li>
        <li><a href="../notification/teacher_notifications.php"><i class="fas fa-users"></i> Send Notifications</a></li>
        <li><a href="../notification/teacher_view_notifications.php"><i class="fas fa-users"></i> View Notifications</a></li>
        <li><a href="../forum/teacher_forum_courses.php"><i class="fas fa-clipboard-list"></i> Course Forum</a></li>
        <li><a href="../profile/teacher_profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
        <li>
    <a href="../../../common/logout.php" onclick="return confirm('Are you sure you want to log out?');">
        <i class="fas fa-sign-out-alt"></i> Log out
    </a>
</li>
    </ul>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>