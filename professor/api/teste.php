<?php
// Desativar a exibição de erros
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Definir o cabeçalho de resposta
header('Content-Type: application/json');

// Criar uma resposta JSON simples
$response = [
    "status" => "success",
    "message" => "API funcionando corretamente",
    "timestamp" => date('Y-m-d H:i:s')
];

// Enviar resposta
echo json_encode($response);
exit();
?>