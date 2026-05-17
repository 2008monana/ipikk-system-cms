<?php
/**
 * API para incrementar visualizações via AJAX
 * Chamada por: incrementar-visualizacao.php?tipo=noticia&id=1
 */

require_once 'config/index.php';

header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? '';
$id = intval($_GET['id'] ?? 0);

$response = ['success' => false, 'message' => 'Parâmetros inválidos'];

if ($tipo === 'noticia' && $id > 0) {
    incrementarVisualizacaoNoticia($id);
    $response = ['success' => true, 'message' => 'Visualização de notícia registrada'];
    
} elseif ($tipo === 'curso' && $id > 0) {
    incrementarVisualizacaoCurso($id);
    $response = ['success' => true, 'message' => 'Visualização de curso registrada'];
    
} elseif ($tipo === 'area' && $id > 0) {
    incrementarVisualizacaoArea($id);
    $response = ['success' => true, 'message' => 'Visualização de área registrada'];
}

echo json_encode($response);
?>