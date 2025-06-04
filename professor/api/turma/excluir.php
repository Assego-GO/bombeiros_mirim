<?php
// Headers necessários
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir arquivos de configuração e objetos
include_once '../../config/database.php';
include_once '../../models/Avaliacao.php';
include_once '../../utils/auth.php';

// Verificar a sessão do professor
$professor_id = verificarSessaoProfessor();

if (!$professor_id) {
    http_response_code(403);
    echo json_encode(array("mensagem" => "Acesso negado. Autenticação necessária."));
    exit();
}

// Obter conexão com o banco de dados
$database = new Database();
$db = $database->getConnection();

// Instanciar objeto de avaliação
$avaliacao = new Avaliacao($db);

// Obter ID da avaliação a ser excluída
$data = json_decode(file_get_contents("php://input"));

// Verificar se o ID foi especificado
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(array("mensagem" => "ID da avaliação não especificado."));
    exit();
}

// Primeiro, carregar a avaliação existente para verificar permissões
$avaliacao->id = $data->id;
if (!$avaliacao->ler_um() || $avaliacao->professor_id != $professor_id) {
    http_response_code(403);
    echo json_encode(array("mensagem" => "Acesso negado. Você não tem permissão para excluir esta avaliação."));
    exit();
}

// Excluir a avaliação
if ($avaliacao->excluir()) {
    http_response_code(200);
    echo json_encode(array("mensagem" => "Avaliação excluída com sucesso."));
} else {
    http_response_code(503);
    echo json_encode(array("mensagem" => "Não foi possível excluir a avaliação."));
}
?>