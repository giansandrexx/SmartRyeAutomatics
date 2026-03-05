document.getElementById('headerDate').textContent =
    new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

const ddBtn  = document.getElementById('userDropdownBtn');
const ddMenu = document.getElementById('userDropdownMenu');
ddBtn.addEventListener('click', () => { ddBtn.classList.toggle('open'); ddMenu.classList.toggle('open'); });
document.addEventListener('click', e => {
    if (!ddBtn.contains(e.target) && !ddMenu.contains(e.target)) {
        ddBtn.classList.remove('open'); ddMenu.classList.remove('open');
    }
});

const addModal   = document.getElementById('addModal');
const addBtn     = document.getElementById('addBtn');
const modalClose = document.getElementById('modalClose');
const modalCancel= document.getElementById('modalCancel');

addBtn.addEventListener('click', () => {
    document.querySelector('[name="date_given"]').value = new Date().toISOString().split('T')[0];
    addModal.classList.add('open');
});
modalClose.addEventListener('click',  () => addModal.classList.remove('open'));
modalCancel.addEventListener('click', () => addModal.classList.remove('open'));
addModal.addEventListener('click', e => { if (e.target === addModal) addModal.classList.remove('open'); });

const confirmOverlay  = document.getElementById('confirmOverlay');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

document.querySelectorAll('.act-btn.del').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteId').value   = btn.dataset.id;
        document.getElementById('confirmMsg').textContent =
            'Delete cash advance of ' + btn.dataset.amount + ' for ' + btn.dataset.name + '?';
        confirmOverlay.classList.add('open');
    });
});
cancelDeleteBtn.addEventListener('click', () => confirmOverlay.classList.remove('open'));
confirmOverlay.addEventListener('click', e => { if (e.target === confirmOverlay) confirmOverlay.classList.remove('open'); });