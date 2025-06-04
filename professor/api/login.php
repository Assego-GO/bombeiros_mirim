<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Registrar erros em um arquivo de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_login_debug.log');

// Função de log personalizada
function loginDebugLog($message) {
    error_log('[LOGIN_DEBUG] ' . $message);
    // Opcional: adicionar saída para o navegador durante debug
    echo $message . "<br>";
}

// Iniciar sessão
session_start();

// Função para retornar respostas em JSON ou formato de debug
function jsonResponse($status, $message, $data = null, $debugMode = false) {
    loginDebugLog("Status: $status, Mensagem: $message");
    
    if ($debugMode) {
        // Modo de debug mostra informações detalhadas
        echo "<pre>";
        echo "Status: $status\n";
        echo "Mensagem: $message\n";
        if ($data !== null) {
            print_r($data);
        }
        echo "</pre>";
        exit;
    }
    
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
    // Debug: Log dos dados de entrada
    loginDebugLog("Dados de entrada - Email: " . ($_POST["email"] ?? 'NÃO DEFINIDO'));
    
    // Verificar campos obrigatórios
    if (!isset($_POST["email"]) || !isset($_POST["senha"]) || empty($_POST["email"]) || empty($_POST["senha"])) {
        jsonResponse('error', 'Por favor, preencha todos os campos.', null, true);
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
            loginDebugLog("Erro de conexão: " . $conn->connect_error);
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
        
        // Debug: Verificar todos os professores
        $debugProfessores = $conn->query("SELECT id, nome, email FROM professor");
        loginDebugLog("Professores cadastrados:");
        while ($prof = $debugProfessores->fetch_assoc()) {
            loginDebugLog("ID: {$prof['id']}, Nome: {$prof['nome']}, Email: {$prof['email']}");
        }
        
        // Primeiro verificar na tabela "professor"
        $stmt = $conn->prepare("
            SELECT id, nome, email, senha, telefone
            FROM professor 
            WHERE email = ?
        ");
        
        if (!$stmt) {
            loginDebugLog("Erro na preparação da consulta: " . $conn->error);
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Debug: Log do resultado da consulta
        loginDebugLog("Número de linhas encontradas na tabela professor: " . $result->num_rows);
        
        // Se encontrou o professor
        if ($result->num_rows > 0) {
            $professor = $result->fetch_assoc();
            
            // Debug: Log dos detalhes do professor
            loginDebugLog("Professor encontrado - ID: {$professor['id']}, Nome: {$professor['nome']}, Email: {$professor['email']}");
            loginDebugLog("Senha armazenada: {$professor['senha']}");
            
            // Verificar a senha
            $senhaCorreta = password_verify($senha, $professor['senha']);
            
            loginDebugLog("Verificação de senha: " . ($senhaCorreta ? 'CORRETA' : 'INCORRETA'));
            
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
                
                loginDebugLog("Login de professor bem-sucedido");
                
                jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                    'redirect' => 'professor/index.php'
                ], true);
            } else {
                // Senha incorreta
                loginDebugLog("Senha incorreta para professor");
                jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.', null, true);
            }
        } else {
            // Não encontrou professor, verificar na tabela "usuarios"
            loginDebugLog("Nenhum professor encontrado, verificando tabela de usuários");
            
            $stmt = $conn->prepare("
                SELECT id, nome, email, senha, tipo, foto
                FROM usuarios 
                WHERE email = ?
            ");
            
            if (!$stmt) {
                loginDebugLog("Erro na preparação da consulta de usuários: " . $conn->error);
                throw new Exception("Erro na preparação da consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Debug: Log do resultado da consulta de usuários
            loginDebugLog("Número de linhas encontradas na tabela usuarios: " . $result->num_rows);
            
            // Se encontrou o usuário na tabela "usuarios"
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // Debug: Log dos detalhes do usuário
                loginDebugLog("Usuário encontrado - ID: {$usuario['id']}, Nome: {$usuario['nome']}, Email: {$usuario['email']}, Tipo: {$usuario['tipo']}");
                
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
                    
                    loginDebugLog("Login de usuário bem-sucedido, redirecionando para: $redirect");
                    
                    jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                        'redirect' => $redirect
                    ], true);
                } else {
                    // Senha incorreta
                    loginDebugLog("Senha incorreta para usuário");
                    jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.', null, true);
                }
            } else {
                // Não encontrou o usuário em nenhuma tabela
                loginDebugLog("Email não encontrado em nenhuma tabela");
                jsonResponse('error', 'E-mail não encontrado. Verifique o endereço de e-mail ou entre em contato com a secretaria.', null, true);
            }
        }
    } catch (Exception $e) {
        // Registrar o erro no log
        loginDebugLog('Erro no login: ' . $e->getMessage());
        jsonResponse('error', 'Ocorreu um erro durante o login. Por favor, tente novamente mais tarde.', null, true);
    }
} else {
    // Método não permitido
    loginDebugLog('Método de requisição não permitido');
    jsonResponse('error', 'Método não permitido', null, true);
}