<?php
// Desativar a exibição de erros HTML
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Registrar erros em arquivo de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
require "../../env_config.php";

// Configuração da conexão com o banco de dados
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];// Substitua pelo seu usuário real do MySQL
$password = $_ENV['DB_PASS'];  // Substitua pela sua senha real do MySQL
$dbname =  $_ENV['DB_NAME'];


// Tratar erros de conexão sem gerar HTML
try {
    // Criar conexão
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar se houve erro na conexão
    if ($conn->connect_error) {
        // Registrar o erro em log, mas não exibir diretamente
        error_log("Erro de conexão com o banco de dados: " . $conn->connect_error);
        
        // Se este arquivo for acessado diretamente, retornar JSON
        if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error', 
                'message' => 'Erro na conexão com o banco de dados'
            ]);
            exit();
        }
        
        // Se este arquivo for incluído em outro, lançar exceção
        // para ser capturada pelo bloco try/catch do arquivo principal
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // Definir conjunto de caracteres
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Registrar o erro em log
    error_log("Erro de conexão: " . $e->getMessage());
    
    // Se este arquivo for acessado diretamente, retornar JSON
    if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error', 
            'message' => 'Erro na conexão com o banco de dados'
        ]);
        exit();
    }
    
    // Se for incluído em outro arquivo, a exceção será propagada
    // para ser tratada pelo bloco try/catch do arquivo principal
}
?>