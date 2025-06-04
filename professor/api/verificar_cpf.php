<?php
// Exibir erros durante o desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão
session_start();

// Incluir arquivo de configuração
require_once __DIR__ . '/config.php';

// Verificar se a conexão com o banco está funcionando
if (!isset($conn) || $conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erro na conexão com o banco de dados']);
    exit();
}

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se o CPF foi fornecido
    if (!isset($_POST["cpf"]) || empty($_POST["cpf"])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Por favor, digite o CPF do responsável.']);
        exit();
    }
    
    // Obter e limpar o CPF
    $cpf = preg_replace('/[^0-9]/', '', $_POST["cpf"]);
    
    // Verificar se o CPF tem 11 dígitos
    if (strlen($cpf) !== 11) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CPF inválido. Por favor, digite um CPF válido com 11 dígitos.']);
        exit();
    }
    
    // Formatar CPF para busca no banco
    $cpfFormatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    
    // Consulta para obter o responsável pelo CPF
    $stmt = $conn->prepare("SELECT id, nome, cpf, parentesco FROM responsaveis WHERE cpf = ?");
    $stmt->bind_param("s", $cpfFormatado);
    $stmt->execute();
    $result_responsavel = $stmt->get_result();
    
    // Verificar se encontrou o responsável
    if ($result_responsavel->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CPF não encontrado. Verifique se digitou corretamente ou entre em contato com a secretaria.']);
        exit();
    }
    
    $responsavel = $result_responsavel->fetch_assoc();
    
    // Consulta para obter o(s) aluno(s) vinculado(s) a este responsável
    $stmt = $conn->prepare("
        SELECT a.id, a.nome, a.numero_matricula, a.senha
        FROM alunos a
        JOIN aluno_responsavel ar ON a.id = ar.aluno_id
        WHERE ar.responsavel_id = ?
    ");
    $stmt->bind_param("i", $responsavel['id']);
    $stmt->execute();
    $result_alunos = $stmt->get_result();
    
    // Verificar se encontrou algum aluno vinculado
    if ($result_alunos->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Não há alunos vinculados a este responsável. Entre em contato com a secretaria.']);
        exit();
    }
    
    // Pegar o primeiro aluno (caso haja mais de um, poderíamos listar todos)
    $aluno = $result_alunos->fetch_assoc();
    
    // Verificar se o aluno já tem senha cadastrada
    if (!empty($aluno['senha'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Este aluno já possui senha cadastrada. Por favor, faça login com a matrícula e senha.']);
        exit();
    }
    
    // Guardar informações na sessão para uso posterior
    $_SESSION["aluno_id"] = $aluno["id"];
    $_SESSION["aluno_nome"] = $aluno["nome"];
    $_SESSION["aluno_matricula"] = $aluno["numero_matricula"];
    $_SESSION["responsavel_id"] = $responsavel["id"];
    $_SESSION["responsavel_nome"] = $responsavel["nome"];
    
    // Retornar sucesso com os dados do aluno e responsável
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Aluno encontrado! Por favor, cadastre uma senha para acesso.',
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
    exit();
} else {
    // Método não permitido
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    exit();
}
?>