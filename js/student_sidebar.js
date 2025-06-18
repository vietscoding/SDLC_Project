// js/teacher_sidebar.js

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    const sidebarLinks = sidebar.querySelectorAll('ul li a');

    if (!sidebarToggle || !sidebar || !sidebarOverlay) {
        console.error('Sidebar elements not found. Check your HTML IDs.');
        return;
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        if (mainContent) {
            mainContent.classList.remove('sidebar-open');
        }
    }

    // Function to set the active sidebar link
    function setActiveSidebarLink() {
        const currentPagePath = window.location.pathname.split('/').pop();

        sidebarLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            link.classList.remove('active'); // Remove active from all first to ensure only one is active

            // Direct match (for dashboard, users, etc.)
            if (linkPath === currentPagePath) {
                link.classList.add('active');
            }
            // Special handling for teacher_add_course.php, teacher_edit_course.php, etc.
            // These should activate the 'Courses' link (teacher_courses.php)
            else if (
                (currentPagePath === 'course_detail.php' || currentPagePath === 'lesson.php' || currentPagePath === 'quiz_list.php' || currentPagePath === 'quiz.php' || currentPagePath === 'teacher_enroll_approval.php' ) &&
                linkPath === 'courses.php'
            ) {
                link.classList.add('active');
            }
            // Handle specific cases for assignments
             else if (
                ( currentPagePath === '' || currentPagePath === '' || currentPagePath === '' || currentPagePath === '' ) &&
                linkPath === 'student_search_courses.php'
            ) {
                link.classList.add('active');
            }
            else if (
                ( currentPagePath === 'submit_assignment.php' || currentPagePath === '' || currentPagePath === '' || currentPagePath === '' ) &&
                linkPath === 'student_assignments.php'
            ) {
                link.classList.add('active');
            }
            else if (
                ( currentPagePath === 'student_forum.php' || currentPagePath === 'student_create_post.php' || currentPagePath === 'student_my_posts.php' || currentPagePath === 'student_edit_post.php' || currentPagePath === 'student_view_post.php' ) &&
                linkPath === 'student_forum_courses.php'
            ) {
                link.classList.add('active');
            }
            else if (
                ( currentPagePath === 'student_change_email.php' || currentPagePath === 'student_change_password.php'  ) &&
                linkPath === 'student_profile.php'
            ) {
                link.classList.add('active');
            }
        });
    }

    // Initial call to set active link on page load
    setActiveSidebarLink();

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        if (mainContent) {
            mainContent.classList.toggle('sidebar-open');
        }
    });

    sidebarOverlay.addEventListener('click', closeSidebar);

    // Close sidebar when clicking on a link (mobile only)
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
            // No need to call setActiveSidebarLink() here as page reload will trigger DOMContentLoaded
        });
    });

    // Handle screen resize to ensure sidebar displays correctly
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
            sidebarOverlay.classList.remove('active');
            if (mainContent) {
                mainContent.classList.remove('sidebar-open');
            }
        }
        setActiveSidebarLink(); // Re-evaluate active link on resize, especially for initial load
    });
});