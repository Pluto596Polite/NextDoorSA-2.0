/*
 * Shared navbar behaviour for every NextDoorSA page:
 *   1. Highlights the nav link matching the current page.
 *   2. Makes the "Account" dropdown reflect login state (queries session_status.php).
 * Include with: <script src="nav-auth.js"></script> just before </body>.
 */
(function () {
    'use strict';

    function currentPage() {
        const path = window.location.pathname.split('/').pop();
        return path && path.length ? path : 'Home.html';
    }

    function highlightActiveLink() {
        const here = currentPage().toLowerCase();
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            const href = (link.getAttribute('href') || '').split('?')[0].toLowerCase();
            if (href && href === here) {
                link.classList.add('active', 'fw-semibold');
                link.setAttribute('aria-current', 'page');
            }
        });
    }

    function renderDropdown(menu, status) {
        if (status && status.loggedIn) {
            const name = (status.name || 'Account').replace(/</g, '&lt;');
            menu.innerHTML =
                '<li><h6 class="dropdown-header">Hi, ' + name + '</h6></li>' +
                '<li><a class="dropdown-item" href="Profile.html"><i class="bi bi-person me-2"></i>Profile</a></li>' +
                '<li><a class="dropdown-item" href="MyListings.html"><i class="bi bi-card-list me-2"></i>My Listings</a></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>';
        } else {
            menu.innerHTML =
                '<li><a class="dropdown-item" href="LogIntoAccount.html"><i class="bi bi-box-arrow-in-right me-2"></i>Log In</a></li>' +
                '<li><a class="dropdown-item" href="CreateAnAccount.html"><i class="bi bi-person-plus me-2"></i>Create Account</a></li>';
        }
    }

    function updateAccountMenu() {
        const toggle = document.getElementById('accountDropdown');
        if (!toggle) return;
        const menu = toggle.parentElement.querySelector('.dropdown-menu');
        if (!menu) return;

        fetch('session_status.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(status => {
                renderDropdown(menu, status);
                if (status && status.loggedIn && status.name) {
                    // Show the logged-in name on the toggle itself.
                    toggle.innerHTML = '<i class="bi bi-person-circle fs-5"></i> ' + status.name.replace(/</g, '&lt;');
                }
            })
            .catch(() => { /* offline / not deployed: leave static menu untouched */ });
    }

    document.addEventListener('DOMContentLoaded', () => {
        highlightActiveLink();
        updateAccountMenu();
    });
})();
