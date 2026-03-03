(function () {
    const btn     = document.getElementById('schedHamburgerBtn');
    const drawer  = document.getElementById('schedDrawer');
    const overlay = document.getElementById('schedNavOverlay');
    const close   = document.getElementById('schedDrawerClose');

    function open() {
        drawer.classList.add('open');
        overlay.classList.add('visible');
        btn.classList.add('is-open');
    }

    function shut() {
        drawer.classList.remove('open');
        overlay.classList.remove('visible');
        btn.classList.remove('is-open');
    }

    if (btn)     btn.addEventListener('click', open);
    if (close)   close.addEventListener('click', shut);
    if (overlay) overlay.addEventListener('click', shut);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') shut();
    });
})();

(function () {

    var userBtn  = document.getElementById('userDropdownBtn');
    var userMenu = document.getElementById('userDropdownMenu');

    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = userMenu.classList.contains('open');
            userMenu.classList.toggle('open', !isOpen);
            userBtn.classList.toggle('open', !isOpen);
        });
    }

    var stocksToggle   = document.getElementById('stocksToggle');
    var stocksMenu     = document.getElementById('stocksMenu');
    var stocksDropdown = document.getElementById('stocksDropdown');

    if (stocksToggle && stocksMenu && stocksDropdown) {

        document.body.appendChild(stocksMenu);

        stocksMenu.style.cssText = [
            'position: fixed',
            'z-index: 999999',
            'min-width: 210px',
            'background: #ffffff',
            'border: 1px solid #e2e8f0',
            'border-radius: 12px',
            'box-shadow: 0 10px 30px rgba(0,0,0,0.18)',
            'padding: 6px 0',
            'list-style: none',
            'margin: 0',
            'display: none'
        ].join(';');

        stocksMenu.querySelectorAll('li').forEach(function(li) {
            li.style.cssText = 'list-style:none; margin:0; padding:0;';
        });

        stocksMenu.querySelectorAll('li a').forEach(function(a) {
            a.style.cssText = [
                'display: flex',
                'align-items: center',
                'gap: 8px',
                'padding: 10px 18px',
                'font-size: 13px',
                'font-weight: 500',
                'color: #374151',
                'text-decoration: none',
                'white-space: nowrap'
            ].join(';');

            a.addEventListener('mouseenter', function() {
                this.style.background = 'rgba(33,150,243,0.1)';
                this.style.color = '#1976d2';
            });
            a.addEventListener('mouseleave', function() {
                this.style.background = '';
                this.style.color = '#374151';
            });
        });

        stocksMenu.querySelectorAll('li a i').forEach(function(icon) {
            icon.style.cssText = 'width:14px; text-align:center; font-size:12px; color:#60a5fa;';
        });

        function positionMenu() {
            var rect = stocksToggle.getBoundingClientRect();
            stocksMenu.style.top  = (rect.bottom + 6) + 'px';
            stocksMenu.style.left = rect.left + 'px';
        }

        function openStocks() {
            positionMenu();
            stocksMenu.style.display = 'block';
            stocksDropdown.classList.add('mobile-open');
            stocksToggle.setAttribute('aria-expanded', 'true');
        }

        function closeStocks() {
            stocksMenu.style.display = 'none';
            stocksDropdown.classList.remove('mobile-open');
            stocksToggle.setAttribute('aria-expanded', 'false');
        }

        stocksToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.innerWidth <= 640) return;
            if (stocksDropdown.classList.contains('mobile-open')) {
                closeStocks();
            } else {
                openStocks();
            }
        });

        stocksMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                closeStocks();
            });
        });

        window.addEventListener('resize', function () {
            if (stocksDropdown.classList.contains('mobile-open')) positionMenu();
        });

        window.addEventListener('scroll', function () {
            if (stocksDropdown.classList.contains('mobile-open')) positionMenu();
        }, true);
    }

    document.addEventListener('click', function (e) {
        if (userBtn && userMenu) {
            if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
                userBtn.classList.remove('open');
            }
        }
        if (stocksDropdown && stocksMenu) {
            if (!stocksDropdown.contains(e.target) && !stocksMenu.contains(e.target)) {
                if (stocksDropdown.classList.contains('mobile-open')) {
                    stocksMenu.style.display = 'none';
                    stocksDropdown.classList.remove('mobile-open');
                    if (stocksToggle) stocksToggle.setAttribute('aria-expanded', 'false');
                }
            }
        }
    });

    var topHeader   = document.querySelector('.top-header');
    var headerRight = document.querySelector('.header-right');
    var navBar      = document.querySelector('.nav-bar');

    if (!topHeader) return;

    var hasNavBar = !!navBar;

    var userNameEl = document.querySelector('.user-name');
    var userRoleEl = document.querySelector('.user-role');
    var userName   = userNameEl ? (userNameEl.innerText || userNameEl.textContent || '').trim() : '';
    var userRole   = userRoleEl ? (userRoleEl.innerText || userRoleEl.textContent || '').trim() : '';

    var hamburger = document.createElement('button');
    hamburger.id = 'mobileHamburger';
    hamburger.className = hasNavBar ? 'mobile-hamburger-btn' : 'sched-hamburger-btn';
    hamburger.setAttribute('aria-label', 'Open navigation');
    hamburger.innerHTML = '<span></span><span></span><span></span>';

    if (headerRight) {
        headerRight.appendChild(hamburger);
    } else {
        topHeader.appendChild(hamburger);
    }

    var overlay = document.createElement('div');
    overlay.className = hasNavBar ? 'mobile-nav-overlay' : 'sched-nav-overlay';
    document.body.appendChild(overlay);

    var drawerEl;

    if (hasNavBar) {

        drawerEl = navBar;

        var closeBtn = document.createElement('button');
        closeBtn.className = 'mobile-drawer-close';
        closeBtn.setAttribute('aria-label', 'Close navigation');
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        navBar.insertBefore(closeBtn, navBar.firstChild);

        var drawerHeader = document.createElement('div');
        drawerHeader.className = 'mobile-drawer-header';
        drawerHeader.innerHTML =
            '<div class="mobile-drawer-user-icon"><i class="fas fa-user"></i></div>' +
            '<div>' +
                '<div class="mobile-drawer-user-name">' + userName + '</div>' +
                '<div class="mobile-drawer-user-role">' + userRole + '</div>' +
            '</div>';
        navBar.insertBefore(drawerHeader, closeBtn.nextSibling);

        var drawerFooter = document.createElement('div');
        drawerFooter.className = 'mobile-drawer-footer';
        drawerFooter.innerHTML =
            '<a href="../portal.php"><i class="fas fa-arrow-left"></i> Back to Portal</a>' +
            '<a href="../portal.php?logout=1" class="mobile-drawer-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>';
        navBar.appendChild(drawerFooter);

        closeBtn.addEventListener('click', closeDrawer);

        navBar.querySelectorAll('ul li a').forEach(function(link) {
            if (link.id !== 'stocksToggle') {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 640) closeDrawer();
                });
            }
        });

        if (stocksToggle && stocksDropdown) {
            stocksToggle.addEventListener('click', function() {
                if (window.innerWidth > 640) return;
                if (stocksMenu.parentNode !== stocksDropdown) {
                    stocksDropdown.appendChild(stocksMenu);
                    stocksMenu.style.cssText = '';
                }
                stocksDropdown.classList.toggle('drawer-stocks-open');
                stocksMenu.style.display = stocksDropdown.classList.contains('drawer-stocks-open') ? 'block' : 'none';
            });
        }

    } else {

        var schedDrawer = document.createElement('div');
        schedDrawer.className = 'sched-drawer';

        var topHeaderBg = window.getComputedStyle(topHeader).backgroundColor;
        if (topHeaderBg && topHeaderBg !== 'rgba(0, 0, 0, 0)' && topHeaderBg !== 'transparent') {
            schedDrawer.style.background = topHeaderBg;
        }

        var drawerLinksHTML = '';
        var drawerFooterHTML = '';

        if (userMenu) {
            var allItems = userMenu.querySelectorAll('.dropdown-item');
            allItems.forEach(function(item) {
                var href    = item.getAttribute('href') || '#';
                var isDanger = item.classList.contains('dropdown-item-danger');
                var icon    = item.querySelector('i') ? item.querySelector('i').outerHTML : '';
                var label   = item.innerText.trim();

                if (isDanger) {
                    drawerFooterHTML +=
                        '<a href="' + href + '" class="sched-logout">' + icon + ' ' + label + '</a>';
                } else {
                    drawerLinksHTML +=
                        '<a href="' + href + '">' + icon + ' ' + label + '</a>';
                }
            });
        }

        var drawerHeaderHTML =
            '<button class="sched-drawer-close" aria-label="Close menu"><i class="fas fa-times"></i></button>' +
            '<div class="sched-drawer-header">' +
                '<div class="sched-drawer-user-icon"><i class="fas fa-user"></i></div>' +
                '<div>' +
                    '<div class="sched-drawer-user-name">' + userName + '</div>' +
                    '<div class="sched-drawer-user-role">' + userRole + '</div>' +
                '</div>' +
            '</div>';

        var drawerBodyHTML = drawerLinksHTML
            ? '<div class="sched-drawer-links">' + drawerLinksHTML + '</div>'
            : '';

        var drawerFooterWrapHTML = drawerFooterHTML
            ? '<div class="sched-drawer-footer">' + drawerFooterHTML + '</div>'
            : '';

        schedDrawer.innerHTML = drawerHeaderHTML + drawerBodyHTML + drawerFooterWrapHTML;

        document.body.appendChild(schedDrawer);
        drawerEl = schedDrawer;

        schedDrawer.querySelector('.sched-drawer-close').addEventListener('click', closeDrawer);

        schedDrawer.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 640) closeDrawer();
            });
        });
    }

    function openDrawer() {
        if (hasNavBar) {
            var bg = window.getComputedStyle(navBar).backgroundColor;
            if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
                navBar.style.background = bg;
            }
            navBar.classList.add('mobile-drawer-open');
        } else {
            drawerEl.classList.add('open');
        }
        overlay.classList.add('visible');
        hamburger.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (hasNavBar) {
            navBar.classList.remove('mobile-drawer-open');
        } else {
            drawerEl.classList.remove('open');
        }
        overlay.classList.remove('visible');
        hamburger.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = hasNavBar
            ? navBar.classList.contains('mobile-drawer-open')
            : drawerEl.classList.contains('open');
        isOpen ? closeDrawer() : openDrawer();
    });

    overlay.addEventListener('click', closeDrawer);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 640) closeDrawer();
    });

})();

