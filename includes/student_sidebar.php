<?php
// includes/student_sidebar.php

// Lấy tên file của trang đang được truy cập
$current_script_name = basename($_SERVER['PHP_SELF']);

/**
 * Hàm kiểm tra và trả về lớp 'active' cho liên kết sidebar.
 *
 * @param string $link_target_href Đường dẫn đầy đủ của liên kết trong sidebar
 * (Ví dụ: '../dashboard/student_dashboard.php').
 * @param string $current_script   Tên file PHP của trang hiện tại (từ basename($_SERVER['PHP_SELF'])).
 * @return string Trả về 'active' nếu liên kết nên được đánh dấu active, ngược lại trả về chuỗi rỗng.
 */
function isActive($link_target_href, $current_script) {
    // Lấy chỉ tên file từ đường dẫn href để so sánh đồng nhất
    $link_filename_only = basename($link_target_href);

    // 1. Kiểm tra sự trùng khớp trực tiếp giữa tên file liên kết và tên file trang hiện tại
    if ($link_filename_only === $current_script) {
        return 'active';
    }

    // 2. Kiểm tra các trường hợp trang hiện tại là trang con của một mục sidebar chính (cha)
    // Key là tên file của mục sidebar cha, value là mảng các tên file của trang con.
    $parent_child_map = [
        'courses.php'             => ['student_search_courses.php', 'course_detail.php'],
        'student_assignments.php' => ['student_view_assignments.php'],
        'student_forum_courses.php' => ['student_add_forum_post.php', 'student_view_forum_post.php'],
        'student_profile.php'     => ['student_change_email.php', 'student_change_password.php'],
        // Thêm các mối quan hệ cha-con khác nếu có.
        // Ví dụ: Nếu 'student_quiz_list.php' là trang cha cho 'student_quiz_details.php'
        // 'student_quiz_list.php' => ['student_quiz_details.php'],
    ];

    // Nếu $link_filename_only là một khóa trong map (tức là nó là một mục cha)
    if (array_key_exists($link_filename_only, $parent_child_map)) {
        // Và $current_script (tên file trang hiện tại) nằm trong danh sách các trang con của mục cha đó
        if (in_array($current_script, $parent_child_map[$link_filename_only])) {
            return 'active';
        }
    }

    return ''; // Mặc định không active
}
?>
<div class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="https://cdn.haitrieu.com/wp-content/uploads/2023/02/Logo-Truong-cao-dang-Quoc-te-BTEC-FPT.png" alt="BTEC Logo">
    </div>
    <ul>
        <li><a href="../dashboard/student_dashboard.php" class="<?php echo isActive('../dashboard/student_dashboard.php', $current_script_name); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="../courses/courses.php" class="<?php echo isActive('../courses/courses.php', $current_script_name); ?>"><i class="fas fa-book"></i> <span>Courses</span></a></li>
        <li><a href="../courses/student_search_courses.php" class="<?php echo isActive('../courses/student_search_courses.php', $current_script_name); ?>"><i class="fas fa-search"></i> <span>Search Courses</span></a></li>
        <li><a href="../courses/progress.php" class="<?php echo isActive('../courses/progress.php', $current_script_name); ?>"><i class="fas fa-chart-line"></i> <span>Academic Progress</span></a></li>
        <li><a href="../notification/notifications.php" class="<?php echo isActive('../notification/notifications.php', $current_script_name); ?>"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
        <li><a href="../assignment/student_assignments.php" class="<?php echo isActive('../assignment/student_assignments.php', $current_script_name); ?>"><i class="fas fa-tasks"></i> <span>Assignments</span></a></li>
        <li><a href="../assignment/student_view_assignments.php" class="<?php echo isActive('../assignment/student_view_assignments.php', $current_script_name); ?>"><i class="fas fa-check-circle"></i> <span>Grades & Results</span></a></li>
        <li><a href="../forum/student_forum_courses.php" class="<?php echo isActive('../forum/student_forum_courses.php', $current_script_name); ?>"><i class="fas fa-comments"></i> <span>Course Forum</span></a></li>
        <li><a href="../profile/student_profile.php" class="<?php echo isActive('../profile/student_profile.php', $current_script_name); ?>"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
        <li>
            <a href="../../../common/logout.php" onclick="return confirm('Are you sure you want to log out?');">
                <i class="fas fa-sign-out-alt"></i> Log out
            </a>
        </li>
    </ul>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>