document.querySelectorAll('.delete-post').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this post?")) {
            let postId = this.getAttribute('data-post-id');

            fetch(`admin_delete_post.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `post_id=${postId}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    this.closest("div").remove();
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
