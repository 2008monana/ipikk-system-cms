<?php
/**
 * Quadro de Honra - Área Restrita IPIKK
 * Gestão do quadro de honra (3 classes obrigatórias: 10ª, 11ª, 12ª)
 */

$titulo_pagina = 'Quadro de Honra';
$css_especifico = 'admin-quadro-honra.css';

require_once dirname(__DIR__) . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('conteudo_site');

$db = getDB();

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

// Buscar quadro de honra ativo
$qh = $db->query("SELECT * FROM quadro_honra WHERE ativo = 1 ORDER BY id DESC LIMIT 1")->fetch();

if (!$qh) {
    $db->exec("INSERT INTO quadro_honra (ano_lectivo, ativo) VALUES ('2024/2025', 1)");
    $qh = $db->query("SELECT * FROM quadro_honra WHERE ativo = 1 ORDER BY id DESC LIMIT 1")->fetch();
}

// Buscar alunos por classe
$alunos_por_classe = [];
$stmt = $db->prepare("SELECT * FROM quadro_honra_classe WHERE quadro_honra_id = ?");
$stmt->execute([$qh['id']]);
$alunos_db = $stmt->fetchAll();

foreach ($alunos_db as $aluno) {
    $alunos_por_classe[$aluno['classe']] = $aluno;
}

// Definir as 3 classes obrigatórias
$classes = [10, 11, 12];
$alunos = [];
foreach ($classes as $classe) {
    if (isset($alunos_por_classe[$classe])) {
        $alunos[$classe] = $alunos_por_classe[$classe];
    } else {
        $alunos[$classe] = [
            'id' => null,
            'classe' => $classe,
            'nome' => '',
            'media' => '',
            'curso' => '',
            'foto_url' => null
        ];
    }
}

