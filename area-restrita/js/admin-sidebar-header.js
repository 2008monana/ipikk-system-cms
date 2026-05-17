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
// Mantém o mesmo padrão visual/HTML do modal de confirmação do módulo Notícias.
let acaoPendenteConfirmacaoGlobal = null;

function obterConfigConfirmacaoGlobal(tipoAcao = 'eliminar') {
    const configs = {
        eliminar: {
            iconeClasse: 'fa-exclamation-triangle',
            botaoTexto: 'Eliminar',
            corPrimaria: '#dc2626',
            corSecundaria: '#b91c1c',
            fundoIcone: 'linear-gradient(135deg, #fee2e2, #fff1f2)'
        },
        publicar: {
            iconeClasse: 'fa-paper-plane',
            botaoTexto: 'Confirmar',
            corPrimaria: '#16a34a',
            corSecundaria: '#15803d',
            fundoIcone: 'linear-gradient(135deg, #dcfce7, #f0fdf4)'
        },
        arquivar: {
            iconeClasse: 'fa-box-archive',
            botaoTexto: 'Arquivar',
            corPrimaria: '#d97706',
            corSecundaria: '#b45309',
            fundoIcone: 'linear-gradient(135deg, #fef3c7, #fff7ed)'
        },
        restaurar: {
            iconeClasse: 'fa-rotate-left',
            botaoTexto: 'Restaurar',
            corPrimaria: '#2563eb',
            corSecundaria: '#1d4ed8',
            fundoIcone: 'linear-gradient(135deg, #dbeafe, #eff6ff)'
        },
        info: {
            iconeClasse: 'fa-info',
            botaoTexto: 'Confirmar',
            corPrimaria: '#0a9396',
            corSecundaria: '#087f82',
            fundoIcone: 'linear-gradient(135deg, #dff7f8, #f0fdfa)'
        }
    };

    return configs[tipoAcao] || configs.eliminar;
}

