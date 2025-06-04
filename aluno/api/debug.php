<?php
// Ativar exibição de erros para fins de depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Incluir arquivo de configuração
require_once __DIR__ . '/config1.php';

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
    // Log dos dados recebidos
    error_log("Dados POST recebidos: " . print_r($_POST, true));
    
    // Verificar campos obrigatórios
    if (!isset($_POST["matricula"]) || !isset($_POST["senha"]) || empty($_POST["matricula"]) || empty($_POST["senha"])) {
        jsonResponse('error', 'Por favor, preencha todos os campos.');
    }
    
    // Obter os dados de login
    $matricula = "SA" . $_POST["matricula"];
    $senha = $_POST["senha"];
    
    try {
        // Verificar conexão com o banco
        if ($conn->connect_error) {
            error_log("Erro de conexão: " . $conn->connect_error);
            jsonResponse('error', 'Erro de conexão com o banco de dados.');
        }
        
        // Log da matrícula sendo procurada
        error_log("Buscando matrícula: " . $matricula);
        
        // Consulta para verificar o aluno e a senha
        $stmt = $conn->prepare("
            SELECT id, nome, numero_matricula, senha, foto 
            FROM alunos 
            WHERE numero_matricula = ?
        ");
        
        if (!$stmt) {
            error_log("Erro na preparação da consulta: " . $conn->error);
            jsonResponse('error', 'Erro na preparação da consulta.');
        }
        
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Verificar se encontrou o aluno
        if ($result->num_rows === 0) {
            error_log("Matrícula não encontrada: " . $matricula);
            jsonResponse('error', 'Matrícula não encontrada. Verifique o número ou entre em contato com a secretaria.');
        }
        
        $aluno = $result->fetch_assoc();
        error_log("Aluno encontrado: " . print_r($aluno, true));
        
        // Verificar se o aluno tem senha cadastrada
        if ($aluno['senha'] === null) {
            error_log("Aluno sem senha cadastrada: " . $aluno['id']);
            jsonResponse('error', 'Você ainda não cadastrou sua senha. Por favor, use a opção "Verificar Aluno" para cadastrar sua senha.');
        }
        
        // Verificar a senha
        if (password_verify($senha, $aluno['senha'])) {
            error_log("Senha correta para o aluno: " . $aluno['id']);
            
            // Login bem-sucedido
            $_SESSION["usuario_id"] = $aluno["id"];
            $_SESSION["usuario_nome"] = $aluno["nome"];
            $_SESSION["usuario_matricula"] = $aluno["numero_matricula"];
            $_SESSION["usuario_foto"] = $aluno["foto"];
            $_SESSION["logado"] = true;
            
            // Obter informações adicionais do aluno (opcional)
            $stmt = $conn->prepare("
                SELECT e.cidade, e.bairro
                FROM enderecos e
                WHERE e.aluno_id = ?
                LIMIT 1
            ");
            
            if (!$stmt) {
                error_log("Erro na preparação da consulta de endereço: " . $conn->error);
            } else {
                $stmt->bind_param("i", $aluno["id"]);
                $stmt->execute();
                $result_endereco = $stmt->get_result();
                
                if ($result_endereco->num_rows > 0) {
                    $endereco = $result_endereco->fetch_assoc();
                    $_SESSION["usuario_cidade"] = $endereco["cidade"];
                    $_SESSION["usuario_bairro"] = $endereco["bairro"];
                }
            }
            
            // Obter informações da matrícula ativa
            $stmt = $conn->prepare("
                SELECT m.unidade, m.turma, m.status
                FROM matriculas m
                WHERE m.aluno_id = ? 
                ORDER BY m.data_matricula DESC
                LIMIT 1
            ");
            
            if (!$stmt) {
                error_log("Erro na preparação da consulta de matrícula: " . $conn->error);
                jsonResponse('error', 'Erro ao verificar informações de matrícula.');
            }
            
            $stmt->bind_param("i", $aluno["id"]);
            $stmt->execute();
            $result_matricula = $stmt->get_result();
            
            // Definir redirecionamento padrão para o dashboard
            $redirect = '/luis/superacao/aluno/dashboard.php';
            
            if ($result_matricula->num_rows > 0) {
                $matricula_info = $result_matricula->fetch_assoc();
                $_SESSION["usuario_unidade"] = $matricula_info["unidade"];
                $_SESSION["usuario_turma"] = $matricula_info["turma"];
                $_SESSION["usuario_status"] = $matricula_info["status"];
                
                error_log("Status da matrícula: " . $matricula_info["status"]);
                
                // Se a matrícula estiver pendente, redireciona para a página de pendência
                if ($matricula_info["status"] === 'pendente') {
                    $redirect = '/luis/superacao/aluno/matricula_pendente.php';
                }
            } else {
                error_log("Nenhuma matrícula encontrada para o aluno: " . $aluno['id']);
            }
            
            error_log("Redirecionando para: " . $redirect);
            jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                'redirect' => $redirect
            ]);
        } else {
            error_log("Senha incorreta para o aluno: " . $aluno['id']);
            jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.');
        }
    } catch (Exception $e) {
        // Registrar o erro no log
        error_log('Erro no login: ' . $e->getMessage());
        jsonResponse('error', 'Ocorreu um erro durante o login: ' . $e->getMessage());
    }
} else {
    // Método não permitido
    jsonResponse('error', 'Método não permitido');
}
?>