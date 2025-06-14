document.querySelectorAll('.delete-post').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm("Are you sure?")) {
            let postId = this.getAttribute('data-post-id');
            fetch(`admin_delete_post.php?post_id=${postId}`, {
                method: 'GET'
            }).then(response => response.text()).then(data => {
                if (data.includes("success")) {
                    this.closest("div").remove(); // Xóa bài ngay trên giao diện
                } else {
                    alert("Error deleting post.");
                }
            });
        }
    });
});