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
        <li><a href="student_dashboard.php" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
           <li><a href="courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
            <li><a href="student_search_courses.php"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
            <li><a href="progress.php"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
            <li><a href="student_assignments.php"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
            <li><a href="student_view_assignments.php"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
            <li><a href="student_forum_courses.php"><i class="fas fa-comments"></i> <span>Course Forum</span></a></li>
            <li><a href="student_profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
        <li>
    <a href="logout.php" onclick="return confirm('Are you sure you want to log out?');">
        <i class="fas fa-sign-out-alt"></i> Log out
    </a>
</li>
    </ul>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>