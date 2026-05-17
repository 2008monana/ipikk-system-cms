<?php
/**
 * Pagina Inscricoes - IPIKK
 * Exibe informacoes sobre o processo de inscricao e matricula
 */

require_once '../config/index.php';

$config = getDB()->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$areas = getDB()->query("SELECT * FROM areas WHERE ativo = 1 ORDER BY ordem")->fetchAll();

$todos_cursos = getDB()->query("SELECT * FROM cursos WHERE estado = 'ativo' ORDER BY nome")->fetchAll();
$cursos_por_area = [];
foreach ($todos_cursos as $curso_item) {
    $cursos_por_area[$curso_item['area_id']][] = $curso_item;
}

$status_inscricoes = getDB()->query("SELECT status FROM controle_inscricoes WHERE id = 1")->fetch();
$link_inscricao = ($status_inscricoes && $status_inscricoes['status'] === 'abertas') ? 'inscricoes.php' : 'inscricoes-indisponiveis.php';

// Se as inscrições estiverem fechadas, impedir acesso direto via URL
if (!$status_inscricoes || $status_inscricoes['status'] !== 'abertas') {
    header('Location: inscricoes-indisponiveis.php');
    exit;
}

$conteudo = getDB()->query("SELECT * FROM conteudo_inscricoes WHERE id = 1")->fetch();

if (!$conteudo) {
    $conteudo = [
        'titulo_disponivel' => 'Processo de Inscricao',
        'msg_abertura' => 'Saiba como fazer parte do Instituto Medio Politecnico Industrial do Kilamba Kiaxi. Siga os passos abaixo e garanta sua vaga!',
        'documentos' => '["Fotocopia do Bilhete de Identidade (ou Certidao de Nascimento)", "Certificado de Habilitacoes (6a ou 9a classe)", "2 Fotos tipo passe (recentes)", "Declaracao de residencia (atualizada)", "Atestado Medico (fisico e mental)", "Processo de candidatura preenchido (fornecido na escola)"]',
        'passos_inscricao' => '["Dirija-se a Escola", "Escolha o Curso", "Entrega dos Documentos", "Aguarde o Teste"]',
        'texto_teste' => 'Todos os candidatos inscritos deverao realizar o teste de admissao, que avaliara os conhecimentos nas disciplinas de Lingua Portuguesa e Matematica.',
        'horario_teste' => '8h as 12h',
        'data_teste' => null,
        'titulo_matricula' => 'Processo de Matricula',
        'descricao_matricula' => 'Apos ser aprovado no teste de admissao, o processo e simples e rapido!',
        'passos_matricula' => '[{"titulo":"Tire a Foto","descricao":"Dirija-se a escola para tirar a sua fotografia oficial para o cartao de estudante."},{"titulo":"Receba o Cartao","descricao":"Apos tirar a foto, aguarde alguns dias e volte a escola para buscar o seu cartao de estudante."},{"titulo":"Inicio das Aulas","descricao":"Com o cartao entregue, aguarde o calendario letivo para o inicio normal das aulas."}]',
        'cards_matricula' => '[{"icone":"fa-book","titulo":"Acesso a Biblioteca","descricao":"Utilize o cartao para emprestimo de livros e acesso as instalacoes."},{"icone":"fa-utensils","titulo":"Cantina Escolar","descricao":"Identificacao para uso da cantina e refeitorio."},{"icone":"fa-bus","titulo":"Transporte","descricao":"Descontos em transportes publicos (quando aplicavel)."}]',
        'info_importantes' => '["Prazo para tirar a foto: Ate 15 dias apos a divulgacao dos resultados", "Horario para atendimento: Segunda a Sexta, das 8h as 15h", "Documento necessario: Apenas o comprovante de aprovacao (entregue no ato da inscricao)", "Local: Secretaria do IPIKK"]',
        'texto_cartao_estudante' => 'O cartao de estudante e o seu documento oficial dentro do IPIKK. Ele sera necessario para:',
        'mensagem_cartao_destaque' => 'O cartao e gratuito e sera entregue apos a realizacao da foto na escola.',
        'vagas_curso' => '[{"curso":"Tecnico de Obras","vagas":40},{"curso":"Desenhador Projectista","vagas":35},{"curso":"Energia e Instalacoes","vagas":45},{"curso":"Frio e Climatizacao","vagas":38},{"curso":"Gestao de Sistemas","vagas":25},{"curso":"Tecnico de Informatica","vagas":28},{"curso":"Tecnologias de Moveis","vagas":32}]',
        'data_resultados' => null,
        'resultados_disponiveis' => 0,
        'contacto_telefone' => $config['telefone'] ?? '+244 933 096 705',
        'contacto_email' => $config['email_inscricoes'] ?? 'inscricoes@ipikk.ao',
        'contacto_horario' => 'Segunda a Sexta, das 8h as 16h',
        'contacto_endereco' => $config['endereco_completo'] ?? 'Nova Vida, Kilamba Kiaxi, Luanda - Angola'
    ];
}

