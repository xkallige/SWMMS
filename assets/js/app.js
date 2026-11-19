document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"], button:not([type])');
        if (button) button.disabled = true;
    });
});

document.querySelectorAll('.clean-accordion .accordion-collapse').forEach((panel) => {
    const item = panel.closest('.accordion-item');
    if (!item) return;

    const syncActiveState = () => {
        item.classList.toggle('is-active', panel.classList.contains('show'));
    };

    panel.addEventListener('shown.bs.collapse', syncActiveState);
    panel.addEventListener('hidden.bs.collapse', syncActiveState);
    syncActiveState();
});

const portalMenuToggle = document.querySelector('.portal-menu-toggle');
const portalSidebar = document.getElementById('portalSidebar');
const portalSidebarBackdrop = document.querySelector('.portal-sidebar-backdrop');
if (portalMenuToggle && portalSidebar) {
    const setPortalMenu = (open) => {
        portalSidebar.classList.toggle('is-open', open);
        document.body.classList.toggle('portal-menu-open', open);
        portalMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    portalMenuToggle.addEventListener('click', () => setPortalMenu(!portalSidebar.classList.contains('is-open')));
    portalSidebarBackdrop?.addEventListener('click', () => setPortalMenu(false));
    portalSidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setPortalMenu(false)));
}