function garantirModalConfirmacaoGlobal() {
    let modal = document.getElementById('modalConfirmacao');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'modalConfirmacao';
    modal.className = 'modal-confirmacao';
    modal.innerHTML = `
        <div class="modal-confirmacao-caixa">
            <div class="modal-confirmacao-icone" id="modalConfirmacaoIconeWrapper">
                <i class="fas fa-exclamation-triangle" id="modalConfirmacaoIcone"></i>
            </div>
            <h3 id="modalConfirmacaoTitulo">Confirmar ação</h3>
            <p id="modalConfirmacaoTexto">Tem certeza que deseja continuar?</p>
            <div class="modal-confirmacao-botoes">
                <button type="button" class="botao-cancelar botao-cancelar-modal" id="botaoCancelarConfirmacao">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="botao-perigo-confirmacao botao-confirmar-modal" id="botaoConfirmarAcao">
                    <i class="fas fa-exclamation-triangle" id="modalConfirmacaoBotaoIcone"></i> <span id="modalConfirmacaoBotaoTexto">Confirmar</span>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
}


function aplicarEstilosCriticosModalConfirmacao(modal) {
    Object.assign(modal.style, {
        alignItems: 'center',
        backdropFilter: 'blur(4px)',
        background: 'linear-gradient(135deg, rgba(5, 19, 43, 0.84), rgba(0, 0, 0, 0.88))',
        display: 'flex',
        inset: '0',
        justifyContent: 'center',
        padding: '20px',
        position: 'fixed',
        zIndex: '30000'
    });

    const caixa = modal.querySelector('.modal-confirmacao-caixa');
    if (caixa) {
        Object.assign(caixa.style, {
            background: '#ffffff',
            border: '1px solid rgba(226, 232, 240, 0.95)',
            borderRadius: '22px',
            boxShadow: '0 28px 58px rgba(0, 0, 0, 0.36)',
            maxWidth: '450px',
            padding: '30px',
            position: 'relative',
            textAlign: 'center',
            width: 'min(92vw, 450px)'
        });
    }

    const icone = modal.querySelector('.modal-confirmacao-icone');
    if (icone) {
        Object.assign(icone.style, {
            alignItems: 'center',
            background: 'linear-gradient(135deg, #fee2e2, #fff1f2)',
            borderRadius: '50%',
            boxShadow: '0 10px 24px rgba(220, 38, 38, 0.18)',
            color: '#dc2626',
            display: 'flex',
            fontSize: '1.55rem',
            height: '66px',
            justifyContent: 'center',
            margin: '0 auto 18px',
            width: '66px'
        });
    }


    const botaoCancelar = modal.querySelector('#botaoCancelarConfirmacao');
    if (botaoCancelar) {
        Object.assign(botaoCancelar.style, {
            alignItems: 'center',
            background: 'linear-gradient(135deg, #ffffff, #f1f5f9)',
            border: '1px solid #dbe3ee',
            borderRadius: '40px',
            boxShadow: '0 6px 16px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9)',
            color: '#334155',
            display: 'inline-flex',
            fontSize: '0.9rem',
            fontWeight: '800',
            gap: '9px',
            justifyContent: 'center',
            minHeight: '46px',
            minWidth: '138px',
            padding: '13px 26px'
        });
    }
}

function fecharModalConfirmacaoGlobal() {
    const modal = document.getElementById('modalConfirmacao');
    if (modal) {
        modal.classList.remove('ativo');
        modal.style.display = 'none';
    }
    acaoPendenteConfirmacaoGlobal = null;
    document.body.style.overflow = '';
}

    const botaoConfirmar = modal.querySelector('#botaoConfirmarAcao');
    if (botaoConfirmar) {
        Object.assign(botaoConfirmar.style, {
            alignItems: 'center',
            background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 48%, #991b1b 100%)',
            border: '1px solid rgba(185, 28, 28, 0.35)',
            borderRadius: '40px',
            boxShadow: '0 10px 22px rgba(220, 38, 38, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.24)',
            color: '#ffffff',
            display: 'inline-flex',
            fontSize: '0.9rem',
            fontWeight: '800',
            gap: '9px',
            justifyContent: 'center',
            minHeight: '46px',
            minWidth: '138px',
            padding: '13px 26px'
        });
    }
}

function fecharModalConfirmacaoGlobal() {
    const modal = document.getElementById('modalConfirmacao');
    if (modal) {
        modal.classList.remove('ativo');
        modal.style.display = 'none';
    }
    acaoPendenteConfirmacaoGlobal = null;
    document.body.style.overflow = '';
}

window.abrirModalConfirmacao = function(titulo, texto, callbackConfirmar, tipoAcao = 'eliminar') {
    const modal = garantirModalConfirmacaoGlobal();
    const tituloEl = document.getElementById('modalConfirmacaoTitulo');
    const textoEl = document.getElementById('modalConfirmacaoTexto');
    const iconeEl = document.getElementById('modalConfirmacaoIcone');
    const botaoIconeEl = document.getElementById('modalConfirmacaoBotaoIcone');
    const botaoTextoEl = document.getElementById('modalConfirmacaoBotaoTexto');
    const iconeWrapper = document.getElementById('modalConfirmacaoIconeWrapper');
    const botaoConfirmar = document.getElementById('botaoConfirmarAcao');
    const botaoCancelar = document.getElementById('botaoCancelarConfirmacao');
    const config = obterConfigConfirmacaoGlobal(tipoAcao);

    aplicarEstilosCriticosModalConfirmacao(modal);

    if (tituloEl) tituloEl.textContent = titulo;
    if (textoEl) textoEl.textContent = texto;
    if (iconeEl) iconeEl.className = `fas ${config.iconeClasse}`;
    if (botaoIconeEl) botaoIconeEl.className = `fas ${config.iconeClasse}`;
    if (botaoTextoEl) botaoTextoEl.textContent = config.botaoTexto;
    if (iconeWrapper) {
        iconeWrapper.style.background = config.fundoIcone;
        iconeWrapper.style.color = config.corPrimaria;
    }
    if (botaoConfirmar) {
        const gradienteConfirmar = tipoAcao === 'eliminar'
            ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 48%, #991b1b 100%)'
            : `linear-gradient(135deg, ${config.corPrimaria}, ${config.corSecundaria})`;
        botaoConfirmar.style.background = gradienteConfirmar;
        botaoConfirmar.style.boxShadow = tipoAcao === 'eliminar'
            ? '0 10px 22px rgba(220, 38, 38, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.24)'
            : `0 8px 18px ${config.corPrimaria}55`;
        botaoConfirmar.onclick = function() {
            if (typeof acaoPendenteConfirmacaoGlobal === 'function') {
                acaoPendenteConfirmacaoGlobal();
            }
            fecharModalConfirmacaoGlobal();
        };
    }
    if (botaoCancelar) botaoCancelar.onclick = fecharModalConfirmacaoGlobal;
    modal.onclick = function(event) {
        if (event.target === modal) fecharModalConfirmacaoGlobal();
    };

    acaoPendenteConfirmacaoGlobal = callbackConfirmar;
    modal.classList.add('ativo');
    document.body.style.overflow = 'hidden';
};

document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('modalConfirmacao');
    if (event.key === 'Escape' && modal?.classList.contains('ativo')) {
        fecharModalConfirmacaoGlobal();
    }
});

function escapeHtmlConfirmacao(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}
