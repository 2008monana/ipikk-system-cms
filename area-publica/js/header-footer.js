/// ===== SCROLL =====
const cabecalho = document.getElementById('cabecalho');
const botaoTopo = document.getElementById('botaoTopo');
window.addEventListener('scroll', () => {
    if (cabecalho) cabecalho.classList.toggle('rolado', window.scrollY > 60);
    if (botaoTopo) botaoTopo.style.display = window.scrollY > 300 ? 'flex' : 'none';
});
if (botaoTopo) {
    botaoTopo.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ===== WHATSAPP =====
const botaoWhatsapp = document.getElementById('botaoWhatsapp');
if (botaoWhatsapp) {
    botaoWhatsapp.addEventListener('click', e => {
        e.preventDefault();
        window.open('https://wa.me/244933096705', '_blank');
    });
}

// ===== SIDEBAR — abrir/fechar =====
const botaoMenu = document.getElementById('botaoMenu');
const sidebar   = document.getElementById('sidebarMobile');
const overlay   = document.getElementById('overlaySidebar');
const fecharBtn = document.getElementById('fecharSidebar');

function abrirSidebar() {
    sidebar.classList.add('aberto');
    overlay.classList.add('visivel');
    document.body.style.overflow = 'hidden';
    botaoMenu.classList.add('ativo');
    setTimeout(() => {
        const nav = document.querySelector('.sidebar-nav');
        if (nav) nav.scrollTop = 0;
    }, 50);
}

function fecharSidebar() {
    sidebar.classList.remove('aberto');
    overlay.classList.remove('visivel');
    document.body.style.overflow = '';
    botaoMenu.classList.remove('ativo');
    document.querySelectorAll('.sidebar-submenu').forEach(s => s.classList.remove('aberto'));
    document.querySelectorAll('[data-target], [data-alvo]').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        b.classList.remove('aberto');
    });
    document.querySelectorAll('.sidebar-link.has-submenu').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
    });
}

if (botaoMenu && sidebar) {
    botaoMenu.addEventListener('click', () => sidebar.classList.contains('aberto') ? fecharSidebar() : abrirSidebar());
}
if (fecharBtn) fecharBtn.addEventListener('click', fecharSidebar);
if (overlay) overlay.addEventListener('click', fecharSidebar);
if (sidebar) {
    sidebar.querySelectorAll('a.sidebar-link, a.sidebar-sub-link, a.sidebar-link-pagina, a.sidebar-oferta-link').forEach(link => {
        link.addEventListener('click', fecharSidebar);
    });
}

// ===== SIDEBAR — submenus =====
function rolarAteElemento(el) {
    const nav = document.querySelector('.sidebar-nav');
    if (!nav || !el) return;
    setTimeout(() => {
        const elTop    = el.getBoundingClientRect().top - nav.getBoundingClientRect().top + nav.scrollTop;
        const elBottom = elTop + el.offsetHeight;
        if (elTop < nav.scrollTop) {
            nav.scrollTo({ top: Math.max(0, elTop - 20), behavior: 'smooth' });
        } else if (elBottom > nav.scrollTop + nav.clientHeight) {
            nav.scrollTo({ top: elBottom - nav.clientHeight + 20, behavior: 'smooth' });
        }
    }, 120);
}

function toggleSubmenu(btn, alvoId) {
    const submenu = document.getElementById(alvoId);
    if (!submenu) return;
    const abrindo = !submenu.classList.contains('aberto');
    if (abrindo) {
        submenu.classList.add('aberto');
        btn.setAttribute('aria-expanded', 'true');
        btn.classList.add('aberto');
        rolarAteElemento(submenu);
    } else {
        submenu.classList.remove('aberto');
        btn.setAttribute('aria-expanded', 'false');
        btn.classList.remove('aberto');
        submenu.querySelectorAll('.sidebar-submenu').forEach(s => s.classList.remove('aberto'));
        submenu.querySelectorAll('[data-target], [data-alvo]').forEach(b => {
            b.setAttribute('aria-expanded', 'false');
            b.classList.remove('aberto');
        });
    }
}

// Botões de toggle de submenu
document.querySelectorAll('.toggle-submenu-btn[data-target], .botao-seta-sidebar[data-target], .sidebar-link.has-submenu[data-target], .botao-seta-sidebar[data-alvo], .sidebar-link.has-submenu[data-alvo]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const alvo = this.getAttribute('data-target') || this.getAttribute('data-alvo');
        if (alvo) toggleSubmenu(this, alvo);
    });
});

// ===== PREVENIR PROPAGAÇÃO =====
document.querySelectorAll('.conteudo-suspenso, .submenu-suspenso, .sidebar-submenu').forEach(m => {
    m.addEventListener('click', e => e.stopPropagation());
});

// ===== TRADUÇÃO ONLINE (LIBRETRANSLATE VIA BACKEND) =====
const idiomaPadrao = 'pt';
let idiomaAtual = localStorage.getItem('idioma_ipikk') || idiomaPadrao;
const origemTextos = new WeakMap();
const cacheTraducao = new Map();
let traducaoEmAndamento = false;

function atualizarUIIdioma(idioma) {
    const spanIdioma = document.getElementById('idiomaAtual');
    if (spanIdioma) spanIdioma.textContent = idioma === 'en' ? 'English' : 'Português';
    document.querySelectorAll('.opcao-idioma').forEach(opt => {
        opt.classList.toggle('ativo', opt.getAttribute('data-lang') === idioma);
    });
}

