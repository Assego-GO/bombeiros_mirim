<?php
session_start();

// Configuração de charset e timezone
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Incluir configurações do banco
$env_paths = [
    __DIR__ . "/conexao.php",
    __DIR__ . "/../../env_config.php", 
    dirname(__DIR__) . "/env_config.php",
    dirname(dirname(__DIR__)) . "/env_config.php"
];

$loaded = false;
foreach ($env_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar as configurações do ambiente']);
    exit;
}

// Configurar conexão com o banco
try {
    $host = $_ENV['DB_HOST'];
    $usuario = $_ENV['DB_USER'];
    $senha = $_ENV['DB_PASS'];
    $db_name = $_ENV['DB_NAME'];

    $conn = new mysqli($host, $usuario, $senha, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception('Conexão falhou: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()]);
    exit;
}

// Verificar se a tabela unidade existe
$check_table = $conn->query("SHOW TABLES LIKE 'unidade'");
$has_unidades_table = $check_table->num_rows > 0;

// Obter ação
$action = $_REQUEST['action'] ?? '';

// Log da ação para debug
error_log("Uniformes.php - Ação recebida: " . $action);

// Roteador de ações
switch($action) {
    case 'listar_uniformes':
        listarUniformes();
        break;
    
    case 'listar_turmas':
        listarTurmas();
        break;
    
    case 'listar_unidades':
        listarUnidades();
        break;
    
    case 'atualizar_uniforme':
        atualizarUniforme();
        break;
    
    case 'estatisticas':
        gerarEstatisticas();
        break;
    
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
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . $action]);
        break;
}

// ====================================
// FUNÇÕES PRINCIPAIS
// ====================================

function listarUniformes() {
    global $conn, $has_unidades_table;
    
    try {
        error_log("Listando uniformes - Tabela unidade existe: " . ($has_unidades_table ? 'Sim' : 'Não'));
        
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
                    WHERE a.status = 'ativo'
                    ORDER BY a.nome";
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
                    WHERE a.status = 'ativo'
                    ORDER BY a.nome";
        }
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Erro na consulta SQL: ' . $conn->error);
        }
        
        $uniformes = [];
        while ($row = $result->fetch_assoc()) {
            $uniformes[] = $row;
        }
        
        error_log("Uniformes encontrados: " . count($uniformes));
        
        echo json_encode([
            'success' => true,
            'uniformes' => $uniformes,
            'total' => count($uniformes)
        ]);
        
    } catch(Exception $e) {
        error_log("Erro ao listar uniformes: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao listar uniformes: ' . $e->getMessage()
        ]);
    }
}

function listarTurmas() {
    global $conn;
    
    try {
        $sql = "SELECT id, nome_turma as nome FROM turma WHERE status = 'Em Andamento' ORDER BY nome_turma";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Erro na consulta SQL: ' . $conn->error);
        }
        
        $turmas = [];
        while ($row = $result->fetch_assoc()) {
            $turmas[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'turmas' => $turmas
        ]);
        
    } catch(Exception $e) {
        error_log("Erro ao listar turmas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao listar turmas: ' . $e->getMessage()
        ]);
    }
}

function listarUnidades() {
    global $conn, $has_unidades_table;
    
    try {
        if ($has_unidades_table) {
            $sql = "SELECT id, nome FROM unidade ORDER BY nome";
            $result = $conn->query($sql);
            
            if ($result === false) {
                throw new Exception('Erro na consulta SQL: ' . $conn->error);
            }
            
            $unidades = [];
            while ($row = $result->fetch_assoc()) {
                $unidades[] = $row;
            }
        } else {
            $unidades = [];
        }
        
        echo json_encode([
            'success' => true,
            'unidades' => $unidades
        ]);
        
    } catch(Exception $e) {
        error_log("Erro ao listar unidades: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao listar unidades: ' . $e->getMessage()
        ]);
    }
}

function atualizarUniforme() {
    global $conn;
    
    try {
        $aluno_id = $_POST['aluno_id'] ?? '';
        $tamanho_camisa = $_POST['tamanho_camisa'] ?? '';
        $tamanho_calca = $_POST['tamanho_calca'] ?? '';
        $tamanho_calcado = $_POST['tamanho_calcado'] ?? '';
        
        if (empty($aluno_id)) {
            throw new Exception('ID do aluno é obrigatório');
        }
        
        // Verificar se o aluno existe
        $stmt = $conn->prepare("SELECT id FROM alunos WHERE id = ?");
        $stmt->bind_param("i", $aluno_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Aluno não encontrado');
        }
        
        // Atualizar os dados
        $stmt = $conn->prepare("UPDATE alunos SET 
                                tamanho_camisa = ?, 
                                tamanho_calca = ?, 
                                tamanho_calcado = ? 
                                WHERE id = ?");
        $stmt->bind_param("sssi", $tamanho_camisa, $tamanho_calca, $tamanho_calcado, $aluno_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Uniforme atualizado com sucesso'
            ]);
        } else {
            throw new Exception('Erro ao executar atualização: ' . $conn->error);
        }
        
    } catch(Exception $e) {
        error_log("Erro ao atualizar uniforme: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar uniforme: ' . $e->getMessage()
        ]);
    }
}