$documentos = json_decode($conteudo['documentos'], true) ?? [];
$passos_inscricao = json_decode($conteudo['passos_inscricao'], true) ?? [];
$passos_matricula = json_decode($conteudo['passos_matricula'], true) ?? [];
$cards_matricula = json_decode($conteudo['cards_matricula'], true) ?? [];
$info_importantes = json_decode($conteudo['info_importantes'], true) ?? [];
$vagas_curso = json_decode($conteudo['vagas_curso'], true) ?? [];

$ano_lectivo = $config['ano_lectivo_atual'] ?? date('Y') . '/' . (date('Y') + 1);
$vagas_reais = getDB()->prepare("SELECT c.nome, v.vagas_disponiveis 
                                   FROM vagas_curso v 
                                   JOIN cursos c ON v.curso_id = c.id 
                                   WHERE v.ano_lectivo = ?");
$vagas_reais->execute([$ano_lectivo]);
$vagas_reais_data = $vagas_reais->fetchAll();

if (!empty($vagas_reais_data)) {
    $vagas_curso = [];
    foreach ($vagas_reais_data as $vaga) {
        $vagas_curso[] = ['curso' => $vaga['nome'], 'vagas' => $vaga['vagas_disponiveis']];
    }
}

$badge_cores = [
    'Construcao Civil' => 'badge-construcao',
    'Tecnico de Obras' => 'badge-construcao',
    'Desenhador Projectista' => 'badge-construcao',
    'Electricidade' => 'badge-eletricidade',
    'Energia e Instalacoes' => 'badge-eletricidade',
    'Mecanica' => 'badge-mecanica',
    'Frio e Climatizacao' => 'badge-mecanica',
    'Informatica' => 'badge-informatica',
    'Gestao de Sistemas' => 'badge-informatica',
    'Tecnico de Informatica' => 'badge-informatica',
    'Tecnologias de Moveis' => 'badge-moveis'
];

$inscricoes_abertas = ($status_inscricoes && $status_inscricoes['status'] === 'abertas');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPIKK - Inscricoes</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link href="<?= $config['favicon_url'] ?? 'foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <link rel="stylesheet" href="css/header-footer.css">
    
    <style>
          /* === VARIÁVEIS CSS === */
:root {
    --ciano-suave: #008bb5;
    --ciano-claro: #e6f7ff;
    --ciano-escuro-suave: #006d8f;
    --gradiente-suave: linear-gradient(135deg, #008bb5 0%, #006d8f 100%);
    --cinza-claro-bg: #f5f8fa;
    --texto-principal: #2c3e50;
    --texto-secundario: #5a6b7a;
    --branco: #ffffff;
    --transition: all 0.3s ease;
    --verde-sucesso: #28a745;
    --verde-claro: #e8f5e9;
    --laranja-destaque: #ff6b35;
    --laranja-claro: #fff3e6;
    --roxo-matricula: #6f42c1;
    --roxo-claro: #f3e8ff;
    --amarelo-vagas: #f39c12;
    --amarelo-claro: #fff8e7;
    --azul-info: #3498db;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Montserrat', sans-serif;
    background: var(--cinza-claro-bg);
    color: var(--texto-principal);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}
h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; font-weight: 600; }
a { text-decoration: none; color: inherit; transition: var(--transition); }

/* ===== TABS (ABAS) ===== */
.tabs-container {
    max-width: 1400px;
    margin: 40px auto 20px;
    padding: 0 40px;
}
.tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e0e7ed;
    margin-bottom: 30px;
}
.tab-btn {
    background: none;
    border: none;
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    color: var(--texto-secundario);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.tab-btn i { font-size: 1.2rem; }
.tab-btn:hover { color: var(--ciano-suave); background: var(--ciano-claro); }
.tab-btn.ativo { color: var(--ciano-suave); background: var(--ciano-claro); }
.tab-btn.ativo::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--ciano-suave);
    border-radius: 3px;
}
.tab-conteudo { display: none; animation: fadeIn 0.4s ease; }
.tab-conteudo.ativo { display: block; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== CONTEÚDO PRINCIPAL ===== */
.main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

/* Hero Section */
.hero-inscricoes, .hero-matriculas {
    border-radius: 30px;
    padding: 50px 40px;
    margin-bottom: 40px;
    color: var(--branco);
    text-align: center;
}
.hero-inscricoes { background: var(--gradiente-suave); }
.hero-matriculas { background: linear-gradient(135deg, var(--roxo-matricula) 0%, #5a32a3 100%); }
.hero-inscricoes h1, .hero-matriculas h1 { font-size: 2.5rem; margin-bottom: 15px; }
.hero-inscricoes p, .hero-matriculas p { font-size: 1.1rem; opacity: 0.95; max-width: 700px; margin: 0 auto; }
.hero-inscricoes i, .hero-matriculas i { font-size: 3rem; margin-bottom: 20px; display: inline-block; }

/* Cards de Informação */
.grid-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}
.card-info {
    background: var(--branco);
    border-radius: 24px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: var(--transition);
    border: 1px solid rgba(0,139,181,0.1);
}
.card-info:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,139,181,0.1);
}
.card-info .icone-card {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
}
.card-info.inscricao .icone-card { background: var(--ciano-claro); }
.card-info.matricula .icone-card { background: var(--roxo-claro); }
.card-info.vagas .icone-card { background: var(--amarelo-claro); }
.card-info .icone-card i { font-size: 2rem; }
.card-info.inscricao .icone-card i { color: var(--ciano-suave); }
.card-info.matricula .icone-card i { color: var(--roxo-matricula); }
.card-info.vagas .icone-card i { color: var(--amarelo-vagas); }
.card-info h2 {
    font-size: 1.5rem;
    color: var(--texto-principal);
    margin-bottom: 20px;
}
.card-info h2 i { margin-right: 10px; font-size: 1.3rem; }
.card-info.inscricao h2 i { color: var(--ciano-suave); }
.card-info.matricula h2 i { color: var(--roxo-matricula); }
.card-info.vagas h2 i { color: var(--amarelo-vagas); }
.lista-documentos {
    list-style: none;
    margin-top: 15px;
}
.lista-documentos li {
    padding: 12px 0;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    align-items: center;
    gap: 12px;
}
.lista-documentos li:last-child { border-bottom: none; }
.lista-documentos li i { width: 24px; }
.card-info.inscricao .lista-documentos li i { color: var(--ciano-suave); }
.card-info.matricula .lista-documentos li i { color: var(--roxo-matricula); }
.card-info.vagas .lista-documentos li i { color: var(--amarelo-vagas); }
.lista-documentos li strong { color: var(--texto-principal); }

