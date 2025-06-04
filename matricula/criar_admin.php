<?php
include('./api/conexao.php');

// Dados do novo usuário
$nome = 'Administrador';
$email = 'admin@superacao.com';
$senha = 'admin123';
$tipo = 'admin';
$foto = 'default.png';

// Gera o hash da senha
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Remove se já existir
$conn->query("DELETE FROM usuarios WHERE email = '$email'");

// Insere o novo admin
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo, foto) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nome, $email, $hash, $tipo, $foto);

if ($stmt->execute()) {
    echo "✅ Usuário criado com sucesso!<br>";
    echo "Email: $email<br>";
    echo "Senha: $senha<br>";
} else {
    echo "Erro ao inserir: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
