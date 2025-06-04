<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "conexao.php";
$id = $_GET['id'] ?? 0;
$sql = "DELETE FROM unidade WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode([
        "status" => "sucesso", 
        "mensagem" => "Unidade excluída com sucesso!"
    ]);
} else {
    echo json_encode([
        "status" => "erro", 
        "mensagem" => $stmt->error
    ]);
}
?>