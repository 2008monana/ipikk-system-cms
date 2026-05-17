<?php
/**
 * Cursos - Area Restrita IPIKK
 */

$titulo_pagina = 'Cursos';
$css_especifico = 'admin-cursos.css';

$base_path = dirname(__DIR__);
require_once $base_path . '/config/index.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: area-restrita.php');
    exit;
}

require_once __DIR__ . '/includes/verificar-permissao.php';

// ===== VERIFICAR PERMISSÃO ESPECÍFICA =====
verificarPermissao('oferta_formativa');

$db = getDB();

$stmt = $db->prepare("SELECT id, nome, email, foto_url FROM utilizadores WHERE id = ?");
$stmt->execute([$_SESSION['utilizador_id']]);
$usuario_logado = $stmt->fetch();

if (!$usuario_logado) {
    session_destroy();
    header('Location: area-restrita.php');
    exit;
}

$config = $db->query("SELECT * FROM configuracoes WHERE id = 1")->fetch();

$stmt = $db->query("
    SELECT id, nome, slug, descricao_curta, descricao_completa,
           icone_classe, cor_primaria, imagem_url, ordem, ativo
    FROM areas
    WHERE ativo = 1
    ORDER BY ordem ASC, nome ASC
");
$areas_db = $stmt->fetchAll();

$stmt = $db->query("
    SELECT c.id, c.area_id, c.nome, c.slug, c.duracao, c.nivel,
           c.estado, c.destaque, c.icone_classe, c.cor,
           c.imagem_hero, c.subtitulo_hero, c.descricao_curta,
           c.descricao_completa, c.sobre_descricao, c.objetivo,
           c.competencias_descricao, c.certificacao_descricao,
           c.programa_pdf_url, c.ordem,
           a.nome AS area_nome, a.cor_primaria AS area_cor, a.icone_classe AS area_icone
    FROM cursos c
    LEFT JOIN areas a ON a.id = c.area_id
    WHERE c.estado != 'arquivado' OR c.estado IS NULL
    ORDER BY c.ordem ASC, c.nome ASC
");
$cursos_db = $stmt->fetchAll();

$planos_por_curso = [];
$stmt = $db->query("
    SELECT curso_id, classe, pdf_url, id as plano_id
    FROM plano_curricular
    ORDER BY curso_id ASC, classe ASC
");
foreach ($stmt->fetchAll() as $plano) {
    $curso_id = (int)$plano['curso_id'];
    if (!isset($planos_por_curso[$curso_id])) {
        $planos_por_curso[$curso_id] = [];
    }
    $planos_por_curso[$curso_id][(string)$plano['classe']] = [
        'url' => $plano['pdf_url'],
        'id' => $plano['plano_id']
    ];
}

$saidas_por_curso = [];
$stmt = $db->query("
    SELECT id, curso_id, titulo, descricao, competencias, imagem_url, ordem
    FROM saidas_profissionais
    ORDER BY curso_id ASC, ordem ASC, id ASC
");
foreach ($stmt->fetchAll() as $saida) {
    $curso_id = (int)$saida['curso_id'];
    if (!isset($saidas_por_curso[$curso_id])) {
        $saidas_por_curso[$curso_id] = [];
    }
    $competencias = [];
    if (!empty($saida['competencias'])) {
        $decodificado = json_decode($saida['competencias'], true);
        if (is_array($decodificado)) {
            $competencias = $decodificado;
        } else {
            $competencias = array_values(array_filter(array_map('trim', explode(',', $saida['competencias']))));
        }
    }
    $saidas_por_curso[$curso_id][] = [
        'id' => (int)$saida['id'],
        'titulo' => $saida['titulo'],
        'descricao' => $saida['descricao'],
        'competencias' => $competencias,
        'imagem_url' => $saida['imagem_url'],
        'ordem' => (int)$saida['ordem']
    ];
}

$projetos_por_curso = [];
$stmt = $db->query("
    SELECT id, curso_id, titulo, categoria, ano, autor, descricao, imagem_url, ordem
    FROM projetos
    ORDER BY curso_id ASC, ordem ASC, id ASC
");
foreach ($stmt->fetchAll() as $projeto) {
    $curso_id = (int)$projeto['curso_id'];
    if (!isset($projetos_por_curso[$curso_id])) {
        $projetos_por_curso[$curso_id] = [];
    }
    $projetos_por_curso[$curso_id][] = [
        'id' => (int)$projeto['id'],
        'titulo' => $projeto['titulo'],
        'categoria' => $projeto['categoria'],
        'ano' => $projeto['ano'],
        'autor' => $projeto['autor'],
        'descricao' => $projeto['descricao'],
        'imagem_url' => $projeto['imagem_url'],
        'ordem' => (int)$projeto['ordem']
    ];
}

foreach ($cursos_db as &$curso) {
    $curso_id = (int)$curso['id'];
    $curso['planos'] = $planos_por_curso[$curso_id] ?? [];
    $curso['saidas'] = $saidas_por_curso[$curso_id] ?? [];
    $curso['projetos'] = $projetos_por_curso[$curso_id] ?? [];
}
unset($curso);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Restrita - Cursos</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="../area-publica/foto/ipikk_new_logo.png" rel="icon">
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    <link rel="stylesheet" href="css/admin-cursos.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --azul-primario: #003072;
            --verde-acento: #0a9396;
            --cinza-claro: #f5f7fa;
            --cinza-medio: #e0e4e8;
            --cinza-escuro: #2c3e50;
            --cor-texto: #333;
            --branco: #fff;
            --sucesso: #28a745;
            --aviso: #ffc107;
            --perigo: #dc3545;
            --info: #17a2b8;
            --sombra: 0 2px 10px rgba(0,0,0,0.1);
            --sombra-forte: 0 5px 20px rgba(0,0,0,0.15);
            --transicao: all 0.3s ease;
            --borda-arredondada: 8px;
            --largura-sidebar: 280px;
            --altura-topo: 70px;
        }
        .conteudo-principal { margin-left: var(--largura-sidebar); min-height: 100vh; }
        .barra-topo { height: var(--altura-topo); background: var(--branco); box-shadow: var(--sombra); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; position: sticky; top: 0; z-index: 999; }
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
        .barra-topo h1 { font-size: 24px; margin: 0; }
        .area-direita-topo { display: flex; align-items: center; gap: 20px; }
        .btn-primario { display: flex; align-items: center; gap: 8px; padding: 12px 25px; background: linear-gradient(135deg, var(--verde-acento), var(--azul-primario)); color: var(--branco); border: none; border-radius: var(--borda-arredondada); font-weight: 600; cursor: pointer; transition: var(--transicao); box-shadow: var(--sombra); }
        .btn-primario:hover { transform: translateY(-2px); box-shadow: var(--sombra-forte); }
        .wrapper-conteudo { padding: 30px; }
        .secao-conteudo { background: var(--branco); border-radius: var(--borda-arredondada); padding: 25px; margin-bottom: 25px; box-shadow: var(--sombra); }
        .titulo-secao { font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .cabecalho-secao { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        .grade-areas { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 10px; }
        .card-area { background: var(--branco); border-radius: var(--borda-arredondada); padding: 18px; display: flex; align-items: center; gap: 12px; cursor: default; transition: var(--transicao); border: 2px solid var(--cinza-medio); position: relative; overflow: hidden; }
        .card-area:hover { transform: translateY(-3px); box-shadow: var(--sombra-forte); border-color: var(--verde-acento); }
        .card-area.ativo { border-color: var(--verde-acento); background: rgba(10,147,150,0.05); }
        .barra-cor-area { width: 6px; height: 50px; border-radius: 3px; position: absolute; left: 0; top: 50%; transform: translateY(-50%); }
        .icone-area { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--branco); flex-shrink: 0; margin-left: 10px; }
        .info-area { flex: 1; cursor: pointer; }
        .info-area h4 { font-size: 16px; margin-bottom: 3px; color: var(--azul-primario); }
        .info-area p { font-size: 12px; color: var(--cinza-escuro); margin: 0; line-height: 1.4; }
        .info-area .contador-cursos { display: block; font-size: 11px; color: var(--verde-acento); margin-top: 5px; font-weight: 500; }
        .acoes-area { display: flex; flex-direction: column; gap: 6px; margin-left: 10px; opacity: 0.6; transition: var(--transicao); }
        .card-area:hover .acoes-area { opacity: 1; }
        .btn-icone-area { width: 32px; height: 32px; border-radius: 8px; background: var(--cinza-claro); border: 1px solid var(--cinza-medio); color: var(--azul-primario); cursor: pointer; transition: var(--transicao); display: flex; align-items: center; justify-content: center; }
        .btn-icone-area:hover { background: var(--verde-acento); border-color: var(--verde-acento); color: white; transform: scale(1.05); }
        .btn-icone-area.btn-perigo:hover { background: var(--perigo); border-color: var(--perigo); color: white; }

        .grade-estatisticas { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 20px; }
        .item-estatistica { padding: 20px; background: var(--cinza-claro); border-radius: var(--borda-arredondada); border-left: 4px solid var(--verde-acento); display: flex; align-items: center; gap: 15px; }
        .item-estatistica i { font-size: 32px; color: var(--azul-primario); }
        .info-estatistica h3 { font-size: 14px; color: var(--cinza-escuro); margin-bottom: 5px; }
        .info-estatistica p { font-size: 24px; font-weight: 700; color: var(--azul-primario); margin: 0; }

        .grade-filtros { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; }
        .item-filtro.largura-total { grid-column: 1 / -1; }
        .caixa-busca { position: relative; }
        .caixa-busca i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--cinza-escuro); }
        .caixa-busca input { width: 100%; padding: 14px 20px 14px 45px; border: 2px solid var(--cinza-medio); border-radius: var(--borda-arredondada); font-size: 15px; }
        .selecao-form { width: 100%; padding: 14px 15px; border: 2px solid var(--cinza-medio); border-radius: var(--borda-arredondada); font-size: 14px; }

        .checkbox-curso-wrapper {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            background: rgba(255,255,255,0.92);
            padding: 5px 6px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }
        .checkbox-curso { width: 18px; height: 18px; cursor: pointer; accent-color: var(--verde-acento); display: block; }
        .card-curso.selecionado { border-color: var(--verde-acento); box-shadow: 0 0 0 2px var(--verde-acento); }

        .grade-cursos { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px; }
        .card-curso { background: var(--branco); border: 2px solid var(--cinza-medio); border-radius: var(--borda-arredondada); overflow: hidden; transition: var(--transicao); position: relative; }
        .card-curso:hover { transform: translateY(-3px); box-shadow: var(--sombra-forte); border-color: var(--verde-acento); }
        .barra-cor-curso { height: 8px; background: var(--azul-primario); }
        .conteudo-curso { padding: 20px; }
        .cabecalho-curso { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .icone-curso { width: 60px; height: 60px; border-radius: 50%; background: var(--cinza-claro); display: flex; align-items: center; justify-content: center; font-size: 30px; color: var(--azul-primario); border: 2px solid var(--cinza-medio); }
        .info-curso h3 { font-size: 18px; margin: 0 0 5px 0; }
        .info-curso p { color: var(--cinza-escuro); font-size: 13px; margin: 0; display: flex; align-items: center; gap: 5px; }
        .descricao-curso { font-size: 14px; color: #666; margin-bottom: 15px; line-height: 1.5; }
        .metadados-curso { display: flex; gap: 15px; margin-bottom: 15px; }
        .item-metadado { display: flex; align-items: center; gap: 5px; font-size: 13px; color: var(--cinza-escuro); }
        .rodape-curso { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 2px solid var(--cinza-medio); }
        .badge-curso { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-ativo { background: rgba(40,167,69,0.1); color: var(--sucesso); }
        .badge-pausado { background: rgba(255,193,7,0.1); color: #d39e00; }
        .badge-arquivado { background: rgba(108,117,125,0.1); color: #6c757d; }
        .acoes-curso { display: flex; gap: 8px; }
        .btn-icone { width: 36px; height: 36px; border-radius: 50%; background: var(--cinza-claro); border: 2px solid var(--cinza-medio); color: var(--cinza-escuro); cursor: pointer; transition: var(--transicao); display: flex; align-items: center; justify-content: center; }
        .btn-icone:hover { background: var(--verde-acento); border-color: var(--verde-acento); color: var(--branco); transform: scale(1.1); }

        .acoes-rapidas { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .btn-acao { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--cinza-claro); border: 2px solid var(--cinza-medio); border-radius: var(--borda-arredondada); cursor: pointer; transition: var(--transicao); font-size: 13px; font-weight: 500; }
        .btn-acao:hover:not(:disabled) { background: var(--azul-primario); border-color: var(--azul-primario); color: var(--branco); }
        .btn-acao:disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,30,70,0.75); backdrop-filter: blur(6px); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
        .conteudo-modal { background: var(--branco); border-radius: 16px; max-width: 1000px; width: 100%; max-height: 92vh; overflow-y: auto; animation: slideInModal 0.35s cubic-bezier(0.34,1.56,0.64,1); box-shadow: 0 25px 60px rgba(0,30,70,0.35); }
        @keyframes slideInModal { from { opacity: 0; transform: translateY(-60px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .cabecalho-modal { background: linear-gradient(135deg, var(--azul-primario) 0%, #0a4da8 50%, var(--verde-acento) 100%); padding: 28px 30px; border-radius: 16px 16px 0 0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .esquerda-cabecalho { display: flex; align-items: center; gap: 14px; }
        .icone-cabecalho { width: 52px; height: 52px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .texto-cabecalho h2 { color: var(--branco); font-size: 22px; margin: 0 0 2px 0; }
        .texto-cabecalho p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 0; }
        .btn-fechar { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: var(--branco); font-size: 20px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; transition: var(--transicao); display: flex; align-items: center; justify-content: center; }
        .btn-fechar:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
        .corpo-modal { padding: 30px; }

        .abas-modal { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid var(--cinza-medio); padding-bottom: 10px; overflow-x: auto; }
        .aba-modal { padding: 10px 20px; background: var(--cinza-claro); border: 2px solid var(--cinza-medio); border-radius: var(--borda-arredondada) var(--borda-arredondada) 0 0; border-bottom: none; cursor: pointer; font-weight: 600; font-size: 14px; transition: var(--transicao); white-space: nowrap; }
        .aba-modal:hover { background: var(--cinza-medio); }
        .aba-modal.ativo { background: var(--azul-primario); border-color: var(--azul-primario); color: var(--branco); }
        .conteudo-aba { display: none; }
        .conteudo-aba.ativo { display: block; }

        .secao-form { background: var(--cinza-claro); border-radius: 12px; padding: 22px 25px; margin-bottom: 20px; border-left: 5px solid var(--verde-acento); }
        .secao-form h3 { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 700; color: var(--azul-primario); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--cinza-medio); }
        .linha-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px; }
        .grupo-form { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; }
        .grupo-form label { font-size: 12px; font-weight: 700; color: var(--cinza-escuro); text-transform: uppercase; letter-spacing: 0.5px; }
        .controle-form { padding: 12px 14px; border: 2px solid var(--cinza-medio); border-radius: 8px; font-size: 14px; font-family: inherit; transition: var(--transicao); background: var(--branco); width: 100%; }
        .controle-form:focus { outline: none; border-color: var(--verde-acento); box-shadow: 0 0 0 4px rgba(10,147,150,0.12); }
        textarea.controle-form { resize: vertical; min-height: 100px; }

        .sugestoes-icones { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .sugestao-icone { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: var(--cinza-claro); border: 1px solid var(--cinza-medio); border-radius: 20px; font-size: 12px; cursor: pointer; transition: var(--transicao); }
        .sugestao-icone:hover { background: var(--verde-acento); border-color: var(--verde-acento); color: white; transform: translateY(-2px); }
        .icone-input-wrapper { display: flex; align-items: center; gap: 12px; }
        .icone-preview { width: 48px; height: 48px; background: var(--cinza-claro); border: 2px solid var(--cinza-medio); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--azul-primario); flex-shrink: 0; }
        .grupo-seletor-cor { display: flex; align-items: center; gap: 12px; }
        .preview-cor { width: 48px; height: 48px; border-radius: 12px; border: 2px solid var(--cinza-medio); }
        .area-upload-arquivo { border: 2px dashed var(--cinza-medio); border-radius: var(--borda-arredondada); padding: 20px; text-align: center; background: var(--branco); cursor: pointer; transition: var(--transicao); }
        .area-upload-arquivo:hover { border-color: var(--verde-acento); background: var(--cinza-claro); }
        .preview-arquivo { margin-top: 15px; padding: 15px; background: var(--branco); border-radius: var(--borda-arredondada); border: 1px solid var(--cinza-medio); display: none; align-items: center; justify-content: space-between; }
        .preview-arquivo.ativo { display: flex; }
        .preview-arquivo img { max-width: 100px; max-height: 100px; border-radius: var(--borda-arredondada); }
        .wrapper-toggle { display: flex; align-items: center; gap: 10px; }
        .toggle-switch { position: relative; width: 44px; height: 24px; display: inline-block; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: var(--transicao); border-radius: 24px; }
        .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: var(--transicao); border-radius: 50%; }
        input:checked + .toggle-slider { background-color: var(--verde-acento); }
        input:checked + .toggle-slider:before { transform: translateX(20px); }
        .acoes-form { display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 2px solid var(--cinza-medio); margin-top: 20px; }
        .btn-cancelar { background: var(--branco); color: var(--cinza-escuro); border: 2px solid var(--cinza-medio); padding: 14px 28px; border-radius: var(--borda-arredondada); font-weight: 600; cursor: pointer; }
        .btn-salvar { background: linear-gradient(135deg, var(--sucesso), #218838); color: var(--branco); padding: 14px 28px; border-radius: var(--borda-arredondada); font-weight: 600; cursor: pointer; border: none; }
        .notificacao { position: fixed; top: 20px; right: 20px; padding: 16px 28px; background: linear-gradient(135deg, var(--sucesso), #218838); color: #fff; border-radius: 12px; z-index: 99999; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .sem-resultados { text-align: center; padding: 40px; color: #666; }
        @media (max-width: 768px) { .conteudo-principal { margin-left: 0; } .grade-areas, .grade-cursos, .grade-estatisticas { grid-template-columns: 1fr; } .linha-form { grid-template-columns: 1fr; } }

        .grade-icones { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .opcao-icone { width: 45px; height: 45px; border: 2px solid var(--cinza-medio); border-radius: 8px; background: white; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; }
        .opcao-icone.selecionado { background: var(--verde-acento); color: white; border-color: var(--verde-acento); }
        .item-dinamico { border: 1px solid var(--cinza-medio); border-radius: 8px; padding: 15px; margin-bottom: 15px; position: relative; }
        .item-dinamico .btn-remover { position: absolute; top: 10px; right: 10px; width: 30px; height: 30px; border-radius: 50%; background: var(--perigo); color: white; border: none; cursor: pointer; }
        .btn-adicionar { display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: var(--branco); border: 2px dashed var(--verde-acento); border-radius: var(--borda-arredondada); color: var(--verde-acento); font-weight: 600; cursor: pointer; width: 100%; justify-content: center; margin-top: 10px; }

        .preview-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--cinza-medio); padding-bottom: 10px; }
        .preview-tab { padding: 8px 20px; background: var(--cinza-claro); border: none; border-radius: 20px; cursor: pointer; font-weight: 600; transition: var(--transicao); }
        .preview-tab.ativo { background: var(--azul-primario); color: white; }
        .preview-tab:hover { background: var(--verde-acento); color: white; }
        .preview-conteudo { display: none; }
        .preview-conteudo.ativo { display: block; }

        #modalPreviewArea .conteudo-modal,
        #modalPreviewCurso .conteudo-modal { max-width: 900px !important; width: 90% !important; max-height: 85vh !important; }
        #modalPreviewArea .corpo-modal,
        #modalPreviewCurso .corpo-modal { max-height: calc(85vh - 80px); overflow-y: auto; padding: 20px; }
        .preview-conteudo { max-height: 70vh; overflow-y: auto; }

        .gradiente-obras { background: linear-gradient(to bottom, rgba(42, 46, 51, 0.25) 0%, rgba(36, 37, 39, 0.78) 100%); }
        .gradiente-desenhador { background: linear-gradient(to bottom, rgba(180, 110, 0, 0.30) 0%, rgba(140, 80, 0, 0.78) 100%); }
        .gradiente-eletricidade { background: linear-gradient(135deg, rgba(15, 23, 42, 0.8), rgba(46, 134, 193, 0.6)); }
        .gradiente-mecanica { background: linear-gradient(to bottom, rgba(224, 123, 42, 0.3), rgba(184, 94, 26, 0.9)); }
        .gradiente-gestao { background: linear-gradient(to bottom, rgba(46, 134, 193, 0.3), rgba(26, 90, 140, 0.9)); }
        .gradiente-informatica { background: linear-gradient(to bottom, rgba(45, 122, 58, 0.3), rgba(30, 85, 39, 0.9)); }
        .gradiente-moveis { background: linear-gradient(to bottom, rgba(192, 57, 43, 0.3), rgba(150, 45, 34, 0.9)); }

        #modalIframeCurso .conteudo-modal { max-width: 1200px; width: 95%; max-height: 90vh; }
        #modalIframeCurso .corpo-modal { padding: 0; height: calc(90vh - 80px); }
        #modalIframeCurso iframe { width: 100%; height: 100%; border: none; border-radius: 0 0 16px 16px; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="conteudo-principal">
    <header class="barra-topo">
        <button class="botao-menu-mobile" id="botaoMenuMobile" onclick="window.openSidebar && window.openSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>Cursos</h1>
        <div class="area-direita-topo">
            <button class="btn-primario" id="btnNovoCurso"><i class="fas fa-plus"></i><span>Novo Curso</span></button>
        </div>
    </header>

    <div class="wrapper-conteudo">
        <section class="secao-conteudo">
            <div class="cabecalho-secao">
                <h2 class="titulo-secao"><i class="fas fa-layer-group"></i> Areas de Formacao</h2>
                <button class="btn-primario" onclick="abrirModalArea()" style="padding: 8px 15px;"><i class="fas fa-plus"></i> Nova Area</button>
            </div>
            <div class="grade-areas" id="containerAreas"></div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-chart-pie"></i> Visao Geral</h2>
            <div class="grade-estatisticas">
                <div class="item-estatistica"><i class="fas fa-book-open"></i><div class="info-estatistica"><h3>Total de Cursos</h3><p id="estatisticaTotal"><?= count($cursos_db) ?></p></div></div>
                <div class="item-estatistica"><i class="fas fa-check-circle"></i><div class="info-estatistica"><h3>Ativos</h3><p id="estatisticaAtivos"><?= count(array_filter($cursos_db, fn($c) => $c['estado'] === 'ativo')) ?></p></div></div>
                <div class="item-estatistica"><i class="fas fa-pause-circle"></i><div class="info-estatistica"><h3>Pausados</h3><p id="estatisticaPausados"><?= count(array_filter($cursos_db, fn($c) => $c['estado'] === 'pausado')) ?></p></div></div>
                <div class="item-estatistica"><i class="fas fa-archive"></i><div class="info-estatistica"><h3>Arquivados</h3><p id="estatisticaArquivados"><?= count(array_filter($cursos_db, fn($c) => $c['estado'] === 'arquivado')) ?></p></div></div>
                <div class="item-estatistica"><i class="fas fa-star"></i><div class="info-estatistica"><h3>Em Destaque</h3><p id="estatisticaDestaques"><?= count(array_filter($cursos_db, fn($c) => $c['destaque'] == 1)) ?></p></div></div>
            </div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-filter"></i> Filtros</h2>
            <div class="grade-filtros">
                <div class="item-filtro largura-total"><div class="caixa-busca"><i class="fas fa-search"></i><input type="text" id="campoBusca" placeholder="Buscar curso por nome ou descricao..."></div></div>
                <div class="item-filtro"><label>Area</label><select class="selecao-form" id="filtroArea"><option value="">Todas</option><?php foreach ($areas_db as $area): ?><option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nome']) ?></option><?php endforeach; ?></select></div>
                <div class="item-filtro"><label>Estado</label><select class="selecao-form" id="filtroEstado"><option value="">Todos</option><option value="ativo">Ativos</option><option value="pausado">Pausados</option><option value="arquivado">Arquivados</option></select></div>
            </div>
        </section>

        <section class="secao-conteudo">
            <div class="cabecalho-secao"><h2 class="titulo-secao"><i class="fas fa-list"></i> Cursos Disponiveis <span id="contadorCursos" style="color: var(--verde-acento);">(<?= count($cursos_db) ?> cursos)</span></h2></div>
            <div class="grade-cursos" id="containerCursos"></div>
        </section>

        <section class="secao-conteudo">
            <h2 class="titulo-secao"><i class="fas fa-bolt"></i> Acoes Rapidas</h2>
            <div class="acoes-rapidas">
                <button class="btn-acao" id="btnSelecionarTodosCursos"><i class="fas fa-check-square"></i> Selecionar Todos</button>
                <button class="btn-acao" id="btnEliminarSelecionadosCursos" disabled><i class="fas fa-trash-alt"></i> Eliminar Selecionados</button>
            </div>
        </section>
    </div>
</main>

<!-- MODAL AREA -->
<div id="modalArea" class="modal">
    <div class="conteudo-modal" style="max-width: 750px;">
        <div class="cabecalho-modal">
            <div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-layer-group"></i></div><div class="texto-cabecalho"><h2 id="tituloModalArea">Nova Area</h2><p id="subtituloModalArea">Preencha os dados da area de formacao</p></div></div>
            <button class="btn-fechar" onclick="fecharModalArea()"><i class="fas fa-times"></i></button>
        </div>
        <div class="corpo-modal">
            <form id="formularioArea" onsubmit="return salvarArea(event)">
                <input type="hidden" id="areaId" name="area_id">
                <div class="secao-form">
                    <h3><i class="fas fa-info-circle"></i> Informacoes Basicas</h3>
                    <div class="grupo-form"><label>Nome da Area *</label><input type="text" id="areaNome" class="controle-form" required placeholder="Ex: Construcao Civil"></div>
                    <div class="grupo-form"><label>Descricao Curta</label><textarea id="areaDescricaoCurta" class="controle-form" rows="2" placeholder="Breve descricao que aparece nos cards"></textarea></div>
                    <div class="grupo-form"><label>Descricao Completa</label><textarea id="areaDescricaoCompleta" class="controle-form" rows="4" placeholder="Descricao detalhada que aparece na pagina da area"></textarea></div>
                </div>
                <div class="secao-form">
                    <h3><i class="fas fa-palette"></i> Aparencia</h3>
                    <div class="linha-form"><div class="grupo-form"><label>Cor Primaria</label><div class="grupo-seletor-cor"><input type="color" id="areaCor" class="controle-form" value="#6c757d"><div class="preview-cor" id="previewCor" style="background: #6c757d;"></div></div></div><div class="grupo-form"><label>Icone (Font Awesome)</label><div class="icone-input-wrapper"><div class="icone-preview" id="iconePreview"><i class="fas fa-layer-group" id="iconePreviewIcon"></i></div><input type="text" id="areaIcone" class="controle-form" placeholder="Ex: fa-helmet-safety, fa-bolt" value="fa-layer-group"></div></div></div>
                    <div class="sugestoes-icones"><button type="button" class="sugestao-icone" data-icone="fa-helmet-safety"><i class="fas fa-helmet-safety"></i> Capacete</button><button type="button" class="sugestao-icone" data-icone="fa-bolt"><i class="fas fa-bolt"></i> Raio</button><button type="button" class="sugestao-icone" data-icone="fa-gear"><i class="fas fa-gear"></i> Engrenagem</button><button type="button" class="sugestao-icone" data-icone="fa-laptop-code"><i class="fas fa-laptop-code"></i> Computador</button><button type="button" class="sugestao-icone" data-icone="fa-couch"><i class="fas fa-couch"></i> Sofa</button><button type="button" class="sugestao-icone" data-icone="fa-graduation-cap"><i class="fas fa-graduation-cap"></i> Capelo</button><button type="button" class="sugestao-icone" data-icone="fa-tools"><i class="fas fa-tools"></i> Ferramentas</button><button type="button" class="sugestao-icone" data-icone="fa-wrench"><i class="fas fa-wrench"></i> Chave</button><button type="button" class="sugestao-icone" data-icone="fa-hammer"><i class="fas fa-hammer"></i> Martelo</button></div>
                    <div class="grupo-form" style="margin-top: 20px;"><label>Imagem da Area (Capa)</label><div class="area-upload-arquivo" onclick="document.getElementById('areaImagemInput').click()"><i class="fas fa-cloud-upload-alt"></i><p><strong>Clique para fazer upload</strong></p><small>JPG, PNG | 1200x800px</small></div><input type="file" id="areaImagemInput" accept="image/*" style="display: none;"><div class="preview-arquivo" id="previewAreaImagem"><div><img id="miniaturaAreaImagem" style="max-width: 100px;"></div><button type="button" class="btn-icone" onclick="removerAreaImagem()"><i class="fas fa-trash"></i></button></div></div>
                </div>
                <div class="secao-form">
                    <h3><i class="fas fa-cog"></i> Configuracoes</h3>
                    <div class="linha-form"><div class="grupo-form"><label>Ordem de Exibicao</label><input type="number" id="areaOrdem" class="controle-form" value="0" min="0"></div><div class="grupo-form"><label>Status</label><div class="wrapper-toggle"><label class="toggle-switch"><input type="checkbox" id="areaAtivo" checked><span class="toggle-slider"></span></label><span>Ativo (visivel no site)</span></div></div></div>
                </div>
                <div class="acoes-form"><button type="button" class="btn-cancelar" onclick="fecharModalArea()">Cancelar</button><button type="submit" class="btn-salvar"><i class="fas fa-save"></i> Salvar Area</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW AREA -->
<div id="modalPreviewArea" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal"><div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-eye"></i></div><div class="texto-cabecalho"><h2>Pre-visualizacao da Area</h2><p>Veja como ficara no site publico</p></div></div><button class="btn-fechar" onclick="fecharModalPreview()"><i class="fas fa-times"></i></button></div>
        <div class="corpo-modal">
            <div class="preview-tabs"><button class="preview-tab ativo" onclick="mudarPreviewAreaTab('card')">Card na Oferta Formativa</button><button class="preview-tab" onclick="mudarPreviewAreaTab('pagina')">Pagina da Area</button></div>
            <div id="previewCardArea" class="preview-conteudo ativo"></div>
            <div id="previewPaginaArea" class="preview-conteudo"></div>
        </div>
        <div class="acoes-form" style="margin-top:0; border-top:none;"><button type="button" class="btn-cancelar" onclick="fecharModalPreview()">Fechar</button></div>
    </div>
</div>

<!-- MODAL PREVIEW CURSO -->
<div id="modalPreviewCurso" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal"><div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-eye"></i></div><div class="texto-cabecalho"><h2>Pre-visualizacao do Curso</h2><p>Veja como ficara na pagina publica</p></div></div><button class="btn-fechar" onclick="fecharModalPreviewCurso()"><i class="fas fa-times"></i></button></div>
        <div class="corpo-modal" id="previewCursoContent"></div>
        <div class="acoes-form" style="margin-top:0; border-top:none;"><button type="button" class="btn-cancelar" onclick="fecharModalPreviewCurso()">Fechar</button></div>
    </div>
</div>

<!-- MODAL IFRAME CURSO -->
<div id="modalIframeCurso" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal"><div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-eye"></i></div><div class="texto-cabecalho"><h2 id="iframeCursoTitulo">Visualizar Curso</h2><p id="iframeCursoSubtitulo">Pre-visualizacao em tempo real</p></div></div><button class="btn-fechar" onclick="fecharModalIframe()"><i class="fas fa-times"></i></button></div>
        <div class="corpo-modal"><iframe id="iframeCurso" src="about:blank" title="Pre-visualizacao do curso"></iframe></div>
        <div class="acoes-form" style="margin-top:0; border-top:none; padding: 15px; justify-content: center;"><button type="button" class="btn-cancelar" onclick="fecharModalIframe()">Fechar</button><button type="button" class="btn-salvar" id="btnAbrirNovaAba" onclick="abrirCursoNovaAba()"><i class="fas fa-external-link-alt"></i> Abrir em nova aba</button></div>
    </div>
</div>


<!-- MODAL CURSO (PRINCIPAL) -->
<div id="modalCurso" class="modal">
    <div class="conteudo-modal">
        <div class="cabecalho-modal">
            <div class="esquerda-cabecalho"><div class="icone-cabecalho"><i class="fas fa-graduation-cap"></i></div><div class="texto-cabecalho"><h2 id="tituloModalCurso">Novo Curso</h2><p id="subtituloModalCurso">Preencha os dados do curso</p></div></div>
            <button class="btn-fechar" onclick="fecharModalCurso()"><i class="fas fa-times"></i></button>
        </div>

        <div class="corpo-modal">
            <div class="abas-modal">
                <div class="aba-modal ativo" data-aba="basico">Informacoes Basicas</div>
                <div class="aba-modal" data-aba="detalhes">Detalhes</div>
                <div class="aba-modal" data-aba="curricular">Plano Curricular</div>
                <div class="aba-modal" data-aba="saidas">Saidas</div>
                <div class="aba-modal" data-aba="projectos">Projectos</div>
                <div class="aba-modal" data-aba="config">Configuracoes</div>
            </div>

            <form id="formularioCurso" onsubmit="return salvarCurso(event)">
                <input type="hidden" id="cursoId">

                <div class="conteudo-aba ativo" data-aba="basico">
                    <div class="secao-form">
                        <h3><i class="fas fa-info-circle"></i> Informacoes Basicas</h3>
                        <div class="grupo-form">
                            <label>Nome do Curso *</label>
                            <input type="text" id="cursoNome" class="controle-form" required>
                        </div>
                        <div class="linha-form">
                            <div class="grupo-form">
                                <label>Area *</label>
                                <select id="cursoAreaId" class="controle-form" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($areas_db as $area): ?>
                                    <option value="<?= $area['id'] ?>"><?= htmlspecialchars($area['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grupo-form">
                                <label>Duracao *</label>
                                <input type="text" id="cursoDuracao" class="controle-form" required placeholder="Ex: 4 anos">
                            </div>
                        </div>
                        <div class="grupo-form">
                            <label>Descricao Curta *</label>
                            <textarea id="cursoDescricaoCurta" class="controle-form" rows="2" maxlength="200" required></textarea>
                            <div class="contador-caracteres" id="contadorDescricaoCurta">0 / 200</div>
                        </div>
                        <div class="grupo-form">
                            <label><i class="fas fa-align-left"></i> Sobre o Curso </label>
                            <textarea id="cursoSobreDescricao" class="controle-form" rows="2" placeholder="Tudo sobre o curso"></textarea>
                        </div>
                        <div class="grupo-form">
                            <label><i class="fas fa-list-check"></i> Competências em Destaque (Card)</label>
                            <textarea id="cursoCompetenciasCard" class="controle-form" rows="4" placeholder="Uma competência por linha&#10;Ex: Formação técnica especializada"></textarea>
                            <small style="color:#6c757d;">Estas linhas serão exibidas nos cards dos cursos da página da área.</small>
                        </div>
                    </div>
                </div>

                <div class="conteudo-aba" data-aba="detalhes">
                    <div class="secao-form">
                        <h3><i class="fas fa-book-open"></i> Detalhes do Curso</h3>
                        <div class="grupo-form"><label>Objetivo</label><textarea id="cursoObjetivo" class="controle-form" rows="3"></textarea></div>
                        <div class="grupo-form"><label>Competencias</label><textarea id="cursoCompetencias" class="controle-form" rows="3"></textarea></div>
                        <div class="grupo-form"><label>Certificacao</label><input type="text" id="cursoCertificacao" class="controle-form"></div>
                    </div>
                </div>

                <!-- ===== PLANO CURRICULAR (COM RESUMO GERAL - CLASSE 0 E BOTÃO REMOVER) ===== -->
                <div class="conteudo-aba" data-aba="curricular">
                    <div class="secao-form">
                        <h3><i class="fas fa-calendar-alt"></i> Plano Curricular</h3>
                        <p style="margin-bottom: 20px; font-size: 13px;">Faça o upload do PDF do plano curricular para cada classe (10ª a 13ª) ou para o resumo geral.</p>

                        <!-- ===== PDF RESUMO GERAL (CLASSE 0) ===== -->
                        <div class="grupo-form" style="margin-bottom: 25px; padding: 20px; border: 1px solid var(--cinza-medio); border-radius: 12px; background: #f8fafc;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <label style="font-size: 16px;"><i class="fas fa-file-pdf" style="color: #28a745;"></i> Plano Curricular - Resumo Geral</label>
                                <button type="button" class="btn-remover-pdf" data-classe="0" style="background: #dc3545; color: white; border: none; border-radius: 8px; padding: 5px 12px; cursor: pointer; display: none;">
                                    <i class="fas fa-trash"></i> Remover PDF
                                </button>
                            </div>
                            <p style="font-size: 13px; margin-bottom: 10px;">Este PDF será exibido na aba "Geral" da página do curso, contendo a visão geral de todas as classes.</p>
                            <div class="area-upload-arquivo" onclick="document.getElementById('pdf0Input').click()" style="margin-top: 10px;">
                                <i class="fas fa-file-pdf" style="font-size: 30px; color: #28a745;"></i>
                                <p><strong>Clique para fazer upload do PDF Resumo</strong></p>
                                <small>Formato: PDF | Tamanho máximo: 10MB</small>
                            </div>
                            <input type="file" id="pdf0Input" accept=".pdf" style="display: none;" data-classe="0">
                            <div class="preview-arquivo" id="pdf0Preview" style="margin-top: 10px; display: none;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-file-pdf" style="font-size: 24px; color: #28a745;"></i>
                                    <span id="pdf0Nome"></span>
                                    <span id="pdf0Existente" style="color: #28a745; font-size: 12px;"></span>
                                </div>
                            </div>
                        </div>

                        <!-- PDF para cada classe (10, 11, 12, 13) -->
                        <?php for ($classe = 10; $classe <= 13; $classe++): ?>
                        <div class="grupo-form" style="margin-bottom: 25px; padding: 20px; border: 1px solid var(--cinza-medio); border-radius: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <label style="font-size: 16px;"><i class="fas fa-file-pdf" style="color: #dc3545;"></i> Plano Curricular - <?= $classe ?>ª Classe</label>
                                <button type="button" class="btn-remover-pdf" data-classe="<?= $classe ?>" style="background: #dc3545; color: white; border: none; border-radius: 8px; padding: 5px 12px; cursor: pointer; display: none;">
                                    <i class="fas fa-trash"></i> Remover PDF
                                </button>
                            </div>
                            <div class="area-upload-arquivo" onclick="document.getElementById('pdf<?= $classe ?>Input').click()" style="margin-top: 10px;">
                                <i class="fas fa-file-pdf" style="font-size: 30px; color: #dc3545;"></i>
                                <p><strong>Clique para fazer upload do PDF</strong></p>
                                <small>Formato: PDF | Tamanho máximo: 10MB</small>
                            </div>
                            <input type="file" id="pdf<?= $classe ?>Input" accept=".pdf" style="display: none;" data-classe="<?= $classe ?>">
                            <div class="preview-arquivo" id="pdf<?= $classe ?>Preview" style="margin-top: 10px; display: none;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-file-pdf" style="font-size: 24px; color: #dc3545;"></i>
                                    <span id="pdf<?= $classe ?>Nome"></span>
                                    <span id="pdf<?= $classe ?>Existente" style="color: #28a745; font-size: 12px;"></span>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="conteudo-aba" data-aba="saidas">
                    <div class="secao-form">
                        <h3><i class="fas fa-briefcase"></i> Saidas Profissionais</h3>
                        <div id="containerSaidas"></div>
                        <button type="button" class="btn-adicionar" onclick="adicionarSaida()"><i class="fas fa-plus"></i> Adicionar Saida Profissional</button>
                    </div>
                </div>

                <div class="conteudo-aba" data-aba="projectos">
                    <div class="secao-form">
                        <h3><i class="fas fa-project-diagram"></i> Projectos Realizados</h3>
                        <div id="containerProjectos"></div>
                        <button type="button" class="btn-adicionar" onclick="adicionarProjecto()"><i class="fas fa-plus"></i> Adicionar Projecto</button>
                    </div>
                </div>

                <div class="conteudo-aba" data-aba="config">
                    <div class="secao-form">
                        <h3><i class="fas fa-cog"></i> Configuracoes</h3>
                        <div class="linha-form"><div class="grupo-form"><label>Estado</label><select id="cursoEstado" class="controle-form"><option value="ativo">Ativo</option><option value="pausado">Pausado</option><option value="arquivado">Arquivado</option></select></div><div class="grupo-form"><label>Cor</label><input type="color" id="cursoCor" value="#003072" class="controle-form"></div></div>
                        <div class="linha-form"><div class="grupo-form"><label>Destaque</label><div class="wrapper-toggle"><label class="toggle-switch"><input type="checkbox" id="cursoDestaque"><span class="toggle-slider"></span></label><span>Destacar na pagina inicial</span></div></div><div class="grupo-form"><label><i class="fas fa-icons"></i> Icone (Font Awesome)</label><div class="icone-input-wrapper" style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;"><div class="icone-preview" id="iconePreviewCurso" style="width: 48px; height: 48px; background: var(--cinza-claro); border: 2px solid var(--cinza-medio); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--azul-primario); flex-shrink: 0;"><i class="fas fa-graduation-cap" id="iconePreviewIconCurso"></i></div><input type="text" id="cursoIcone" class="controle-form" placeholder="Ex: fa-graduation-cap, fa-helmet-safety, fa-laptop-code" value="fa-graduation-cap" style="flex: 1;"></div><div class="sugestoes-icones"><span style="font-size: 12px; color: #666; margin-right: 5px;">Sugestoes:</span><button type="button" class="sugestao-icone" data-icone="fa-graduation-cap"><i class="fas fa-graduation-cap"></i> Capelo</button><button type="button" class="sugestao-icone" data-icone="fa-helmet-safety"><i class="fas fa-helmet-safety"></i> Capacete</button><button type="button" class="sugestao-icone" data-icone="fa-laptop-code"><i class="fas fa-laptop-code"></i> Computador</button><button type="button" class="sugestao-icone" data-icone="fa-bolt"><i class="fas fa-bolt"></i> Raio</button><button type="button" class="sugestao-icone" data-icone="fa-gear"><i class="fas fa-gear"></i> Engrenagem</button><button type="button" class="sugestao-icone" data-icone="fa-couch"><i class="fas fa-couch"></i> Sofa</button><button type="button" class="sugestao-icone" data-icone="fa-wrench"><i class="fas fa-wrench"></i> Chave</button><button type="button" class="sugestao-icone" data-icone="fa-hammer"><i class="fas fa-hammer"></i> Martelo</button><button type="button" class="sugestao-icone" data-icone="fa-drafting-compass"><i class="fas fa-drafting-compass"></i> Compasso</button><button type="button" class="sugestao-icone" data-icone="fa-microchip"><i class="fas fa-microchip"></i> Chip</button><button type="button" class="sugestao-icone" data-icone="fa-database"><i class="fas fa-database"></i> Base Dados</button><button type="button" class="sugestao-icone" data-icone="fa-cloud"><i class="fas fa-cloud"></i> Nuvem</button><button type="button" class="sugestao-icone" data-icone="fa-rocket"><i class="fas fa-rocket"></i> Foguete</button><button type="button" class="sugestao-icone" data-icone="fa-paintbrush"><i class="fas fa-paintbrush"></i> Pintura</button></div></div></div>
                        <div class="grupo-form"><label>Imagem de Capa</label><div class="area-upload-arquivo" onclick="document.getElementById('imagemInput').click()"><i class="fas fa-cloud-upload-alt"></i><p>Clique para fazer upload da imagem</p></div><input type="file" id="imagemInput" accept="image/*" style="display: none;"><div class="preview-arquivo" id="previewImagem"><div><img id="miniaturaImagem" style="max-width: 100px;"></div><button type="button" class="btn-icone" onclick="removerImagem()"><i class="fas fa-trash"></i></button></div></div>
                    </div>
                </div>

                <div class="acoes-form">
                    <button type="button" class="btn-cancelar" onclick="fecharModalCurso()">Cancelar</button>
                    <button type="button" class="btn-salvar" id="btnSalvarCurso">Salvar Curso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/admin-sidebar-header.js"></script>
<script>
window.ADMIN_CURSOS_DATA = {
    areas:    <?php echo json_encode($areas_db, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    cursos:   <?php echo json_encode($cursos_db, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    planos:   <?php echo json_encode($planos_por_curso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    saidas:   <?php echo json_encode($saidas_por_curso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    projetos: <?php echo json_encode($projetos_por_curso, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};

let areas            = window.ADMIN_CURSOS_DATA.areas    || [];
let cursos           = window.ADMIN_CURSOS_DATA.cursos   || [];
let planosPorCurso   = window.ADMIN_CURSOS_DATA.planos   || {};
let saidasPorCurso   = window.ADMIN_CURSOS_DATA.saidas   || {};
let projetosPorCurso = window.ADMIN_CURSOS_DATA.projetos || {};
let filtroAreaAtual  = null;
let urlCursoAtual    = '';

function confirmarAcao(titulo, texto, callbackConfirmar, tipoAcao = 'eliminar') {
    if (typeof window.abrirModalConfirmacao === 'function') {
        window.abrirModalConfirmacao(titulo, texto, callbackConfirmar, tipoAcao);
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'ipikk-confirm-overlay ativo';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:30000;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 50% 18%, rgba(10,147,150,.24), transparent 34%), linear-gradient(135deg, rgba(5,19,43,.88), rgba(0,0,0,.9));backdrop-filter:blur(5px) saturate(115%);padding:20px;';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = `
        <div class="ipikk-confirm-box">
            <div class="ipikk-confirm-icon eliminar"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 class="ipikk-confirm-title">${escapeHtml(titulo)}</h3>
            <p class="ipikk-confirm-body">${escapeHtml(texto)}</p>
            <div class="ipikk-confirm-actions">
                <button type="button" class="ipikk-confirm-btn ipikk-confirm-cancel" data-confirm-cancel>Cancelar</button>
                <button type="button" class="ipikk-confirm-btn ipikk-confirm-action ${tipoAcao}">Eliminar</button>
            </div>
        </div>
    `;
    const caixa = overlay.querySelector('.ipikk-confirm-box');
    if (caixa) {
        caixa.style.cssText = 'background:linear-gradient(#fff,#fff) padding-box, linear-gradient(135deg, rgba(10,147,150,.55), rgba(0,48,114,.18), rgba(220,38,38,.3)) border-box;border:1px solid transparent;border-radius:24px;box-shadow:0 30px 70px rgba(2,8,23,.42), 0 2px 8px rgba(255,255,255,.18) inset;max-width:470px;overflow:hidden;padding:34px 32px 30px;position:relative;text-align:center;width:min(100%,470px);';
    }
    const overflowAnterior = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const fechar = () => {
        document.body.style.overflow = overflowAnterior;
        overlay.remove();
    };
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('[data-confirm-cancel]')) fechar();
        if (event.target.closest('.ipikk-confirm-action')) {
            fechar();
            if (typeof callbackConfirmar === 'function') callbackConfirmar();
        }
    });
    document.body.appendChild(overlay);
}

// Objeto para armazenar arquivos PDF selecionados (incluindo classe 0)
let pdfFiles = {
    0: null, 10: null, 11: null, 12: null, 13: null
};
let pdfParaRemover = {
    0: false, 10: false, 11: false, 12: false, 13: false
};

// ========== SELECAO EM MASSA DE CURSOS ==========
let cursosSelecionados = new Set();

function atualizarEstadoBotoesMassaCursos() {
    const btnEliminar = document.getElementById('btnEliminarSelecionadosCursos');
    if (btnEliminar) btnEliminar.disabled = cursosSelecionados.size === 0;
}

function selecionarTodosCursos() {
    const checkboxes = document.querySelectorAll('.checkbox-curso');
    const todosSelecionados = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => {
        cb.checked = !todosSelecionados;
        const id = parseInt(cb.dataset.id);
        if (cb.checked) { cursosSelecionados.add(id); cb.closest('.card-curso')?.classList.add('selecionado'); }
        else { cursosSelecionados.delete(id); cb.closest('.card-curso')?.classList.remove('selecionado'); }
    });
    atualizarEstadoBotoesMassaCursos();
}

function eliminarCursosSelecionados() {
    if (cursosSelecionados.size === 0) { mostrarNotificacao('Nenhum curso selecionado', 'error'); return; }
    confirmarAcao(
        'Confirmar eliminação',
        `Eliminar ${cursosSelecionados.size} curso(s) permanentemente?`,
        () => {
            const ids = Array.from(cursosSelecionados);
            let processados = 0, erros = 0;
            ids.forEach(id => {
        fetch('processos/processar-curso.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=delete_curso&curso_id=${id}` })
            .then(r => r.json())
            .then(data => {
                processados++;
                if (data.success) { const index = cursos.findIndex(c => c.id == id); if (index !== -1) cursos.splice(index, 1); }
                else erros++;
                if (processados === ids.length) {
                    if (erros === 0) mostrarNotificacao(`${processados} curso(s) eliminado(s) com sucesso!`, 'success');
                    else mostrarNotificacao(`${processados - erros} curso(s) eliminados, ${erros} erro(s).`, 'error');
                    cursosSelecionados.clear();
                    renderizarCursos();
                    atualizarEstatisticas();
                }
            })
            .catch(() => { processados++; erros++; if (processados === ids.length) { mostrarNotificacao(`Erro ao eliminar alguns cursos.`, 'error'); cursosSelecionados.clear(); renderizarCursos(); } });
            });
        },
        'eliminar'
    );
}

function exportarCursosCompleto() {
    const csv = [['ID', 'Nome', 'Area', 'Duracao', 'Estado', 'Destaque', 'Cor', 'Icone', 'Descricao Curta', 'Descricao Completa', 'Objetivo', 'Competencias', 'Certificacao', 'Imagem URL', 'PDF 10a', 'PDF 11a', 'PDF 12a', 'PDF 13a', 'PDF Resumo']];
    cursos.forEach(curso => {
        const area = areas.find(a => a.id == curso.area_id);
        const pdfs = planosPorCurso[curso.id] || {};
        csv.push([
            curso.id,
            `"${(curso.nome||'').replace(/"/g, '""')}"`,
            `"${(area?.nome || '').replace(/"/g, '""')}"`,
            curso.duracao || '',
            curso.estado || '',
            curso.destaque || '',
            curso.cor || '',
            curso.icone_classe || '',
            `"${(curso.descricao_curta || '').replace(/"/g, '""')}"`,
            `"${(curso.descricao_completa || '').replace(/"/g, '""')}"`,
            `"${(curso.objetivo || '').replace(/"/g, '""')}"`,
            `"${(curso.competencias_descricao || '').replace(/"/g, '""')}"`,
            `"${(curso.certificacao_descricao || '').replace(/"/g, '""')}"`,
            curso.imagem_hero || '',
            pdfs[10]?.url || '',
            pdfs[11]?.url || '',
            pdfs[12]?.url || '',
            pdfs[13]?.url || '',
            pdfs[0]?.url || ''
        ]);
    });
    const blob = new Blob(['\uFEFF' + csv.map(r => r.join(',')).join('\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `cursos_completo_${new Date().toISOString().slice(0,10)}.csv`; a.click();
    mostrarNotificacao('Exportacao concluida!', 'success');
}

function importarCursos() {
    const input = document.createElement('input'); input.type = 'file'; input.accept = '.csv';
    input.onchange = async (e) => {
        const file = e.target.files[0]; if (!file) return;
        const reader = new FileReader();
        reader.onload = async (event) => {
            const csvText = event.target.result;
            const linhas = csvText.split('\n');
            const headers = linhas[0].split(',').map(h => h.replace(/"/g, '').trim());
            const nomeIdx   = headers.findIndex(h => h === 'Nome');
            if (nomeIdx === -1) { mostrarNotificacao('CSV invalido. Necessario coluna Nome.', 'error'); return; }
            let importados = 0, atualizados = 0, erros = 0;
            for (let i = 1; i < linhas.length; i++) {
                const valores = linhas[i].split(',').map(v => v.replace(/^"|"$/g, '').trim());
                if (valores.length < 2) continue;
                const nome = valores[nomeIdx];
                if (!nome) continue;
                const areaNome = headers.findIndex(h => h === 'Area') !== -1 ? valores[headers.findIndex(h => h === 'Area')] : '';
                const area = areas.find(a => a.nome === areaNome);
                const duracao = headers.findIndex(h => h === 'Duracao') !== -1 ? valores[headers.findIndex(h => h === 'Duracao')] : '4 anos';
                const estado = headers.findIndex(h => h === 'Estado') !== -1 ? valores[headers.findIndex(h => h === 'Estado')] : 'ativo';
                const cursoExistente = cursos.find(c => c.nome === nome);
                const formData = new FormData();
                formData.append('action', cursoExistente ? 'update_curso' : 'create_curso');
                if (cursoExistente) formData.append('curso_id', cursoExistente.id);
                formData.append('nome', nome);
                formData.append('area_id', area?.id || '');
                formData.append('duracao', duracao);
                formData.append('estado', estado);
                formData.append('destaque', '0');
                formData.append('descricao_curta', headers.findIndex(h => h === 'Descricao Curta') !== -1 ? valores[headers.findIndex(h => h === 'Descricao Curta')] : '');
                formData.append('descricao_completa', headers.findIndex(h => h === 'Descricao Completa') !== -1 ? valores[headers.findIndex(h => h === 'Descricao Completa')] : '');
                try {
                    const response = await fetch('processos/processar-curso.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.success) { if (cursoExistente) atualizados++; else importados++; }
                    else erros++;
                } catch (error) { erros++; }
            }
            mostrarNotificacao(`Importacao concluida! Novos: ${importados}, Atualizados: ${atualizados}, Erros: ${erros}`, 'success');
            setTimeout(() => location.reload(), 2000);
        };
        reader.readAsText(file, 'UTF-8');
    };
    input.click();
}

function atualizarEstatisticas() {
    document.getElementById('estatisticaTotal').textContent     = cursos.length;
    document.getElementById('estatisticaAtivos').textContent    = cursos.filter(c => c.estado === 'ativo').length;
    document.getElementById('estatisticaPausados').textContent  = cursos.filter(c => c.estado === 'pausado').length;
    document.getElementById('estatisticaArquivados').textContent= cursos.filter(c => c.estado === 'arquivado').length;
    document.getElementById('estatisticaDestaques').textContent = cursos.filter(c => c.destaque == 1).length;
}

function escapeHtml(t) { if (t === null || t === undefined) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

function mostrarNotificacao(msg, tipo = 'success') {
    const n = document.createElement('div'); n.className = 'notificacao';
    n.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${escapeHtml(msg)}`;
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 24px;border-radius:12px;z-index:99999;font-weight:600;display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;color:#fff;background:${tipo === 'success' ? 'linear-gradient(135deg,#28a745,#218838)' : 'linear-gradient(135deg,#dc3545,#c82333)'};box-shadow:0 8px 24px rgba(0,0,0,.2);`;
    document.body.appendChild(n); setTimeout(() => n.remove(), 3000);
}

function criarOverlayGradiente(hex) { hex = (hex || '#003072').replace('#', ''); const r = parseInt(hex.slice(0,2),16), g = parseInt(hex.slice(2,4),16), b = parseInt(hex.slice(4,6),16); return `linear-gradient(to bottom,rgba(${r},${g},${b},.25),rgba(${Math.round(r*.4)},${Math.round(g*.4)},${Math.round(b*.4)},.85))`; }
function normalizarUrlMidiaAdmin(url){ if(!url) return ''; const u=String(url).trim(); if(/^https?:\/\//i.test(u)) return u; return '../'+u.replace(/^\/+/, ''); }
function getImagemArea(area) { const pad = { 'construcao-civil':'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800&q=80','eletricidade':'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80','mecanica':'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&q=80','informatica':'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80','tecnologia-moveis':'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80','alfaiataria':'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&q=80' }; if (!area.imagem_url) return pad[area.slug] || pad['informatica']; return normalizarUrlMidiaAdmin(area.imagem_url); }
function getImagemCurso(curso) { const pad = { 'construcao-civil-obras':'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=800&q=80','construcao-civil-desenhador':'https://images.unsplash.com/photo-1581091226033-d5c48150dbaa?w=800&q=80','eletricidade-instalacoes':'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80','mecanica-climatizacao':'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800&q=80','informatica-gestao-sistemas':'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80','informatica-tecnico':'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&q=80','tecnologia-moveis-curso':'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80' }; if (!curso.imagem_hero) return pad[curso.slug] || pad['construcao-civil-obras']; return normalizarUrlMidiaAdmin(curso.imagem_hero); }
function getOverlayCurso(curso, corAreaFallback) { const map = { 'construcao-civil-obras':'linear-gradient(to bottom,rgba(42,46,51,.25) 0%,rgba(36,37,39,.78) 100%)','construcao-civil-desenhador':'linear-gradient(to bottom,rgba(180,110,0,.45) 0%,rgba(140,80,0,.92) 100%)','eletricidade-instalacoes':'linear-gradient(135deg,rgba(15,23,42,.8),rgba(46,134,193,.6))','mecanica-climatizacao':'linear-gradient(to bottom,rgba(224,123,42,.3),rgba(184,94,26,.9))','informatica-gestao-sistemas':'linear-gradient(to bottom,rgba(46,134,193,.3),rgba(26,90,140,.9))','informatica-tecnico':'linear-gradient(to bottom,rgba(45,122,58,.3),rgba(30,85,39,.9))','tecnologia-moveis-curso':'linear-gradient(to bottom,rgba(192,57,43,.3),rgba(150,45,34,.9))' }; return map[curso.slug] || criarOverlayGradiente(corAreaFallback); }
function getCorCurso(curso, corAreaFallback) { const map = { 'construcao-civil-obras':'#6c757d','construcao-civil-desenhador':'#b46e00','eletricidade-instalacoes':'#2e86c1','mecanica-climatizacao':'#e07b2a','informatica-gestao-sistemas':'#1a5a8c','informatica-tecnico':'#2d7a3a','tecnologia-moveis-curso':'#c0392b' }; return map[curso.slug] || curso.cor || corAreaFallback || '#003072'; }
function getBotaoCores(curso) { const map = { 'construcao-civil-obras':{ bg:'#6c757d',hov:'#444' },'construcao-civil-desenhador':{ bg:'#e6a817',hov:'#c9920e' },'eletricidade-instalacoes':{ bg:'#2e86c1',hov:'#1f5a8a' },'mecanica-climatizacao':{ bg:'#e07b2a',hov:'#c95a0e' },'informatica-gestao-sistemas':{ bg:'#2e86c1',hov:'#1f5a8a' },'informatica-tecnico':{ bg:'#2d7a3a',hov:'#1e5527' },'tecnologia-moveis-curso':{ bg:'#c0392b',hov:'#a83224' } }; return map[curso.slug] || { bg:'#003072',hov:'#001a40' }; }

function renderizarAreas() {
    const c = document.getElementById('containerAreas'); if (!c) return;
    c.innerHTML = '';
    if (!areas.length) { c.innerHTML = '<div class="sem-resultados"><i class="fas fa-info-circle"></i> Nenhuma area cadastrada.</div>'; return; }
    areas.forEach(area => {
        const total = cursos.filter(x => x.area_id == area.id).length;
        const card = document.createElement('div');
        card.className = `card-area ${filtroAreaAtual == area.id ? 'ativo' : ''}`;
        card.dataset.areaId = area.id;
        card.innerHTML = `<div class="barra-cor-area" style="background:${area.cor_primaria||'#6c757d'};"></div><div class="icone-area" style="background:${area.cor_primaria||'#6c757d'};margin-left:10px;"><i class="fas ${area.icone_classe||'fa-layer-group'}"></i></div><div class="info-area" onclick="filtrarPorArea(${area.id})"><h4>${escapeHtml(area.nome)}</h4><p>${escapeHtml((area.descricao_curta||'').substring(0,60))}${(area.descricao_curta?.length>60)?'...':''}</p><small class="contador-cursos">${total} curso${total!==1?'s':''}</small></div><div class="acoes-area"><button class="btn-icone-area" onclick="editarArea(${area.id})" title="Editar"><i class="fas fa-edit"></i></button><button class="btn-icone-area btn-perigo" onclick="eliminarArea(${area.id})" title="Eliminar"><i class="fas fa-trash"></i></button></div>`;
        c.appendChild(card);
    });
}

function visualizarCurso(cursoId) {
    const curso = cursos.find(c => c.id == cursoId);
    if (!curso) { mostrarNotificacao('Curso nao encontrado', 'error'); return; }
    const url = '../area-publica/curso.php?slug=' + curso.slug;
    urlCursoAtual = url;
    document.getElementById('iframeCursoTitulo').textContent = curso.nome;
    document.getElementById('iframeCursoSubtitulo').innerHTML = '<i class="fas fa-link"></i> ' + url;
    document.getElementById('iframeCurso').src = url;
    document.getElementById('modalIframeCurso').style.display = 'flex';
}
function fecharModalIframe() { document.getElementById('iframeCurso').src = 'about:blank'; document.getElementById('modalIframeCurso').style.display = 'none'; }
function abrirCursoNovaAba() { if (urlCursoAtual) window.open(urlCursoAtual, '_blank'); }

function renderizarCursos() {
    const c = document.getElementById('containerCursos'); if (!c) return;
    c.innerHTML = '';
    let filtrados = [...cursos];
    if (filtroAreaAtual) filtrados = filtrados.filter(x => x.area_id == filtroAreaAtual);
    const est = document.getElementById('filtroEstado')?.value || '';
    if (est) filtrados = filtrados.filter(x => x.estado === est);
    const bsc = (document.getElementById('campoBusca')?.value||'').toLowerCase();
    if (bsc) filtrados = filtrados.filter(x => x.nome?.toLowerCase().includes(bsc)||x.descricao_curta?.toLowerCase().includes(bsc));
    if (!filtrados.length) { c.innerHTML = '<div class="sem-resultados"><i class="fas fa-info-circle"></i> Nenhum curso encontrado.</div>'; document.getElementById('contadorCursos').innerHTML = '(0 cursos)'; return; }
    filtrados.forEach(curso => {
        const area = areas.find(a => a.id == curso.area_id);
        const cor  = getCorCurso(curso, area?.cor_primaria);
        const em   = curso.estado||'ativo';
        const ico  = {ativo:'check-circle',pausado:'pause-circle',arquivado:'archive'}[em];
        const lbl  = {ativo:'Ativo',pausado:'Pausado',arquivado:'Arquivado'}[em];
        const card = document.createElement('div');
        card.className = 'card-curso';
        card.innerHTML = `<div class="checkbox-curso-wrapper"><input type="checkbox" class="checkbox-curso" data-id="${curso.id}" onclick="event.stopPropagation()"></div><div class="barra-cor-curso" style="background:${cor};"></div><div class="conteudo-curso"><div class="cabecalho-curso"><div class="icone-curso"><i class="fas ${curso.icone_classe||'fa-graduation-cap'}"></i></div><div class="info-curso"><h3>${escapeHtml(curso.nome)}</h3><p><i class="fas fa-tag"></i> ${escapeHtml(area?.nome||'Sem area')}</p></div></div><p class="descricao-curso">${escapeHtml((curso.descricao_curta||'').substring(0,100))}${(curso.descricao_curta?.length>100)?'...':''}</p><div class="metadados-curso"><span class="item-metadado"><i class="fas fa-clock"></i> ${curso.duracao||'N/A'}</span></div><div class="rodape-curso"><span class="badge-curso badge-${em}"><i class="fas fa-${ico}"></i> ${lbl}</span><div class="acoes-curso"><button class="btn-icone" onclick="visualizarCurso(${curso.id})" title="Visualizar"><i class="fas fa-eye"></i></button><button class="btn-icone" onclick="editarCurso(${curso.id})" title="Editar"><i class="fas fa-edit"></i></button><button class="btn-icone" onclick="eliminarCurso(${curso.id})" title="Eliminar"><i class="fas fa-trash"></i></button></div></div></div>`;
        c.appendChild(card);
    });
    document.getElementById('contadorCursos').innerHTML = `(${filtrados.length} cursos)`;
}

function filtrarPorArea(id) { filtroAreaAtual = filtroAreaAtual == id ? null : id; renderizarAreas(); renderizarCursos(); }

function abrirModalArea(id = null) {
    const modal = document.getElementById('modalArea');
    document.getElementById('formularioArea').reset();
    document.getElementById('areaId').value = '';
    document.getElementById('tituloModalArea').innerHTML = 'Nova Area';
    document.getElementById('previewAreaImagem').classList.remove('ativo');
    document.getElementById('areaAtivo').checked = true;
    document.getElementById('areaOrdem').value = '0';
    document.getElementById('areaIcone').value = 'fa-layer-group';
    document.getElementById('iconePreviewIcon').className = 'fas fa-layer-group';
    document.getElementById('areaCor').value = '#6c757d';
    document.getElementById('previewCor').style.background = '#6c757d';
    if (id) {
        const area = areas.find(a => a.id == id);
        if (area) {
            document.getElementById('tituloModalArea').innerHTML = 'Editar Area';
            document.getElementById('areaId').value = area.id;
            document.getElementById('areaNome').value = area.nome||'';
            document.getElementById('areaDescricaoCurta').value = area.descricao_curta||'';
            document.getElementById('areaDescricaoCompleta').value = area.descricao_completa||'';
            document.getElementById('areaCor').value = area.cor_primaria||'#6c757d';
            document.getElementById('previewCor').style.background = area.cor_primaria||'#6c757d';
            document.getElementById('areaIcone').value = area.icone_classe||'fa-layer-group';
            document.getElementById('iconePreviewIcon').className = 'fas '+(area.icone_classe||'fa-layer-group');
            document.getElementById('areaOrdem').value = area.ordem||0;
            document.getElementById('areaAtivo').checked = area.ativo==1;
            if (area.imagem_url) { document.getElementById('miniaturaAreaImagem').src = normalizarUrlMidiaAdmin(area.imagem_url); document.getElementById('previewAreaImagem').classList.add('ativo'); }
        }
    }
    modal.style.display = 'flex';
}
function fecharModalArea() { document.getElementById('modalArea').style.display='none'; }
function editarArea(id) { abrirModalArea(id); }

function eliminarArea(id) {
    const area = areas.find(a=>a.id==id);
    const tot = cursos.filter(c=>c.area_id==id).length;
    if (tot) { mostrarNotificacao(`Nao pode eliminar "${area.nome}" — tem ${tot} curso(s).`,'error'); return; }
    confirmarAcao(
        'Confirmar eliminação',
        `Eliminar a area "${area.nome}"?`,
        () => {
            fetch('processos/processar-curso.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=delete_area&area_id=${id}`})
                .then(r=>r.json()).then(d=>{ if(d.success){mostrarNotificacao(d.message,'success');location.reload();}else mostrarNotificacao(d.message,'error'); })
                .catch(()=>mostrarNotificacao('Erro ao comunicar','error'));
        },
        'eliminar'
    );
}

function salvarArea(e) {
    e.preventDefault();
    const fd = new FormData();
    const id = document.getElementById('areaId').value;
    fd.append('action', id?'update_area':'create_area');
    fd.append('area_id', id);
    fd.append('nome', document.getElementById('areaNome').value);
    fd.append('descricao_curta', document.getElementById('areaDescricaoCurta').value);
    fd.append('descricao_completa', document.getElementById('areaDescricaoCompleta').value);
    fd.append('cor_primaria', document.getElementById('areaCor').value);
    fd.append('icone_classe', document.getElementById('areaIcone').value);
    fd.append('ordem', document.getElementById('areaOrdem').value);
    fd.append('ativo', document.getElementById('areaAtivo').checked?'1':'0');
    const img = document.getElementById('areaImagemInput');
    if (img?.files.length) fd.append('imagem_url', img.files[0]);
    mostrarNotificacao('A processar...','info');
    fetch('processos/processar-curso.php',{method:'POST',body:fd})
        .then(r=>r.json()).then(d=>{ if(d.success){mostrarNotificacao(d.message,'success');setTimeout(()=>location.reload(),1000);}else mostrarNotificacao(d.message||'Erro ao salvar','error'); })
        .catch(()=>mostrarNotificacao('Erro ao comunicar','error'));
}

function buildPreviewOferta(area) {
    const cor = area.cor_primaria||'#003072';
    const overlay = criarOverlayGradiente(cor);
    const imagem = getImagemArea(area);
    const total = cursos.filter(c=>c.area_id==area.id).length;
    const icone = area.icone_classe||'fa-graduation-cap';
    return `<div class="pvof-scene pv-anim"><div class="pvof-label"><i class="fas fa-globe" style="margin-right:5px;"></i> Aspeto na pagina Oferta Formativa</div><a class="pvof-card" href="#" onclick="return false;"><div class="pvof-capa" style="background-image:url('${imagem}')"><div class="pvof-overlay" style="background:${overlay};"></div><div class="pvof-body"><div class="pvof-icon"><i class="fas ${icone}"></i></div><h2 class="pvof-title">${escapeHtml(area.nome)}</h2><p class="pvof-desc">${escapeHtml(area.descricao_curta||'Formacao tecnica especializada')}</p></div></div><div class="pvof-footer"><span class="pvof-count">${total} curso${total!==1?'s':''} disponivel${total!==1?'is':''}</span><span class="pvof-btn" style="color:${cor};border-color:${cor};">Explorar area →</span></div></a><p style="font-size:.72rem;color:rgba(255,255,255,.4);text-align:center;margin-top:4px;"><i class="fas fa-info-circle"></i> Pre-visualizacao fiel ao site publico</p></div>`;
}
function buildPreviewPaginaArea(area) {
    const cor = area.cor_primaria||'#003072';
    const cursosArea = cursos.filter(c=>c.area_id==area.id);
    let cursosHtml = '';
    cursosArea.forEach(curso => {
        const imgC  = getImagemCurso(curso);
        const overlC= getOverlayCurso(curso, cor);
        const corC  = getCorCurso(curso, cor);
        const btnC  = getBotaoCores(curso);
        const comps = window.COMPS?.[curso.slug]||['Formacao tecnica especializada','Pratica em laboratorios','Preparacao profissional'];
        cursosHtml += `<article class="pvacard pv-anim"><div class="pvacard-capa"><img src="${imgC}" alt="${escapeHtml(curso.nome)}" class="pvacard-img" loading="lazy"/><div class="pvacard-overlay" style="background:${overlC};"></div><div class="pvacard-infocapa"><div class="pvacard-icon"><i class="fas ${curso.icone_classe||'fa-graduation-cap'}"></i></div><div><div class="pvacard-nome">${escapeHtml(curso.nome)}</div><div class="pvacard-alabel">Area de ${escapeHtml(area.nome)}</div></div></div></div><div class="pvacard-body"><ul class="pvacard-comps">${comps.map(cp=>`<li class="pvacard-comp"><span class="pvacard-check" style="color:${corC};">✓</span>${escapeHtml(cp)}</li>`).join('')}</ul><span class="pvacard-btn" style="background:${btnC.bg};">Ver detalhes do curso →</span></div></article>`;
    });
    if (!cursosHtml) cursosHtml = '<div class="pv-empty"><i class="fas fa-graduation-cap"></i>Nenhum curso cadastrado nesta area.</div>';
    return `<div class="pvarea-wrap pv-anim" style="--pv-cor:${cor};"><div class="pvarea-head"><h2 class="pvarea-title">Area de Formacao: ${escapeHtml(area.nome)}</h2><p class="pvarea-desc">${escapeHtml(area.descricao_completa||area.descricao_curta||'Descricao da area de formacao')}</p><span class="pvarea-linha" style="background:${cor};"></span></div><div class="pvarea-grid">${cursosHtml}</div></div>`;
}
function previewArea(areaId) {
    const area = areas.find(a=>a.id==areaId);
    if (!area) { mostrarNotificacao('Area nao encontrada','error'); return; }
    const modal = document.getElementById('modalPreviewArea');
    const corpo = modal.querySelector('.corpo-modal');
    corpo.innerHTML = `<div class="pv-tab-bar" id="pvAreaTabBar"><button class="pv-tab-btn ativo" onclick="pvAreaTab('oferta',this)"><i class="fas fa-th-large"></i> Card na Oferta Formativa</button><button class="pv-tab-btn" onclick="pvAreaTab('area',this)"><i class="fas fa-layer-group"></i> Pagina da Area</button></div><div id="pvAreaContent"></div>`;
    document.getElementById('pvAreaContent').innerHTML = buildPreviewOferta(area);
    modal._areaData = area;
    modal.style.display = 'flex';
}
function pvAreaTab(tipo, btn) {
    const modal = document.getElementById('modalPreviewArea');
    const area  = modal._areaData;
    document.querySelectorAll('#pvAreaTabBar .pv-tab-btn').forEach(b=>b.classList.remove('ativo'));
    btn.classList.add('ativo');
    const cont = document.getElementById('pvAreaContent');
    cont.innerHTML = tipo==='oferta' ? buildPreviewOferta(area) : buildPreviewPaginaArea(area);
    cont.scrollTop = 0;
}
function fecharModalPreview() { document.getElementById('modalPreviewArea').style.display='none'; }
function mudarPreviewAreaTab(tipo) { const btns = document.querySelectorAll('#pvAreaTabBar .pv-tab-btn'); if (!btns.length) return; pvAreaTab(tipo==='card'?'oferta':'area', tipo==='card'?btns[0]:btns[1]); }

function previewCurso(cursoId) {
    const curso = cursos.find(c=>c.id==cursoId);
    if (!curso) { mostrarNotificacao('Curso nao encontrado','error'); return; }
    const area   = areas.find(a=>a.id==curso.area_id);
    const cor    = getCorCurso(curso, area?.cor_primaria);
    const imgHero= getImagemCurso(curso);
    const grad   = getOverlayCurso(curso, area?.cor_primaria);
    const icone  = curso.icone_classe||'fa-graduation-cap';
    const planos  = planosPorCurso[cursoId]||{};
    const pdfData = {};
    for (let cl=10;cl<=13;cl++) { pdfData[cl] = { titulo:`Plano Curricular — ${cl}a Classe`, descricao:`${curso.nome} · ${cl}a Classe`, url:planos[cl]?.url||null, disponivel:!!planos[cl]?.url }; }
    const saidas = saidasPorCurso[cursoId]||[];
    let saidasHtml = saidas.length ? saidas.map(s=>`<div class="pvcurso-saida pv-anim"><div class="pvcurso-saida-head" style="background:${cor};"><h4>${escapeHtml(s.titulo)}</h4></div><div class="pvcurso-saida-body"><p class="pvcurso-saida-desc">${escapeHtml(s.descricao||'')}</p><div class="pvcurso-saida-tags">${(s.competencias||[]).map(t=>`<span class="pvcurso-saida-tag" style="background:${getCorAlpha(cor,.14)};color:${cor};">${escapeHtml(t)}</span>`).join('')}</div></div></div>`).join('') : `<div class="pv-empty"><i class="fas fa-briefcase"></i>As saidas profissionais serao divulgadas em breve.</div>`;
    const projetos = projetosPorCurso[cursoId]||[];
    let projetosHtml = projetos.length ? projetos.map(p=>`<div class="pvcurso-proj-card pv-anim"><img src="${escapeHtml(p.imagem_url||'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=600&q=80')}" alt="${escapeHtml(p.titulo)}" class="pvcurso-proj-img" loading="lazy"/><div class="pvcurso-proj-body"><span class="pvcurso-proj-cat" style="background:${getCorAlpha(cor,.14)};color:${cor};">${escapeHtml(p.categoria||'Projecto')}</span><div class="pvcurso-proj-tit">${escapeHtml(p.titulo)}</div><div class="pvcurso-proj-desc">${escapeHtml((p.descricao||'').substring(0,100))}${p.descricao?.length>100?'...':''}</div><div class="pvcurso-proj-meta"><span><i class="far fa-calendar"></i>${escapeHtml(p.ano||new Date().getFullYear())}</span><span><i class="fas fa-user-graduate"></i>${escapeHtml(p.autor||'Aluno IPIKK')}</span></div></div></div>`).join('') : `<div class="pv-empty"><i class="fas fa-project-diagram"></i>Os projectos serao divulgados em breve.</div>`;
    const comps = window.COMPS?.[curso.slug]||['Formacao tecnica especializada','Pratica em laboratorios','Preparacao profissional'];
    const html = `<div style="--pv-cor:${cor};"><div class="pvcurso-hero pv-anim" style="background-image:url('${imgHero}');"><div class="pvcurso-hero-overlay" style="background:${grad};"></div><div class="pvcurso-hero-content"><div class="pvcurso-emblem"><i class="fas ${icone}"></i></div><h1 class="pvcurso-title">${escapeHtml(curso.nome)}</h1><p class="pvcurso-sub">${escapeHtml(curso.descricao_curta||'Formacao tecnica especializada')}</p><div class="pvcurso-metas">                            <div class="pvcurso-meta">
                                <div class="pvcurso-meta-ico"><i class="far fa-clock"></i></div>
                                <div class="pvcurso-meta-lbl">Duracao</div>
                                <div class="pvcurso-meta-val">${escapeHtml(curso.duracao||'4 Anos')}</div>
                            </div>
                            <div class="pvcurso-meta">
                                <div class="pvcurso-meta-ico"><i class="fas fa-graduation-cap"></i></div>
                                <div class="pvcurso-meta-lbl">Nivel</div>
                                <div class="pvcurso-meta-val">${escapeHtml(curso.nivel||'Tecnico Medio')}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pvcurso-secao pv-anim">
                    <h2 class="pvcurso-sec-title" style="--pv-cor:${cor};">Sobre o Curso</h2>
                    <p class="pvcurso-sec-sub">${escapeHtml(curso.sobre_descricao||curso.descricao_curta||'Formacao completa para o mercado de trabalho')}</p>
                    <div class="pvcurso-sobre-grid">
                        <div class="pvcurso-sobre-card" style="border-top-color:${cor};">
                            <div class="pvcurso-sobre-ico" style="background:${getCorAlpha(cor,.09)};color:${cor};"><i class="fas fa-bullseye"></i></div>
                            <h4>Objectivo</h4>
                            <p>${escapeHtml(curso.objetivo||'Formar profissionais capacitados para actuar no mercado de trabalho.')}</p>
                        </div>
                        <div class="pvcurso-sobre-card" style="border-top-color:${cor};">
                            <div class="pvcurso-sobre-ico" style="background:${getCorAlpha(cor,.09)};color:${cor};"><i class="fas fa-tools"></i></div>
                            <h4>Competencias</h4>
                            <p>${escapeHtml(curso.competencias_descricao||comps.slice(0,2).join('; '))}</p>
                        </div>
                        <div class="pvcurso-sobre-card" style="border-top-color:${cor};">
                            <div class="pvcurso-sobre-ico" style="background:${getCorAlpha(cor,.09)};color:${cor};"><i class="fas fa-certificate"></i></div>
                            <h4>Certificacao</h4>
                            <p>${escapeHtml(curso.certificacao_descricao||'Diploma de Tecnico Medio reconhecido pelo Ministerio da Educacao.')}</p>
                        </div>
                    </div>
                </div>
                <div class="pvcurso-secao pvcurso-secao-alt pv-anim">
                    <h2 class="pvcurso-sec-title" style="--pv-cor:${cor};">Plano Curricular</h2>
                    <p class="pvcurso-sec-sub">Selecione a classe para aceder ao documento oficial em PDF.</p>
                    <div class="pvcurso-abas" id="pvAbas_${cursoId}">
                        ${[10,11,12,13].map((cl,i)=>`<button class="pvcurso-aba${i===0?' ativo':''}" data-cl="${cl}" onclick="pvMudarClasse(${cursoId},${cl},this)" style="--pv-cor:${cor};">${cl}a Classe</button>`).join('')}
                    </div>
                    <div class="pvcurso-pdf-box" id="pvPdf_${cursoId}">${buildPdfBlock(pdfData[10], cor)}</div>
                    <script>window._pvPdf=window._pvPdf||{};window._pvPdf[${cursoId}]=${JSON.stringify(pdfData)};<\/script>
                </div>
                <div class="pvcurso-secao pv-anim">
                    <h2 class="pvcurso-sec-title" style="--pv-cor:${cor};">Saidas Profissionais</h2>
                    <p class="pvcurso-sec-sub">Conheca as oportunidades que o curso oferece no mercado de trabalho.</p>
                    <div class="pvcurso-saidas-grid">${saidasHtml}</div>
                </div>
                <div class="pvcurso-secao pvcurso-secao-dark pv-anim">
                    <h2 class="pvcurso-sec-title" style="--pv-cor:${cor};">Projectos Realizados</h2>
                    <p class="pvcurso-sec-sub">Conheca alguns dos projectos desenvolvidos pelos nossos alunos.</p>
                    <div class="pvcurso-proj-grid">${projetosHtml}</div>
                </div>
                <p style="text-align:center;font-size:.7rem;color:#adb5bd;padding:6px 0 18px;"><i class="fas fa-eye"></i> Pre-visualizacao fiel a pagina publica do curso</p>
            </div>`;
    const cont = document.getElementById('previewCursoContent');
    cont.innerHTML = html;
    cont.scrollTop = 0;
    document.getElementById('modalPreviewCurso').style.display = 'flex';
}

function getCorAlpha(cor, alpha) {
    const hex = cor.replace('#', '');
    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function buildPdfBlock(dados, cor) {
    if (dados.disponivel && dados.url) {
        return `<div class="pvcurso-pdf-block" style="border-left-color:${cor};">
                    <div class="pvcurso-pdf-info">
                        <div class="pvcurso-pdf-ico"><i class="fas fa-file-pdf"></i></div>
                        <div>
                            <div class="pvcurso-pdf-rot">Documento Oficial</div>
                            <div class="pvcurso-pdf-tit">${escapeHtml(dados.titulo)}</div>
                            <div class="pvcurso-pdf-desc">${escapeHtml(dados.descricao)}</div>
                        </div>
                    </div>
                    <a href="${escapeHtml(normalizarUrlMidiaAdmin(dados.url))}" class="pvcurso-pdf-btn" style="background:${cor};" target="_blank"><i class="fas fa-eye"></i> Ver Documento</a>
                </div>`;
    }
    return `<div class="pvcurso-pdf-block" style="border-left-color:${cor};">
                <div class="pvcurso-pdf-info">
                    <div class="pvcurso-pdf-ico"><i class="fas fa-file-pdf"></i></div>
                    <div>
                        <div class="pvcurso-pdf-rot">Documento Oficial</div>
                        <div class="pvcurso-pdf-tit">${escapeHtml(dados.titulo)}</div>
                        <div class="pvcurso-pdf-desc">${escapeHtml(dados.descricao)}</div>
                    </div>
                </div>
                <span class="pvcurso-pdf-btn na" style="background:#adb5bd;cursor:default;"><i class="fas fa-file-pdf"></i> PDF nao disponivel</span>
            </div>`;
}

function pvMudarClasse(cursoId, cl, btn) {
    const dados = window._pvPdf?.[cursoId]?.[cl];
    const curso = cursos.find(c=>c.id==cursoId);
    const area  = areas.find(a=>a.id==curso?.area_id);
    const cor   = getCorCurso(curso, area?.cor_primaria);
    btn.closest('.pvcurso-abas').querySelectorAll('.pvcurso-aba').forEach(b=>b.classList.remove('ativo'));
    btn.classList.add('ativo');
    const box = document.getElementById(`pvPdf_${cursoId}`);
    if (!box||!dados) return;
    box.style.opacity='0';
    setTimeout(()=>{ box.innerHTML=buildPdfBlock(dados,cor); box.style.opacity='1'; }, 180);
}

function fecharModalPreviewCurso() { document.getElementById('modalPreviewCurso').style.display='none'; }

// ============================================
// FUNÇÕES PARA UPLOAD E REMOÇÃO DE PDFs
// ============================================

function carregarPDFsExistentes(cursoId) {
    const planos = planosPorCurso[cursoId] || {};
    const classes = [0, 10, 11, 12, 13];

    classes.forEach(classe => {
        const planoExistente = planos[classe];
        const previewDiv = document.getElementById(`pdf${classe}Preview`);
        const nomeSpan = document.getElementById(`pdf${classe}Nome`);
        const existenteSpan = document.getElementById(`pdf${classe}Existente`);
        const btnRemover = document.querySelector(`.btn-remover-pdf[data-classe="${classe}"]`);

        if(planoExistente && planoExistente.url) {
            if(previewDiv) {
                previewDiv.style.display = 'flex';
                if(nomeSpan) {
                    const nomeArquivo = planoExistente.url.split('/').pop();
                    nomeSpan.textContent = nomeArquivo;
                }
                if(existenteSpan) {
                    existenteSpan.textContent = ' (PDF atual)';
                }
            }
            if(btnRemover) btnRemover.style.display = 'inline-block';
            pdfParaRemover[classe] = false;
            pdfFiles[classe] = null;
        } else {
            if(previewDiv) previewDiv.style.display = 'none';
            if(btnRemover) btnRemover.style.display = 'none';
            pdfParaRemover[classe] = false;
            pdfFiles[classe] = null;
        }
    });
}

function handlePDFChange(e) {
    const input = e.target;
    const classe = parseInt(input.dataset.classe);
    const file = input.files[0];
    if(!file) return;

    if(file.type !== 'application/pdf') {
        mostrarNotificacao('Formato inválido. Selecione um arquivo PDF.', 'error');
        input.value = '';
        return;
    }

    if(file.size > 10 * 1024 * 1024) {
        mostrarNotificacao('Arquivo muito grande. Máximo 10MB', 'error');
        input.value = '';
        return;
    }

    pdfFiles[classe] = file;
    pdfParaRemover[classe] = false;

    const previewDiv = document.getElementById(`pdf${classe}Preview`);
    const nomeSpan = document.getElementById(`pdf${classe}Nome`);
    const existenteSpan = document.getElementById(`pdf${classe}Existente`);
    const btnRemover = document.querySelector(`.btn-remover-pdf[data-classe="${classe}"]`);

    if(previewDiv) {
        previewDiv.style.display = 'flex';
        if(nomeSpan) nomeSpan.textContent = file.name;
        if(existenteSpan) existenteSpan.textContent = ' (novo PDF)';
    }
    if(btnRemover) btnRemover.style.display = 'inline-block';
}

function handleRemoverPDF(e) {
    e.preventDefault();
    e.stopPropagation();
    const btn = e.currentTarget;
    const classe = parseInt(btn.dataset.classe);

    confirmarAcao(
        'Confirmar remoção',
        `Tem certeza que deseja remover o PDF da ${classe === 0 ? 'aba Geral' : classe + 'ª Classe'}?`,
        () => {
            pdfParaRemover[classe] = true;
            pdfFiles[classe] = null;

            const input = document.getElementById(`pdf${classe}Input`);
            if(input) input.value = '';

            const previewDiv = document.getElementById(`pdf${classe}Preview`);
            if(previewDiv) previewDiv.style.display = 'none';

            btn.style.display = 'none';

            mostrarNotificacao(`PDF ${classe === 0 ? 'Resumo Geral' : 'da ' + classe + 'ª Classe'} será removido ao salvar.`, 'info');
        },
        'eliminar'
    );
}

function setupPDFUploads() {
    const cursoId = document.getElementById('cursoId').value;
    const classes = [0, 10, 11, 12, 13];

    classes.forEach(classe => {
        const input = document.getElementById(`pdf${classe}Input`);
        if(input) {
            input.removeEventListener('change', handlePDFChange);
            input.addEventListener('change', handlePDFChange);
        }

        const btnRemover = document.querySelector(`.btn-remover-pdf[data-classe="${classe}"]`);
        if(btnRemover) {
            btnRemover.removeEventListener('click', handleRemoverPDF);
            btnRemover.addEventListener('click', handleRemoverPDF);
        }

        if(cursoId && planosPorCurso[cursoId] && planosPorCurso[cursoId][classe] && planosPorCurso[cursoId][classe].url) {
            const previewDiv = document.getElementById(`pdf${classe}Preview`);
            const nomeSpan = document.getElementById(`pdf${classe}Nome`);
            const existenteSpan = document.getElementById(`pdf${classe}Existente`);
            if(previewDiv) {
                previewDiv.style.display = 'flex';
                if(nomeSpan) nomeSpan.textContent = planosPorCurso[cursoId][classe].url.split('/').pop();
                if(existenteSpan) existenteSpan.textContent = ' (PDF atual)';
            }
            if(btnRemover) btnRemover.style.display = 'inline-block';
            pdfParaRemover[classe] = false;
        } else {
            const previewDiv = document.getElementById(`pdf${classe}Preview`);
            if(previewDiv) previewDiv.style.display = 'none';
            if(btnRemover) btnRemover.style.display = 'none';
            pdfFiles[classe] = null;
            pdfParaRemover[classe] = false;
        }
    });
}

// ============================================
// ABRIR/FECHAR MODAL CURSO
// ============================================

function abrirModalCurso(id = null) {
    const modal = document.getElementById('modalCurso');
    document.getElementById('formularioCurso').reset();
    document.getElementById('cursoId').value = '';
    document.getElementById('tituloModalCurso').innerHTML = 'Novo Curso';

    // Resetar PDFs
    const classes = [0, 10, 11, 12, 13];
    classes.forEach(classe => {
        pdfFiles[classe] = null;
        pdfParaRemover[classe] = false;
        const previewDiv = document.getElementById(`pdf${classe}Preview`);
        if(previewDiv) previewDiv.style.display = 'none';
        const input = document.getElementById(`pdf${classe}Input`);
        if(input) input.value = '';
        const btnRemover = document.querySelector(`.btn-remover-pdf[data-classe="${classe}"]`);
        if(btnRemover) btnRemover.style.display = 'none';
    });

    document.getElementById('previewImagem').classList.remove('ativo');
    document.getElementById('containerSaidas').innerHTML = '';
    document.getElementById('containerProjectos').innerHTML = '';
    document.getElementById('cursoDescricaoCurta').value = '';
    document.getElementById('cursoSobreDescricao').value = '';
    document.getElementById('cursoObjetivo').value = '';
    document.getElementById('cursoCompetencias').value = '';
    document.getElementById('cursoCertificacao').value = '';
    document.getElementById('cursoCompetenciasCard').value = '';

    if(id) {
        const curso = cursos.find(c => c.id == id);
        if(curso) {
            const area = areas.find(a => a.id == curso.area_id);
            document.getElementById('tituloModalCurso').innerHTML = 'Editar Curso';
            document.getElementById('cursoId').value = curso.id;
            document.getElementById('cursoNome').value = curso.nome || '';
            document.getElementById('cursoAreaId').value = curso.area_id || '';
            document.getElementById('cursoDuracao').value = curso.duracao || '';
            document.getElementById('cursoEstado').value = curso.estado || 'ativo';
            document.getElementById('cursoDestaque').checked = curso.destaque == 1;
            document.getElementById('cursoCor').value = getCorCurso(curso, area?.cor_primaria);
            document.getElementById('cursoDescricaoCurta').value = curso.descricao_curta || '';
            document.getElementById('cursoSobreDescricao').value = curso.sobre_descricao || '';
            document.getElementById('cursoObjetivo').value = curso.objetivo || '';
            document.getElementById('cursoCompetencias').value = curso.competencias_descricao || '';
            document.getElementById('cursoCertificacao').value = curso.certificacao_descricao || '';
            document.getElementById('cursoCompetenciasCard').value = curso.competencias_card || '';

            const icone = curso.icone_classe || 'fa-graduation-cap';
            document.getElementById('cursoIcone').value = icone;
            if(document.getElementById('iconePreviewIconCurso')) {
                document.getElementById('iconePreviewIconCurso').className = 'fas ' + icone;
            }

            // Carregar PDFs existentes
            carregarPDFsExistentes(id);

            if(saidasPorCurso[curso.id]?.length) {
                saidasPorCurso[curso.id].forEach(s => adicionarSaida(s));
            }
            if(projetosPorCurso[curso.id]?.length) {
                projetosPorCurso[curso.id].forEach(p => adicionarProjecto(p));
            }
            if(curso.imagem_hero) {
                document.getElementById('miniaturaImagem').src = '../uploads/cursos/' + curso.imagem_hero;
                document.getElementById('previewImagem').classList.add('ativo');
            }
        }
    }

    const sel = document.getElementById('cursoAreaId');
    if(sel) {
        const val = sel.value;
        sel.innerHTML = '<option value="">Selecione...</option>';
        areas.forEach(a => {
            sel.innerHTML += `<option value="${a.id}" ${val == a.id ? 'selected' : ''}>${escapeHtml(a.nome)}</option>`;
        });
    }

    modal.style.display = 'flex';
    setupPDFUploads();
}

function fecharModalCurso() {
    document.getElementById('modalCurso').style.display='none';
}

function editarCurso(id) {
    abrirModalCurso(id);
}

function eliminarCurso(id) {
    confirmarAcao(
        'Confirmar eliminação',
        'Eliminar este curso?',
        () => {
            fetch('processos/processar-curso.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`action=delete_curso&curso_id=${id}`
            })
            .then(r=>r.json())
            .then(d=>{
                if(d.success){
                    mostrarNotificacao(d.message,'success');
                    location.reload();
                } else {
                    mostrarNotificacao(d.message,'error');
                }
            })
            .catch(()=>mostrarNotificacao('Erro ao comunicar','error'));
        },
        'eliminar'
    );
}

// ============================================
// SALVAR CURSO (COM PDFs E REMOÇÃO)
// ============================================
async function salvarCurso(event) {
    if (event) event.preventDefault();

    console.log("=== INICIANDO SALVAMENTO DO CURSO ===");

    const id = document.getElementById('cursoId').value;
    const nome = document.getElementById('cursoNome').value.trim();
    const area_id = document.getElementById('cursoAreaId').value;
    const duracao = document.getElementById('cursoDuracao').value;
    const estado = document.getElementById('cursoEstado').value;
    const cor = document.getElementById('cursoCor').value;
    const descricao_curta = document.getElementById('cursoDescricaoCurta').value;
    const sobre_descricao = document.getElementById('cursoSobreDescricao').value;
    const objetivo = document.getElementById('cursoObjetivo').value;
    const competencias = document.getElementById('cursoCompetencias').value;
    const competencias_card = document.getElementById('cursoCompetenciasCard').value;
    const certificacao = document.getElementById('cursoCertificacao').value;
    const destaque = document.getElementById('cursoDestaque').checked ? 1 : 0;
    const icone_classe = document.getElementById('cursoIcone').value;

    // Validação básica
    if (!nome) {
        mostrarNotificacao('O nome do curso é obrigatório.', 'error');
        return false;
    }
    if (!area_id) {
        mostrarNotificacao('Selecione uma área.', 'error');
        return false;
    }
    if (!duracao) {
        mostrarNotificacao('A duração é obrigatória.', 'error');
        return false;
    }

    console.log("Dados do curso:", { id, nome, area_id, duracao, estado });

    const formData = new FormData();
    formData.append('action', id ? 'update_curso' : 'create_curso');
    if (id) formData.append('curso_id', id);
    formData.append('nome', nome);
    formData.append('area_id', area_id);
    formData.append('duracao', duracao);
    formData.append('estado', estado);
    formData.append('cor', cor);
    formData.append('descricao_curta', descricao_curta);
    formData.append('sobre_descricao', sobre_descricao);
    formData.append('objetivo', objetivo);
    formData.append('competencias_descricao', competencias);
    formData.append('competencias_card', competencias_card);
    formData.append('certificacao_descricao', certificacao);
    formData.append('destaque', destaque);
    formData.append('icone_classe', icone_classe);

    // Imagem de capa
    const imgInput = document.getElementById('imagemInput');
    if (imgInput && imgInput.files.length > 0) {
        formData.append('imagem_hero', imgInput.files[0]);
        console.log("Imagem adicionada:", imgInput.files[0].name);
    }

    // PDFs (classes 0, 10, 11, 12, 13)
    const classes = [0, 10, 11, 12, 13];
    for(let i of classes) {
        if(pdfFiles[i]) {
            formData.append(`pdf_${i}`, pdfFiles[i]);
            console.log(`PDF ${i === 0 ? 'Resumo Geral' : i + 'ª classe'} adicionado:`, pdfFiles[i].name);
        }
        if(pdfParaRemover[i]) {
            formData.append(`remover_pdf_${i}`, '1');
            console.log(`PDF ${i === 0 ? 'Resumo Geral' : i + 'ª classe'} marcado para remoção`);
        }
    }

    // Saídas
    const saidas = [];
    document.querySelectorAll('#containerSaidas .item-dinamico').forEach(item => {
        const titulo = item.querySelector('.saida-titulo')?.value || '';
        if(titulo) {
            const comps = (item.querySelector('.saida-competencias')?.value || '').split(',').map(c => c.trim()).filter(c => c);
            saidas.push({
                id: item.querySelector('.saida-id')?.value || '',
                titulo,
                descricao: item.querySelector('.saida-descricao')?.value || '',
                competencias: comps,
                imagem_url: item.querySelector('.saida-imagem-url')?.value || ''
            });
        }
    });
    formData.append('saidas', JSON.stringify(saidas));

    // Projetos
    const projetos = [];
    document.querySelectorAll('#containerProjectos .item-dinamico').forEach(item => {
        const titulo = item.querySelector('.projeto-titulo')?.value || '';
        if(titulo) {
            projetos.push({
                id: item.querySelector('.projeto-id')?.value || '',
                titulo,
                categoria: item.querySelector('.projeto-categoria')?.value || 'Geral',
                descricao: item.querySelector('.projeto-descricao')?.value || '',
                imagem_url: item.querySelector('.projeto-imagem-url')?.value || '',
                ano: item.querySelector('.projeto-ano')?.value || new Date().getFullYear(),
                autor: item.querySelector('.projeto-autor')?.value || 'Aluno IPIKK'
            });
        }
    });
    formData.append('projetos', JSON.stringify(projetos));

    // Desabilitar botão para evitar duplo clique
    const btnSalvar = document.getElementById('btnSalvarCurso');
    const textoOriginal = btnSalvar ? btnSalvar.innerHTML : '';
    if (btnSalvar) {
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';
    }

    mostrarNotificacao('A guardar curso...', 'info');

    try {
        const response = await fetch('processos/processar-curso.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        console.log("Resposta do servidor:", data);

        if (data.success) {
            mostrarNotificacao(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarNotificacao(data.message || 'Erro ao salvar curso', 'error');
            if (btnSalvar) {
                btnSalvar.disabled = false;
                btnSalvar.innerHTML = textoOriginal;
            }
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao comunicar com o servidor: ' + error.message, 'error');
        if (btnSalvar) {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = textoOriginal;
        }
    }

    return false;
}

// ============================================
// SAÍDAS E PROJETOS
// ============================================

function adicionarSaida(dados = null) {
    const container = document.getElementById('containerSaidas');
    const div = document.createElement('div');
    div.className = 'item-dinamico';
    div.style.cssText = 'margin-bottom:20px;padding:15px;border:1px solid var(--cinza-medio);border-radius:8px;position:relative;';
    const imagemPreview = dados?.imagem_url || '';
    div.innerHTML = `
        <input type="hidden" class="saida-id" value="${escapeHtml(dados?.id || '')}">
        <input type="hidden" class="saida-imagem-url" value="${escapeHtml(imagemPreview)}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <strong style="color:var(--azul-primario);">Saida Profissional</strong>
            <button type="button" class="btn-remover-saida" style="background:#dc3545;color:white;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Titulo da Saida *</label>
                <input type="text" class="controle-form saida-titulo" placeholder="Ex: Desenhador Projectista" value="${escapeHtml(dados?.titulo || '')}">
            </div>
            <div class="grupo-form">
                <label>Imagem Representativa</label>
                <div class="area-upload-imagem" style="border:2px dashed var(--cinza-medio);border-radius:8px;padding:10px;text-align:center;cursor:pointer;">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p style="font-size:12px;">Clique para fazer upload</p>
                    <input type="file" class="upload-imagem-saida" accept="image/*" style="display:none;">
                </div>
                <div class="preview-imagem-saida" style="margin-top:10px;${imagemPreview?'':'display:none;'}">
                    <img src="${imagemPreview?normalizarUrlMidiaAdmin(imagemPreview):''}" style="max-width:100px;max-height:80px;border-radius:8px;">
                    <button type="button" class="btn-remover-imagem" style="background:#dc3545;color:white;border:none;padding:2px 8px;border-radius:4px;margin-left:10px;">Remover</button>
                </div>
            </div>
        </div>
        <div class="grupo-form">
            <label>Descricao</label>
            <textarea class="controle-form saida-descricao" rows="2">${escapeHtml(dados?.descricao || '')}</textarea>
        </div>
        <div class="grupo-form">
            <label>Competencias (separadas por virgula)</label>
            <input type="text" class="controle-form saida-competencias" placeholder="Ex: CAD, BIM, Projetos" value="${escapeHtml(dados?.competencias ? dados.competencias.join(', ') : '')}">
        </div>
    `;
    container.appendChild(div);

    const uploadArea = div.querySelector('.area-upload-imagem');
    const fileInput = div.querySelector('.upload-imagem-saida');
    const previewDiv = div.querySelector('.preview-imagem-saida');
    const previewImg = previewDiv.querySelector('img');
    const urlInput = div.querySelector('.saida-imagem-url');

    uploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async function(e) {
        const file = e.target.files[0]; if(!file) return;
        const fd = new FormData(); fd.append('action','upload_imagem_saida'); fd.append('imagem',file);
        try {
            const r = await fetch('processos/processar-curso.php',{method:'POST',body:fd});
            const d = await r.json();
            if(d.success){
                previewImg.src=normalizarUrlMidiaAdmin(d.url);
                previewDiv.style.display='block';
                urlInput.value=d.url;
                mostrarNotificacao('Imagem carregada!','success');
            } else {
                mostrarNotificacao('Erro no upload','error');
            }
        } catch(err){ mostrarNotificacao('Erro no upload','error'); }
    });

    div.querySelector('.btn-remover-saida')?.addEventListener('click', () => {
        confirmarAcao('Confirmar eliminação', 'Eliminar esta saída profissional?', () => div.remove(), 'eliminar');
    });

    div.querySelector('.btn-remover-imagem')?.addEventListener('click', () => {
        previewDiv.style.display='none';
        previewImg.src='';
        urlInput.value='';
        fileInput.value='';
    });
}

function adicionarProjecto(dados = null) {
    const container = document.getElementById('containerProjectos');
    const div = document.createElement('div');
    div.className = 'item-dinamico';
    div.style.cssText = 'margin-bottom:20px;padding:15px;border:1px solid var(--cinza-medio);border-radius:8px;position:relative;';
    const imagemPreview = dados?.imagem_url || '';
    div.innerHTML = `
        <input type="hidden" class="projeto-id" value="${escapeHtml(dados?.id || '')}">
        <input type="hidden" class="projeto-imagem-url" value="${escapeHtml(imagemPreview)}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <strong style="color:var(--azul-primario);">Projecto</strong>
            <button type="button" class="btn-remover-projeto" style="background:#dc3545;color:white;border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Titulo do Projecto *</label>
                <input type="text" class="controle-form projeto-titulo" placeholder="Ex: Casa Familiar" value="${escapeHtml(dados?.titulo || '')}">
            </div>
            <div class="grupo-form">
                <label>Categoria</label>
                <select class="controle-form projeto-categoria">
                    <option value="Geral" ${dados?.categoria==='Geral'?'selected':''}>Geral</option>
                    <option value="Projecto Final" ${dados?.categoria==='Projecto Final'?'selected':''}>Projecto Final</option>
                    <option value="Estruturas" ${dados?.categoria==='Estruturas'?'selected':''}>Estruturas</option>
                    <option value="Reabilitacao" ${dados?.categoria==='Reabilitacao'?'selected':''}>Reabilitacao</option>
                    <option value="Design" ${dados?.categoria==='Design'?'selected':''}>Design</option>
                </select>
            </div>
        </div>
        <div class="linha-form">
            <div class="grupo-form">
                <label>Ano</label>
                <input type="text" class="controle-form projeto-ano" placeholder="Ex: 2024" value="${escapeHtml(dados?.ano || new Date().getFullYear())}">
            </div>
            <div class="grupo-form">
                <label>Autor</label>
                <input type="text" class="controle-form projeto-autor" placeholder="Ex: Joao Silva" value="${escapeHtml(dados?.autor || 'Aluno IPIKK')}">
            </div>
        </div>
        <div class="grupo-form">
            <label>Imagem do Projecto</label>
            <div class="area-upload-imagem-projeto" style="border:2px dashed var(--cinza-medio);border-radius:8px;padding:10px;text-align:center;cursor:pointer;">
                <i class="fas fa-cloud-upload-alt"></i>
                <p style="font-size:12px;">Clique para fazer upload da imagem</p>
                <input type="file" class="upload-imagem-projeto" accept="image/*" style="display:none;">
            </div>
            <div class="preview-imagem-projeto" style="margin-top:10px;${imagemPreview?'':'display:none;'}">
                <img src="${imagemPreview?normalizarUrlMidiaAdmin(imagemPreview):''}" style="max-width:100px;max-height:80px;border-radius:8px;">
                <button type="button" class="btn-remover-imagem-projeto" style="background:#dc3545;color:white;border:none;padding:2px 8px;border-radius:4px;margin-left:10px;">Remover</button>
            </div>
        </div>
        <div class="grupo-form">
            <label>Descricao</label>
            <textarea class="controle-form projeto-descricao" rows="2">${escapeHtml(dados?.descricao || '')}</textarea>
        </div>
    `;
    container.appendChild(div);

    const uploadArea = div.querySelector('.area-upload-imagem-projeto');
    const fileInput = div.querySelector('.upload-imagem-projeto');
    const previewDiv = div.querySelector('.preview-imagem-projeto');
    const previewImg = previewDiv.querySelector('img');
    const urlInput = div.querySelector('.projeto-imagem-url');

    uploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async function(e) {
        const file = e.target.files[0]; if(!file) return;
        const fd = new FormData(); fd.append('action','upload_imagem_projeto'); fd.append('imagem',file);
        try {
            const r = await fetch('processos/processar-curso.php',{method:'POST',body:fd});
            const d = await r.json();
            if(d.success){
                previewImg.src=normalizarUrlMidiaAdmin(d.url);
                previewDiv.style.display='block';
                urlInput.value=d.url;
                mostrarNotificacao('Imagem carregada!','success');
            } else {
                mostrarNotificacao('Erro no upload','error');
            }
        } catch(err){ mostrarNotificacao('Erro no upload','error'); }
    });

    div.querySelector('.btn-remover-projeto')?.addEventListener('click', () => {
        confirmarAcao('Confirmar eliminação', 'Eliminar este projecto?', () => div.remove(), 'eliminar');
    });

    div.querySelector('.btn-remover-imagem-projeto')?.addEventListener('click', () => {
        previewDiv.style.display='none';
        previewImg.src='';
        urlInput.value='';
        fileInput.value='';
    });
}

function removerImagem() {
    document.getElementById('imagemInput').value='';
    document.getElementById('previewImagem').classList.remove('ativo');
}

function removerAreaImagem(){
    document.getElementById('areaImagemInput').value='';
    document.getElementById('previewAreaImagem').classList.remove('ativo');
}

function gerarRelatorio(){ alert(`RELATORIO DE CURSOS\n\nTotal: ${cursos.length} cursos`); }
function imprimirCatalogo(){ window.print(); }

// ============================================
// INICIALIZAÇÃO
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    renderizarAreas();
    renderizarCursos();
    setupPDFUploads();

    document.getElementById('btnNovoCurso')?.addEventListener('click', ()=>abrirModalCurso());
    document.getElementById('filtroArea')?.addEventListener('change', function(){ filtroAreaAtual=this.value||null; renderizarAreas(); renderizarCursos(); });
    document.getElementById('filtroEstado')?.addEventListener('change', renderizarCursos);
    document.getElementById('campoBusca')?.addEventListener('input', renderizarCursos);
    document.getElementById('btnSelecionarTodosCursos')?.addEventListener('click', selecionarTodosCursos);
    document.getElementById('btnEliminarSelecionadosCursos')?.addEventListener('click', eliminarCursosSelecionados);
    document.getElementById('btnImportarCursos')?.addEventListener('click', importarCursos);

    document.addEventListener('change', function(e) {
        if(!e.target.classList.contains('checkbox-curso')) return;
        const id = parseInt(e.target.dataset.id);
        const card = e.target.closest('.card-curso');
        if(e.target.checked) {
            cursosSelecionados.add(id);
            card?.classList.add('selecionado');
        } else {
            cursosSelecionados.delete(id);
            card?.classList.remove('selecionado');
        }
        atualizarEstadoBotoesMassaCursos();
    });

    document.querySelectorAll('.aba-modal').forEach(btn=>{
        btn.addEventListener('click',function(){
            const aba=this.dataset.aba;
            document.querySelectorAll('.aba-modal').forEach(b=>b.classList.remove('ativo'));
            document.querySelectorAll('.conteudo-aba').forEach(c=>c.classList.remove('ativo'));
            this.classList.add('ativo');
            document.querySelector(`.conteudo-aba[data-aba="${aba}"]`)?.classList.add('ativo');
        });
    });

    document.getElementById('cursoDescricaoCurta')?.addEventListener('input',function(){
        document.getElementById('contadorDescricaoCurta').innerHTML=this.value.length+' / 200';
    });

    document.getElementById('imagemInput')?.addEventListener('change',e=>{
        const f=e.target.files[0]; if(!f)return;
        const r=new FileReader();
        r.onload=ev=>{
            document.getElementById('miniaturaImagem').src=ev.target.result;
            document.getElementById('previewImagem').classList.add('ativo');
        };
        r.readAsDataURL(f);
    });

    document.getElementById('areaImagemInput')?.addEventListener('change',e=>{
        const f=e.target.files[0]; if(!f)return;
        const r=new FileReader();
        r.onload=ev=>{
            document.getElementById('miniaturaAreaImagem').src=ev.target.result;
            document.getElementById('previewAreaImagem').classList.add('ativo');
        };
        r.readAsDataURL(f);
    });

    document.getElementById('areaIcone')?.addEventListener('input',function(){
        document.getElementById('iconePreviewIcon').className='fas '+this.value.trim();
    });

    document.getElementById('areaCor')?.addEventListener('input',function(){
        document.getElementById('previewCor').style.background=this.value;
    });

    document.querySelectorAll('.sugestao-icone').forEach(btn=>{
        btn.addEventListener('click',function(){
            const ic=this.dataset.icone;
            document.getElementById('areaIcone').value=ic;
            document.getElementById('iconePreviewIcon').className='fas '+ic;
        });
    });

    const iconeInputCurso = document.getElementById('cursoIcone');
    const iconePreviewCurso = document.getElementById('iconePreviewIconCurso');
    if(iconeInputCurso && iconePreviewCurso) {
        iconeInputCurso.addEventListener('input', function() {
            iconePreviewCurso.className = 'fas ' + (this.value.trim() || 'fa-graduation-cap');
        });
        document.querySelectorAll('.sugestao-icone').forEach(btn => {
            btn.addEventListener('click', function() {
                iconeInputCurso.value = this.dataset.icone;
                iconePreviewCurso.className = 'fas ' + this.dataset.icone;
            });
        });
    }

    ['modalArea','modalCurso','modalPreviewArea','modalPreviewCurso','modalIframeCurso'].forEach(id=>{
        const m=document.getElementById(id);
        if(m) m.addEventListener('click',e=>{
            if(e.target===m){
                if(id==='modalIframeCurso') fecharModalIframe();
                else m.style.display='none';
            }
        });
    });

    document.addEventListener('keydown',e=>{
        if(e.key!=='Escape')return;
        ['modalArea','modalCurso','modalPreviewArea','modalPreviewCurso','modalIframeCurso'].forEach(id=>{
            const m=document.getElementById(id);
            if(m?.style.display==='flex'){
                if(id==='modalIframeCurso') fecharModalIframe();
                else m.style.display='none';
            }
        });
    });

    const btnSalvarCurso = document.getElementById('btnSalvarCurso');
    if (btnSalvarCurso) {
        btnSalvarCurso.addEventListener('click', function(e) {
            e.preventDefault();
            salvarCurso(e);
        });
    }

    const formCurso = document.getElementById('formularioCurso');
    if (formCurso) {
        formCurso.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarCurso(e);
        });
    }
});
</script>
</body>
</html>