function obterNosTradutiveis() {
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
            const parent = node.parentElement;
            if (!parent) return NodeFilter.FILTER_REJECT;
            const tag = parent.tagName.toLowerCase();
            if (['script', 'style', 'noscript', 'textarea'].includes(tag)) return NodeFilter.FILTER_REJECT;
            if (parent.closest('.dropdown-idioma, #google_translate_element')) return NodeFilter.FILTER_REJECT;
            return NodeFilter.FILTER_ACCEPT;
        }
    });
    const nos = [];
    let no;
    while ((no = walker.nextNode())) nos.push(no);
    return nos;
}

async function traduzirTextoOnline(texto, source, target) {
    const chave = `${source}:${target}:${texto}`;
    if (cacheTraducao.has(chave)) return cacheTraducao.get(chave);

    const resp = await fetch('processar-traducao.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ texto, source, target })
    });
    const data = await resp.json();
    if (!data.success) throw new Error(data.message || 'Falha na tradução');
    cacheTraducao.set(chave, data.translatedText);
    return data.translatedText;
}

function garantirIndicadorTraducao() {
    let indicador = document.getElementById('traducaoStatus');
    if (indicador) return indicador;
    indicador = document.createElement('div');
    indicador.id = 'traducaoStatus';
    indicador.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:99999;background:#003072;color:#fff;padding:10px 14px;border-radius:8px;font-size:12px;box-shadow:0 4px 12px rgba(0,0,0,.2);display:none;';
    indicador.textContent = 'A traduzir conteúdo...';
    document.body.appendChild(indicador);
    return indicador;
}

async function aplicarTraducaoOnline(idioma) {
    if (traducaoEmAndamento) return;
    traducaoEmAndamento = true;
    const indicador = garantirIndicadorTraducao();
    indicador.style.display = 'block';

    const source = idioma === 'en' ? 'pt' : 'en';
    const target = idioma;
    const nos = obterNosTradutiveis();
    try {
        for (const no of nos) {
            const textoAtual = no.nodeValue.trim();
            if (!textoAtual) continue;
            if (!origemTextos.has(no)) origemTextos.set(no, no.nodeValue);
            if (idioma === 'pt') {
                const original = origemTextos.get(no);
                if (typeof original === 'string') no.nodeValue = original;
                continue;
            }
            try {
                const traduzido = await traduzirTextoOnline(textoAtual, source, target);
                if (traduzido) no.nodeValue = no.nodeValue.replace(textoAtual, traduzido);
            } catch (_) {}
        }
    } finally {
        traducaoEmAndamento = false;
        indicador.style.display = 'none';
    }
}

function mudarIdioma(idioma) {
    if (idioma !== 'pt' && idioma !== 'en') return;
    idiomaAtual = idioma;
    localStorage.setItem('idioma_ipikk', idioma);
    atualizarUIIdioma(idioma);
    aplicarTraducaoOnline(idioma);
}

function inicializarTradutor() {
    localStorage.setItem('idioma_ipikk', idiomaAtual);
    atualizarUIIdioma(idiomaAtual);
    if (idiomaAtual === 'en') {
        aplicarTraducaoOnline('en');
    }
}

// Eventos do seletor de idioma
document.addEventListener('DOMContentLoaded', () => {
    inicializarTradutor();
    
    // Eventos dos botões de idioma
    const opcoesIdioma = document.querySelectorAll('.opcao-idioma');
    opcoesIdioma.forEach(opcao => {
        opcao.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const lang = opcao.getAttribute('data-lang');
            mudarIdioma(lang);
        });
    });
    
    // Inicialização da sidebar
    if (botaoTopo) botaoTopo.style.display = 'none';
    document.querySelectorAll('.sidebar-submenu').forEach(s => s.classList.remove('aberto'));
});

// ===== WEB PUSH (passo inicial) =====
async function registarServiceWorkerPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return null;
    try {
        return await navigator.serviceWorker.register('/area-publica/push-sw.js');
    } catch (_) {
        return null;
    }
}

async function subscreverPush() {
    const reg = await registarServiceWorkerPush();
    if (!reg) return;
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') return;

    let sub = await reg.pushManager.getSubscription();
    if (!sub) {
        // Subscription sem applicationServerKey (modo inicial para infra)
        sub = await reg.pushManager.subscribe({ userVisibleOnly: true });
    }

    const json = sub.toJSON();
    await fetch('processar-push.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            acao: 'subscrever',
            endpoint: json.endpoint,
            p256dh: json.keys?.p256dh || '',
            auth: json.keys?.auth || ''
        })
    });
}

function iniciarPromptPushSuave() {
    if (localStorage.getItem('push_prompt_decisao_ipikk')) return;
    const handler = async () => {
        window.removeEventListener('click', handler);
        window.removeEventListener('scroll', handler);
        if (!('Notification' in window) || Notification.permission === 'denied') return;
        const aceitar = confirm('Deseja receber notificações de novas notícias do IPIKK?');
        localStorage.setItem('push_prompt_decisao_ipikk', aceitar ? 'aceitou' : 'recusou');
        if (aceitar) await subscreverPush();
    };
    window.addEventListener('click', handler, { once: true });
    window.addEventListener('scroll', handler, { once: true });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarPromptPushSuave();
});
