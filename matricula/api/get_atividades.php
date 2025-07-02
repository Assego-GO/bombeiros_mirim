<?php
// api/get_atividades_simples.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// ===== CONFIGURAR CONEXÃO =====
$env_paths = [
    __DIR__ . "/../env_config.php",
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
    echo json_encode(['success' => false, 'message' => 'Configurações não encontradas']);
    exit;
}

$db_host = $_ENV['DB_HOST'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? '';
$db_user = $_ENV['DB_USER'] ?? '';
$db_pass = $_ENV['DB_PASS'] ?? '';

try {
    // Conexão com o banco usando PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Query SIMPLES - apenas tabela atividades
    $sql = "SELECT * FROM atividades ORDER BY data_atividade DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $atividades = $stmt->fetchAll();
    
    // Processar dados simples
    $atividadesProcessadas = [];
    
    foreach ($atividades as $atividade) {
        $atividadesProcessadas[] = [
            'id' => (int) $atividade['id'],
            'nome_atividade' => $atividade['nome_atividade'],
            'turma_id' => (int) $atividade['turma_id'],
            'turma_nome' => 'Turma ' . $atividade['turma_id'], // Fallback
            'professor_id' => (int) $atividade['professor_id'],
            'professor_nome' => 'Professor ' . $atividade['professor_id'], // Fallback
            'unidade_id' => null,
            'unidade_nome' => 'Unidade não informada',
            'data_atividade' => $atividade['data_atividade'],
            'hora_inicio' => $atividade['hora_inicio'],
            'hora_termino' => $atividade['hora_termino'],
            'local_atividade' => $atividade['local_atividade'],
            'instrutor_responsavel' => $atividade['instrutor_responsavel'],
            'objetivo_atividade' => $atividade['objetivo_atividade'],
            'conteudo_abordado' => $atividade['conteudo_abordado'],
            'anexos' => null,
            'status' => $atividade['status'],
            'criado_em' => date('d/m/Y H:i:s', strtotime($atividade['criado_em'])),
            'atualizado_em' => date('d/m/Y H:i:s', strtotime($atividade['atualizado_em'])),
            'eh_hoje' => $atividade['data_atividade'] === date('Y-m-d'),
            'eh_futura' => $atividade['data_atividade'] > date('Y-m-d')
        ];
    }
    
    // Estatísticas
    $stats = [
        'total' => count($atividadesProcessadas),
        'planejadas' => count(array_filter($atividadesProcessadas, fn($a) => $a['status'] === 'planejada')),
        'em_andamento' => count(array_filter($atividadesProcessadas, fn($a) => $a['status'] === 'em_andamento')),
        'concluidas' => count(array_filter($atividadesProcessadas, fn($a) => $a['status'] === 'concluida')),
        'canceladas' => count(array_filter($atividadesProcessadas, fn($a) => $a['status'] === 'cancelada'))
    ];
    
    echo json_encode([
        'success' => true,
        'atividades' => $atividadesProcessadas,
        'stats' => $stats,
        'message' => 'Atividades carregadas com sucesso (versão simples)'
    ], JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>