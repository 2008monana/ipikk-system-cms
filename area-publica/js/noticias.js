// ===== DADOS DAS NOTÍCIAS =====
const pageDataEl = document.getElementById('noticiasPageData');
const noticiasData = JSON.parse(pageDataEl?.dataset.noticias || '[]');

function normalizarDataNoticia(noticia) {
    const referencia = noticia.created_at || noticia.data_publicacao;
    const d = new Date(referencia);
    return isNaN(d.getTime()) ? new Date() : d;
}

function formatarDataSlider(data) {
    const d = new Date(data);
    const meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    return `${d.getDate()} ${meses[d.getMonth()]} ${d.getFullYear()}`;
}

function formatarDataCompleta(data) {
    const d = new Date(data);
    const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()} às ${hh}:${mm}`;
}

function tempoRelativo(data) {
    const agora = new Date();
    const d = new Date(data);
    const diffSeg = Math.max(0, Math.floor((agora - d) / 1000));

    if (diffSeg < 60) return 'agora mesmo';
    const min = Math.floor(diffSeg / 60);
    if (min < 60) return min === 1 ? 'há 1 minuto' : `há ${min} minutos`;
    const h = Math.floor(min / 60);
    if (h < 24) return h === 1 ? 'há 1 hora' : `há ${h} horas`;
    const dias = Math.floor(h / 24);
    if (dias < 30) return dias === 1 ? 'há 1 dia' : `há ${dias} dias`;
    const meses = Math.floor(dias / 30);
    if (meses < 12) return meses === 1 ? 'há 1 mês' : `há ${meses} meses`;
    const anos = Math.floor(meses / 12);
    return anos === 1 ? 'há 1 ano' : `há ${anos} anos`;
}

function obterThumbNoticia(noticia) {
    if (noticia.imagem_url) return normalizarUrlNoticia(noticia.imagem_url);
    return `https://via.placeholder.com/640x360/003072/ffffff?text=${encodeURIComponent('Vídeo')}`;
}

function normalizarUrlNoticia(url) {
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;
    return '..' + String(url).replace(/^\/+/, '');
}

function normalizarTagsNoticia(tagsRaw) {
    if (!tagsRaw) return [];

    let tags = [];
    if (Array.isArray(tagsRaw)) {
        tags = tagsRaw;
    } else {
        try {
            const parsed = JSON.parse(tagsRaw);
            tags = Array.isArray(parsed) ? parsed : [tagsRaw];
        } catch (e) {
            tags = String(tagsRaw).split(',');
        }
    }

    return tags
        .map(tag => String(tag)
            .replace(/\\/g, '')
            .replace(/^\[+|\]+$/g, '')
            .replace(/^"+|"+$/g, '')
            .replace(/^'+|'+$/g, '')
            .trim()
        )
        .filter(Boolean);
}

