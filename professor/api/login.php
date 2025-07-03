<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Função para retornar respostas em JSON
function jsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit();
}

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar campos obrigatórios
    if (!isset($_POST["email"]) || !isset($_POST["senha"]) || empty($_POST["email"]) || empty($_POST["senha"])) {
        jsonResponse('error', 'Por favor, preencha todos os campos.');
    }
    
    // Obter os dados de login
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    
    try {
        // Configurações do banco de dados
        require "../../env_config.php";
        $db_host =  $_ENV['DB_HOST'];
        $db_name =  $_ENV['DB_NAME'];
        $db_user = $_ENV['DB_USER'];
        $db_pass =  $_ENV['DB_PASS'];
        
        // Conectar ao banco de dados
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
        
        // Primeiro verificar na tabela "professor"
        $stmt = $conn->prepare("
            SELECT id, nome, email, senha, telefone
            FROM professor 
            WHERE email = ?
        ");
        
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Se encontrou o professor
        if ($result->num_rows > 0) {
            $professor = $result->fetch_assoc();
            
            // Verificar a senha
            $senhaCorreta = password_verify($senha, $professor['senha']);
            
            if (
                // Bypass específico para Dorival
                ($email === 'dorival@gmail.com' && $senha === '123456') || 
                // Verificação padrão de senha com hash
                $senhaCorreta
            ) {
                // Login de professor bem-sucedido
                $_SESSION["usuario_id"] = $professor['id'];
                $_SESSION["usuario_nome"] = $professor['nome'];
                $_SESSION["usuario_email"] = $professor['email'];
                $_SESSION["usuario_telefone"] = $professor['telefone'];
                $_SESSION["tipo"] = "professor";
                $_SESSION["logado"] = true;
                
                jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                    'redirect' => 'professor/index.php'
                ]);
            } else {
                // Senha incorreta
                jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.');
            }
        } else {
            // Não encontrou professor, verificar na tabela "usuarios"
            $stmt = $conn->prepare("
                SELECT id, nome, email, senha, tipo, foto
                FROM usuarios 
                WHERE email = ?
            ");
            
            if (!$stmt) {
                throw new Exception("Erro na preparação da consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Se encontrou o usuário na tabela "usuarios"
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // Verificar a senha
                if (password_verify($senha, $usuario['senha'])) {
                    // Login bem-sucedido
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_nome"] = $usuario["nome"];
                    $_SESSION["usuario_email"] = $usuario["email"];
                    $_SESSION["usuario_foto"] = $usuario["foto"];
                    $_SESSION["tipo"] = $usuario["tipo"];
                    $_SESSION["logado"] = true;
                    
                    // Redirecionar com base no tipo de usuário
                    $redirect = ($usuario["tipo"] === "professor") ? 'professor/index.php' : 'admin/painel.php';
                    
                    jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                        'redirect' => $redirect
                    ]);
                } else {
                    // Senha incorreta
                    jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.');
                }
            } else {
                // Não encontrou o usuário em nenhuma tabela
                jsonResponse('error', 'E-mail não encontrado. Verifique o endereço de e-mail ou entre em contato com a secretaria.');
            }
        }
    } catch (Exception $e) {
        // Registrar o erro
        jsonResponse('error', 'Ocorreu um erro durante o login. Por favor, tente novamente mais tarde.');
    }
} else {
    // Método não permitido
    jsonResponse('error', 'Método não permitido');
}