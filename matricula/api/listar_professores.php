<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "conexao.php";
$resultado = $conn->query("SELECT id, nome FROM professor");
$dados = [];

while ($row = $resultado->fetch_assoc()) {
    $dados[] = $row;
}

echo json_encode($dados);
?>