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
                (currentPagePath === 'teacher_lessons.php' || currentPagePath === 'edit_lesson.php' || currentPagePath === 'teacher_enrollments.php' || currentPagePath === 'teacher_progress.php' || currentPagePath === 'teacher_enroll_approval.php' ) &&
                linkPath === 'teacher_courses.php'
            ) {
                link.classList.add('active');
            }
            // Handle specific cases for assignments
            else if(currentPagePath === 'teacher_course_detail.php' && linkPath === 'teacher_search_courses.php') {
                link.classList.add('active');
            }
            // Handle specific cases for quizzes
            else if((currentPagePath === 'teacher_assignment_edit.php'|| currentPagePath === 'teacher_assignment_submissions.php') && linkPath === 'teacher_assignments.php') {
                link.classList.add('active');
            }
            // Handle specific cases for forum posts within a course
            else if((currentPagePath === 'teacher_quiz_edit.php'|| currentPagePath === 'teacher_quiz_questions.php' || currentPagePath === 'teacher_quiz_questions_edit.php' || currentPagePath === 'teacher_quiz_results.php') && linkPath === 'teacher_quizzes.php') {
                link.classList.add('active');
            }
            else if((currentPagePath === 'teacher_forum.php'
                || currentPagePath === 'teacher_my_posts.php' 
                || currentPagePath === 'teacher_create_post.php' 
                || currentPagePath === 'teacher_edit_comment.php'
                || currentPagePath === 'teacher_edit_post.php'
                || currentPagePath === 'teacher_view_post.php') && linkPath === 'teacher_forum_courses.php') {
                link.classList.add('active');
            }
            // Handle specific cases for profile edits
            else if( (currentPagePath === 'teacher_change_email.php' || currentPagePath === 'teacher_change_password.php') && linkPath === 'teacher_profile.php') {
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