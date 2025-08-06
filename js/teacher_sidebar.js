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

    /**
     * Chuẩn hóa một đường dẫn tuyệt đối (từ window.location.pathname)
     * về một đường dẫn tương đối so với một base path của dự án.
     * @param {string} absolutePath Đường dẫn tuyệt đối từ gốc domain (ví dụ: /SDLC_project/module/teacher/dashboard/teacher_dashboard.php)
     * @param {string} projectBasePath Đường dẫn cơ sở của dự án trong URL (ví dụ: /SDLC_project/)
     * @returns {string} Đường dẫn tương đối (ví dụ: module/teacher/dashboard/teacher_dashboard.php)
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
        const currentPathname = window.location.pathname; // Ví dụ: /SDLC_project/module/teacher/dashboard/teacher_dashboard.php

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
        const currentPageFileName = currentRelativePath.split('/').pop().split('?')[0]; // Lấy tên file cuối cùng (ví dụ: teacher_dashboard.php)

        sidebarLinks.forEach(link => {
            link.classList.remove('active'); // Xóa lớp 'active' khỏi tất cả các liên kết trước
            const linkHref = link.getAttribute('href'); // Lấy href của liên kết sidebar (ví dụ: ../dashboard/teacher_dashboard.php)
            
            if (!linkHref) return; // Bỏ qua nếu không có href

            const linkFileName = linkHref.split('/').pop().split('?')[0]; // Lấy tên file từ href của liên kết

            // So sánh trực tiếp tên file
            if (linkFileName === currentPageFileName) {
                link.classList.add('active');
            }
            // Xử lý các trang con (parent-child relationships)
            else if (linkFileName === 'teacher_profile.php' && ['teacher_change_email.php', 'teacher_change_password.php'].includes(currentPageFileName)) {
                link.classList.add('active');
            }
            // Courses and related pages
            else if (linkFileName === 'teacher_courses.php' && ['teacher_lessons.php', 'edit_lesson.php', 'teacher_enrollments.php', 'teacher_progress.php', 'teacher_enroll_approval.php', 'teacher_add_course.php', 'teacher_edit_course.php','teacher_analytics.php'].includes(currentPageFileName)) {
                link.classList.add('active');
            }
            // Search Courses (assuming teacher_course_detail.php is a child of search courses in the UI flow)
            else if (linkFileName === 'teacher_search_courses.php' && ['teacher_course_detail.php'].includes(currentPageFileName)) {
                link.classList.add('active');
            }
            // Assignments and related pages
            else if (linkFileName === 'teacher_assignments.php' && ['teacher_assignment_edit.php', 'teacher_assignment_submissions.php'].includes(currentPageFileName)) {
                link.classList.add('active');
            }
            // Quizzes and related pages
            else if (linkFileName === 'teacher_quizzes.php' && ['teacher_quiz_edit.php', 'teacher_quiz_questions.php', 'teacher_quiz_questions_edit.php', 'teacher_quiz_results.php'].includes(currentPageFileName)) {
                link.classList.add('active');
            }
            // Course Forum and related pages
            else if (linkFileName === 'teacher_forum_courses.php' && ['teacher_forum.php', 'teacher_my_posts.php', 'teacher_create_post.php', 'teacher_edit_comment.php', 'teacher_edit_post.php', 'teacher_view_post.php','teacher_edit_post.php'].includes(currentPageFileName)) {
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
        // Gọi lại để đảm bảo active link được đặt lại nếu có thay đổi kích thước ảnh hưởng đến hiển thị
        setActiveSidebarLink();
    });
});