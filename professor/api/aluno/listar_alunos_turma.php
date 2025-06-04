<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_GET['turma_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da turma ausente']);
    exit;
}

$turma_id = $_GET['turma_id'];

require "../../../env_config.php";
$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT a.id, a.nome, a.numero_matricula
        FROM alunos a
        JOIN matriculas m ON a.id = m.aluno_id
        WHERE m.turma = ? AND m.status IN ('ativo', 'pendente')
        ORDER BY a.nome
    ");
    $stmt->execute([$turma_id]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'alunos' => $alunos
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
}
