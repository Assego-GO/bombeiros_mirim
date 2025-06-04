<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificações mais detalhadas de autenticação
if (!isset($_SESSION['usuario_id'])) {
    // Log detalhado
    error_log('Sem usuário na sessão ao tentar gerar PDF');
    header('Location: ../index.php');
    exit;
}

// Verificação específica para admin
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    // Log detalhado
    error_log('Tentativa de acesso de não admin: ' . 
        (isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : 'tipo não definido') . 
        ' (ID: ' . $_SESSION['usuario_id'] . ')');
    header('Location: ../index.php');
    exit;
}
// Definir reporte de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tentar encontrar o arquivo de conexão
$possivelCaminho1 = dirname(__FILE__) . '/../config/conexao.php';
$possivelCaminho2 = dirname(__FILE__) . '/config/conexao.php';
$possivelCaminho3 = '/var/www/html/luis/superacao/matricula/config/conexao.php';
require "../../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

if (file_exists($possivelCaminho1)) {
    require_once $possivelCaminho1;
} elseif (file_exists($possivelCaminho2)) {
    require_once $possivelCaminho2;
} elseif (file_exists($possivelCaminho3)) {
    require_once $possivelCaminho3;
} else {
    // Criar conexão manualmente como fallback
    $conexao = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conexao->connect_error) {
        die("Não foi possível conectar ao banco de dados: " . $conexao->connect_error);
    }
}

// Função para formatar a data
function formatarData($dataString) {
    if (empty($dataString)) return '-';
    
    $timestamp = strtotime($dataString);
    if ($timestamp === false) return $dataString;
    
    return date('d/m/Y', $timestamp);
}

// Função para formatar o status
function formatarStatusTexto($status) {
    if (empty($status)) return '-';
    
    switch(strtolower($status)) {
        case 'ativo':
            return 'Ativo';
        case 'inativo':
            return 'Inativo';
        case 'pendente':
            return 'Pendente';
        default:
            return ucfirst($status);
    }
}

// Função para obter a classe CSS do status
function getStatusClass($status) {
    switch(strtolower($status)) {
        case 'ativo':
            return 'status-ativo';
        case 'inativo':
            return 'status-inativo';
        case 'pendente':
            return 'status-pendente';
        default:
            return '';
    }
}

