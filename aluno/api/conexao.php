<?php
require "../../env_config.php";
// conexao.php - Arquivo para conexão com o banco de dados
$host =  $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER']; // Substitua pelo seu usuário
$senha = $_ENV['DB_PASS']; // Substitua pela sua senha
$banco = $_ENV['DB_NAME']; 



try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
    die();
}
?>