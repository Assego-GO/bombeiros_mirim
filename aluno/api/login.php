<?php
// Desativar a exibição de erros HTML
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Registrar erros em um arquivo de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Iniciar sessão
session_start();

// Verificar o caminho do arquivo de configuração
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    // Tentar encontrar em um nível acima
    $config_file = __DIR__ . '/../../config.php';
}

// Incluir arquivo de configuração
if (file_exists($config_file)) {
    require_once $config_file;
} else {
    // Erro se não encontrar o arquivo
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro de configuração: arquivo config.php não encontrado'
    ]);
    exit();
}

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
    if (!isset($_POST["matricula"]) || !isset($_POST["senha"]) || empty($_POST["matricula"]) || empty($_POST["senha"])) {
        jsonResponse('error', 'Por favor, preencha todos os campos.');
    }
    
    // Obter os dados de login
    $matricula = "SA" . $_POST["matricula"];
    $senha = $_POST["senha"];
    
    try {
        // Consulta para verificar o aluno e a senha
        $stmt = $conn->prepare("
            SELECT id, nome, numero_matricula, senha, foto 
            FROM alunos 
            WHERE numero_matricula = ?
        ");
        
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Verificar se encontrou o aluno
        if ($result->num_rows === 0) {
            jsonResponse('error', 'Matrícula não encontrada. Verifique o número ou entre em contato com a secretaria.');
        }
        
        $aluno = $result->fetch_assoc();
        
        // Verificar se o aluno tem senha cadastrada
        if ($aluno['senha'] === null) {
            jsonResponse('error', 'Você ainda não cadastrou sua senha. Por favor, use a opção "Verificar Aluno" para cadastrar sua senha.');
        }
        
        // Verificar a senha
        if (password_verify($senha, $aluno['senha'])) {
            // Login bem-sucedido
            $_SESSION["usuario_id"] = $aluno["id"];
            $_SESSION["usuario_nome"] = $aluno["nome"];
            $_SESSION["usuario_matricula"] = $aluno["numero_matricula"];
            $_SESSION["usuario_foto"] = $aluno["foto"];
            $_SESSION["usuario_tipo"] = 'aluno'; // Definir tipo como 'aluno'
            $_SESSION["logado"] = true;

            // >>> Correção principal para avaliação funcionar também:
            $_SESSION["aluno_id"] = $aluno["id"];

            // Obter informações adicionais do aluno (opcional)
            $stmt = $conn->prepare("
                SELECT e.cidade, e.bairro
                FROM enderecos e
                WHERE e.aluno_id = ?
                LIMIT 1
            ");
            
            $stmt->bind_param("i", $aluno["id"]);
            $stmt->execute();
            $result_endereco = $stmt->get_result();
            
            if ($result_endereco->num_rows > 0) {
                $endereco = $result_endereco->fetch_assoc();
                $_SESSION["usuario_cidade"] = $endereco["cidade"];
                $_SESSION["usuario_bairro"] = $endereco["bairro"];
            }
            
            // Obter informações da matrícula ativa
            $stmt = $conn->prepare("
                SELECT m.unidade, m.turma, m.status
                FROM matriculas m
                WHERE m.aluno_id = ? 
                ORDER BY m.data_matricula DESC
                LIMIT 1
            ");
            
            $stmt->bind_param("i", $aluno["id"]);
            $stmt->execute();
            $result_matricula = $stmt->get_result();
            
            // CORREÇÃO: Use caminho relativo para o redirecionamento
            $redirect = '../aluno/dashboard.php';
            
            if ($result_matricula->num_rows > 0) {
                $matricula_info = $result_matricula->fetch_assoc();
                $_SESSION["usuario_unidade"] = $matricula_info["unidade"];
                $_SESSION["usuario_turma"] = $matricula_info["turma"];
                $_SESSION["usuario_status"] = $matricula_info["status"];
                
                // Se a matrícula estiver pendente, redireciona para a página de pendência
                if ($matricula_info["status"] === 'pendente') {
                    $redirect = '../aluno/matricula_pendente.php';
                }
            }
            
            // Retornar sucesso com o redirecionamento
            jsonResponse('success', 'Login realizado com sucesso! Redirecionando...', [
                'redirect' => $redirect
            ]);
        } else {
            // Senha incorreta
            jsonResponse('error', 'Senha incorreta. Por favor, tente novamente.');
        }
    } catch (Exception $e) {
        // Registrar o erro no log
        error_log('Erro no login: ' . $e->getMessage());
        jsonResponse('error', 'Ocorreu um erro durante o login. Por favor, tente novamente mais tarde.');
    }
} else {
    // Método não permitido
    jsonResponse('error', 'Método não permitido');
}
?>