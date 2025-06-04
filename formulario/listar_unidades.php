<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexao.php';
try {
    $stmt = $conexao->prepare("SELECT * FROM unidade ORDER BY nome");
    $stmt->execute();
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    echo json_encode($unidades);
    exit; 
} catch(PDOException $e) {
    
    http_response_code(500); 
    echo json_encode([
        'erro' => true,
        'mensagem' => "Erro ao buscar unidades: " . $e->getMessage()
    ]);
    exit;
}