try {
    // Processar parâmetros de filtro
    $filtros = [
        'aluno' => isset($_GET['aluno']) ? $_GET['aluno'] : '',
        'unidade' => isset($_GET['unidade']) ? $_GET['unidade'] : '',
        'turma' => isset($_GET['turma']) ? $_GET['turma'] : '',
        'status' => isset($_GET['status']) ? $_GET['status'] : '',
        'data_inicial' => isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '',
        'data_final' => isset($_GET['data_final']) ? $_GET['data_final'] : '',
    ];
    
    // Construir a query SQL utilizando as tabelas corretas conforme estrutura do BD
    $query = "SELECT 
                a.id as aluno_id, 
                a.nome as aluno_nome, 
                m.data_matricula, 
                m.status,
                u.nome as unidade_nome,
                t.nome_turma as turma_nome
              FROM 
                alunos a
              LEFT JOIN 
                matriculas m ON a.id = m.aluno_id
              LEFT JOIN 
                unidade u ON m.unidade = u.id
              LEFT JOIN 
                turma t ON m.turma = t.id";
    
    // Condições WHERE começam aqui
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($filtros['aluno'])) {
        $whereConditions[] = "a.nome LIKE ?";
        $params[] = '%' . $filtros['aluno'] . '%';
        $types .= 's';
    }
    
    if (!empty($filtros['unidade'])) {
        $whereConditions[] = "m.unidade = ?";
        $params[] = $filtros['unidade'];
        $types .= 's';
    }
    
    if (!empty($filtros['turma'])) {
        $whereConditions[] = "m.turma = ?";
        $params[] = $filtros['turma'];
        $types .= 's';
    }
    
    if (!empty($filtros['status'])) {
        $whereConditions[] = "m.status = ?";
        $params[] = $filtros['status'];
        $types .= 's';
    }
    
    if (!empty($filtros['data_inicial'])) {
        $whereConditions[] = "m.data_matricula >= ?";
        $params[] = $filtros['data_inicial'];
        $types .= 's';
    }
    
    if (!empty($filtros['data_final'])) {
        $whereConditions[] = "m.data_matricula <= ?";
        $params[] = $filtros['data_final'];
        $types .= 's';
    }
    
    // Adicionar cláusula WHERE se houver condições
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Adicionar ORDER BY
    $query .= " ORDER BY a.nome";
    
    // Preparar e executar a consulta
    $stmt = $conexao->prepare($query);
    
    // Vincular parâmetros, se houver
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Executar a consulta
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar consulta: " . $stmt->error . " Query: " . $query);
    }
    
    $resultado = $stmt->get_result();
    $matriculas = [];
    
    // Verificar se há resultados
    if ($resultado) {
        $matriculas = $resultado->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Erro ao obter resultados da consulta");
    }
    
    // Carregar nomes de responsáveis separadamente para simplificar
    foreach ($matriculas as $key => $matricula) {
        $queryResp = "SELECT r.nome FROM responsaveis r 
                     JOIN aluno_responsavel ar ON r.id = ar.responsavel_id 
                     WHERE ar.aluno_id = ?";
        $stmtResp = $conexao->prepare($queryResp);
        $stmtResp->bind_param('i', $matricula['aluno_id']);
        $stmtResp->execute();
        $resultResp = $stmtResp->get_result();
        
        $responsaveis = [];
        while ($row = $resultResp->fetch_assoc()) {
            $responsaveis[] = $row['nome'];
        }
        
        $matriculas[$key]['responsaveis'] = implode(', ', $responsaveis);
        $stmtResp->close();
    }
    
    // Buscar nomes completos de unidades e turmas para os filtros
    if (!empty($filtros['unidade'])) {
        $stmtUnidade = $conexao->prepare("SELECT nome FROM unidade WHERE id = ?");
        $stmtUnidade->bind_param('s', $filtros['unidade']);
        $stmtUnidade->execute();
        $resultUnidade = $stmtUnidade->get_result();
        if ($rowUnidade = $resultUnidade->fetch_assoc()) {
            $filtros['unidade'] = $rowUnidade['nome'];
        }
        $stmtUnidade->close();
    }
    
    if (!empty($filtros['turma'])) {
        $stmtTurma = $conexao->prepare("SELECT nome_turma FROM turma WHERE id = ?");
        $stmtTurma->bind_param('s', $filtros['turma']);
        $stmtTurma->execute();
        $resultTurma = $stmtTurma->get_result();
        if ($rowTurma = $resultTurma->fetch_assoc()) {
            $filtros['turma'] = $rowTurma['nome_turma'];
        }
        $stmtTurma->close();
    }
    
    // Formatar datas nos filtros para exibição
    if (!empty($filtros['data_inicial'])) {
        $filtros['data_inicial'] = formatarData($filtros['data_inicial']);
    }
    
    if (!empty($filtros['data_final'])) {
        $filtros['data_final'] = formatarData($filtros['data_final']);
    }
    
    // Fechar o statement antes de gerar o HTML
    $stmt->close();
    
    // Gerar a saída HTML
    $dataAtual = date('d/m/Y H:i:s');
    $usuarioNome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Usuário do Sistema';
    $totalMatriculas = count($matriculas);
    
    // Iniciar o HTML
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Matrículas - SuperAção</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0078d7;
            padding-bottom: 20px;
        }
        .logo {
            max-width: 100px;
            margin-right: 20px;
        }
        .header-text {
            flex-grow: 1;
        }
        .header-text h1 {
            margin: 0;
            color: #0078d7;
            font-size: 24px;
        }
        .header-text h2 {
            margin: 5px 0 0;
            font-size: 18px;
            font-weight: normal;
            color: #666;
        }
        .meta-info {
            text-align: right;
            font-size: 14px;
            color: #666;
        }
        .meta-info p {
            margin: 5px 0;
        }
        .report-title {
            font-size: 22px;
            margin: 20px 0;
            color: #0078d7;
            text-align: center;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary h3 {
            margin-top: 0;
            color: #0078d7;
        }
        .filters-list {
            margin-bottom: 10px;
        }
        .filter-item {
            margin-bottom: 5px;
        }
        .filter-label {
            font-weight: bold;
            color: #555;
        }
        .table-container {
            margin: 20px 0;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #0078d7;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-ativo {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inativo {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }
        .legend {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .legend h3 {
            margin-top: 0;
            color: #0078d7;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 3px;
        }
        .legend-label {
            font-weight: bold;
        }
        .legend-description {
            margin-left: 10px;
            color: #666;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .print-button {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .print-button button {
            background-color: #0078d7;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .print-button button:hover {
            background-color: #005a9e;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
            }
            .print-button {
                display: none;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-text">
                <h1>SuperAção</h1>
                <h2>Relatório de Matrículas</h2>
            </div>
            <div class="meta-info">
                <p><strong>Data:</strong> <?php echo $dataAtual; ?></p>
                <p><strong>Gerado por:</strong> <?php echo $usuarioNome; ?></p>
            </div>
        </div>

        <h2 class="report-title">Relatório de Matrículas</h2>

        <div class="summary">
            <h3>Resumo do Relatório</h3>
            <p><strong>Total de Matrículas:</strong> <?php echo $totalMatriculas; ?></p>
            
            <div class="filters-list">
                <h4>Filtros Aplicados:</h4>
                <?php
                $filtrosAtivos = false;
                foreach ($filtros as $nome => $valor) {
                    if (!empty($valor)) {
                        $filtrosAtivos = true;
                        echo '<div class="filter-item">';
                        echo '<span class="filter-label">' . ucfirst($nome) . ':</span> ' . $valor;
                        echo '</div>';
                    }
                }
                
                if (!$filtrosAtivos) {
                    echo '<div class="filter-item">Nenhum filtro aplicado - Exibindo todas as matrículas</div>';
                }
                ?>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome do Aluno</th>
                        <th>Responsável</th>
                        <th>Unidade</th>
                        <th>Turma</th>
                        <th>Data da Matrícula</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($matriculas) > 0): ?>
                        <?php foreach ($matriculas as $m): ?>
                            <tr>
                                <td><?php echo isset($m['aluno_nome']) ? $m['aluno_nome'] : '-'; ?></td>
                                <td><?php echo isset($m['responsaveis']) ? $m['responsaveis'] : '-'; ?></td>
                                <td><?php echo isset($m['unidade_nome']) ? $m['unidade_nome'] : '-'; ?></td>
                                <td><?php echo isset($m['turma_nome']) ? $m['turma_nome'] : '-'; ?></td>
                                <td><?php echo isset($m['data_matricula']) ? formatarData($m['data_matricula']) : '-'; ?></td>
                                <td>
                                    <?php if (isset($m['status'])): ?>
                                        <span class="status <?php echo getStatusClass($m['status']); ?>">
                                            <?php echo formatarStatusTexto($m['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">Nenhuma matrícula encontrada com os filtros aplicados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="legend">
            <h3>Legenda - Status</h3>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #d4edda;"></div>
                <span class="legend-label">Ativo:</span>
                <span class="legend-description">Aluno matriculado e frequentando regularmente</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #fff3cd;"></div>
                <span class="legend-label">Pendente:</span>
                <span class="legend-description">Matrícula em processamento ou com pendências</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #f8d7da;"></div>
                <span class="legend-label">Inativo:</span>
                <span class="legend-description">Aluno não está mais frequentando ou matrícula cancelada</span>
            </div>
        </div>

        <div class="print-button">
            <button onclick="window.print()">Imprimir Relatório</button>
        </div>

        <div class="footer">
            <p>SuperAção - Todos os direitos reservados © <?php echo date('Y'); ?></p>
            <p>Este relatório contém informações confidenciais para uso interno da instituição SuperAção. Não compartilhe sem autorização.</p>
        </div>
    </div>

    <script>
        // Imprimir automaticamente ao carregar a página
        window.onload = function() {
            // Esperar 1 segundo para garantir que tudo foi carregado corretamente
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
<?php
} catch (Exception $e) {
    // Registrar o erro em um arquivo de log
    error_log('Erro na geração do relatório: ' . $e->getMessage(), 0);
    
    // Exibir mensagem de erro amigável
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Erro na Geração do Relatório</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
            .error-container { max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #e3e3e3; border-radius: 5px; background-color: #f9f9f9; }
            h1 { color: #d9534f; }
            .back-btn { display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #337ab7; color: white; text-decoration: none; border-radius: 4px; }
            .error-details { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px; }
            .error-message { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Erro na Geração do Relatório</h1>
            <p>Ocorreu um erro ao tentar gerar o relatório de matrículas. Por favor, tente novamente ou entre em contato com o suporte.</p>
            
            <a href="../matricula.php" class="back-btn">Voltar para a página de matrículas</a>
            
            <div class="error-details">
                <h3>Detalhes técnicos:</h3>
                <div class="error-message">
                    ' . htmlspecialchars($e->getMessage()) . '
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Fechar conexão com o banco de dados
if (isset($conexao)) {
    $conexao->close();
}
?>