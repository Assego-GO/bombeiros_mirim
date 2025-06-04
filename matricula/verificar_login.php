<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('./api/conexao.php');

// Verificação de campos
if (!isset($_POST['email'], $_POST['senha'])) {
    $_SESSION['erro_login'] = "Por favor, preencha todos os campos.";
    header('Location: index.php');
    exit;
}

$email = $_POST['email'];
$senha = $_POST['senha'];

// Primeiro, verificar na tabela professor
$stmt = $conn->prepare("SELECT id, nome, senha, telefone FROM professor WHERE email = ?");
if (!$stmt) {
    $_SESSION['erro_login'] = "Erro interno no sistema. Tente novamente.";
    header('Location: index.php');
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $professor = $resultado->fetch_assoc();

    // Verifica a senha
    if (password_verify($senha, $professor['senha'])) {
        $_SESSION['usuario_id'] = $professor['id'];
        $_SESSION['usuario_nome'] = $professor['nome'];
        $_SESSION['usuario_tipo'] = 'professor';
        $_SESSION['usuario_telefone'] = $professor['telefone'];
        $_SESSION['usuario_foto'] = 'default.png'; // Foto padrão para professor
        
        header('Location: ../professor/dashboard.php');
        exit;
    } else {
        $_SESSION['erro_login'] = "Senha incorreta para este usuário.";
        header('Location: index.php');
        exit;
    }
}

// Se não for professor, verificar na tabela usuarios
$stmt = $conn->prepare("SELECT id, nome, senha, tipo, foto FROM usuarios WHERE email = ?");
if (!$stmt) {
    $_SESSION['erro_login'] = "Erro interno no sistema. Tente novamente.";
    header('Location: index.php');
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();

    // Verifica a senha
    if (password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['usuario_foto'] = $usuario['foto'] ?? 'default.png';
        
        if ($_SESSION['usuario_tipo'] == 'admin') {
            header('Location: painel.php');
            exit;
        }
        if ($_SESSION['usuario_tipo'] == 'professor') {
            header('Location: ../professor/dashboard.php');
            exit;
        }
        if ($_SESSION['usuario_tipo'] == 'aluno') {
            header('Location: ../aluno/dashboard.php');
            exit;
        }
    } else {
        $_SESSION['erro_login'] = "Usuário ou senha incorretos.";
        header('Location: index.php');
        exit;
    }
} else {
    $_SESSION['erro_login'] = "Usuário não encontrado.";
    header('Location: index.php');
    exit;
}

$stmt->close();
$conn->close();
?>