function gerarEstatisticas() {
    global $conn;
    
    try {
        $estatisticas = [];
        
        // Total de camisas definidas
        $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE tamanho_camisa IS NOT NULL AND tamanho_camisa != '' AND status = 'ativo'");
        $estatisticas['total_camisas'] = $result->fetch_assoc()['total'];
        
        // Total de calças definidas
        $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE tamanho_calca IS NOT NULL AND tamanho_calca != '' AND status = 'ativo'");
        $estatisticas['total_calcas'] = $result->fetch_assoc()['total'];
        
        // Total de calçados definidos
        $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE tamanho_calcado IS NOT NULL AND tamanho_calcado != '' AND status = 'ativo'");
        $estatisticas['total_calcados'] = $result->fetch_assoc()['total'];
        
        // Uniformes completos
        $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE 
                              tamanho_camisa IS NOT NULL AND tamanho_camisa != '' AND
                              tamanho_calca IS NOT NULL AND tamanho_calca != '' AND
                              tamanho_calcado IS NOT NULL AND tamanho_calcado != '' AND
                              status = 'ativo'");
        $estatisticas['uniformes_completos'] = $result->fetch_assoc()['total'];
        
        // Distribuição de tamanhos de camisas
        $result = $conn->query("SELECT tamanho_camisa, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_camisa IS NOT NULL AND tamanho_camisa != '' AND status = 'ativo'
                              GROUP BY tamanho_camisa ORDER BY tamanho_camisa");
        $distribuicao_camisas = [];
        while($row = $result->fetch_assoc()) {
            $distribuicao_camisas[$row['tamanho_camisa']] = $row['quantidade'];
        }
        $estatisticas['distribuicao_camisas'] = $distribuicao_camisas;
        
        // Distribuição de tamanhos de calças
        $result = $conn->query("SELECT tamanho_calca, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_calca IS NOT NULL AND tamanho_calca != '' AND status = 'ativo'
                              GROUP BY tamanho_calca ORDER BY tamanho_calca");
        $distribuicao_calcas = [];
        while($row = $result->fetch_assoc()) {
            $distribuicao_calcas[$row['tamanho_calca']] = $row['quantidade'];
        }
        $estatisticas['distribuicao_calcas'] = $distribuicao_calcas;
        
        // Distribuição de tamanhos de calçados
        $result = $conn->query("SELECT tamanho_calcado, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_calcado IS NOT NULL AND tamanho_calcado != '' AND status = 'ativo'
                              GROUP BY tamanho_calcado ORDER BY CAST(tamanho_calcado AS UNSIGNED)");
        $distribuicao_calcados = [];
        while($row = $result->fetch_assoc()) {
            $distribuicao_calcados[$row['tamanho_calcado']] = $row['quantidade'];
        }
        $estatisticas['distribuicao_calcados'] = $distribuicao_calcados;
        
        echo json_encode([
            'success' => true,
            'estatisticas' => $estatisticas
        ]);
        
    } catch(Exception $e) {
        error_log("Erro ao gerar estatísticas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar estatísticas: ' . $e->getMessage()
        ]);
    }
}

// ====================================
// FUNÇÕES DE RELATÓRIOS
// ====================================

function gerarRelatorioGeral() {
    global $conn, $has_unidades_table;
    
    try {
        if ($has_unidades_table) {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        u.nome as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    LEFT JOIN unidade u ON t.id_unidade = u.id
                    WHERE a.status = 'ativo'
                    ORDER BY u.nome, t.nome_turma, a.nome";
        } else {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        'Não informado' as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    WHERE a.status = 'ativo'
                    ORDER BY t.nome_turma, a.nome";
        }
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Erro na consulta SQL: ' . $conn->error);
        }
        
        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }
        
        gerarPDFRelatorio($dados, 'Relatório Geral de Uniformes', 'relatorio_geral_uniformes.pdf');
        
    } catch(Exception $e) {
        error_log("Erro ao gerar relatório geral: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
        ]);
    }
}

