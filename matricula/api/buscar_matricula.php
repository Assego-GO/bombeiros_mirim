<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

if (!isset($_GET['id'])) {
    echo json_encode(["status" => "erro", "mensagem" => "ID não fornecido"]);
    exit;
}

$aluno_id = $_GET['id']; // Este é o ID do aluno que vem da tabela

// Log para debug
file_put_contents('debug_buscar.log', date('Y-m-d H:i:s') . " - Buscando aluno ID: $aluno_id\n", FILE_APPEND);

$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        m.id AS matricula_id, 
        m.data_matricula,
        m.status,
        m.turma AS turma_id,
        m.unidade AS unidade_id,
        t.nome_turma AS turma_nome,
        u.nome AS unidade_nome
    FROM alunos a
    JOIN matriculas m ON a.id = m.aluno_id
    LEFT JOIN turma t ON m.turma = t.id
    LEFT JOIN unidade u ON m.unidade = u.id
    WHERE a.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Log para debug
    file_put_contents('debug_buscar.log', date('Y-m-d H:i:s') . " - Dados encontrados: " . print_r($row, true) . "\n", FILE_APPEND);
    
    // Buscar responsáveis
    $sql_resp = "
        SELECT r.id, r.nome, r.telefone, r.email
        FROM aluno_responsavel ar
        JOIN responsaveis r ON ar.responsavel_id = r.id
        WHERE ar.aluno_id = ?
    ";
    
    $stmt_resp = $conn->prepare($sql_resp);
    $stmt_resp->bind_param("i", $aluno_id);
    $stmt_resp->execute();
    $result_resp = $stmt_resp->get_result();
    
    $responsaveis = [];
    while ($resp = $result_resp->fetch_assoc()) {
        $responsaveis[] = $resp;
    }
    
    $row['responsaveis'] = $responsaveis;
    
    echo json_encode($row);
} else {
    echo json_encode(["status" => "erro", "mensagem" => "Matrícula não encontrada"]);
}
?>