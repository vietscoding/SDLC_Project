document.addEventListener("DOMContentLoaded", () => {
    const likeButtons = document.querySelectorAll(".like-btn");

    likeButtons.forEach(button => {
        button.addEventListener("click", () => {
            const postId = button.getAttribute("data-post-id");

            fetch("like_post.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`like-count-${postId}`).innerText = data.like_count;
                } else {
                    alert(data.message);
                }
            })
            .catch(() => {
                alert("Có lỗi xảy ra khi like bài post.");
            });
        });
    });
});
