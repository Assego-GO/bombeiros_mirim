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

// Obter conexão com o banco de dados
$database = new Database();
$db = $database->getConnection();

// Instanciar objeto de avaliação
$avaliacao = new Avaliacao($db);

// Verificar se o ID do aluno foi passado
if (isset($_GET['aluno_id'])) {
    // Obter avaliações por aluno
    $stmt = $avaliacao->ler_por_aluno($_GET['aluno_id']);
} else {
    // Obter todas as avaliações do professor
    $stmt = $avaliacao->ler_por_professor($professor_id);
}

$num = $stmt->rowCount();

if ($num > 0) {
    $avaliacoes_arr = array();
    $avaliacoes_arr["registros"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $avaliacao_item = array(
            "id" => $id,
            "aluno_id" => $aluno_id,
            "nome_aluno" => $nome_aluno ?? "",
            "professor_id" => $professor_id,
            "turma_id" => $turma_id,
            "nome_turma" => $nome_turma ?? "",
            "data_avaliacao" => $data_avaliacao,
            "altura" => $altura,
            "peso" => $peso,
            "imc" => $imc,
            "imc_status" => $imc_status,
            "velocidade" => $velocidade,
            "resistencia" => $resistencia,
            "coordenacao" => $coordenacao,
            "agilidade" => $agilidade,
            "forca" => $forca,
            "desempenho_detalhes" => $desempenho_detalhes,
            "participacao" => $participacao,
            "trabalho_equipe" => $trabalho_equipe,
            "disciplina" => $disciplina,
            "respeito_regras" => $respeito_regras,
            "comportamento_notas" => $comportamento_notas,
            "observacoes" => $observacoes,
            "criado_em" => $criado_em
        );

        array_push($avaliacoes_arr["registros"], $avaliacao_item);
    }

    http_response_code(200);
    echo json_encode($avaliacoes_arr);
} else {
    http_response_code(404);
    echo json_encode(array("mensagem" => "Nenhuma avaliação encontrada."));
}
?>