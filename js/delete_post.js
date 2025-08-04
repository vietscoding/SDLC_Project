document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this post?")) {
            let postId = this.getAttribute('data-post-id');
            fetch(`admin_delete_post.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${encodeURIComponent(postId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    this.closest('.forum-post-item').remove();
                } else {
                    alert("Lỗi: " + data.message);
                }
            })
            .catch(error => {
                alert("Error occurred while deleting the post.");
                console.error(error);
            });
        }
    });
});
