<?php
/**
 * API para exportar relatórios de uniformes em PDF/HTML
 * Baseado no sistema de ranking que já funciona
 * CONFIGURADO PARA UTF-8 E TIMEZONE BRASIL
 */

// ========== CONFIGURAÇÕES UTF-8 E TIMEZONE ==========
// 1. PRIMEIRA COISA: Configurar timezone do Brasil
date_default_timezone_set('America/Sao_Paulo');

// 2. Configurar encoding interno do PHP
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// 3. Configurar locale para português brasileiro
setlocale(LC_ALL, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// 4. Iniciar sessão
session_start();

// 5. Headers UTF-8 OBRIGATÓRIOS
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Verificação de admin
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once "conexao.php";

// ========== CONFIGURAÇÃO UTF-8 NO BANCO ==========
// 6. Configurar charset da conexão MySQL
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4'); // utf8mb4 é melhor que utf8
}

// Verificar se a tabela unidade existe
$check_table = $conn->query("SHOW TABLES LIKE 'unidade'");
$has_unidades_table = $check_table->num_rows > 0;

// Obter ação
$action = $_REQUEST['action'] ?? '';

// Roteador de ações
switch($action) {
    case 'relatorio_geral':
        gerarRelatorioGeral();
        break;
    
    case 'relatorio_por_turma':
        gerarRelatorioPorTurma();
        break;
    
    case 'relatorio_tamanhos':
        gerarRelatorioTamanhos();
        break;
    
    case 'relatorio_incompletos':
        gerarRelatorioIncompletos();
        break;
    
    case 'exportar_todos':
        exportarTodosRelatorios();
        break;
    
    default:
        gerarRelatorioGeral();
        break;
}

// ====================================
// FUNÇÕES PRINCIPAIS DE RELATÓRIOS
// ====================================

