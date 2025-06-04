<?php
// Headers necessários
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
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

// Obter dados enviados
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
    echo json_encode(array("mensagem" => "Acesso negado. Você não tem permissão para modificar esta avaliação."));
    exit();
}

// Verificar que os campos necessários foram enviados
if (
    empty($data->aluno_id) ||
    empty($data->turma_id) ||
    empty($data->data_avaliacao)
) {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Dados incompletos. Não foi possível atualizar a avaliação."));
    exit();
}

// Definir valores da avaliação
$avaliacao->aluno_id = $data->aluno_id;
$avaliacao->professor_id = $professor_id; // Do token/sessão
$avaliacao->turma_id = $data->turma_id;
$avaliacao->data_avaliacao = $data->data_avaliacao;

// Dados físicos
$avaliacao->altura = $data->altura ?? null;
$avaliacao->peso = $data->peso ?? null;
$avaliacao->imc = $data->imc ?? null;
$avaliacao->imc_status = $data->imc_status ?? null;

// Habilidades físicas
$avaliacao->velocidade = $data->velocidade ?? null;
$avaliacao->resistencia = $data->resistencia ?? null;
$avaliacao->coordenacao = $data->coordenacao ?? null;
$avaliacao->agilidade = $data->agilidade ?? null;
$avaliacao->forca = $data->forca ?? null;
$avaliacao->desempenho_detalhes = $data->desempenho_detalhes ?? null;

// Comportamento
$avaliacao->participacao = $data->participacao ?? null;
$avaliacao->trabalho_equipe = $data->trabalho_equipe ?? null;
$avaliacao->disciplina = $data->disciplina ?? null;
$avaliacao->respeito_regras = $data->respeito_regras ?? null;
$avaliacao->comportamento_notas = $data->comportamento_notas ?? null;

$avaliacao->observacoes = $data->observacoes ?? null;

// Atualizar a avaliação
if ($avaliacao->atualizar()) {
    http_response_code(200);
    echo json_encode(array("mensagem" => "Avaliação atualizada com sucesso."));
} else {
    http_response_code(503);
    echo json_encode(array("mensagem" => "Não foi possível atualizar a avaliação."));
}
?>