// Buscar cursos para os selects
$cursos = $db->query("SELECT id, nome FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();

// Cores padrão para cursos
$cores_cursos = [];
$stmt = $db->query("
    SELECT c.nome, COALESCE(NULLIF(TRIM(c.cor), ''), NULLIF(TRIM(a.cor_primaria), ''), '#6c757d') as cor
    FROM cursos c
    LEFT JOIN areas a ON c.area_id = a.id
    WHERE c.estado = 'ativo'
");
while ($row = $stmt->fetch()) {
    $cores_cursos[$row['nome']] = $row['cor'];
}

// Buscar citação
$pagina = getPagina('quadro-honra');
$citacao_texto = $pagina['citacao']['texto'] ?? '';
$citacao_referencia = $pagina['citacao']['referencia'] ?? '';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="conteudo-principal">
    <header class="barra-superior">
        <div class="esquerda-barra">
            <button class="botao-menu-mobile" id="botaoMenuMobile" onclick="window.openSidebar && window.openSidebar()">
            <i class="fas fa-bars"></i>
            </button>
            <h1 class="titulo-pagina">
                <i class="fas fa-trophy"></i> Quadro de Honra
            </h1>
        </div>
    </header>

    <div class="conteudo-pagina">

        <!-- INFORMAÇÕES GERAIS -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-calendar-alt"></i> Informações Gerais
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label><i class="fas fa-calendar"></i> Ano Lectivo *</label>
                    <input type="text" id="anoLectivo" class="campo-form" value="<?= htmlspecialchars($qh['ano_lectivo']) ?>" required>
                </div>
            </div>
        </div>

        <!-- MELHORES ALUNOS POR CLASSE (3 CLASSES OBRIGATÓRIAS) -->
        <div class="secao-conteudo">
            <div class="secao-header">
                <h2 class="secao-titulo">
                    <i class="fas fa-users"></i> Melhores Alunos por Classe
                </h2>
                <span class="badge-obrigatorio">* Todos os campos são obrigatórios</span>
            </div>

            <div id="listaMelhoresClasse">
                <?php foreach ($alunos as $classe => $aluno): ?>
                <div class="item-melhor-classe" data-classe="<?= $classe ?>">
                    <div class="item-header">
                        <h3 class="item-titulo">
                            <span class="classe-badge">
                                <i class="fas fa-graduation-cap"></i> <?= $classe ?>ª Classe
                            </span>
                        </h3>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label><i class="fas fa-user"></i> Nome do Aluno *</label>
                            <input type="text" class="campo-form nome-input" value="<?= htmlspecialchars($aluno['nome'] ?? '') ?>" required>
                        </div>
                        <div class="grupo-form">
                            <label><i class="fas fa-chart-line"></i> Média *</label>
                            <input type="text" class="campo-form media-input" value="<?= htmlspecialchars($aluno['media'] ?? '') ?>" placeholder="Ex: 17 Valores" required>
                        </div>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label><i class="fas fa-graduation-cap"></i> Curso *</label>
                            <select class="campo-form curso-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($cursos as $curso): ?>
                                <option value="<?= htmlspecialchars($curso['nome']) ?>" <?= ($aluno['curso'] ?? '') == $curso['nome'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['nome']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grupo-form">
                            <label><i class="fas fa-palette"></i> Cor do Curso</label>
                            <div class="cor-preview"></div>
                        </div>
                    </div>
                    <div class="linha-form">
                        <div class="grupo-form">
                            <label><i class="fas fa-image"></i> Foto do Aluno</label>
                            <div class="area-upload-pequena" onclick="document.getElementById('fotoClasse_<?= $classe ?>').click()">
                                <i class="fas fa-cloud-upload-alt"></i> Upload
                            </div>
                            <input type="file" id="fotoClasse_<?= $classe ?>" accept="image/*" style="display: none;" data-classe="<?= $classe ?>" onchange="previewClasseFoto(this)">
                            <div class="preview-foto-pequena" id="previewClasse_<?= $classe ?>">
                                <?php if (!empty($aluno['foto_url']) && $aluno['foto_url'] != 'foto/sem_foto.png'): ?>
                                <img src="<?= htmlspecialchars(normalizarUrlMidia($aluno['foto_url'] ?? 'foto/sem_foto.png', '..')) ?>" class="classe-foto-preview">
                                <button type="button" class="btn-remover-pequeno" onclick="removerClasseFoto(<?= $classe ?>)">×</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grupo-form">
                            <label>&nbsp;</label>
                            <div class="info-texto">A foto será exibida no card da classe</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CITAÇÃO -->
        <div class="secao-conteudo">
            <h2 class="secao-titulo">
                <i class="fas fa-quote-right"></i> Citação do Quadro de Honra
            </h2>
            <div class="linha-form">
                <div class="grupo-form">
                    <label>Texto da Citação</label>
                    <textarea id="citacaoTexto" class="campo-form area-texto" rows="3" placeholder="Digite a citação..."><?= htmlspecialchars($citacao_texto) ?></textarea>
                </div>
                <div class="grupo-form">
                    <label>Referência</label>
                    <input type="text" id="citacaoReferencia" class="campo-form" value="<?= htmlspecialchars($citacao_referencia) ?>" placeholder="Ex: Lucas 2:40">
                </div>
            </div>
        </div>

        <!-- AÇÕES -->
        <div class="secao-acoes">
            <button class="btn-primario btn-grande" onclick="salvarQuadroHonra()">
                <i class="fas fa-save"></i> Guardar Quadro de Honra
            </button>
            <button class="btn-secundario" onclick="previewQuadroHonra()">
                <i class="fas fa-eye"></i> Pré-visualizar
            </button>
        </div>
    </div>
</main>

<div id="modalConfirmacao" class="modal-confirmacao">
    <div class="modal-confirmacao-caixa">
        <div class="modal-confirmacao-icone">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 id="confirmacaoTitulo">Confirmar ação</h3>
        <p id="confirmacaoTexto">Tem certeza que deseja continuar?</p>
        <div class="modal-confirmacao-botoes">
            <button class="botao-cancelar-modal" id="btnCancelarConfirmacao">Cancelar</button>
            <button class="botao-confirmar-modal" id="btnConfirmarAcao">Confirmar</button>
        </div>
    </div>
</div>

<style>
/* ===== ESTILOS ADMIN QUADRO HONRA ===== */
/* Botão menu mobile - comportamento correto */
.botao-menu-mobile {
    display: none; /* escondido no desktop */
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #f5f7fa;
    color: #008bb5;
    font-size: 18px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    align-items: center;
    justify-content: center;
}

.botao-menu-mobile:hover {
    background: #e6f7ff;
    transform: scale(1.05);
}

@media (max-width: 768px) {
    .botao-menu-mobile {
        display: flex; /* aparece só no mobile */
    }
}
/* Secções principais */
.secao-conteudo {
    background: white;
    border-radius: 24px;
    padding: 28px;
    margin-bottom: 32px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid #f0f2f5;
    transition: all 0.3s ease;
}

.secao-conteudo:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    border-color: #e2e8f0;
}

.secao-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 12px;
}

.secao-titulo {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #0f172a;
}

.secao-titulo i {
    color: #0a9396;
    font-size: 1.2rem;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 147, 150, 0.1);
    border-radius: 12px;
}

