function setActiveSidebarLink() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');

    sidebarLinks.forEach((link) => {
        const href = link.getAttribute('href');
        if (!href) return;
        const linkPath = href.split('/').pop();
        if (linkPath === currentPath) {
            link.classList.add('active');
            // If this link is inside a dropdown, open the dropdown
            const dropdown = link.closest('.sidebar-dropdown');
            if (dropdown) {
                dropdown.classList.add('open');
            }
        } else {
            link.classList.remove('active');
        }
    });
}

function setupDropdownToggle() {
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.dropdown-toggle');
        if (!toggle) return;
        
        e.preventDefault();
        const dropdown = toggle.closest('.sidebar-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('open');
            console.log('Dropdown toggled:', dropdown.classList.contains('open'));
        }
    });
}

function bindSidebarToggle() {
    document.addEventListener('click', (event) => {
        if (event.target.closest('#sidebarToggle')) {
            document.body.classList.toggle('sidebar-collapsed');
        }
    });
}

async function loadPartial(targetId, partialPath) {
    const target = document.getElementById(targetId);
    if (!target) return;

    try {
        const response = await fetch(partialPath);
        if (!response.ok) return;
        target.innerHTML = await response.text();
    } catch (error) {
        console.error('Failed to load partial:', partialPath, error);
    }
}

async function loadLayout() {
    await Promise.all([
        loadPartial('navbar-placeholder', 'includes/navbar.html'),
        loadPartial('sidebar-placeholder', 'includes/sidebar.html'),
        loadPartial('footer-placeholder', 'includes/footer.html')
    ]);

    setActiveSidebarLink();
}

bindSidebarToggle();
setupDropdownToggle();
loadLayout();