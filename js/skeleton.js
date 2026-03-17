(function () {
    function revealCards() {
        var skelGrid  = document.getElementById('skeleton-grid');
        var realGrid  = document.getElementById('real-grid');

        if (!skelGrid || !realGrid) return;

        skelGrid.style.transition  = 'opacity 0.25s ease';
        skelGrid.style.opacity     = '0';

        setTimeout(function () {
            skelGrid.style.display = 'none';

            realGrid.style.display = 'grid';

            requestAnimationFrame(function () {
                realGrid.style.opacity    = '0';
                realGrid.style.transition = 'opacity 0.3s ease';
                requestAnimationFrame(function () {
                    realGrid.style.opacity = '1';
                });
            });
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', revealCards);
    } else {

        revealCards();
    }
})();