/* Badge obrigatório */
.badge-obrigatorio {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    border: 1px solid #fcd34d;
}

/* Grid de formulário */
.linha-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

/* Grupos de formulário */
.grupo-form {
    margin-bottom: 0;
}

.grupo-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #64748b;
}

.grupo-form label i {
    margin-right: 6px;
    color: #0a9396;
}

/* Campos de input */
.campo-form {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    background: white;
    font-family: inherit;
}

.campo-form:focus {
    outline: none;
    border-color: #0a9396;
    box-shadow: 0 0 0 3px rgba(10, 147, 150, 0.1);
}

.campo-form::placeholder {
    color: #cbd5e1;
    font-size: 0.85rem;
}

select.campo-form {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
}

.area-texto {
    resize: vertical;
    min-height: 90px;
    line-height: 1.5;
}

/* Preview da cor do curso */
.cor-preview {
    width: 100%;
    height: 46px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Items de cada classe */
.item-melhor-classe {
    background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #eef2f8;
    transition: all 0.3s ease;
    position: relative;
}

.item-melhor-classe:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

.item-header {
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eef2f8;
}

.item-titulo {
    margin: 0;
}

.classe-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 40px;
    font-size: 0.85rem;
    font-weight: 700;
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 48, 114, 0.2);
}

.classe-badge i {
    font-size: 0.9rem;
}

/* Upload de imagem */
.area-upload-pequena {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s ease;
    background: #fafbfc;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.area-upload-pequena:hover {
    border-color: #0a9396;
    background: #f0fdfa;
    color: #0a9396;
}

.area-upload-pequena i {
    font-size: 0.9rem;
}

