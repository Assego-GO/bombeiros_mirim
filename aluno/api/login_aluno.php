<?php
// login_aluno.php
session_start();
include 'conexao.php';

// Limpar possíveis sessões anteriores
session_unset();
session_destroy();
session_start();

$matricula = $_POST['matricula'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($matricula) || empty($senha)) {
    $_SESSION['erro_login'] = 'Por favor, preencha matrícula e senha';
    echo json_encode([
        'erro' => 'Preencha matrícula e senha', 
        'redirect' => '../index.php'
    ]);
    exit;
}

// Certificar-se de que a matrícula começa com SA
$matricula = strpos($matricula, 'SA') === false ? 'SA' . $matricula : $matricula;

$sql = "SELECT * FROM alunos WHERE numero_matricula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matricula);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['erro_login'] = 'Matrícula não encontrada';
    echo json_encode([
        'erro' => 'Matrícula não encontrada', 
        'redirect' => '../index.php'
    ]);
    exit;
}

$aluno = $result->fetch_assoc();

if (empty($aluno['senha'])) {
    // Primeiro acesso - cadastrar senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sqlUpdate = "UPDATE alunos SET senha = ? WHERE numero_matricula = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ss", $senhaHash, $matricula);
    $stmtUpdate->execute();

    // Configurar sessão com informações do aluno
    $_SESSION['usuario_id'] = $aluno['id'];
    $_SESSION['usuario_nome'] = $aluno['nome'];
    $_SESSION['usuario_matricula'] = $aluno['numero_matricula'];
    $_SESSION['usuario_tipo'] = 'aluno';
    $_SESSION['usuario_foto'] = $aluno['foto'] ?? '';
    $_SESSION['logado'] = true;

    echo json_encode([
        'sucesso' => 'Senha cadastrada com sucesso', 
        'primeiro_acesso' => true,
        'redirect' => '../aluno/dashboard.php'
    ]);
    exit;
} else {
    // Login normal
    if (password_verify($senha, $aluno['senha'])) {
        // Buscar informações adicionais da matrícula
        $sqlMatricula = "SELECT status FROM matriculas WHERE aluno_id = ? ORDER BY data_matricula DESC LIMIT 1";
        $stmtMatricula = $conn->prepare($sqlMatricula);
        $stmtMatricula->bind_param("i", $aluno['id']);
        $stmtMatricula->execute();
        $resultMatricula = $stmtMatricula->get_result();
        $statusMatricula = 'pendente'; // Padrão

        if ($resultMatricula->num_rows > 0) {
            $matriculaInfo = $resultMatricula->fetch_assoc();
            $statusMatricula = $matriculaInfo['status'];
        }

        // Configurar sessão com informações do aluno
        $_SESSION['usuario_id'] = $aluno['id'];
        $_SESSION['usuario_nome'] = $aluno['nome'];
        $_SESSION['usuario_matricula'] = $aluno['numero_matricula'];
        $_SESSION['usuario_tipo'] = 'aluno';
        $_SESSION['usuario_foto'] = $aluno['foto'] ?? '';
        $_SESSION['usuario_status_matricula'] = $statusMatricula;
        $_SESSION['logado'] = true;

        // Definir redirecionamento baseado no status da matrícula
        $redirect = '../aluno/dashboard.php';
        if ($statusMatricula === 'pendente') {
            $redirect = '../aluno/matricula_pendente.php';
        }

        echo json_encode([
            'sucesso' => 'Login realizado', 
            'redirect' => $redirect,
            'dados' => [
                'nome' => $aluno['nome'],
                'numero_matricula' => $aluno['numero_matricula'],
                'escola' => $aluno['escola'],
                'serie' => $aluno['serie'],
                'data_nascimento' => $aluno['data_nascimento'],
                'rg' => $aluno['rg'],
                'cpf' => $aluno['cpf'],
                'info_saude' => $aluno['info_saude'],
                'data_matricula' => $aluno['data_matricula']
            ]
        ]);
        exit;
    } else {
        $_SESSION['erro_login'] = 'Senha incorreta';
        echo json_encode([
            'erro' => 'Senha incorreta', 
            'redirect' => '../index.php'
        ]);
        exit;
    }
}
?>