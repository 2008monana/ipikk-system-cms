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

// Modal global de confirmação usado nas páginas administrativas.
window.abrirModalConfirmacao = function(titulo, texto, callbackConfirmar, tipoAcao = 'info') {
    const tipos = {
        eliminar: { classe: 'eliminar', textoBotao: 'Eliminar', iconeClasse: 'fa-exclamation-triangle' },
        publicar: { classe: 'publicar', textoBotao: 'Confirmar', iconeClasse: 'fa-paper-plane' },
        restaurar: { classe: 'restaurar', textoBotao: 'Restaurar', iconeClasse: 'fa-rotate-left' },
        info: { classe: 'info', textoBotao: 'Confirmar', iconeClasse: 'fa-info' }
    };
    const config = tipos[tipoAcao] || tipos.info;
    const overlay = document.createElement('div');
    overlay.className = 'ipikk-confirm-overlay';
    aplicarEstilosOverlayConfirmacao(overlay);
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    overlay.innerHTML = `
        <div class="ipikk-confirm-box">
            <div class="ipikk-confirm-icon ${config.classe}" aria-hidden="true"><i class="fas ${config.iconeClasse}"></i></div>
            <h3 class="ipikk-confirm-title">${escapeHtmlConfirmacao(titulo)}</h3>
            <p class="ipikk-confirm-body">${escapeHtmlConfirmacao(texto)}</p>
            <div class="ipikk-confirm-actions">
                <button type="button" class="ipikk-confirm-btn ipikk-confirm-cancel">Cancelar</button>
                <button type="button" class="ipikk-confirm-btn ipikk-confirm-action ${config.classe}">${config.textoBotao}</button>
            </div>
        </div>
    `;

    const overflowAnterior = document.body.style.overflow;

    function fechar() {
        document.removeEventListener('keydown', aoPressionarTecla);
        document.body.style.overflow = overflowAnterior;
        overlay.remove();
    }

    function aoPressionarTecla(event) {
        if (event.key === 'Escape') fechar();
    }

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) fechar();
    });
    overlay.querySelector('.ipikk-confirm-close')?.addEventListener('click', fechar);
    overlay.querySelector('.ipikk-confirm-cancel').addEventListener('click', fechar);
    overlay.querySelector('.ipikk-confirm-action').addEventListener('click', () => {
        fechar();
        if (typeof callbackConfirmar === 'function') callbackConfirmar();
    });

    document.addEventListener('keydown', aoPressionarTecla);
    document.body.style.overflow = 'hidden';
    document.body.appendChild(overlay);
    aplicarEstilosCaixaConfirmacao(overlay.querySelector('.ipikk-confirm-box'));
    overlay.querySelector('.ipikk-confirm-cancel').focus();
};


function aplicarEstilosOverlayConfirmacao(overlay) {
    Object.assign(overlay.style, {
        alignItems: 'center',
        background: 'linear-gradient(135deg, rgba(5, 19, 43, 0.82), rgba(0, 0, 0, 0.86))',
        backdropFilter: 'blur(3px)',
        display: 'flex',
        inset: '0',
        justifyContent: 'center',
        padding: '20px',
        position: 'fixed',
        zIndex: '30000'
    });
}

function aplicarEstilosCaixaConfirmacao(caixa) {
    if (!caixa) return;

    Object.assign(caixa.style, {
        background: '#ffffff',
        borderRadius: '16px',
        boxShadow: '0 24px 48px rgba(0, 0, 0, 0.35)',
        maxWidth: '460px',
        padding: '26px',
        position: 'relative',
        textAlign: 'center',
        width: '100%'
    });
}

function escapeHtmlConfirmacao(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}
