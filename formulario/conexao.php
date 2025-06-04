<?php

require "../env_config.php";

$host =  $_ENV['DB_HOST'];   
$usuario = $_ENV['DB_USER'];        
$senha = $_ENV['DB_PASS'];         
$banco = $_ENV['DB_NAME'];  


try {
    $conexao = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    
    
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
   
    die('Erro na conexão com o banco de dados: ' . $e->getMessage());
}


function limparDados($dados) {
    $dados = trim($dados);
    $dados = stripslashes($dados);
    $dados = htmlspecialchars($dados);
    return $dados;
}