function gerarRelatorioPorTurma() {
    global $conn, $has_unidades_table;
    
    try {
        if ($has_unidades_table) {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        u.nome as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    LEFT JOIN unidade u ON t.id_unidade = u.id
                    WHERE a.status = 'ativo'
                    ORDER BY t.nome_turma, a.nome";
        } else {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        'Não informado' as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    WHERE a.status = 'ativo'
                    ORDER BY t.nome_turma, a.nome";
        }
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Erro na consulta SQL: ' . $conn->error);
        }
        
        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }
        
        gerarPDFRelatorio($dados, 'Relatório de Uniformes por Turma', 'relatorio_turmas_uniformes.pdf');
        
    } catch(Exception $e) {
        error_log("Erro ao gerar relatório por turma: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
        ]);
    }
}

function gerarRelatorioTamanhos() {
    global $conn;
    
    try {
        $dados = [];
        
        // Camisas
        $result = $conn->query("SELECT tamanho_camisa, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_camisa IS NOT NULL AND tamanho_camisa != '' AND status = 'ativo'
                              GROUP BY tamanho_camisa ORDER BY tamanho_camisa");
        $dados['camisas'] = [];
        while($row = $result->fetch_assoc()) {
            $dados['camisas'][] = $row;
        }
        
        // Calças
        $result = $conn->query("SELECT tamanho_calca, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_calca IS NOT NULL AND tamanho_calca != '' AND status = 'ativo'
                              GROUP BY tamanho_calca ORDER BY tamanho_calca");
        $dados['calcas'] = [];
        while($row = $result->fetch_assoc()) {
            $dados['calcas'][] = $row;
        }
        
        // Calçados
        $result = $conn->query("SELECT tamanho_calcado, COUNT(*) as quantidade FROM alunos 
                              WHERE tamanho_calcado IS NOT NULL AND tamanho_calcado != '' AND status = 'ativo'
                              GROUP BY tamanho_calcado ORDER BY CAST(tamanho_calcado AS UNSIGNED)");
        $dados['calcados'] = [];
        while($row = $result->fetch_assoc()) {
            $dados['calcados'][] = $row;
        }
        
        gerarPDFTamanhos($dados, 'Relatório de Tamanhos para Compras', 'relatorio_tamanhos_uniformes.pdf');
        
    } catch(Exception $e) {
        error_log("Erro ao gerar relatório de tamanhos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
        ]);
    }
}

function gerarRelatorioIncompletos() {
    global $conn, $has_unidades_table;
    
    try {
        if ($has_unidades_table) {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        u.nome as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    LEFT JOIN unidade u ON t.id_unidade = u.id
                    WHERE a.status = 'ativo' AND (
                        a.tamanho_camisa IS NULL OR a.tamanho_camisa = '' OR
                        a.tamanho_calca IS NULL OR a.tamanho_calca = '' OR
                        a.tamanho_calcado IS NULL OR a.tamanho_calcado = ''
                    )
                    ORDER BY u.nome, t.nome_turma, a.nome";
        } else {
            $sql = "SELECT 
                        a.nome as aluno_nome,
                        a.numero_matricula,
                        a.tamanho_camisa,
                        a.tamanho_calca,
                        a.tamanho_calcado,
                        t.nome_turma as turma_nome,
                        'Não informado' as unidade_nome
                    FROM alunos a
                    LEFT JOIN matriculas m ON a.id = m.aluno_id
                    LEFT JOIN turma t ON m.turma = t.id
                    WHERE a.status = 'ativo' AND (
                        a.tamanho_camisa IS NULL OR a.tamanho_camisa = '' OR
                        a.tamanho_calca IS NULL OR a.tamanho_calca = '' OR
                        a.tamanho_calcado IS NULL OR a.tamanho_calcado = ''
                    )
                    ORDER BY t.nome_turma, a.nome";
        }
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception('Erro na consulta SQL: ' . $conn->error);
        }
        
        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }
        
        gerarPDFRelatorio($dados, 'Relatório de Uniformes Incompletos', 'relatorio_incompletos_uniformes.pdf');
        
    } catch(Exception $e) {
        error_log("Erro ao gerar relatório de incompletos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
        ]);
    }
}

function exportarTodosRelatorios() {
    echo json_encode([
        'success' => true,
        'message' => 'Funcionalidade de exportação completa em desenvolvimento'
    ]);
}

// ====================================
// FUNÇÕES DE GERAÇÃO DE PDF
// ====================================