/* Preview de foto */
.preview-foto-pequena {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.classe-foto-preview {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

.btn-remover-pequeno {
    background: #fee2e2;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    color: #dc2626;
    font-size: 1rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-remover-pequeno:hover {
    background: #dc2626;
    color: white;
    transform: scale(1.05);
}

/* Info text */
.info-texto {
    font-size: 0.7rem;
    color: #94a3b8;
    padding: 12px 0 0 0;
    line-height: 1.4;
}

/* Botões de ação */
.secao-acoes {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    margin-top: 24px;
    padding-top: 8px;
}

.btn-primario {
    background: linear-gradient(135deg, #003072 0%, #0a5e6b 100%);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primario i {
    transition: transform 0.2s ease;
}

.btn-primario:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0, 48, 114, 0.3);
}

.btn-primario:hover i {
    transform: rotate(-10deg);
}

.btn-grande {
    padding: 12px 32px;
    font-size: 0.9rem;
}

.btn-secundario {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    color: #475569;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-secundario:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.btn-secundario i {
    transition: transform 0.2s ease;
}

.btn-secundario:hover i {
    transform: translateX(3px);
}

/* Modal de confirmação */
.modal-confirmacao {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 10001;
    align-items: center;
    justify-content: center;
}

.modal-confirmacao.ativo {
    display: flex;
}

.modal-confirmacao-caixa {
    background: white;
    border-radius: 28px;
    padding: 32px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    animation: zoomIn 0.2s ease;
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.2);
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.modal-confirmacao-caixa h3 {
    margin: 0 0 8px 0;
    font-size: 1.2rem;
    color: #1e293b;
}

.modal-confirmacao-caixa p {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.modal-confirmacao-icone {
    width: 60px;
    height: 60px;
    margin: 0 auto 16px;
    background: #fee2e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #dc2626;
}

.modal-confirmacao-botoes {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.botao-cancelar-modal, .botao-confirmar-modal {
    padding: 10px 24px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.botao-cancelar-modal {
    background: #f1f5f9;
    color: #64748b;
}

.botao-cancelar-modal:hover {
    background: #e2e8f0;
}

.botao-confirmar-modal {
    background: #dc2626;
    color: white;
}

.botao-confirmar-modal:hover {
    background: #b91c1c;
    transform: scale(1.02);
}

/* Notificação */
.notificacao {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 24px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 50px;
    z-index: 99999;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    animation: slideInRight 0.3s ease;
}

.notificacao.erro {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.notificacao.info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsividade */
@media (max-width: 768px) {
    .secao-conteudo {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .secao-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .linha-form {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .badge-obrigatorio {
        font-size: 0.65rem;
        padding: 4px 10px;
    }
    
    .item-melhor-classe {
        padding: 18px;
    }
    
    .classe-badge {
        font-size: 0.75rem;
        padding: 6px 14px;
    }
    
    .secao-acoes {
        flex-direction: column;
    }
    
    .btn-primario, .btn-secundario {
        width: 100%;
        justify-content: center;
    }
    
    .notificacao {
        left: 16px;
        right: 16px;
        top: 16px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .secao-titulo {
        font-size: 1rem;
    }
    
    .secao-titulo i {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
    }
    
    .classe-badge i {
        display: none;
    }
    
    .classe-foto-preview {
        width: 55px;
        height: 55px;
    }
    
    .modal-confirmacao-caixa {
        padding: 24px;
    }
    
    .modal-confirmacao-caixa h3 {
        font-size: 1rem;
    }
}

/* Animação de loading para o botão salvar */
.btn-primario:active {
    transform: scale(0.98);
}

/* Estilo para campos inválidos */
.campo-form.error {
    border-color: #dc2626;
    background-color: #fef2f2;
}

.campo-form.error:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

/* Scrollbar personalizada para modais */
.modal-conteudo::-webkit-scrollbar {
    width: 6px;
}

.modal-conteudo::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.modal-conteudo::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.modal-conteudo::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
const coresCursos = <?php echo json_encode($cores_cursos, JSON_UNESCAPED_UNICODE); ?>;
let classeFotos = {};

function getCorCurso(cursoNome) {
    return coresCursos[cursoNome] || '#6c757d';
}

function mostrarNotificacao(mensagem, tipo = 'sucesso') {
    const notif = document.createElement('div');
    notif.className = `notificacao ${tipo}`;
    notif.innerHTML = `<i class="fas fa-${tipo === 'sucesso' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensagem}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

function previewClasseFoto(input) {
    const classe = input.dataset.classe;
    const file = input.files[0];
    if (!file) return;
    
    if (file.size > 5 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 5MB', 'erro');
        input.value = '';
        return;
    }
    
    classeFotos[classe] = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewDiv = document.getElementById(`previewClasse_${classe}`);
        previewDiv.innerHTML = `
            <img src="${e.target.result}" class="classe-foto-preview">
            <button type="button" class="btn-remover-pequeno" onclick="removerClasseFoto(${classe})">×</button>
        `;
    };
    reader.readAsDataURL(file);
}

function removerClasseFoto(classe) {
    delete classeFotos[classe];
    const input = document.getElementById(`fotoClasse_${classe}`);
    if (input) input.value = '';
    const previewDiv = document.getElementById(`previewClasse_${classe}`);
    previewDiv.innerHTML = '';
}

function validarCampos() {
    let valido = true;
    const items = document.querySelectorAll('.item-melhor-classe');
    
    items.forEach(item => {
        const nome = item.querySelector('.nome-input')?.value.trim();
        const media = item.querySelector('.media-input')?.value.trim();
        const curso = item.querySelector('.curso-select')?.value;
        
        if (!nome) {
            valido = false;
            item.querySelector('.nome-input').style.borderColor = '#dc3545';
        } else {
            item.querySelector('.nome-input').style.borderColor = '#e0e4e8';
        }
        
        if (!media) {
            valido = false;
            item.querySelector('.media-input').style.borderColor = '#dc3545';
        } else {
            item.querySelector('.media-input').style.borderColor = '#e0e4e8';
        }
        
        if (!curso) {
            valido = false;
            item.querySelector('.curso-select').style.borderColor = '#dc3545';
        } else {
            item.querySelector('.curso-select').style.borderColor = '#e0e4e8';
        }
    });
    
    return valido;
}

function coletarMelhoresClasse() {
    const melhores = [];
    const items = document.querySelectorAll('.item-melhor-classe');
    
    items.forEach(item => {
        const classe = parseInt(item.dataset.classe);
        const nome = item.querySelector('.nome-input')?.value.trim();
        const media = item.querySelector('.media-input')?.value.trim();
        const curso = item.querySelector('.curso-select')?.value;
        const id = item.dataset.id;
        
        if (nome && media && curso) {
            melhores.push({
                id: id || null,
                classe: classe,
                nome: nome,
                media: media,
                curso: curso,
                foto_index: classe
            });
        }
    });
    
    return melhores;
}

async function salvarQuadroHonra() {
    const anoLectivo = document.getElementById('anoLectivo').value;
    const citacaoTexto = document.getElementById('citacaoTexto').value;
    const citacaoReferencia = document.getElementById('citacaoReferencia').value;
    
    if (!anoLectivo) {
        mostrarNotificacao('Preencha o ano lectivo', 'erro');
        return;
    }
    
    if (!validarCampos()) {
        mostrarNotificacao('Preencha todos os campos obrigatórios das 3 classes', 'erro');
        return;
    }
    
    const melhoresClasse = coletarMelhoresClasse();
    
    if (melhoresClasse.length !== 3) {
        mostrarNotificacao('As 3 classes (10ª, 11ª, 12ª) são obrigatórias', 'erro');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'salvar');
    formData.append('quadro_honra_id', <?= $qh['id'] ?>);
    formData.append('ano_lectivo', anoLectivo);
    formData.append('citacao_texto', citacaoTexto);
    formData.append('citacao_referencia', citacaoReferencia);
    formData.append('melhores_classe', JSON.stringify(melhoresClasse));
    
    for (const [classe, file] of Object.entries(classeFotos)) {
        formData.append(`classe_foto_${classe}`, file);
    }
    
    mostrarNotificacao('A processar...', 'info');
    
    try {
        const response = await fetch('processos/processar-quadro-honra.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao(data.message, 'sucesso');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacao(data.message, 'erro');
        }
    } catch (error) {
        mostrarNotificacao('Erro ao salvar quadro de honra', 'erro');
    }
}

function previewQuadroHonra() {
    window.open('../area-publica/quadro-honra.php', '_blank');
}

// Atualizar cores dos previews
document.querySelectorAll('.item-melhor-classe .curso-select').forEach(select => {
    const preview = select.closest('.linha-form')?.querySelector('.cor-preview');
    if (preview && select.value) {
        preview.style.background = getCorCurso(select.value);
    }
    select.addEventListener('change', function() {
        const preview = this.closest('.linha-form')?.querySelector('.cor-preview');
        if (preview) {
            preview.style.background = getCorCurso(this.value);
        }
    });
});

document.getElementById('btnCancelarConfirmacao')?.addEventListener('click', () => {
    document.getElementById('modalConfirmacao').classList.remove('ativo');
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('modalConfirmacao').classList.remove('ativo');
    }
});
</script>

<?php include 'includes/footer.php'; ?>