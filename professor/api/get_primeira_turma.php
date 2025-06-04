<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado']);
    exit;
}

$professor_id = $_SESSION['usuario_id'];

// Conexão com o banco

require "../../env_config.php";
$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar a primeira turma do professor
    $query = "SELECT id FROM turma WHERE id_professor = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$professor_id]);
    
    if ($stmt->rowCount() > 0) {
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'turma_id' => $turma['id']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nenhuma turma encontrada']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
?>