function gerarRelatorioGeral() {
    global $conn;
    
    try {
        // Parâmetros da consulta
        $turma_id = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
        $unidade_id = isset($_GET['unidade_id']) && !empty($_GET['unidade_id']) ? intval($_GET['unidade_id']) : null;
        
        // Buscar dados dos uniformes
        $uniformes_data = buscarDadosUniformes($turma_id, $unidade_id);
        
        // ========== HEADERS PARA DOWNLOAD UTF-8 ==========
        $nome_arquivo = "relatorio_geral_uniformes_" . date('Y-m-d_H-i-s') . ".html";
        
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        
        // Gerar HTML para impressão
        echo gerarHTMLRelatorioGeral($uniformes_data, $turma_id, $unidade_id);
        
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório geral: " . $e->getMessage());
        http_response_code(500);
        echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function gerarRelatorioPorTurma() {
    global $conn;
    
    try {
        $turma_id = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
        $unidade_id = isset($_GET['unidade_id']) && !empty($_GET['unidade_id']) ? intval($_GET['unidade_id']) : null;
        
        $uniformes_data = buscarDadosUniformes($turma_id, $unidade_id);
        
        $nome_arquivo = "relatorio_por_turma_uniformes_" . date('Y-m-d_H-i-s') . ".html";
        
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        
        echo gerarHTMLRelatorioPorTurma($uniformes_data, $turma_id, $unidade_id);
        
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório por turma: " . $e->getMessage());
        http_response_code(500);
        echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function gerarRelatorioTamanhos() {
    global $conn;
    
    try {
        $tamanhos_data = buscarDadosTamanhos();
        
        $nome_arquivo = "relatorio_tamanhos_uniformes_" . date('Y-m-d_H-i-s') . ".html";
        
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        
        echo gerarHTMLRelatorioTamanhos($tamanhos_data);
        
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório de tamanhos: " . $e->getMessage());
        http_response_code(500);
        echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function gerarRelatorioIncompletos() {
    global $conn;
    
    try {
        $turma_id = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
        $unidade_id = isset($_GET['unidade_id']) && !empty($_GET['unidade_id']) ? intval($_GET['unidade_id']) : null;
        
        $uniformes_data = buscarDadosUniformesIncompletos($turma_id, $unidade_id);
        
        $nome_arquivo = "relatorio_incompletos_uniformes_" . date('Y-m-d_H-i-s') . ".html";
        
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        
        echo gerarHTMLRelatorioIncompletos($uniformes_data, $turma_id, $unidade_id);
        
    } catch (Exception $e) {
        error_log("Erro ao gerar relatório de incompletos: " . $e->getMessage());
        http_response_code(500);
        echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function exportarTodosRelatorios() {
    // Redirecionar para a primeira página com todos os relatórios
    $url_base = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Exportando Todos os Relatórios</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .loading { margin: 20px; }
            .relatorio-item { margin: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
            a { color: #dc3545; text-decoration: none; font-weight: bold; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>🔥 Exportação de Todos os Relatórios de Uniformes</h1>
        <p>Clique nos links abaixo para baixar cada relatório:</p>
        
        <div class="relatorio-item">
            <a href="' . $url_base . '?action=relatorio_geral" target="_blank">
                📋 1. Relatório Geral de Uniformes
            </a>
        </div>
        
        <div class="relatorio-item">
            <a href="' . $url_base . '?action=relatorio_por_turma" target="_blank">
                📚 2. Relatório de Uniformes por Turma
            </a>
        </div>
        
        <div class="relatorio-item">
            <a href="' . $url_base . '?action=relatorio_tamanhos" target="_blank">
                📐 3. Relatório de Tamanhos para Compras
            </a>
        </div>
        
        <div class="relatorio-item">
            <a href="' . $url_base . '?action=relatorio_incompletos" target="_blank">
                ⚠️ 4. Relatório de Uniformes Incompletos
            </a>
        </div>
        
        <p style="margin-top: 30px; color: #666; font-size: 12px;">
            Cada relatório será aberto em uma nova aba e baixado automaticamente
        </p>
        
        <script>
            // Abrir todos os relatórios automaticamente com delay
            setTimeout(function() {
                window.open("' . $url_base . '?action=relatorio_geral", "_blank");
            }, 1000);
            
            setTimeout(function() {
                window.open("' . $url_base . '?action=relatorio_por_turma", "_blank");
            }, 2000);
            
            setTimeout(function() {
                window.open("' . $url_base . '?action=relatorio_tamanhos", "_blank");
            }, 3000);
            
            setTimeout(function() {
                window.open("' . $url_base . '?action=relatorio_incompletos", "_blank");
            }, 4000);
        </script>
    </body>
    </html>';
}

// ====================================
// FUNÇÕES DE BUSCA DE DADOS
// ====================================

function buscarDadosUniformes($turma_id = null, $unidade_id = null) {
    global $conn, $has_unidades_table;
    
    // ========== QUERY COM CHARSET UTF-8 E TIMEZONE ==========
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET time_zone = '-03:00'"); // Forçar horário de Brasília no MySQL
    
    if ($has_unidades_table) {
        $sql = "SELECT 
                    a.id,
                    a.nome as aluno_nome,
                    a.numero_matricula,
                    a.tamanho_camisa,
                    a.tamanho_calca,
                    a.tamanho_calcado,
                    t.nome_turma as turma_nome,
                    t.id as turma_id,
                    u.nome as unidade_nome,
                    u.id as unidade_id
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN turma t ON m.turma = t.id
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE a.status = 'ativo'";
    } else {
        $sql = "SELECT 
                    a.id,
                    a.nome as aluno_nome,
                    a.numero_matricula,
                    a.tamanho_camisa,
                    a.tamanho_calca,
                    a.tamanho_calcado,
                    t.nome_turma as turma_nome,
                    t.id as turma_id,
                    'Não informado' as unidade_nome,
                    NULL as unidade_id
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN turma t ON m.turma = t.id
                WHERE a.status = 'ativo'";
    }
    
    if ($turma_id) {
        $sql .= " AND t.id = " . intval($turma_id);
    }
    
    if ($unidade_id && $has_unidades_table) {
        $sql .= " AND u.id = " . intval($unidade_id);
    }
    
    $sql .= " ORDER BY u.nome, t.nome_turma, a.nome";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro na consulta de uniformes: " . $conn->error);
    }
    
    $uniformes = [];
    while ($row = $result->fetch_assoc()) {
        // ========== GARANTIR UTF-8 NOS DADOS ==========
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        
        // Determinar status do uniforme
        $row['status_uniforme'] = determinarStatusUniforme($row);
        $row['status_classe'] = strtolower(str_replace(' ', '_', $row['status_uniforme']));
        
        $uniformes[] = $row;
    }
    
    return $uniformes;
}

function buscarDadosUniformesIncompletos($turma_id = null, $unidade_id = null) {
    global $conn, $has_unidades_table;
    
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET time_zone = '-03:00'");
    
    if ($has_unidades_table) {
        $sql = "SELECT 
                    a.id,
                    a.nome as aluno_nome,
                    a.numero_matricula,
                    a.tamanho_camisa,
                    a.tamanho_calca,
                    a.tamanho_calcado,
                    t.nome_turma as turma_nome,
                    t.id as turma_id,
                    u.nome as unidade_nome,
                    u.id as unidade_id
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN turma t ON m.turma = t.id
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE a.status = 'ativo' AND (
                    a.tamanho_camisa IS NULL OR a.tamanho_camisa = '' OR
                    a.tamanho_calca IS NULL OR a.tamanho_calca = '' OR
                    a.tamanho_calcado IS NULL OR a.tamanho_calcado = ''
                )";
    } else {
        $sql = "SELECT 
                    a.id,
                    a.nome as aluno_nome,
                    a.numero_matricula,
                    a.tamanho_camisa,
                    a.tamanho_calca,
                    a.tamanho_calcado,
                    t.nome_turma as turma_nome,
                    t.id as turma_id,
                    'Não informado' as unidade_nome,
                    NULL as unidade_id
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id
                LEFT JOIN turma t ON m.turma = t.id
                WHERE a.status = 'ativo' AND (
                    a.tamanho_camisa IS NULL OR a.tamanho_camisa = '' OR
                    a.tamanho_calca IS NULL OR a.tamanho_calca = '' OR
                    a.tamanho_calcado IS NULL OR a.tamanho_calcado = ''
                )";
    }
    
    if ($turma_id) {
        $sql .= " AND t.id = " . intval($turma_id);
    }
    
    if ($unidade_id && $has_unidades_table) {
        $sql .= " AND u.id = " . intval($unidade_id);
    }
    
    $sql .= " ORDER BY u.nome, t.nome_turma, a.nome";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro na consulta de uniformes incompletos: " . $conn->error);
    }
    
    $uniformes = [];
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        
        $row['status_uniforme'] = determinarStatusUniforme($row);
        $row['status_classe'] = strtolower(str_replace(' ', '_', $row['status_uniforme']));
        
        $uniformes[] = $row;
    }
    
    return $uniformes;
}

function buscarDadosTamanhos() {
    global $conn;
    
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET time_zone = '-03:00'");
    
    $tamanhos = [];
    
    // Camisas
    $result = $conn->query("SELECT tamanho_camisa, COUNT(*) as quantidade FROM alunos 
                          WHERE tamanho_camisa IS NOT NULL AND tamanho_camisa != '' AND status = 'ativo'
                          GROUP BY tamanho_camisa ORDER BY tamanho_camisa");
    
    $tamanhos['camisas'] = [];
    $tamanhos['total_camisas'] = 0;
    while($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $tamanhos['camisas'][] = $row;
        $tamanhos['total_camisas'] += $row['quantidade'];
    }
    
    // Calças
    $result = $conn->query("SELECT tamanho_calca, COUNT(*) as quantidade FROM alunos 
                          WHERE tamanho_calca IS NOT NULL AND tamanho_calca != '' AND status = 'ativo'
                          GROUP BY tamanho_calca ORDER BY tamanho_calca");
    
    $tamanhos['calcas'] = [];
    $tamanhos['total_calcas'] = 0;
    while($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $tamanhos['calcas'][] = $row;
        $tamanhos['total_calcas'] += $row['quantidade'];
    }
    
    // Calçados
    $result = $conn->query("SELECT tamanho_calcado, COUNT(*) as quantidade FROM alunos 
                          WHERE tamanho_calcado IS NOT NULL AND tamanho_calcado != '' AND status = 'ativo'
                          GROUP BY tamanho_calcado ORDER BY CAST(tamanho_calcado AS UNSIGNED)");
    
    $tamanhos['calcados'] = [];
    $tamanhos['total_calcados'] = 0;
    while($row = $result->fetch_assoc()) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $tamanhos['calcados'][] = $row;
        $tamanhos['total_calcados'] += $row['quantidade'];
    }
    
    return $tamanhos;
}

// ====================================
// FUNÇÕES DE GERAÇÃO DE HTML
// ====================================

function gerarHTMLRelatorioGeral($uniformes_data, $turma_id = null, $unidade_id = null) {
    // ========== FORMATAÇÃO UTF-8 E DATA BRASILEIRA ==========
    date_default_timezone_set('America/Sao_Paulo');
    
    $data_atual = date('d/m/Y \à\s H:i:s');
    $hora_geracao = date('H:i:s');
    $data_geracao = date('d/m/Y');
    
    $total_alunos = count($uniformes_data);
    $total_completos = count(array_filter($uniformes_data, function($uniforme) {
        return $uniforme['status_uniforme'] === 'Completo';
    }));
    $total_incompletos = count(array_filter($uniformes_data, function($uniforme) {
        return $uniforme['status_uniforme'] === 'Incompleto';
    }));
    $total_pendentes = count(array_filter($uniformes_data, function($uniforme) {
        return $uniforme['status_uniforme'] === 'Pendente';
    }));
    
    $filtro_info = $turma_id ? "Turma Específica" : "Todas as Turmas";
    if ($unidade_id) {
        $filtro_info .= " - Unidade Específica";
    }
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Relatório Geral de Uniformes - <?= htmlspecialchars($data_atual, ENT_QUOTES, 'UTF-8') ?></title>
        <?= obterCSSBase() ?>
    </head>
    <body>
        <div class="header">
            <h1>👕 Relatório Geral de Uniformes</h1>
            <h2>Programa Bombeiro Mirim do Estado de Goiás</h2>
            <div>
                <strong>Data de Geração:</strong> <?= htmlspecialchars($data_geracao, ENT_QUOTES, 'UTF-8') ?> às <?= htmlspecialchars($hora_geracao, ENT_QUOTES, 'UTF-8') ?> (Horário de Brasília)<br>
                <strong>Filtros:</strong> <?= htmlspecialchars($filtro_info, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        
        <div class="info-geral">
            <div class="info-item">
                <strong><?= $total_alunos ?></strong>
                <span>Total de Alunos</span>
            </div>
            <div class="info-item completo">
                <strong><?= $total_completos ?></strong>
                <span>Uniformes Completos</span>
            </div>
            <div class="info-item incompleto">
                <strong><?= $total_incompletos ?></strong>
                <span>Uniformes Incompletos</span>
            </div>
            <div class="info-item pendente">
                <strong><?= $total_pendentes ?></strong>
                <span>Uniformes Pendentes</span>
            </div>
        </div>
        
        <?php if (empty($uniformes_data)): ?>
            <div class="sem-dados">
                <h3>Nenhum dado de uniforme encontrado</h3>
                <p>Não há dados de uniformes para os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <table class="uniformes-table">
                <thead>
                    <tr>
                        <th>Nome do Aluno</th>
                        <th>Matrícula</th>
                        <th>Turma</th>
                        <th>Unidade</th>
                        <th>Camisa</th>
                        <th>Calça</th>
                        <th>Calçado</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uniformes_data as $uniforme): ?>
                        <tr>
                            <td class="aluno-nome"><?= htmlspecialchars($uniforme['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="matricula"><?= htmlspecialchars($uniforme['numero_matricula'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="turma"><?= htmlspecialchars($uniforme['turma_nome'] ?: 'Sem turma', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="unidade"><?= htmlspecialchars($uniforme['unidade_nome'] ?: 'Sem unidade', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="tamanho <?= $uniforme['tamanho_camisa'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_camisa'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="tamanho <?= $uniforme['tamanho_calca'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_calca'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="tamanho <?= $uniforme['tamanho_calcado'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_calcado'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <span class="status <?= htmlspecialchars($uniforme['status_classe'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($uniforme['status_uniforme'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?= obterRodape($data_atual) ?>
        
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    </body>
    </html>
    <?php
    
    return ob_get_clean();
}

function gerarHTMLRelatorioPorTurma($uniformes_data, $turma_id = null, $unidade_id = null) {
    date_default_timezone_set('America/Sao_Paulo');
    
    $data_atual = date('d/m/Y \à\s H:i:s');
    $hora_geracao = date('H:i:s');
    $data_geracao = date('d/m/Y');
    
    // Agrupar por turma
    $turmas = [];
    foreach ($uniformes_data as $uniforme) {
        $turma_nome = $uniforme['turma_nome'] ?: 'Sem turma';
        if (!isset($turmas[$turma_nome])) {
            $turmas[$turma_nome] = [];
        }
        $turmas[$turma_nome][] = $uniforme;
    }
    
    $total_alunos = count($uniformes_data);
    $total_turmas = count($turmas);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Relatório por Turma - Uniformes</title>
        <?= obterCSSBase() ?>
    </head>
    <body>
        <div class="header">
            <h1>📚 Relatório de Uniformes por Turma</h1>
            <h2>Programa Bombeiro Mirim do Estado de Goiás</h2>
            <div>
                <strong>Data de Geração:</strong> <?= htmlspecialchars($data_geracao, ENT_QUOTES, 'UTF-8') ?> às <?= htmlspecialchars($hora_geracao, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        
        <div class="info-geral">
            <div class="info-item">
                <strong><?= $total_alunos ?></strong>
                <span>Total de Alunos</span>
            </div>
            <div class="info-item">
                <strong><?= $total_turmas ?></strong>
                <span>Total de Turmas</span>
            </div>
        </div>
        
        <?php if (empty($uniformes_data)): ?>
            <div class="sem-dados">
                <h3>Nenhum dado encontrado</h3>
            </div>
        <?php else: ?>
            <?php foreach ($turmas as $nome_turma => $alunos): ?>
                <div class="turma-section">
                    <div class="turma-header">
                        📚 <?= htmlspecialchars($nome_turma, ENT_QUOTES, 'UTF-8') ?> (<?= count($alunos) ?> alunos)
                    </div>
                    
                    <table class="uniformes-table">
                        <thead>
                            <tr>
                                <th>Nome do Aluno</th>
                                <th>Matrícula</th>
                                <th>Unidade</th>
                                <th>Camisa</th>
                                <th>Calça</th>
                                <th>Calçado</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $uniforme): ?>
                                <tr>
                                    <td class="aluno-nome"><?= htmlspecialchars($uniforme['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="matricula"><?= htmlspecialchars($uniforme['numero_matricula'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="unidade"><?= htmlspecialchars($uniforme['unidade_nome'] ?: 'Sem unidade', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="tamanho <?= $uniforme['tamanho_camisa'] ? 'definido' : 'indefinido' ?>">
                                        <?= htmlspecialchars($uniforme['tamanho_camisa'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="tamanho <?= $uniforme['tamanho_calca'] ? 'definido' : 'indefinido' ?>">
                                        <?= htmlspecialchars($uniforme['tamanho_calca'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="tamanho <?= $uniforme['tamanho_calcado'] ? 'definido' : 'indefinido' ?>">
                                        <?= htmlspecialchars($uniforme['tamanho_calcado'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <span class="status <?= htmlspecialchars($uniforme['status_classe'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($uniforme['status_uniforme'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?= obterRodape($data_atual) ?>
        
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    </body>
    </html>
    <?php
    
    return ob_get_clean();
}

function gerarHTMLRelatorioTamanhos($tamanhos_data) {
    date_default_timezone_set('America/Sao_Paulo');
    
    $data_atual = date('d/m/Y \à\s H:i:s');
    $hora_geracao = date('H:i:s');
    $data_geracao = date('d/m/Y');
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Relatório de Tamanhos para Compras</title>
        <?= obterCSSBase() ?>
        <style>
            .tamanhos-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 30px;
                margin: 30px 0;
            }
            
            .tamanho-secao {
                border: 2px solid #dc3545;
                border-radius: 10px;
                overflow: hidden;
                background: white;
            }
            
            .tamanho-header {
                background: #dc3545;
                color: white;
                padding: 15px;
                text-align: center;
                font-size: 18px;
                font-weight: bold;
            }
            
            .tamanho-conteudo {
                padding: 20px;
            }
            
            .tamanho-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px dotted #ccc;
                font-size: 14px;
            }
            
            .tamanho-item:last-child {
                border-bottom: 2px solid #dc3545;
                font-weight: bold;
                background: #f8f9fa;
                margin-top: 10px;
                padding: 12px 0;
            }
            
            .resumo-compras {
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                border: 2px solid #28a745;
                border-radius: 10px;
                padding: 30px;
                margin: 30px 0;
                text-align: center;
            }
            
            .resumo-compras h3 {
                color: #28a745;
                font-size: 20px;
                margin-bottom: 20px;
            }
            
            .resumo-totais {
                display: flex;
                justify-content: space-around;
                margin-top: 20px;
            }
            
            .resumo-item {
                text-align: center;
            }
            
            .resumo-item strong {
                display: block;
                font-size: 24px;
                color: #28a745;
                margin-bottom: 5px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📐 Relatório de Tamanhos para Compras</h1>
            <h2>Programa Bombeiro Mirim do Estado de Goiás</h2>
            <div>
                <strong>Data de Geração:</strong> <?= htmlspecialchars($data_geracao, ENT_QUOTES, 'UTF-8') ?> às <?= htmlspecialchars($hora_geracao, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        
        <div class="tamanhos-grid">
            <!-- Camisas -->
            <div class="tamanho-secao">
                <div class="tamanho-header">👕 CAMISAS</div>
                <div class="tamanho-conteudo">
                    <?php foreach ($tamanhos_data['camisas'] as $item): ?>
                        <div class="tamanho-item">
                            <span><?= strtoupper(htmlspecialchars($item['tamanho_camisa'], ENT_QUOTES, 'UTF-8')) ?></span>
                            <span><?= htmlspecialchars($item['quantidade'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="tamanho-item">
                        <span>TOTAL</span>
                        <span><?= htmlspecialchars($tamanhos_data['total_camisas'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Calças -->
            <div class="tamanho-secao">
                <div class="tamanho-header">👖 CALÇAS</div>
                <div class="tamanho-conteudo">
                    <?php foreach ($tamanhos_data['calcas'] as $item): ?>
                        <div class="tamanho-item">
                            <span><?= strtoupper(htmlspecialchars($item['tamanho_calca'], ENT_QUOTES, 'UTF-8')) ?></span>
                            <span><?= htmlspecialchars($item['quantidade'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="tamanho-item">
                        <span>TOTAL</span>
                        <span><?= htmlspecialchars($tamanhos_data['total_calcas'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Calçados -->
            <div class="tamanho-secao">
                <div class="tamanho-header">👟 CALÇADOS</div>
                <div class="tamanho-conteudo">
                    <?php foreach ($tamanhos_data['calcados'] as $item): ?>
                        <div class="tamanho-item">
                            <span><?= htmlspecialchars($item['tamanho_calcado'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($item['quantidade'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="tamanho-item">
                        <span>TOTAL</span>
                        <span><?= htmlspecialchars($tamanhos_data['total_calcados'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="resumo-compras">
            <h3>🛒 RESUMO PARA COMPRAS</h3>
            <p>Quantidades totais necessárias para aquisição de uniformes</p>
            <div class="resumo-totais">
                <div class="resumo-item">
                    <strong><?= htmlspecialchars($tamanhos_data['total_camisas'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Camisas</span>
                </div>
                <div class="resumo-item">
                    <strong><?= htmlspecialchars($tamanhos_data['total_calcas'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Calças</span>
                </div>
                <div class="resumo-item">
                    <strong><?= htmlspecialchars($tamanhos_data['total_calcados'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Calçados</span>
                </div>
            </div>
        </div>
        
        <?= obterRodape($data_atual) ?>
        
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    </body>
    </html>
    <?php
    
    return ob_get_clean();
}

function gerarHTMLRelatorioIncompletos($uniformes_data, $turma_id = null, $unidade_id = null) {
    date_default_timezone_set('America/Sao_Paulo');
    
    $data_atual = date('d/m/Y \à\s H:i:s');
    $hora_geracao = date('H:i:s');
    $data_geracao = date('d/m/Y');
    
    $total_incompletos = count($uniformes_data);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Relatório de Uniformes Incompletos</title>
        <?= obterCSSBase() ?>
        <style>
            .alerta-incompletos {
                background: linear-gradient(135deg, #ff6b6b, #ee5a52);
                color: white;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: center;
            }
            
            .alerta-incompletos h3 {
                margin: 0 0 10px 0;
                font-size: 18px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>⚠️ Relatório de Uniformes Incompletos</h1>
            <h2>Programa Bombeiro Mirim do Estado de Goiás</h2>
            <div>
                <strong>Data de Geração:</strong> <?= htmlspecialchars($data_geracao, ENT_QUOTES, 'UTF-8') ?> às <?= htmlspecialchars($hora_geracao, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        
        <div class="alerta-incompletos">
            <h3>⚠️ ATENÇÃO: UNIFORMES PENDENTES</h3>
            <p>Este relatório lista apenas alunos com dados de uniforme incompletos ou pendentes</p>
            <p><strong><?= $total_incompletos ?></strong> alunos precisam de atenção</p>
        </div>
        
        <?php if (empty($uniformes_data)): ?>
            <div class="sem-dados" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
                <h3>✅ Excelente! Todos os uniformes estão completos</h3>
                <p>Não há alunos com dados de uniforme incompletos no momento.</p>
            </div>
        <?php else: ?>
            <table class="uniformes-table">
                <thead>
                    <tr>
                        <th>Nome do Aluno</th>
                        <th>Matrícula</th>
                        <th>Turma</th>
                        <th>Unidade</th>
                        <th>Camisa</th>
                        <th>Calça</th>
                        <th>Calçado</th>
                        <th>Status</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uniformes_data as $uniforme): ?>
                        <?php 
                        $observacoes = [];
                        if (!$uniforme['tamanho_camisa']) $observacoes[] = 'Camisa';
                        if (!$uniforme['tamanho_calca']) $observacoes[] = 'Calça';
                        if (!$uniforme['tamanho_calcado']) $observacoes[] = 'Calçado';
                        $obs_texto = 'Falta: ' . implode(', ', $observacoes);
                        ?>
                        <tr>
                            <td class="aluno-nome"><?= htmlspecialchars($uniforme['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="matricula"><?= htmlspecialchars($uniforme['numero_matricula'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="turma"><?= htmlspecialchars($uniforme['turma_nome'] ?: 'Sem turma', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="unidade"><?= htmlspecialchars($uniforme['unidade_nome'] ?: 'Sem unidade', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="tamanho <?= $uniforme['tamanho_camisa'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_camisa'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="tamanho <?= $uniforme['tamanho_calca'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_calca'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="tamanho <?= $uniforme['tamanho_calcado'] ? 'definido' : 'indefinido' ?>">
                                <?= htmlspecialchars($uniforme['tamanho_calcado'] ?: 'N/D', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <span class="status <?= htmlspecialchars($uniforme['status_classe'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($uniforme['status_uniforme'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="observacoes" style="font-size: 10px; color: #dc3545;">
                                <?= htmlspecialchars($obs_texto, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?= obterRodape($data_atual) ?>
        
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        </script>
    </body>
    </html>
    <?php
    
    return ob_get_clean();
}

// ====================================
// FUNÇÕES UTILITÁRIAS
// ====================================

function determinarStatusUniforme($uniforme) {
    $temCamisa = $uniforme['tamanho_camisa'] && $uniforme['tamanho_camisa'] !== '';
    $temCalca = $uniforme['tamanho_calca'] && $uniforme['tamanho_calca'] !== '';
    $temCalcado = $uniforme['tamanho_calcado'] && $uniforme['tamanho_calcado'] !== '';
    
    if ($temCamisa && $temCalca && $temCalcado) {
        return 'Completo';
    } elseif (!$temCamisa && !$temCalca && !$temCalcado) {
        return 'Pendente';
    } else {
        return 'Incompleto';
    }
}

function obterCSSBase() {
    return '
    <style>
        @page {
            margin: 2cm;
            size: A4 landscape;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Arial", sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #dc3545;
        }
        
        .header h1 {
            color: #dc3545;
            font-size: 22px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .header h2 {
            color: #666;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .info-geral {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-item {
            text-align: center;
            min-width: 100px;
        }
        
        .info-item strong {
            display: block;
            font-size: 18px;
            color: #dc3545;
            margin-bottom: 5px;
        }
        
        .info-item.completo strong { color: #28a745; }
        .info-item.incompleto strong { color: #ffc107; }
        .info-item.pendente strong { color: #dc3545; }
        
        .turma-section {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }
        
        .turma-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 12px 15px;
            border-radius: 8px 8px 0 0;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        
        .uniformes-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .uniformes-table th {
            background: #495057;
            color: white;
            padding: 10px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .uniformes-table td {
            padding: 8px 6px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        
        .uniformes-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .aluno-nome {
            text-align: left !important;
            font-weight: 600;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .matricula, .turma, .unidade {
            font-size: 9px;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .tamanho {
            font-weight: bold;
            width: 50px;
        }
        
        .tamanho.definido {
            background: #d4edda;
            color: #155724;
        }
        
        .tamanho.indefinido {
            background: #f8d7da;
            color: #721c24;
            font-style: italic;
        }
        
        .status {
            font-weight: bold;
            font-size: 9px;
            padding: 3px 6px;
            border-radius: 10px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .status.completo {
            background: #28a745;
            color: white;
        }
        
        .status.incompleto {
            background: #ffc107;
            color: #212529;
        }
        
        .status.pendente {
            background: #dc3545;
            color: white;
        }
        
        .sem-dados {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .turma-section {
                page-break-inside: avoid;
            }
            
            .uniformes-table {
                page-break-inside: auto;
            }
            
            .uniformes-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>';
}

function obterRodape($data_atual) {
    return '
    <div class="footer">
        <p><strong>Sistema de Gestão do Programa Bombeiro Mirim - Estado de Goiás</strong></p>
        <p>Documento gerado automaticamente em ' . htmlspecialchars($data_atual, ENT_QUOTES, 'UTF-8') . '</p>
        <p>Timezone: ' . date_default_timezone_get() . ' | Versão do sistema: 2.0</p>
        <p>© ' . date('Y') . ' Associação dos Subtenentes e Sargentos da PM e BM do Estado de Goiás - ASSEGO</p>
        <p style="margin-top: 10px; font-size: 8px;">
            🔥 Corpo de Bombeiros Militar do Estado de Goiás - Formando cidadãos conscientes e preparados para a vida
        </p>
    </div>';
}

?>