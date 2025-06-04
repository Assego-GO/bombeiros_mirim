<?php
// Iniciar sessão
session_start();

// Headers para JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar se foi enviado o ID da turma
if (!isset($_GET['turma_id']) || empty($_GET['turma_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da turma não especificado'
    ]);
    exit;
}

// Configuração do banco de dados
require "../../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

$turma_id = $_GET['turma_id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o professor tem acesso à turma
try {
    $stmt = $pdo->prepare("SELECT id FROM turma WHERE id = ? AND id_professor = ?");
    $stmt->execute([$turma_id, $usuario_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Você não tem acesso a esta turma'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar acesso à turma: ' . $e->getMessage()
    ]);
    exit;
}

// Buscar alunos da turma
try {
    $stmt = $pdo->prepare("
        SELECT a.*, e.cidade, e.bairro 
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        LEFT JOIN enderecos e ON a.id = e.aluno_id
        WHERE m.turma = ? AND m.status != 'inativo'
        ORDER BY a.nome ASC
    ");
    $stmt->execute([$turma_id]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Definir a URL base do projeto
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $caminhoScript = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = preg_replace('/(\/aluno|\/admin|\/painel|\/professor|\/api)$/', '', $caminhoScript);
    $baseUrl = $protocolo . $host . $basePath;

    // Processar URLs de fotos dos alunos
    foreach ($alunos as &$aluno) {
        if (!empty($aluno['foto'])) {
            $filename = basename($aluno['foto']); // apenas o nome do arquivo
            $aluno['foto'] = $baseUrl . '/uploads/fotos/' . $filename;
        }
    }

    echo json_encode([
        'success' => true,
        'alunos' => $alunos
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar alunos: ' . $e->getMessage()
    ]);
    exit;
}
