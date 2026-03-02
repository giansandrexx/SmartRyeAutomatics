function formatDuration(seconds) {
    const days    = Math.floor(seconds / 86400);
    const hours   = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (days > 0) {
        const h = hours > 0 ? `, ${hours} hr${hours !== 1 ? 's' : ''}` : '';
        return `${days} day${days !== 1 ? 's' : ''}${h}`;
    }
    if (hours > 0) {
        const m = minutes > 0 ? `, ${minutes} min${minutes !== 1 ? 's' : ''}` : '';
        return `${hours} hr${hours !== 1 ? 's' : ''}${m}`;
    }
    if (minutes > 0) return `${minutes} min${minutes !== 1 ? 's' : ''}`;
    return `${seconds}s`;
}

function tickDurations() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-borrowed]').forEach(el => {
        const borrowed = parseInt(el.dataset.borrowed, 10);
        if (isNaN(borrowed)) return;
        el.textContent = formatDuration(now - borrowed);
    });
}
tickDurations();
setInterval(tickDurations, 30000);

document.querySelectorAll('.item-card-inner').forEach(row => {
    function toggle() {
        const card    = row.closest('.item-card');
        const form    = card.querySelector('.item-return-wrap');
        const chevron = row.querySelector('.expand-chevron i');
        const isOpen  = form.classList.toggle('open');
        row.setAttribute('aria-expanded', String(isOpen));
        if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
    }

    row.addEventListener('click', toggle);
    row.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
});

document.querySelectorAll('.btn-cancel-return').forEach(btn => {
    btn.addEventListener('click', () => {
        const form    = btn.closest('.item-return-wrap');
        const row     = form.previousElementSibling;
        const chevron = row ? row.querySelector('.expand-chevron i') : null;
        form.classList.remove('open');
        if (row) row.setAttribute('aria-expanded', 'false');
        if (chevron) chevron.style.transform = '';
    });
});