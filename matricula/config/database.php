<?php
/**
 * Arquivo de configuração e conexão com o banco de dados
 * Salve este arquivo em: /luis/matricula/config/database.php
 */

// Configurações do banco de dados - AJUSTE CONFORME SEU AMBIENTE

require "../../env_config.php";
$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];// Nome do banco de dados

// Tratamento de erros
try {
    // Criar conexão
    $conn = new mysqli($db_host, $db_usuario, $db_senha, $db_nome);
    
    // Verificar conexão
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }
    
    // Configurar charset para UTF-8
    if (!$conn->set_charset("utf8")) {
        error_log("Erro ao configurar charset: " . $conn->error);
    }
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    
    // Em ambiente de produção, você pode querer retornar uma mensagem genérica
    // ou redirecionar para uma página de erro, em vez de mostrar detalhes da conexão
    if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
        // Resposta para APIs
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
        exit;
    } else {
        // Resposta para páginas normais
        echo "Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.";
        exit;
    }
}

// Se você precisar de configurações adicionais, como PDO em vez de mysqli, você pode 
// adicionar funções auxiliares abaixo.

/**
 * Função auxiliar para executar consultas com prepared statements de forma simplificada
 * @param string $sql - A consulta SQL com placeholders (?)
 * @param array $params - Array com os parâmetros para a consulta
 * @param string $types - String com os tipos dos parâmetros (i: integer, s: string, d: double, b: blob)
 * @return mysqli_result|bool - Retorna o resultado da consulta ou false em caso de erro
 */
function execQuery($sql, $params = [], $types = null) {
    global $conn;
    
    if (!$conn) {
        error_log("Conexão não disponível para executar a consulta");
        return false;
    }
    
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Erro ao preparar a consulta: " . $conn->error);
            return false;
        }
        
        // Se tiver parâmetros, fazer o bind
        if (!empty($params)) {
            // Se os tipos não foram especificados, tentar inferir
            if ($types === null) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 's'; // Padrão para outros tipos
                    }
                }
            }
            
            // Preparar array para bind_param
            $bindParams = array($types);
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            
            // Usar call_user_func_array para compatibilidade
            call_user_func_array(array($stmt, 'bind_param'), $bindParams);
        }
        
        // Executar a consulta
        if (!$stmt->execute()) {
            error_log("Erro ao executar a consulta: " . $stmt->error);
            return false;
        }
        
        // Obter resultados, se houver
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result ?? true;
        
    } catch (Exception $e) {
        error_log("Exceção ao executar a consulta: " . $e->getMessage());
        return false;
    }
}