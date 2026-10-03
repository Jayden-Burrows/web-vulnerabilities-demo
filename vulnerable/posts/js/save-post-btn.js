const saveBtn = document.getElementById('save-btn');

async function savePost(postId) {
    let action = saveBtn.classList.contains('saved') ? 'delete' : 'insert';

    try {
        const response = await fetch(`logic/process-save-post.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ post_id: postId })
        });

        const data = await response.json();

        if (data['success']) {
            const countSpan = saveBtn.nextElementSibling;
            countSpan.textContent = data['numSaves'];
            saveBtn.classList.toggle('saved');
        } else {
            alert('Could not save post: ' + (data['error'] || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving post:', error.message);
    }
}