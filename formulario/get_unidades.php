<?php
// Configuração da conexão com o banco de dados


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

// Consulta SQL para obter unidades ativas
$sql = "SELECT id, nome FROM unidade WHERE 1";
$result = $conn->query($sql);

$unidades = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Retornar resposta em formato JSON
header('Content-Type: application/json');
echo json_encode($unidades);

$conn->close();
?>