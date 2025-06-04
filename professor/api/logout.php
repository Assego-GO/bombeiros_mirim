<?php
// Iniciar sessão
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se for necessário eliminar completamente a sessão, elimine também o cookie de sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header("Location: ../../matricula/index.php");
exit;