<?php
// Headers necessários
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

// Verificar se o ID foi enviado
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(array("mensagem" => "ID da avaliação não especificado."));
    exit();
}

// Obter conexão com o banco de dados
$database = new Database();
$db = $database->getConnection();

// Instanciar objeto de avaliação
$avaliacao = new Avaliacao($db);
$avaliacao->id = $_GET['id'];

// Ler detalhes da avaliação
if ($avaliacao->ler_um()) {
    // Verificar se o professor tem permissão para ver esta avaliação
    if ($avaliacao->professor_id != $professor_id) {
        http_response_code(403);
        echo json_encode(array("mensagem" => "Acesso negado. Você não tem permissão para acessar esta avaliação."));
        exit();
    }

    // Criar array de avaliação
    $avaliacao_arr = array(
        "id" => $avaliacao->id,
        "aluno_id" => $avaliacao->aluno_id,
        "professor_id" => $avaliacao->professor_id,
        "turma_id" => $avaliacao->turma_id,
        "data_avaliacao" => $avaliacao->data_avaliacao,
        "altura" => $avaliacao->altura,
        "peso" => $avaliacao->peso,
        "imc" => $avaliacao->imc,
        "imc_status" => $avaliacao->imc_status,
        "velocidade" => $avaliacao->velocidade,
        "resistencia" => $avaliacao->resistencia,
        "coordenacao" => $avaliacao->coordenacao,
        "agilidade" => $avaliacao->agilidade,
        "forca" => $avaliacao->forca,
        "desempenho_detalhes" => $avaliacao->desempenho_detalhes,
        "participacao" => $avaliacao->participacao,
        "trabalho_equipe" => $avaliacao->trabalho_equipe,
        "disciplina" => $avaliacao->disciplina,
        "respeito_regras" => $avaliacao->respeito_regras,
        "comportamento_notas" => $avaliacao->comportamento_notas,
        "observacoes" => $avaliacao->observacoes,
        "criado_em" => $avaliacao->criado_em,
        "atualizado_em" => $avaliacao->atualizado_em
    );

    http_response_code(200);
    echo json_encode($avaliacao_arr);
} else {
    http_response_code(404);
    echo json_encode(array("mensagem" => "Avaliação não encontrada."));
}
?>