/* Tabela de Vagas */
.tabela-vagas {
    overflow-x: auto;
    margin-top: 20px;
}
.tabela-vagas table {
    width: 100%;
    border-collapse: collapse;
    background: var(--branco);
    border-radius: 16px;
    overflow: hidden;
}
.tabela-vagas th {
    background: linear-gradient(135deg, var(--amarelo-vagas), #e67e22);
    color: var(--branco);
    padding: 15px;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: left;
}
.tabela-vagas td {
    padding: 15px;
    border-bottom: 1px solid #eef2f6;
    color: var(--texto-secundario);
}
.tabela-vagas tr:hover {
    background: var(--amarelo-claro);
}
.vagas-disponiveis {
    font-weight: 700;
    color: var(--verde-sucesso);
}
.vagas-limitadas {
    font-weight: 700;
    color: var(--laranja-destaque);
}
.vagas-esgotadas {
    font-weight: 700;
    color: #e74c3c;
}
.badge-curso {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-construcao { background: #9FA3A720; color: #5a6268; }
.badge-eletricidade { background: #3A7BC020; color: #2c5a8c; }
.badge-mecanica { background: #E67E2220; color: #b85e1a; }
.badge-informatica { background: #1F7A4D20; color: #155d3a; }
.badge-moveis { background: #e01a1a20; color: #b01313; }

/* Timeline Passo a Passo */
.timeline {
    background: var(--branco);
    border-radius: 30px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}
.timeline h2 {
    text-align: center;
    font-size: 1.8rem;
    margin-bottom: 40px;
    color: var(--texto-principal);
}
.timeline h2 i { color: var(--ciano-suave); margin-right: 10px; }
.passos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 30px;
}
.passo {
    text-align: center;
    position: relative;
}
.numero-passo {
    width: 60px;
    height: 60px;
    background: var(--gradiente-suave);
    color: var(--branco);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 auto 20px;
    box-shadow: 0 5px 15px rgba(0,139,181,0.3);
}
.passo h3 { font-size: 1.1rem; margin-bottom: 10px; color: var(--ciano-suave); }
.passo p { color: var(--texto-secundario); font-size: 0.9rem; }
.passo:not(:last-child)::after {
    content: '→';
    position: absolute;
    right: -20px;
    top: 20px;
    font-size: 1.5rem;
    color: var(--ciano-suave);
    opacity: 0.5;
}
@media (max-width: 768px) {
    .passo:not(:last-child)::after {
        content: '↓';
        right: 50%;
        top: auto;
        bottom: -25px;
        transform: translateX(50%);
    }
}

/* Timeline Matrícula */
.timeline-simples {
    background: var(--roxo-claro);
    border-radius: 30px;
    padding: 40px;
    margin-bottom: 40px;
}
.timeline-simples h2 {
    text-align: center;
    font-size: 1.8rem;
    margin-bottom: 30px;
    color: var(--roxo-matricula);
}
.timeline-simples h2 i { margin-right: 10px; }
.passos-simples {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}
.passo-simples {
    text-align: center;
    flex: 1;
    min-width: 200px;
}
.passo-simples .icone-passo {
    width: 80px;
    height: 80px;
    background: var(--branco);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 5px 15px rgba(111,66,193,0.2);
}
.passo-simples .icone-passo i { font-size: 2rem; color: var(--roxo-matricula); }
.passo-simples h3 { font-size: 1.2rem; margin-bottom: 10px; color: var(--roxo-matricula); }
.passo-simples p { color: var(--texto-secundario); font-size: 0.9rem; }

/* Seção de Teste de Admissão */
.secao-teste {
    background: linear-gradient(135deg, var(--laranja-claro) 0%, #fff 100%);
    border-radius: 30px;
    padding: 40px;
    margin-bottom: 40px;
    border-left: 5px solid var(--laranja-destaque);
}
.secao-teste h2 {
    font-size: 1.6rem;
    margin-bottom: 20px;
    color: var(--laranja-destaque);
}
.secao-teste h2 i { margin-right: 10px; }
.secao-teste p { margin-bottom: 15px; color: var(--texto-secundario); }
.data-teste {
    background: var(--branco);
    padding: 15px 20px;
    border-radius: 16px;
    display: inline-block;
    margin: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.data-teste i { color: var(--laranja-destaque); margin-right: 10px; }
.data-teste strong { color: var(--laranja-destaque); font-size: 1rem; }

/* Seção de Resultados */
.secao-resultados {
    background: var(--verde-claro);
    border-radius: 30px;
    padding: 40px;
    text-align: center;
    margin-bottom: 40px;
}
.secao-resultados h2 {
    font-size: 1.6rem;
    margin-bottom: 15px;
    color: var(--verde-sucesso);
}
.secao-resultados h2 i { margin-right: 10px; }
.secao-resultados p { margin-bottom: 25px; color: var(--texto-secundario); }
.botao-resultados {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: var(--verde-sucesso);
    color: var(--branco);
    padding: 14px 32px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
    border: none;
    cursor: pointer;
}
.botao-resultados:hover {
    background: #1e7e34;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(40,167,69,0.3);
}

/* Seção Cartão de Estudante */
.secao-cartao {
    background: var(--roxo-claro);
    border-radius: 30px;
    padding: 40px;
    text-align: center;
    margin-bottom: 40px;
}
.secao-cartao h2 {
    font-size: 1.6rem;
    margin-bottom: 15px;
    color: var(--roxo-matricula);
}
.secao-cartao h2 i { margin-right: 10px; }
.secao-cartao p { margin-bottom: 20px; color: var(--texto-secundario); }
.cartao-destaque {
    background: var(--branco);
    border-radius: 20px;
    padding: 25px;
    display: inline-block;
    margin-top: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.cartao-destaque i { font-size: 3rem; color: var(--roxo-matricula); margin-bottom: 10px; }
.cartao-destaque p { margin: 0; font-weight: 600; color: var(--roxo-matricula); }

/* Informações Complementares */
.info-complementar {
    background: var(--branco);
    border-radius: 24px;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
}
.info-complementar .info-texto {
    display: flex;
    align-items: center;
    gap: 15px;
}
.info-complementar i { font-size: 2rem; color: var(--ciano-suave); }
.info-complementar p { color: var(--texto-secundario); }
.info-complementar p strong { color: var(--texto-principal); }
.botao-contato {
    background: var(--ciano-claro);
    color: var(--ciano-suave);
    padding: 12px 25px;
    border-radius: 30px;
    font-weight: 600;
    transition: var(--transition);
}
.botao-contato:hover {
    background: var(--ciano-suave);
    color: var(--branco);
    transform: translateY(-2px);
}

/* ===== RODAPÉ ===== */
.rodape { background: var(--gradiente-suave); color: white; padding: 0; margin-top: 60px; }
.container-rodape { max-width: 1400px; margin: 0 auto; padding: 50px 40px 0; }
.conteudo-rodape { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
.foto-instituto { margin-bottom: 15px; }
.foto-instituto img { max-width: 110px; filter: brightness(1.1); }
.descricao-rodape { font-size: 0.9rem; opacity: 0.95; line-height: 1.7; margin: 15px 0 20px; max-width: 280px; color: rgba(255,255,255,0.95); }
.social-rodape { display: flex; gap: 12px; }
.social-rodape a { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; transition: var(--transition); color: var(--branco); font-size: 1rem; }
.social-rodape a:hover { background: rgba(255,255,255,0.3); transform: translateY(-3px); }
.titulo-rodape { font-size: 1rem; font-weight: 600; margin-bottom: 18px; color: var(--branco); letter-spacing: 0.5px; }
.links-rodape { display: flex; flex-direction: column; gap: 10px; }
.link-rodape { font-size: 0.9rem; opacity: 0.9; transition: var(--transition); color: var(--branco); display: inline-block; }
.link-rodape:hover { opacity: 1; transform: translateX(3px); }
.rodape-inferior { border-top: 1px solid rgba(255,255,255,0.15); padding: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.rodape-inferior p { font-size: 0.85rem; opacity: 0.9; color: rgba(255,255,255,0.9); }
.rodape-inferior strong { color: var(--branco); font-weight: 700; }

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 1200px) { .conteudo-rodape { grid-template-columns: 1fr 1fr; gap: 30px; } }
@media (max-width: 992px) { .menu-navegacao { display: none; } .botao-menu-mobile { display: flex; } .tabs-container { padding: 0 20px; } .main-content { padding: 0 20px; } .hero-inscricoes h1, .hero-matriculas h1 { font-size: 2rem; } .tab-btn { padding: 12px 20px; font-size: 1rem; } }
@media (max-width: 768px) { .conteudo-superior { flex-direction: column; gap: 10px; text-align: center; } .conteudo-rodape { grid-template-columns: 1fr; gap: 30px; } .grid-info { grid-template-columns: 1fr; } .timeline, .timeline-simples { padding: 25px; } .passos-simples { flex-direction: column; align-items: center; } }
@media (max-width: 480px) { .sidebar-mobile { width: 280px; } .tab-btn { padding: 10px 15px; font-size: 0.9rem; } .hero-inscricoes h1, .hero-matriculas h1 { font-size: 1.6rem; } }

    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

    <main>
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-btn ativo" data-tab="tab-inscricoes">
                    <i class="fas fa-file-signature"></i> Inscricoes
                </button>
                <button class="tab-btn" data-tab="tab-matriculas">
                    <i class="fas fa-id-card"></i> Matriculas
                </button>
            </div>

            <div id="tab-inscricoes" class="tab-conteudo ativo">
                <div class="hero-inscricoes">
                    <i class="fas fa-file-signature"></i>
                    <h1><?= htmlspecialchars($conteudo['titulo_disponivel'] ?? 'Processo de Inscricao') ?></h1>
                    <p><?= htmlspecialchars($conteudo['msg_abertura'] ?? 'Saiba como fazer parte do Instituto Medio Politecnico Industrial do Kilamba Kiaxi. Siga os passos abaixo e garanta sua vaga!') ?></p>
                </div>

                <div class="grid-info">
                    <div class="card-info inscricao">
                        <h2><i class="fas fa-check-circle"></i> Documentos Necessarios</h2>
                        <ul class="lista-documentos">
                            <?php foreach($documentos as $doc): ?>
                            <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($doc) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="card-info inscricao">
                        <h2><i class="fas fa-school"></i> Onde Entregar?</h2>
                        <ul class="lista-documentos">
                            <li><i class="fas fa-location-dot"></i> <strong>Local:</strong> Instituto Medio Politecnico Industrial do Kilamba Kiaxi</li>
                            <li><i class="fas fa-road"></i> <strong>Endereco:</strong> <?= htmlspecialchars($conteudo['contacto_endereco'] ?? 'Nova Vida, Kilamba Kiaxi, Luanda - Angola') ?></li>
                            <li><i class="fas fa-calendar-alt"></i> <strong>Horario:</strong> <?= htmlspecialchars($conteudo['contacto_horario'] ?? 'Segunda a Sexta, das 8h as 16h') ?></li>
                            <li><i class="fas fa-phone"></i> <strong>Contacto:</strong> <?= htmlspecialchars($conteudo['contacto_telefone'] ?? '+244 933 096 705') ?></li>
                            <li><i class="fas fa-envelope"></i> <strong>Email:</strong> <?= htmlspecialchars($conteudo['contacto_email'] ?? 'inscricoes@ipikk.ao') ?></li>
                        </ul>
                    </div>
                </div>

                <?php if($inscricoes_abertas): ?>
                <div class="card-info vagas" style="margin-bottom: 40px;">
                    <h2><i class="fas fa-graduation-cap"></i> Vagas por Curso - Ano Letivo <?= htmlspecialchars($ano_lectivo) ?></h2>
                    <div class="tabela-vagas">
                        <table>
                            <thead>
                                <tr><th>Curso</th><th>Vagas</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($vagas_curso as $vaga): ?>
                                <tr>
                                    <td><span class="badge-curso <?= $badge_cores[$vaga['curso']] ?? 'badge-construcao' ?>"><?= htmlspecialchars($vaga['curso']) ?></span></td>
                                    <td class="vagas-disponiveis"><?= $vaga['vagas'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="margin-top: 20px; font-size: 0.85rem; color: #666;">
                        <i class="fas fa-clock"></i> As vagas sao preenchidas por ordem de chegada. Garanta a sua vaga o quanto antes!
                    </p>
                </div>
                <?php endif; ?>

                <div class="timeline">
                    <h2><i class="fas fa-steps"></i> Passo a Passo da Inscricao</h2>
                    <div class="passos">
                        <?php foreach($passos_inscricao as $index => $passo): ?>
                        <div class="passo">
                            <div class="numero-passo"><?= $index + 1 ?></div>
                            <h3><?= htmlspecialchars($passo) ?></h3>
                            <p><?php if($index == 0) echo 'Compareca ao Instituto IPIKK com os documentos necessarios.'; 
                                      elseif($index == 1) echo 'Na sala de inscricoes, voce podera escolher o curso pretendido.';
                                      elseif($index == 2) echo 'Entregue toda a documentacao solicitada.';
                                      else echo 'Apos a entrega, aguarde a data do teste de admissao.'; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div id="tab-matriculas" class="tab-conteudo">
                <div class="hero-matriculas">
                    <i class="fas fa-id-card"></i>
                    <h1><?= htmlspecialchars($conteudo['titulo_matricula'] ?? 'Processo de Matricula') ?></h1>
                    <p><?= htmlspecialchars($conteudo['descricao_matricula'] ?? 'Apos ser aprovado no teste de admissao, o processo e simples e rapido!') ?></p>
                </div>

                <div class="timeline-simples">
                    <h2><i class="fas fa-check-circle"></i> Como funciona a Matricula</h2>
                    <div class="passos-simples">
                        <?php foreach($passos_matricula as $index => $passo): ?>
                        <div class="passo-simples">
                            <div class="icone-passo">
                                <i class="fas <?= $index == 0 ? 'fa-camera' : ($index == 1 ? 'fa-id-card' : 'fa-chalkboard-user') ?>"></i>
                            </div>
                            <h3><?= ($index + 1) . '. ' . htmlspecialchars($passo['titulo']) ?></h3>
                            <p><?= htmlspecialchars($passo['descricao']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="secao-cartao">
                    <h2><i class="fas fa-id-card"></i> Cartao de Estudante</h2>
                    <p><?= htmlspecialchars($conteudo['texto_cartao_estudante'] ?? 'O cartao de estudante e o seu documento oficial dentro do IPIKK. Ele sera necessario para:') ?></p>
                    <div class="grid-info" style="margin-bottom: 0;">
                        <?php foreach($cards_matricula as $card): ?>
                        <div class="card-info matricula">
                            <div class="icone-card">
                                <i class="fas <?= $card['icone'] ?>"></i>
                            </div>
                            <h3><?= htmlspecialchars($card['titulo']) ?></h3>
                            <p><?= htmlspecialchars($card['descricao']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cartao-destaque">
                        <i class="fas fa-id-card"></i>
                        <p><?= htmlspecialchars($conteudo['mensagem_cartao_destaque'] ?? 'O cartao e gratuito e sera entregue apos a realizacao da foto na escola.') ?></p>
                    </div>
                </div>

                <div class="secao-teste" style="border-left-color: var(--roxo-matricula); background: var(--roxo-claro);">
                    <h2 style="color: var(--roxo-matricula);"><i class="fas fa-info-circle"></i> Informacoes Importantes</h2>
                    <ul class="lista-documentos" style="list-style: none;">
                        <?php foreach($info_importantes as $info): ?>
                        <li><i class="fas fa-check-circle" style="color: var(--roxo-matricula);"></i> <?= htmlspecialchars($info) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="info-complementar card-info">
            <div class="info-texto">
                <i class="fas fa-question-circle"></i>
                <p><strong>Duvidas sobre o processo de inscricao ou matricula?</strong><br>Entre em contacto com a nossa secretaria para mais informacoes.</p>
            </div>
            <a href="contatos.php" class="botao-contato">
                <i class="fas fa-headset"></i> Fale Conosco
            </a>
        </div>
    </main>

    <div class="botoes-flutuantes">
        <button class="botao-flutuante" id="botaoTopo" title="Voltar ao topo">
            <i class="fas fa-chevron-up"></i>
        </button>
        <?php if($config['whatsapp_numero']): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $config['whatsapp_numero']) ?>" class="botao-flutuante whatsapp" target="_blank" rel="noopener" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="js/header-footer.js"></script>
    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('ativo'));
                document.querySelectorAll('.tab-conteudo').forEach(c => c.classList.remove('ativo'));
                
                btn.classList.add('ativo');
                document.getElementById(tabId).classList.add('ativo');
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

    </script>
</body>
</html>