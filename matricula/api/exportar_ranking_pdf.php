<?php
/**
 * API para exportar ranking dos alunos em PDF
 * Usa MySQLi e gera PDF com layout profissional
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

// 3. Iniciar sessão
session_start();

// 4. Headers UTF-8 OBRIGATÓRIOS
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
// 5. Configurar charset da conexão MySQL
if (isset($conn) && $conn instanceof mysqli) {
    $conn->set_charset('utf8mb4'); // utf8mb4 é melhor que utf8
}

// Verificar se a biblioteca TCPDF está disponível
if (!class_exists('TCPDF')) {
    // Se não tiver TCPDF, usar uma versão simples com HTML/CSS para impressão
    gerarPDFSimples();
    exit;
}

function gerarPDFSimples() {
    global $conn;
    
    try {
        // Parâmetros da consulta
        $turma_id = isset($_GET['turma_id']) && !empty($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
        $periodo = isset($_GET['periodo']) && !empty($_GET['periodo']) ? $_GET['periodo'] : null;
        
        // Buscar dados do ranking (mesma lógica do ranking_alunos.php)
        $ranking_data = buscarDadosRanking($turma_id, $periodo);
        
        // ========== HEADERS PARA DOWNLOAD UTF-8 ==========
        // 6. Headers corretos para UTF-8 com nome de arquivo brasileiro
        $nome_arquivo = "ranking_bombeiro_mirim_" . date('Y-m-d_H-i-s') . ".html";
        
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
        header('Content-Transfer-Encoding: binary');
        
        // Gerar HTML para impressão
        echo gerarHTMLRanking($ranking_data, $turma_id, $periodo);
        
    } catch (Exception $e) {
        error_log("Erro ao gerar PDF simples: " . $e->getMessage());
        http_response_code(500);
        echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}

function buscarDadosRanking($turma_id = null, $periodo = null) {
    global $conn;
    
    // ========== QUERY COM CHARSET UTF-8 E TIMEZONE ==========
    // 7. Garantir que as queries usem UTF-8 e timezone correto
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET time_zone = '-03:00'"); // Forçar horário de Brasília no MySQL
    
    // Query para buscar avaliações
    $sql_avaliacoes = "
        SELECT 
            a.aluno_id,
            a.turma_id,
            al.nome as aluno_nome,
            CONCAT(t.nome_turma, ' - ', u.nome) as turma_nome,
            u.nome as unidade_nome,
            
            -- Nota Física (média das 5 habilidades físicas, convertida para 0-10)
            ROUND(
                COALESCE(
                    (AVG(COALESCE(a.velocidade, 0)) + AVG(COALESCE(a.resistencia, 0)) + 
                     AVG(COALESCE(a.coordenacao, 0)) + AVG(COALESCE(a.agilidade, 0)) + 
                     AVG(COALESCE(a.forca, 0))) / 5 * 2, 0
                ), 2
            ) AS nota_fisica,
            
            -- Nota Comportamento (média dos 4 aspectos comportamentais, convertida para 0-10) 
            ROUND(
                COALESCE(
                    (AVG(COALESCE(a.participacao, 0)) + AVG(COALESCE(a.trabalho_equipe, 0)) + 
                     AVG(COALESCE(a.disciplina, 0)) + AVG(COALESCE(a.respeito_regras, 0))) / 4 * 2, 0
                ), 2
            ) AS nota_comportamento,
            
            COUNT(a.id) as total_avaliacoes
            
        FROM avaliacoes a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN turma t ON a.turma_id = t.id
        LEFT JOIN unidade u ON t.id_unidade = u.id
        WHERE 1=1
    ";
    
    if ($turma_id) {
        $sql_avaliacoes .= " AND a.turma_id = " . intval($turma_id);
    }
    
    $sql_avaliacoes .= " GROUP BY a.aluno_id, a.turma_id, al.nome, t.nome_turma, u.nome";
    
    $result_avaliacoes = $conn->query($sql_avaliacoes);
    
    if (!$result_avaliacoes) {
        throw new Exception("Erro na consulta de avaliações: " . $conn->error);
    }
    
    $avaliacoes_base = [];
    while ($row = $result_avaliacoes->fetch_assoc()) {
        // ========== GARANTIR UTF-8 NOS DADOS ==========
        // 8. Converter dados para UTF-8 se necessário
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $avaliacoes_base[] = $row;
    }
    
    // Query para buscar dados das atividades
    $sql_atividades = "
        SELECT 
            ap.aluno_id,
            ROUND(AVG(
                CASE 
                    WHEN ap.desempenho_conceito = 'excelente' THEN 10
                    WHEN ap.desempenho_conceito = 'bom' THEN 8
                    WHEN ap.desempenho_conceito = 'regular' THEN 6
                    WHEN ap.desempenho_conceito = 'insuficiente' THEN 4
                    WHEN ap.desempenho_nota IS NOT NULL THEN ap.desempenho_nota
                    ELSE 5
                END
            ), 2) AS nota_atividades,
            
            ROUND(AVG(
                CASE 
                    WHEN ap.comportamento = 'excelente' THEN 10
                    WHEN ap.comportamento = 'bom' THEN 8
                    WHEN ap.comportamento = 'regular' THEN 6
                    WHEN ap.comportamento = 'precisa_melhorar' THEN 4
                    ELSE 7
                END
            ), 2) AS nota_comportamento_atividades,
            
            ROUND(
                (SUM(CASE WHEN ap.presenca = 'sim' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2
            ) AS taxa_presenca,
            
            COUNT(*) as total_atividades
            
        FROM atividade_participacao ap
        WHERE ap.presenca IN ('sim', 'falta_justificada')
        GROUP BY ap.aluno_id
    ";
    
    $result_atividades = $conn->query($sql_atividades);
    
    if (!$result_atividades) {
        throw new Exception("Erro na consulta de atividades: " . $conn->error);
    }
    
    // Criar array indexado por aluno_id
    $atividades_por_aluno = [];
    while ($row = $result_atividades->fetch_assoc()) {
        // Converter dados para UTF-8
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        $atividades_por_aluno[$row['aluno_id']] = $row;
    }
    
    // Combinar dados e calcular ranking
    $ranking_final = [];
    
    foreach ($avaliacoes_base as $avaliacao) {
        $aluno_id = $avaliacao['aluno_id'];
        $atividade = isset($atividades_por_aluno[$aluno_id]) ? $atividades_por_aluno[$aluno_id] : null;
        
        $nota_fisica = floatval($avaliacao['nota_fisica'] ?? 0);
        $nota_comportamento = floatval($avaliacao['nota_comportamento'] ?? 0);
        $nota_atividades = floatval($atividade['nota_atividades'] ?? 5);
        $nota_comportamento_atividades = floatval($atividade['nota_comportamento_atividades'] ?? 7);
        $taxa_presenca = floatval($atividade['taxa_presenca'] ?? 80);
        
        // Calcular média final ponderada
        $media_final = round(
            ($nota_fisica * 0.25) +                    // 25% física
            ($nota_comportamento * 0.15) +             // 15% comportamento avaliações
            ($nota_atividades * 0.30) +                // 30% atividades
            ($nota_comportamento_atividades * 0.15) +  // 15% comportamento atividades
            (($taxa_presenca / 10) * 0.15),            // 15% presença (convertida para escala 0-10)
            2
        );
        
        $ranking_final[] = [
            'aluno_id' => intval($aluno_id),
            'turma_id' => intval($avaliacao['turma_id']),
            'aluno_nome' => $avaliacao['aluno_nome'] ?? 'Nome não informado',
            'turma_nome' => $avaliacao['turma_nome'] ?? 'Turma não informada',
            'unidade_nome' => $avaliacao['unidade_nome'] ?? 'Unidade não informada',
            'nota_fisica' => $nota_fisica,
            'nota_comportamento' => $nota_comportamento,
            'nota_atividades' => $nota_atividades,
            'nota_comportamento_atividades' => $nota_comportamento_atividades,
            'taxa_presenca' => $taxa_presenca,
            'media_final' => $media_final,
            'total_avaliacoes' => intval($avaliacao['total_avaliacoes'] ?? 0),
            'total_atividades' => intval($atividade['total_atividades'] ?? 0)
        ];
    }
    
    // Se não há avaliações, buscar alunos ativos
    if (empty($ranking_final)) {
        $sql_alunos = "
            SELECT 
                a.id as aluno_id,
                m.turma as turma_id,
                a.nome as aluno_nome,
                CONCAT(t.nome_turma, ' - ', u.nome) as turma_nome,
                u.nome as unidade_nome
            FROM alunos a
            INNER JOIN matriculas m ON a.id = m.aluno_id
            LEFT JOIN turma t ON m.turma = t.id
            LEFT JOIN unidade u ON t.id_unidade = u.id
            WHERE a.status = 'ativo' AND m.status = 'ativo'
        ";
        
        if ($turma_id) {
            $sql_alunos .= " AND m.turma = " . intval($turma_id);
        }
        
        $result_alunos = $conn->query($sql_alunos);
        
        if ($result_alunos) {
            while ($row = $result_alunos->fetch_assoc()) {
                // Converter dados para UTF-8
                foreach ($row as $key => $value) {
                    if (is_string($value)) {
                        $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                }
                
                $ranking_final[] = [
                    'aluno_id' => intval($row['aluno_id']),
                    'turma_id' => intval($row['turma_id']),
                    'aluno_nome' => $row['aluno_nome'],
                    'turma_nome' => $row['turma_nome'],
                    'unidade_nome' => $row['unidade_nome'],
                    'nota_fisica' => 0.0,
                    'nota_comportamento' => 0.0,
                    'nota_atividades' => 0.0,
                    'nota_comportamento_atividades' => 0.0,
                    'taxa_presenca' => 0.0,
                    'media_final' => 0.0,
                    'total_avaliacoes' => 0,
                    'total_atividades' => 0
                ];
            }
        }
    }
    
    // Ordenar por turma e depois por média final (decrescente)
    usort($ranking_final, function($a, $b) {
        if ($a['turma_id'] == $b['turma_id']) {
            return $b['media_final'] <=> $a['media_final']; // Maior média primeiro
        }
        return $a['turma_id'] <=> $b['turma_id']; // Ordem crescente por turma
    });
    
    // Adicionar posições por turma e premiação
    $turma_atual = null;
    $posicao_na_turma = 0;
    
    for ($i = 0; $i < count($ranking_final); $i++) {
        if ($ranking_final[$i]['turma_id'] != $turma_atual) {
            $turma_atual = $ranking_final[$i]['turma_id'];
            $posicao_na_turma = 1;
        } else {
            $posicao_na_turma++;
        }
        
        $ranking_final[$i]['posicao'] = $posicao_na_turma;
        $ranking_final[$i]['premiado'] = $posicao_na_turma <= 3 ? 'SIM' : 'NÃO'; // Corrigido acentuação
        
        switch ($posicao_na_turma) {
            case 1:
                $ranking_final[$i]['premio'] = '1º LUGAR - OURO';
                $ranking_final[$i]['classe_premio'] = 'ouro';
                break;
            case 2:
                $ranking_final[$i]['premio'] = '2º LUGAR - PRATA';
                $ranking_final[$i]['classe_premio'] = 'prata';
                break;
            case 3:
                $ranking_final[$i]['premio'] = '3º LUGAR - BRONZE';
                $ranking_final[$i]['classe_premio'] = 'bronze';
                break;
            default:
                $ranking_final[$i]['premio'] = 'PARTICIPANTE';
                $ranking_final[$i]['classe_premio'] = 'participante';
        }
    }
    
    return $ranking_final;
}

function gerarHTMLRanking($ranking_data, $turma_id = null, $periodo = null) {
    // ========== FORMATAÇÃO UTF-8 E DATA BRASILEIRA ==========
    // 9. Usar funções que respeitam UTF-8 e timezone brasileiro
    
    // Garantir que estamos usando o timezone correto
    date_default_timezone_set('America/Sao_Paulo');
    
    // Formatação brasileira da data/hora
    $data_atual = date('d/m/Y \à\s H:i:s');
    $hora_geracao = date('H:i:s');
    $data_geracao = date('d/m/Y');
    
    $total_alunos = count($ranking_data);
    $total_premiados = count(array_filter($ranking_data, function($aluno) {
        return $aluno['premiado'] === 'SIM';
    }));
    
    // Agrupar por turma
    $turmas = [];
    foreach ($ranking_data as $aluno) {
        $turma_nome = $aluno['turma_nome'];
        if (!isset($turmas[$turma_nome])) {
            $turmas[$turma_nome] = [];
        }
        $turmas[$turma_nome][] = $aluno;
    }
    
    $filtro_info = $turma_id ? "Turma Específica" : "Todas as Turmas";
    if ($periodo) {
        $filtro_info .= " - Período: " . $periodo;
    }
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <!-- ========== META UTF-8 OBRIGATÓRIO ========== -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Ranking Bombeiro Mirim - <?= htmlspecialchars($data_atual, ENT_QUOTES, 'UTF-8') ?></title>
        <style>
            @page {
                margin: 2cm;
                size: A4;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12px;
                line-height: 1.4;
                color: #333;
                background: white;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #dc3545;
            }
            
            .header h1 {
                color: #dc3545;
                font-size: 24px;
                margin-bottom: 10px;
                text-transform: uppercase;
                font-weight: bold;
            }
            
            .header h2 {
                color: #666;
                font-size: 18px;
                margin-bottom: 15px;
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
            }
            
            .info-item strong {
                display: block;
                font-size: 16px;
                color: #dc3545;
                margin-bottom: 5px;
            }
            
            .turma-section {
                margin-bottom: 40px;
                page-break-inside: avoid;
            }
            
            .turma-header {
                background: linear-gradient(135deg, #dc3545, #c82333);
                color: white;
                padding: 15px;
                border-radius: 8px 8px 0 0;
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                text-transform: uppercase;
            }
            
            .ranking-table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .ranking-table th {
                background: #495057;
                color: white;
                padding: 12px 8px;
                text-align: center;
                font-weight: bold;
                font-size: 11px;
                text-transform: uppercase;
            }
            
            .ranking-table td {
                padding: 10px 8px;
                text-align: center;
                border-bottom: 1px solid #dee2e6;
                font-size: 11px;
            }
            
            .ranking-table tr:nth-child(even) {
                background: #f8f9fa;
            }
            
            .ranking-table tr:hover {
                background: #e9ecef;
            }
            
            .posicao {
                font-weight: bold;
                font-size: 14px;
                width: 50px;
            }
            
            .aluno-nome {
                text-align: left;
                font-weight: 600;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .nota {
                font-weight: bold;
                width: 60px;
            }
            
            .media-final {
                font-weight: bold;
                font-size: 13px;
                background: #e9ecef;
                border-radius: 4px;
                width: 80px;
            }
            
            .premio {
                font-weight: bold;
                font-size: 10px;
                padding: 4px 8px;
                border-radius: 12px;
                text-transform: uppercase;
                white-space: nowrap;
            }
            
            .premio.ouro {
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                color: #b8860b;
            }
            
            .premio.prata {
                background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
                color: #696969;
            }
            
            .premio.bronze {
                background: linear-gradient(135deg, #cd7f32, #daa520);
                color: #8b4513;
            }
            
            .premio.participante {
                background: #e9ecef;
                color: #6c757d;
            }
            
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #dee2e6;
                padding-top: 15px;
            }
            
            .sem-dados {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }
            
            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                    color-adjust: exact;
                }
                
                .turma-section {
                    page-break-inside: avoid;
                }
                
                .ranking-table {
                    page-break-inside: auto;
                }
                
                .ranking-table tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🏆 Ranking dos Alunos</h1>
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
            <div class="info-item">
                <strong><?= $total_premiados ?></strong>
                <span>Alunos Premiados</span>
            </div>
            <div class="info-item">
                <strong><?= count($turmas) ?></strong>
                <span>Turmas Avaliadas</span>
            </div>
        </div>
        
        <?php if (empty($ranking_data)): ?>
            <div class="sem-dados">
                <h3>Nenhum dado de ranking encontrado</h3>
                <p>Não há avaliações registradas para os filtros selecionados.</p>
            </div>
        <?php else: ?>
            <?php foreach ($turmas as $nome_turma => $alunos): ?>
                <div class="turma-section">
                    <div class="turma-header">
                        📚 <?= htmlspecialchars($nome_turma, ENT_QUOTES, 'UTF-8') ?> (<?= count($alunos) ?> alunos)
                    </div>
                    
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>Pos.</th>
                                <th>Nome do Aluno</th>
                                <th>Física</th>
                                <th>Comportamento</th>
                                <th>Atividades</th>
                                <th>Presença</th>
                                <th>Média Final</th>
                                <th>Premiação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno): ?>
                                <tr>
                                    <td class="posicao"><?= $aluno['posicao'] ?>º</td>
                                    <td class="aluno-nome"><?= htmlspecialchars($aluno['aluno_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="nota"><?= number_format($aluno['nota_fisica'], 1, ',', '.') ?></td>
                                    <td class="nota"><?= number_format($aluno['nota_comportamento'], 1, ',', '.') ?></td>
                                    <td class="nota"><?= number_format($aluno['nota_atividades'], 1, ',', '.') ?></td>
                                    <td class="nota"><?= number_format($aluno['taxa_presenca'], 0, ',', '.') ?>%</td>
                                    <td class="media-final"><?= number_format($aluno['media_final'], 1, ',', '.') ?></td>
                                    <td>
                                        <span class="premio <?= htmlspecialchars($aluno['classe_premio'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($aluno['premio'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="footer">
            <p><strong>Critérios de Avaliação:</strong></p>
            <p>Nota Física (25%) + Comportamento Avaliações (15%) + Desempenho Atividades (30%) + Comportamento Atividades (15%) + Taxa de Presença (15%)</p>
            <p>Documento gerado automaticamente pelo Sistema de Gestão Bombeiro Mirim</p>
            <p>Gerado em: <?= htmlspecialchars($data_atual, ENT_QUOTES, 'UTF-8') ?> | Timezone: <?= date_default_timezone_get() ?></p>
            <p>© <?= date('Y') ?> Associação dos Subtenentes e Sargentos da PM e BM do Estado de Goiás - ASSEGO</p>
        </div>
        
        <script>
            // Auto-imprimir quando abrir
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

// Executar a função principal
try {
    gerarPDFSimples();
} catch (Exception $e) {
    error_log("Erro ao gerar PDF do ranking: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>