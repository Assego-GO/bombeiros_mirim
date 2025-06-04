<?php
// Desativar a exibição de erros HTML
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Registrar erros em um arquivo de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Iniciar sessão
session_start();

// Incluir arquivo de configuração
require_once __DIR__ . '/config.php';

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
    // Verificar se usuário está na sessão
    if (!isset($_SESSION["aluno_id"])) {
        jsonResponse('error', 'Sessão expirada. Por favor, verifique o CPF do responsável novamente.');
    }
    
    $aluno_id = $_SESSION["aluno_id"];
    $senha = $_POST["senha"];
    $confirmar_senha = $_POST["confirmar_senha"];
    
    // Verificar se as senhas coincidem
    if ($senha !== $confirmar_senha) {
        jsonResponse('error', 'As senhas não coincidem.');
    } else if (strlen($senha) < 6) {
        jsonResponse('error', 'A senha deve ter pelo menos 6 caracteres.');
    }
    
    try {
        // Hash da senha para armazenamento seguro
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Atualizar a tabela alunos com a senha
        $stmt = $conn->prepare("UPDATE alunos SET senha = ? WHERE id = ?");
        $stmt->bind_param("si", $senha_hash, $aluno_id);
        
        if ($stmt->execute()) {
            // Limpar dados da sessão de verificação
            unset($_SESSION["aluno_id"]);
            unset($_SESSION["aluno_nome"]);
            unset($_SESSION["aluno_matricula"]);
            unset($_SESSION["responsavel_id"]);
            unset($_SESSION["responsavel_nome"]);
            
            jsonResponse('success', 'Senha cadastrada com sucesso! Você já pode fazer login com a matrícula e senha.');
        } else {
            jsonResponse('error', 'Erro ao cadastrar senha: ' . $conn->error);
        }
    } catch (Exception $e) {
        // Registrar o erro no log
        error_log('Erro ao cadastrar senha: ' . $e->getMessage());
        jsonResponse('error', 'Ocorreu um erro ao cadastrar a senha. Por favor, tente novamente mais tarde.');
    }
} else {
    // Método não permitido
    jsonResponse('error', 'Método não permitido');
}
?>