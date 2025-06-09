<?php
// professor/api/conexao.php
require __DIR__ . "/../../env_config.php";

$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$senha = $_ENV['DB_PASS'];
$db_name = $_ENV['DB_NAME'];

$conn = new mysqli($host, $usuario, $senha, $db_name);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>