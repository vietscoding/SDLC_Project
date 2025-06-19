document.addEventListener("DOMContentLoaded", () => {
    const likeButtons = document.querySelectorAll(".like-btn");

    likeButtons.forEach(button => {
        button.addEventListener("click", () => {
            const postId = button.getAttribute("data-post-id");
            const likeCountElement = document.getElementById(`like-count-${postId}`);

            // Determine if the post is currently liked by checking the 'liked' class
            const isCurrentlyLiked = button.classList.contains('liked');

            // Optimistically toggle the UI immediately
            if (isCurrentlyLiked) {
                button.classList.remove('liked');
                // Decrement the count optimistically (will be corrected by server response)
                likeCountElement.innerText = parseInt(likeCountElement.innerText) - 1;
            } else {
                button.classList.add('liked');
                // Increment the count optimistically (will be corrected by server response)
                likeCountElement.innerText = parseInt(likeCountElement.innerText) + 1;
            }

            fetch("../../../common/like_post.php", { // Corrected path
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update with the actual like count from the server
                    likeCountElement.innerText = data.like_count;

                    // Re-sync the button's 'liked' class with the server's actual state
                    // This is important in case the optimistic update was wrong (e.g., server error, or race condition)
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                    } else {
                        button.classList.remove('liked');
                    }
                } else {
                    // If the server operation failed, revert the optimistic UI changes
                    if (isCurrentlyLiked) {
                        button.classList.add('liked');
                        likeCountElement.innerText = parseInt(likeCountElement.innerText) + 1;
                    } else {
                        button.classList.remove('liked');
                        likeCountElement.innerText = parseInt(likeCountElement.innerText) - 1;
                    }
                    alert(data.message);
                }
            })
            .catch(() => {
                // If there's a network error, revert the optimistic UI changes
                if (isCurrentlyLiked) {
                    button.classList.add('liked');
                    likeCountElement.innerText = parseInt(likeCountElement.innerText) + 1;
                } else {
                    button.classList.remove('liked');
                    likeCountElement.innerText = parseInt(likeCountElement.innerText) - 1;
                }
                alert("Có lỗi xảy ra khi like bài post.");
            });
        });
    });
});