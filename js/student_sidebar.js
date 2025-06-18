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
    /**
     * Chuẩn hóa một đường dẫn tuyệt đối (từ window.location.pathname)
     * về một đường dẫn tương đối so với một base path của dự án.
     * @param {string} absolutePath Đường dẫn tuyệt đối từ gốc domain (ví dụ: /SDLC_project/module/student/dashboard/student_dashboard.php)
     * @param {string} projectBasePath Đường dẫn cơ sở của dự án trong URL (ví dụ: /SDLC_project/)
     * @returns {string} Đường dẫn tương đối (ví dụ: module/student/dashboard/student_dashboard.php)
     */
    function getRelativePathFromAbsolute(absolutePath, projectBasePath) {
        if (absolutePath.startsWith(projectBasePath)) {
            let relativePath = absolutePath.substring(projectBasePath.length);
            // Loại bỏ các tham số query và hash nếu có
            relativePath = relativePath.split('?')[0].split('#')[0];
            return relativePath;
        }
        return absolutePath; // Trả về nguyên nếu không khớp base path
    }

    // Function to set the active sidebar link
    /*function setActiveSidebarLink() {
        // --- CẤU HÌNH ĐƯỜNG DẪN GỐC CỦA DỰ ÁN CỦA BẠN ---
        // Bạn cần xác định chính xác phần này.
        // Nếu dự án của bạn chạy ở http://localhost/SDLC_project/
        // thì projectBasePath là '/SDLC_project/'
        // Nếu bạn deploy lên http://yourdomain.com/ (thư mục gốc), thì projectBasePath là '/'
        const projectBasePath = '/SDLC_project/'; // ĐIỀU CHỈNH CÁI NÀY NẾU CẦN

        const currentAbsoluteUrlPath = window.location.pathname; // Ví dụ: /SDLC_project/module/student/dashboard/student_dashboard.php
        const normalizedCurrentPath = getRelativePathFromAbsolute(currentAbsoluteUrlPath, projectBasePath); // Ví dụ: module/student/dashboard/student_dashboard.php
        console.log("Normalized Current Path:", normalizedCurrentPath); // Debugging

        sidebarLinks.forEach(link => {
            const linkHref = link.getAttribute('href'); // Ví dụ: ../dashboard/student_dashboard.php
            link.classList.remove('active');

            let resolvedLinkPathRelative;
            try {
                // Tạo một URL tạm thời để phân giải đường dẫn tương đối
                // Sau đó, lấy pathname và loại bỏ projectBasePath để có đường dẫn tương đối từ gốc dự án
                const tempUrl = new URL(linkHref, window.location.href);
                resolvedLinkPathRelative = getRelativePathFromAbsolute(tempUrl.pathname, projectBasePath);

            } catch (e) {
                console.error("Error resolving link path for:", linkHref, e);
                return;
            }

            console.log("Link Href:", linkHref, "Resolved Relative Link Path:", resolvedLinkPathRelative); // Debugging

            // So sánh các đường dẫn tương đối đã được chuẩn hóa
            if (resolvedLinkPathRelative === normalizedCurrentPath) {
                link.classList.add('active');
            }
            // --- Logic cho các trang con (ví dụ: student_profile.php active khi ở student_change_email.php) ---
            // Phần này vẫn nên dựa vào tên file cuối cùng vì nó đơn giản và dễ quản lý cho các nhóm trang
            else {
                const currentPageFileName = normalizedCurrentPath.split('/').pop();
                const linkFileName = resolvedLinkPathRelative.split('/').pop();

                // Logic cho trang "My Profile"
                const profileRelatedPages = ['student_profile.php', 'student_change_email.php', 'student_change_password.php'];
                if (linkFileName === 'student_profile.php' && profileRelatedPages.includes(currentPageFileName)) {
                    link.classList.add('active');
                }
                // Logic cho trang "Courses"
                else if (linkFileName === 'courses.php' && ['courses.php', 'student_search_courses.php', 'student_course_details.php'].includes(currentPageFileName)) {
                    link.classList.add('active');
                }
                // Logic cho "Assignments", "Notifications", "Progress", "Course Forum"...
                else if (linkFileName === 'student_assignments.php' && ['student_assignments.php', 'student_view_assignments.php'].includes(currentPageFileName)) {
                    link.classList.add('active');
                }
                 else if (linkFileName === 'notifications.php' && ['notifications.php'].includes(currentPageFileName)) {
                    link.classList.add('active');
                }
                else if (linkFileName === 'progress.php' && ['progress.php'].includes(currentPageFileName)) {
                    link.classList.add('active');
                }
                else if (linkFileName === 'student_forum_courses.php' && ['student_forum_courses.php', 'student_add_forum_post.php', 'student_view_forum_post.php'].includes(currentPageFileName)) {
                    link.classList.add('active');
                }
            }
        });
    }*/


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