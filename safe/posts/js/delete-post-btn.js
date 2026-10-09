const deleteBtn = document.getElementById('delete-btn');

if (deleteBtn) {
    deleteBtn.addEventListener('click', () => deletePost(deleteBtn.dataset.postId));
}

async function deletePost(postId) {
    if (!confirm('Delete this post?')) {
        return;
    }
    try {
        const response = await fetch('logic/process-post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, action: 'delete' })
        });
        const data = await response.json();

        if (data.success) {
            window.location.href = data.redirect || 'profile.php?tab=posts';
        } else {
            alert('Could not delete post: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting post:', error.message);
    }
}
