// js/sidebar.js (Updated)

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
            // Special handling for admin_add_course.php and admin_edit_course.php
            // These should activate the 'Manage Courses' link (admin_courses.php)
           else if (
                (currentPagePath === 'admin_add_course.php' || currentPagePath === 'admin_edit_course.php' || currentPagePath === 'admin_enrollments.php' || currentPagePath === 'admin_progress.php') &&
                linkPath === 'admin_courses.php'
            ) {
                link.classList.add('active');
            }
            
          else if( currentPagePath === 'admin_enrollment_approval.php' && linkPath === 'admin_course_enrollments.php') 
                {
                link.classList.add('active');
            }
        else if( currentPagePath === 'admin_edit_user.php' && linkPath === 'admin_users.php') 
                {
                link.classList.add('active');
            }
        else if( (currentPagePath === 'admin_edit_quiz.php'|| currentPagePath === 'admin_quiz_questions.php'||  currentPagePath === 'admin_quiz_questions_edit.php') && linkPath === 'admin_quizzes.php') 
                {
                link.classList.add('active');
            }
        else if( currentPagePath === 'admin_assignment_edit.php' && linkPath === 'admin_assignments.php')
                {
                link.classList.add('active');
            }
        else if( (currentPagePath === 'admin_manage_forum.php'|| currentPagePath === 'admin_manage_course_posts.php') && linkPath === 'admin_forum.php')
                {
                link.classList.add('active');
            }
        else if( (currentPagePath === 'admin_change_email.php'|| currentPagePath === 'admin_change_password.php') && linkPath === 'admin_profile.php')
                {
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