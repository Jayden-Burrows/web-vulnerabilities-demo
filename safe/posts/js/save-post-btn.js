// JS logic for saving a post using the thumbtack

const saveBtn = document.getElementById('save-btn');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

if (saveBtn) {
    saveBtn.addEventListener('click', () => {
        const postId = saveBtn.dataset.postId;
        savePost(postId);
    });
}

// Send the post to the backend to process in the database
async function savePost(postId) {
    let action = saveBtn.classList.contains('saved') ? 'delete' : 'insert';
    try {
        const response = await fetch(`logic/process-save-post.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ post_id: postId })
        });

        const data = await response.json();

        if (data['success']) {
            const countSpan = saveBtn.nextElementSibling;
            if (countSpan) {
                countSpan.textContent = data['numSaves'];
            }
            saveBtn.classList.toggle('saved');
        } else {
            alert('Could not save post: ' + (data['error'] || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving post:', error.message);
    }
}