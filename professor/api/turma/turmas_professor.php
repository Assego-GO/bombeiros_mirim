<?php
// Headers necessários
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir arquivos de configuração e objetos
include_once '../../config/database.php';
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

// Consulta para obter turmas do professor
$query = "SELECT t.id, t.nome_turma, t.capacidade, t.matriculados, 
                  t.dias_aula, t.horario_inicio, t.horario_fim, u.nome as nome_unidade
          FROM turma t
          JOIN unidade u ON t.id_unidade = u.id
          WHERE t.id_professor = ?
          ORDER BY t.nome_turma";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $professor_id);
$stmt->execute();
$num = $stmt->rowCount();

if ($num > 0) {
    $turmas_arr = array();
    $turmas_arr["registros"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $turma_item = array(
            "id" => $id,
            "nome_turma" => $nome_turma,
            "capacidade" => $capacidade,
            "matriculados" => $matriculados,
            "dias_aula" => $dias_aula,
            "horario_inicio" => $horario_inicio,
            "horario_fim" => $horario_fim,
            "nome_unidade" => $nome_unidade
        );

        array_push($turmas_arr["registros"], $turma_item);
    }

    http_response_code(200);
    echo json_encode($turmas_arr);
} else {
    http_response_code(404);
    echo json_encode(array("mensagem" => "Nenhuma turma encontrada para este professor."));
}
?>