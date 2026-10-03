async function deletePost(postId) {
    try {
        const response = await fetch(`logic/process-post.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ post_id: postId, action: 'delete' })
        });

        if (response.ok) {
            window.location.href = 'profile.php?tab=posts';
        }
    } catch (error) {
        console.error('Error saving post:', error.message);
    }
}