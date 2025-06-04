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

// Configuração do banco de dados
require "../../../env_config.php";
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

$usuario_id = $_SESSION['usuario_id'];

// Buscar turmas do professor
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nome as nome_unidade 
        FROM turma t 
        JOIN unidade u ON t.id_unidade = u.id 
        WHERE t.id_professor = ?
    ");
    $stmt->execute([$usuario_id]);
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'turmas' => $turmas
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar turmas: ' . $e->getMessage()
    ]);
    exit;
}