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

// Obter alunos do professor
$stmt = $avaliacao->getAlunosPorProfessor($professor_id);
$num = $stmt->rowCount();

if ($num > 0) {
    $alunos_arr = array();
    $alunos_arr["registros"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $aluno_item = array(
            "id" => $id,
            "nome" => $nome,
            "serie" => $serie,
            "turma_id" => $turma,
            "turma_nome" => $nome_turma
        );

        array_push($alunos_arr["registros"], $aluno_item);
    }

    http_response_code(200);
    echo json_encode($alunos_arr);
} else {
    http_response_code(404);
    echo json_encode(array("mensagem" => "Nenhum aluno encontrado para este professor."));
}
?>