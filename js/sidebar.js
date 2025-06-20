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

    /**
     * Chuẩn hóa một đường dẫn tuyệt đối (từ window.location.pathname)
     * về một đường dẫn tương đối so với một base path của dự án.
     * @param {string} absolutePath Đường dẫn tuyệt đối từ gốc domain (ví dụ: /SDLC_project/module/admin/dashboard/admin_dashboard.php)
     * @param {string} projectBasePath Đường dẫn cơ sở của dự án trong URL (ví dụ: /SDLC_project/)
     * @returns {string} Đường dẫn tương đối (ví dụ: module/admin/dashboard/admin_dashboard.php)
     */
    function getRelativePathFromAbsolute(absolutePath, projectBasePath) {
        if (absolutePath.startsWith(projectBasePath)) {
            // Loại bỏ projectBasePath khỏi đầu đường dẫn tuyệt đối
            let relativePath = absolutePath.substring(projectBasePath.length);
            // Loại bỏ dấu '/' ở cuối nếu có
            if (relativePath.endsWith('/')) {
                relativePath = relativePath.slice(0, -1);
            }
            return relativePath;
        }
        return absolutePath; // Trả về nguyên gốc nếu không khớp base path
    }

    /**
     * Hàm để xác định projectBasePath một cách linh hoạt.
     * Dựa vào cấu trúc thư mục của dự án (ví dụ: sự tồn tại của '/module/', '/common/')
     * để suy luận đường dẫn gốc của dự án trên URL.
     */
    function determineProjectBasePath() {
        const currentPathname = window.location.pathname; // Ví dụ: /SDLC_project/module/admin/dashboard/admin_dashboard.php

        // Cố gắng tìm phần '/module/' trong đường dẫn
        const moduleIndex = currentPathname.indexOf('/module/');
        if (moduleIndex !== -1) {
            return currentPathname.substring(0, moduleIndex + 1); // +1 để bao gồm dấu / cuối cùng
        }

        // Trường hợp fallback: cố gắng tìm '/common/' (nếu file login, logout nằm ở đó)
        const commonIndex = currentPathname.indexOf('/common/');
        if (commonIndex !== -1) {
             return currentPathname.substring(0, commonIndex + 1);
        }

        // Trường hợp cuối cùng: nếu không tìm thấy cấu trúc đặc trưng, giả định dự án nằm ở root
        console.warn("Could not determine projectBasePath dynamically. Assuming project is at web root.");
        return '/';
    }

    // --- CẤU HÌNH ĐƯỜNG DẪN GỐC CỦA DỰ ÁN CỦA BẠN ---
    // Gọi hàm để xác định projectBasePath một cách linh hoạt
    const projectBasePath = determineProjectBasePath();
    console.log("Dynamically determined projectBasePath:", projectBasePath); // Để kiểm tra trong console

    // Function to set the active sidebar link
    function setActiveSidebarLink() {
        const currentPathname = window.location.pathname; // Lấy đường dẫn hiện tại
        const currentRelativePath = getRelativePathFromAbsolute(currentPathname, projectBasePath); // Chuẩn hóa đường dẫn
        const currentPageFileName = currentRelativePath.split('/').pop().split('?')[0]; // Lấy tên file cuối cùng (ví dụ: admin_dashboard.php)

        sidebarLinks.forEach(link => {
            link.classList.remove('active'); // Remove active from all first to ensure only one is active
            const linkHref = link.getAttribute('href'); // Lấy href của liên kết sidebar (ví dụ: ../dashboard/admin_dashboard.php)

            if (!linkHref) return; // Bỏ qua nếu không có href

            const linkFileName = linkHref.split('/').pop().split('?')[0]; // Lấy tên file từ href của liên kết

            // Direct match (for dashboard, users, etc.)
            if (linkFileName === currentPageFileName) {
                link.classList.add('active');
            }
            // Special handling for admin_add_course.php and admin_edit_course.php
            // These should activate the 'Manage Courses' link (admin_courses.php)
           else if (
                (currentPageFileName === 'admin_add_course.php' || currentPageFileName === 'admin_edit_course.php' || currentPageFileName === 'admin_enrollments.php' || currentPageFileName === 'admin_progress.php') &&
                linkFileName === 'admin_courses.php'
            ) {
                link.classList.add('active');
            }
            
          else if( currentPageFileName === 'admin_enrollment_approval.php' && linkFileName === 'admin_course_enrollments.php') 
                {
                link.classList.add('active');
            }
        else if( currentPageFileName === 'admin_edit_user.php' && linkFileName === 'admin_users.php') 
                {
                link.classList.add('active');
            }
        else if( (currentPageFileName === 'admin_edit_quiz.php'|| currentPageFileName === 'admin_quiz_questions.php'||  currentPageFileName === 'admin_quiz_questions_edit.php') && linkFileName === 'admin_quizzes.php') 
                {
                link.classList.add('active');
            }
        else if( currentPageFileName === 'admin_assignment_edit.php' && linkFileName === 'admin_assignments.php')
                {
                link.classList.add('active');
            }
        else if( (currentPageFileName === 'admin_manage_forum.php'|| currentPageFileName === 'admin_manage_course_posts.php') && linkFileName === 'admin_forum.php')
                {
                link.classList.add('active');
            }
        else if( (currentPageFileName === 'admin_change_email.php'|| currentPageFileName === 'admin_change_password.php') && linkFileName === 'admin_profile.php')
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