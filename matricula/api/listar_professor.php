<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

try {
    $sql = "SELECT id, nome, email, telefone FROM professor ORDER BY nome ASC";
    $result = $conn->query($sql);
    
    if (!$result) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao buscar professores: " . $conn->error]);
        exit;
    }
    
    $professores = [];
    while ($row = $result->fetch_assoc()) {
        $professores[] = $row;
    }
    
    echo json_encode($professores);
    
} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => "Exceção: " . $e->getMessage()]);
}
?>