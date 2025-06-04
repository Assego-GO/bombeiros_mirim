<?php
// Desativar a exibição de erros no navegador (muito importante)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Registrar erros em um arquivo de log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Registrar o recebimento da requisição e os dados POST para depuração
error_log('Requisição recebida em verificar_cpf.php');
error_log('Dados POST: ' . print_r($_POST, true));

// Função para tratar erros e retornar JSON
function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => $message
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

// Capturar erros fatais e convertê-los em JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Erro fatal: " . $error['message']
        ]);
    }
});

try {
    // Iniciar sessão
    session_start();

    // Incluir arquivo de configuração com caminho absoluto
    $config_file = __DIR__ . '/config.php';
    if (!file_exists($config_file)) {
        handleError("Arquivo de configuração não encontrado");
    }
    require_once $config_file;

    // Verificar se a conexão com o banco está funcionando
    if (!isset($conn) || $conn->connect_error) {
        handleError("Falha na conexão com o banco de dados");
    }

    // Verificar se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Verificar se o CPF foi fornecido
        if (!isset($_POST["cpf"]) || empty($_POST["cpf"])) {
            jsonResponse('error', 'Por favor, digite o CPF do responsável.');
        }
        
        // Obter e limpar o CPF
        $cpf = preg_replace('/[^0-9]/', '', $_POST["cpf"]);
        
        // Registrar o CPF limpo para depuração
        error_log('CPF limpo: ' . $cpf);
        
        // Verificar se o CPF tem 11 dígitos
        if (strlen($cpf) !== 11) {
            jsonResponse('error', 'CPF inválido. Por favor, digite um CPF válido com 11 dígitos.');
        }
        
        // Formatar CPF para busca no banco
        $cpfFormatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        
        // Registrar o CPF formatado para depuração
        error_log('CPF formatado: ' . $cpfFormatado);
        
        // Consulta para obter o responsável pelo CPF
        $stmt = $conn->prepare("
            SELECT id, nome, cpf, parentesco
            FROM responsaveis
            WHERE cpf = ?
        ");
        
        if (!$stmt) {
            error_log('Erro na preparação da consulta: ' . $conn->error);
            handleError("Erro na preparação da consulta: " . $conn->error);
        }
        
        $stmt->bind_param("s", $cpfFormatado);
        $stmt->execute();
        $result_responsavel = $stmt->get_result();
        
        // Verificar se encontrou o responsável
        if ($result_responsavel->num_rows === 0) {
            jsonResponse('error', 'CPF não encontrado. Verifique se digitou corretamente ou entre em contato com a secretaria.');
        }
        
        $responsavel = $result_responsavel->fetch_assoc();
        error_log('Responsável encontrado: ' . print_r($responsavel, true));
        
        // Consulta para obter o(s) aluno(s) vinculado(s) a este responsável
        // Corrigindo para usar a estrutura correta da tabela
        $stmt = $conn->prepare("
            SELECT a.id, a.nome, a.numero_matricula, a.senha
            FROM alunos a
            JOIN aluno_responsavel ar ON a.id = ar.aluno_id
            WHERE ar.responsavel_id = ?
        ");
        
        if (!$stmt) {
            error_log('Erro na preparação da consulta de alunos: ' . $conn->error);
            handleError("Erro na preparação da consulta de alunos: " . $conn->error);
        }
        
        $stmt->bind_param("i", $responsavel['id']);
        $stmt->execute();
        $result_alunos = $stmt->get_result();
        
        // Verificar se encontrou algum aluno vinculado
        if ($result_alunos->num_rows === 0) {
            jsonResponse('error', 'Não há alunos vinculados a este responsável. Entre em contato com a secretaria.');
        }
        
        // Registrar o número de alunos encontrados para depuração
        error_log('Número de alunos encontrados: ' . $result_alunos->num_rows);
        
        // Pegar o primeiro aluno (caso haja mais de um, poderíamos listar todos)
        $aluno = $result_alunos->fetch_assoc();
        error_log('Dados do aluno: ' . print_r($aluno, true));
        
        // Verificar se o aluno já tem senha cadastrada
        if ($aluno['senha'] !== null && $aluno['senha'] !== '') {
            jsonResponse('error', 'Este aluno já possui senha cadastrada. Por favor, faça login com a matrícula e senha.');
        }
        
        // Guardar informações na sessão para uso posterior
        $_SESSION["aluno_id"] = $aluno["id"]; // Usando id conforme a estrutura do banco
        $_SESSION["aluno_nome"] = $aluno["nome"];
        $_SESSION["aluno_matricula"] = $aluno["numero_matricula"];
        $_SESSION["responsavel_id"] = $responsavel["id"];
        $_SESSION["responsavel_nome"] = $responsavel["nome"];
        
        // Retornar sucesso com os dados do aluno e responsável
        jsonResponse('success', 'Aluno encontrado! Por favor, cadastre uma senha para acesso.', [
            'aluno' => [
                'id' => $aluno['id'],
                'nome' => $aluno['nome'],
                'numero_matricula' => $aluno['numero_matricula']
            ],
            'responsavel' => [
                'id' => $responsavel['id'],
                'nome' => $responsavel['nome'],
                'parentesco' => $responsavel['parentesco']
            ]
        ]);
        
    } else {
        // Método não permitido
        jsonResponse('error', 'Método não permitido');
    }
} catch (Exception $e) {
    // Registrar o erro no log
    error_log('Erro na verificação de CPF: ' . $e->getMessage());
    jsonResponse('error', 'Ocorreu um erro ao verificar o CPF. Por favor, tente novamente mais tarde.');
}

// Se chegar aqui, algo está errado
handleError("Requisição inválida");
?>