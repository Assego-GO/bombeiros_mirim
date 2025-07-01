<?php
session_start();
include('./api/conexao.php');

// AUDITORIA
require "api/auditoria.php";

// Verificar se há usuário logado
if (isset($_SESSION['usuario_id'])) {
    try {
        $audit = new Auditoria($conn);
        
        // Registrar logout
        $audit->log('LOGOUT', 'sistema', $_SESSION['usuario_id'], [
            'nome' => $_SESSION['usuario_nome'] ?? 'N/A',
            'tipo' => $_SESSION['usuario_tipo'] ?? 'N/A'
        ]);
        
    } catch (Exception $e) {
        // Se falhar auditoria, não impede logout
        error_log("Erro na auditoria de logout: " . $e->getMessage());
    }
}

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: index.php');
exit;
?>