const noticiaController = {
    filaNoticias: [...noticiasData].sort((a, b) => normalizarDataNoticia(b) - normalizarDataNoticia(a)),
    intervaloAutomatico: null,
    pausado: false,
    emAnimacao: false,

    init() {
        this.renderizarDestaque();
        this.renderizarSlider();
        this.iniciarAutoPlay();
        this.configurarEventos();
        this.configurarModalEventos();
    },

    get noticiaAtual() {
        return this.filaNoticias[0] || null;
    },

    renderizarDestaque() {
        const noticia = this.noticiaAtual;
        if (!noticia) return;

        const container = document.getElementById('noticiaDestaque');
        const midia = container?.querySelector('.container-midia');
        const categoria = document.getElementById('destaqueCategoria');
        const titulo = document.getElementById('destaqueTitulo');
        const data = document.getElementById('destaqueData');
        const tempo = document.getElementById('destaqueTempo');
        if (!container || !midia || !categoria || !titulo || !data || !tempo) return;

        container.dataset.id = noticia.id;
        categoria.innerHTML = `<i class="fas fa-trophy"></i> ${escapeHtml(noticia.categoria || 'NOTÍCIA')}`;
        titulo.textContent = noticia.titulo || '';

        const dataRef = normalizarDataNoticia(noticia);
        data.textContent = formatarDataCompleta(dataRef);
        tempo.textContent = tempoRelativo(dataRef);

        const midiaHtml = (noticia.tipo_midia === 'video')
            ? `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo || 'Vídeo')}">`
            : `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo || 'Notícia')}">`;

        midia.innerHTML = `${midiaHtml}<div class="overlay-escuro"></div>`;
    },

    renderizarSlider() {
        const lista = document.getElementById('listaSlider');
        if (!lista) return;
        const noticiasSlider = this.filaNoticias.slice(1);

        lista.style.transition = 'none';
        lista.style.transform = 'translateY(0)';
        lista.innerHTML = noticiasSlider.map(noticia => {
            const dataRef = normalizarDataNoticia(noticia);
            return `
                <div class="item-slider" onclick="abrirModalNoticia(${noticia.id})">
                    <div class="miniatura-slider">
                        <img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo)}">
                    </div>
                    <div class="conteudo-slider">
                        <div class="data-slider">
                            <i class="fas fa-clock"></i>
                            ${formatarDataSlider(dataRef)}
                        </div>
                        <h4 class="titulo-slider">${escapeHtml(noticia.titulo)}</h4>
                        <p class="resumo-slider">${escapeHtml(noticia.resumo || limitarTexto(noticia.conteudo, 80))}</p>
                    </div>
                </div>
            `;
        }).join('');
    },

    iniciarAutoPlay() {
        if (this.intervaloAutomatico) clearInterval(this.intervaloAutomatico);
        this.intervaloAutomatico = setInterval(() => {
            if (!this.pausado) this.proximaNoticia();
        }, 5000);
    },

    proximaNoticia() {
        if (this.filaNoticias.length <= 1 || this.emAnimacao) return;

        const lista = document.getElementById('listaSlider');
        const primeiro = lista?.querySelector('.item-slider');
        const altura = primeiro ? primeiro.offsetHeight : 193;

        this.emAnimacao = true;
        if (lista) {
            lista.style.transition = 'transform 0.6s ease';
            lista.style.transform = `translateY(-${altura}px)`;
        }

        setTimeout(() => {
            const primeira = this.filaNoticias.shift();
            this.filaNoticias.push(primeira);
            this.renderizarDestaque();
            this.renderizarSlider();
            this.emAnimacao = false;
        }, 620);
    },

    abrirModal(id) {
        fetch(`incrementar-visualizacao.php?tipo=noticia&id=${id}`).catch(() => {});
        const noticia = this.filaNoticias.find(n => Number(n.id) === Number(id));
        if (!noticia) return;

        const modal = document.getElementById('modalDetalhes');
        const midiaContainer = document.getElementById('modalMidia');
        if (!modal || !midiaContainer) return;

        if (noticia.tipo_midia === 'video' && noticia.video_file) {
            midiaContainer.innerHTML = `<video src="${normalizarUrlNoticia(noticia.video_file)}" controls autoplay poster="${obterThumbNoticia(noticia)}"></video>`;
        } else {
            midiaContainer.innerHTML = `<img src="${obterThumbNoticia(noticia)}" alt="${escapeHtml(noticia.titulo)}">`;
        }

        document.getElementById('modalCategoria').innerHTML = `<i class="fas fa-tag"></i> ${escapeHtml(noticia.categoria || 'NOTÍCIA')}`;
        document.getElementById('modalTitulo').textContent = noticia.titulo || '';
        document.getElementById('modalData').textContent = formatarDataCompleta(normalizarDataNoticia(noticia));
        document.getElementById('modalAutor').textContent = noticia.autor || 'Gabinete de Comunicação';
        document.getElementById('modalVisualizacoes').textContent = `${noticia.visualizacoes || 0} visualizações`;
        document.getElementById('modalDescricao').innerHTML = noticia.conteudo || '';

        const tagsEl = document.getElementById('modalTags');
        const tags = normalizarTagsNoticia(noticia.tags);
        tagsEl.innerHTML = tags.map(tag => `<span class="tag-item"><i class="fas fa-hashtag"></i>${escapeHtml(tag)}</span>`).join('');

        modal.classList.add('visivel');
        document.body.style.overflow = 'hidden';
        this.pausado = true;
    },

    abrirModalDestaque() {
        if (this.noticiaAtual) {
            this.abrirModal(this.noticiaAtual.id);
        }
    },

    fecharModal() {
        const modal = document.getElementById('modalDetalhes');
        if (!modal) return;
        modal.classList.remove('visivel');
        document.body.style.overflow = '';
        document.querySelectorAll('.modal-midia video').forEach(v => v.pause());
        setTimeout(() => { this.pausado = false; }, 100);
    },

    configurarEventos() {
        const areaSlider = document.getElementById('areaSlider');
        areaSlider?.addEventListener('mouseenter', () => { this.pausado = true; });
        areaSlider?.addEventListener('mouseleave', () => { this.pausado = false; });
    },

    configurarModalEventos() {
        const modal = document.getElementById('modalDetalhes');
        const btnFechar = document.querySelector('.btn-fechar-modal');
        btnFechar?.addEventListener('click', () => this.fecharModal());
        modal?.addEventListener('click', (e) => { if (e.target === modal) this.fecharModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.fecharModal(); });
    }
};

// Limitar texto
function limitarTexto(texto, limite) {
    if (!texto) return '';
    if (texto.length <= limite) return texto;
    return texto.substring(0, limite).trim() + '...';
}

// Escapar HTML
function escapeHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function abrirModalNoticia(id) {
    noticiaController.abrirModal(id);
}

function abrirModalNoticiaDestaque() {
    noticiaController.abrirModalDestaque();
}

function fecharModalNoticia() {
    noticiaController.fecharModal();
}

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    noticiaController.init();
});
    