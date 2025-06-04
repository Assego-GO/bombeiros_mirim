<?php

require "../env_config.php";
// Configuração da conexão com o banco de dados
$host = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER']; // Altere conforme seu ambiente
$password = $_ENV['DB_PASS']; // Altere conforme seu ambiente
$dbname =  $_ENV['DB_NAME'];





// Estabelecer conexão
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die(json_encode(['error' => 'Falha na conexão: ' . $conn->connect_error]));
}

// Configurar charset
$conn->set_charset("utf8");

// Obter ID da unidade da requisição
$unidadeId = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : 0;

if ($unidadeId <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Consulta SQL para obter turmas desta unidade com informações de capacidade
$sql = "SELECT id, nome_turma, capacidade, matriculados FROM turma 
        WHERE id_unidade = ? AND status = 'Em Andamento'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $unidadeId);
$stmt->execute();
$result = $stmt->get_result();

$turmas = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $turmas[] = $row;
    }
}

// Retornar resposta em formato JSON
header('Content-Type: application/json');
echo json_encode($turmas);

$stmt->close();
$conn->close();
?>