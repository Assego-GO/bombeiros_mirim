<?php
// Verificar se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está autenticado
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    // Usuário não está autenticado, redirecionar para a página de login
    header('Location: ../matricula/index.php');
    exit;
}
?>