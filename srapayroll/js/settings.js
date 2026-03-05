document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const ddBtn  = document.getElementById('userDropdownBtn');
const ddMenu = document.getElementById('userDropdownMenu');
ddBtn.addEventListener('click', () => {
    ddBtn.classList.toggle('open');
    ddMenu.classList.toggle('open');
});
document.addEventListener('click', e => {
    if (!ddBtn.contains(e.target) && !ddMenu.contains(e.target)) {
        ddBtn.classList.remove('open');
        ddMenu.classList.remove('open');
    }
});