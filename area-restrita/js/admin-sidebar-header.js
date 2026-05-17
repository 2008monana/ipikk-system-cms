if (window.__ipikkSidebarHeaderInitialized) {
    // Evita listeners duplicados quando a página inclui este ficheiro mais de uma vez.
} else {
    window.__ipikkSidebarHeaderInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlaySidebar');
        const btnClose = document.getElementById('sidebarClose');

        function abrirSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('visivel');
            overlay && overlay.classList.add('visivel');
            document.body.style.overflow = 'hidden';
        }

        function fecharSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('visivel');
            overlay && overlay.classList.remove('visivel');
            document.body.style.overflow = '';
        }

        // Delegação em capture: funciona em todas as páginas mesmo que scripts locais
        // adicionem listeners próprios ou parem a propagação depois.
        document.addEventListener('click', function(e) {
            const submenuNivel2 = e.target.closest('.submenu-toggle-level2');
            if (submenuNivel2) {
                e.preventDefault();
                e.stopPropagation();
                submenuNivel2.closest('.submenu-item-has-children, .has-submenu-level2')?.classList.toggle('open');
                return;
            }

            const submenu = e.target.closest('.submenu-toggle');
            if (submenu) {
                e.preventDefault();
                e.stopPropagation();
                submenu.closest('.menu-item-has-children, .has-submenu')?.classList.toggle('open');
                return;
            }

            if (e.target.closest('#botaoMenuMobile, #menuMobileBtn')) {
                abrirSidebar();
            }
        }, true);

        if (overlay) overlay.addEventListener('click', fecharSidebar);
        if (btnClose) btnClose.addEventListener('click', fecharSidebar);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar?.classList.contains('visivel')) fecharSidebar();
        });

        window.openSidebar = abrirSidebar;
        window.closeSidebar = fecharSidebar;
    });
}
