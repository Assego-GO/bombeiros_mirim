<?php
include "conexao.php";

$id = $_GET['id'] ?? 0;

$sql = "DELETE FROM alunos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["status" => "sucesso"]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
}
?>