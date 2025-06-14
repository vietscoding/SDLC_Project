// js/main.js

document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');

    // ** Thêm kiểm tra null cho các phần tử để tránh lỗi nếu không tìm thấy **
    if (!sidebarToggle || !sidebar || !sidebarOverlay) {
        console.error('Sidebar elements not found. Check your HTML IDs.');
        return; // Dừng lại nếu không tìm thấy các phần tử cần thiết
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        if (mainContent) {
            mainContent.classList.remove('sidebar-open');
        }
    }

    // Kiểm tra và khởi tạo trạng thái ban đầu của sidebar khi tải trang
    // Điều này quan trọng để đảm bảo sidebar ẩn trên mobile khi vừa load trang
    if (window.innerWidth <= 768) {
        // sidebar.style.transform = 'translateX(-100%)'; // Đã comment đúng
        // Đảm bảo không có class active nếu sidebar đang ẩn
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        if (mainContent) {
            mainContent.classList.remove('sidebar-open');
        }
    } else {
        // sidebar.style.transform = 'translateX(0)'; // Đã comment đúng
    }


    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        if (mainContent) {
            mainContent.classList.toggle('sidebar-open');
        }
    });

    sidebarOverlay.addEventListener('click', closeSidebar);

    // Đóng sidebar khi click vào một liên kết (chỉ trên mobile)
    const sidebarLinks = sidebar.querySelectorAll('ul li a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) { // Chỉ đóng trên màn hình nhỏ
                closeSidebar();
            }
        });
    });

    // Xử lý khi resize màn hình về desktop để đảm bảo sidebar hiển thị đúng
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar(); // Đảm bảo đóng nếu đang mở và chuyển sang desktop
            // sidebar.style.transform = 'translateX(0)'; // <-- XÓA HOẶC COMMENT DÒNG NÀY!
            sidebarOverlay.classList.remove('active'); // Đảm bảo overlay ẩn
            if (mainContent) {
                mainContent.classList.remove('sidebar-open');
            }
        } else {
            // Khi chuyển sang mobile, nếu sidebar không active, đảm bảo nó ẩn
            if (!sidebar.classList.contains('active')) {
                 // sidebar.style.transform = 'translateX(-100%)'; // <-- XÓA HOẶC COMMENT DÒNG NÀY!
            }
        }
    });
});