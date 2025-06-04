<?php
// login_aluno.php
session_start();
include 'conexao.php';

$matricula = $_POST['matricula'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($matricula) || empty($senha)) {
    echo json_encode(['erro' => 'Preencha matrícula e senha']);
    exit;
}

$sql = "SELECT * FROM alunos WHERE numero_matricula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matricula);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['erro' => 'Matrícula não encontrada']);
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

    $_SESSION['aluno_id'] = $aluno['id'];
    echo json_encode(['sucesso' => 'Senha cadastrada com sucesso', 'primeiro_acesso' => true]);
} else {
    // Login normal
    if (password_verify($senha, $aluno['senha'])) {
        $_SESSION['aluno_id'] = $aluno['id'];
        echo json_encode(['sucesso' => 'Login realizado', 'dados' => [
            'nome' => $aluno['nome'],
            'numero_matricula' => $aluno['numero_matricula'],
            'escola' => $aluno['escola'],
            'serie' => $aluno['serie'],
            'data_nascimento' => $aluno['data_nascimento'],
            'rg' => $aluno['rg'],
            'cpf' => $aluno['cpf'],
            'info_saude' => $aluno['info_saude'],
            'data_matricula' => $aluno['data_matricula']
        ]]);
    } else {
        echo json_encode(['erro' => 'Senha incorreta']);
    }
}
?>