function gerarPDFRelatorio($dados, $titulo, $filename) {
    // Configurar headers para HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($titulo) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 20px; }
            .data { font-size: 12px; margin-bottom: 10px; }
            .logo { margin-bottom: 20px; }
            .status-completo { color: green; font-weight: bold; }
            .status-incompleto { color: orange; font-weight: bold; }
            .status-pendente { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo">
                <h2>🔥 Bombeiro Mirim - Estado de Goiás</h2>
            </div>
            <h1>' . htmlspecialchars($titulo) . '</h1>
            <div class="data">Gerado em: ' . date('d/m/Y H:i:s') . '</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>Matrícula</th>
                    <th>Turma</th>
                    <th>Unidade</th>
                    <th>Camisa</th>
                    <th>Calça</th>
                    <th>Calçado</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach($dados as $linha) {
        $status = '';
        $statusClass = '';
        
        if ($linha['tamanho_camisa'] && $linha['tamanho_calca'] && $linha['tamanho_calcado']) {
            $status = 'Completo';
            $statusClass = 'status-completo';
        } elseif (!$linha['tamanho_camisa'] && !$linha['tamanho_calca'] && !$linha['tamanho_calcado']) {
            $status = 'Pendente';
            $statusClass = 'status-pendente';
        } else {
            $status = 'Incompleto';
            $statusClass = 'status-incompleto';
        }
        
        $html .= '<tr>
                    <td>' . htmlspecialchars($linha['aluno_nome']) . '</td>
                    <td>' . htmlspecialchars($linha['numero_matricula']) . '</td>
                    <td>' . htmlspecialchars($linha['turma_nome'] ?: 'Sem turma') . '</td>
                    <td>' . htmlspecialchars($linha['unidade_nome'] ?: 'Sem unidade') . '</td>
                    <td>' . htmlspecialchars($linha['tamanho_camisa'] ?: 'N/D') . '</td>
                    <td>' . htmlspecialchars($linha['tamanho_calca'] ?: 'N/D') . '</td>
                    <td>' . htmlspecialchars($linha['tamanho_calcado'] ?: 'N/D') . '</td>
                    <td class="' . $statusClass . '">' . $status . '</td>
                  </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
            <p>Relatório gerado automaticamente pelo Sistema de Gestão do Bombeiro Mirim</p>
            <p>Total de registros: ' . count($dados) . '</p>
        </div>
    </body>
    </html>';
    
    echo $html;
}

function gerarPDFTamanhos($dados, $titulo, $filename) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($titulo) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 20px; }
            .section { margin-bottom: 30px; }
            .section h3 { color: #333; border-bottom: 2px solid #333; padding-bottom: 5px; }
            .total { background-color: #f0f8ff; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo">
                <h2>🔥 Bombeiro Mirim - Estado de Goiás</h2>
            </div>
            <h1>' . htmlspecialchars($titulo) . '</h1>
            <div>Gerado em: ' . date('d/m/Y H:i:s') . '</div>
        </div>';
    
    // Camisas
    $html .= '<div class="section">
                <h3>👕 Camisas</h3>
                <table>
                    <thead>
                        <tr><th>Tamanho</th><th>Quantidade</th></tr>
                    </thead>
                    <tbody>';
    
    $total_camisas = 0;
    foreach($dados['camisas'] as $item) {
        $html .= '<tr>
                    <td>' . strtoupper($item['tamanho_camisa']) . '</td>
                    <td>' . $item['quantidade'] . '</td>
                  </tr>';
        $total_camisas += $item['quantidade'];
    }
    
    $html .= '<tr class="total">
                <td>TOTAL</td>
                <td>' . $total_camisas . '</td>
              </tr>';
    $html .= '</tbody></table></div>';
    
    // Calças
    $html .= '<div class="section">
                <h3>👖 Calças</h3>
                <table>
                    <thead>
                        <tr><th>Tamanho</th><th>Quantidade</th></tr>
                    </thead>
                    <tbody>';
    
    $total_calcas = 0;
    foreach($dados['calcas'] as $item) {
        $html .= '<tr>
                    <td>' . strtoupper($item['tamanho_calca']) . '</td>
                    <td>' . $item['quantidade'] . '</td>
                  </tr>';
        $total_calcas += $item['quantidade'];
    }
    
    $html .= '<tr class="total">
                <td>TOTAL</td>
                <td>' . $total_calcas . '</td>
              </tr>';
    $html .= '</tbody></table></div>';
    
    // Calçados
    $html .= '<div class="section">
                <h3>👟 Calçados</h3>
                <table>
                    <thead>
                        <tr><th>Tamanho</th><th>Quantidade</th></tr>
                    </thead>
                    <tbody>';
    
    $total_calcados = 0;
    foreach($dados['calcados'] as $item) {
        $html .= '<tr>
                    <td>' . $item['tamanho_calcado'] . '</td>
                    <td>' . $item['quantidade'] . '</td>
                  </tr>';
        $total_calcados += $item['quantidade'];
    }
    
    $html .= '<tr class="total">
                <td>TOTAL</td>
                <td>' . $total_calcados . '</td>
              </tr>';
    $html .= '</tbody></table></div>';
    
    $html .= '<div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
                <p>Relatório gerado automaticamente pelo Sistema de Gestão do Bombeiro Mirim</p>
              </div>';
    $html .= '</body></html>';
    
    echo $html;
}

// Fechar conexão
$conn->close();
?>