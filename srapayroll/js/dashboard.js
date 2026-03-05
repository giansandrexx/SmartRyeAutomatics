document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

const btn = document.getElementById('userDropdownBtn');
const menu = document.getElementById('userDropdownMenu');
btn.addEventListener('click', () => {
    btn.classList.toggle('open');
    menu.classList.toggle('open');
});
document.addEventListener('click', e => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
        btn.classList.remove('open');
        menu.classList.remove('open');
    }
});