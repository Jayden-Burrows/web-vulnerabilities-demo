// Button For Creating Post

const createBtn = document.getElementById('create-btn');
const createModal = document.getElementById('create-modal');

if (createBtn) {
    createBtn.addEventListener('click', () => {
        createModal.style.display = 'block';
    });
}