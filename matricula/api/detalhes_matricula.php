<?php
header("Content-Type: application/json");
include "conexao.php";

$id = $_GET['id'] ?? 0;

// Busca aluno e informações da matrícula com join nas tabelas pertinentes
$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        m.id AS matricula_id,
        m.data_matricula,
        m.status,
        m.turma AS turma_id,
        m.unidade AS id_unidade,
        t.nome_turma AS nome_turma,
        u.nome AS unidade_nome
    FROM 
        alunos a
    LEFT JOIN 
        matriculas m ON a.id = m.aluno_id
    LEFT JOIN 
        turma t ON m.turma = t.id
    LEFT JOIN 
        unidade u ON m.unidade = u.id
    WHERE 
        a.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$aluno = $result->fetch_assoc();

if (!$aluno) {
    echo json_encode(['error' => 'Aluno não encontrado']);
    exit;
}

// Buscar responsáveis
$res = $conn->prepare("
    SELECT 
        r.* 
    FROM 
        responsaveis r 
    JOIN 
        aluno_responsavel ar ON r.id = ar.responsavel_id 
    WHERE 
        ar.aluno_id = ?
");
$res->bind_param("i", $id);
$res->execute();
$resp_result = $res->get_result();
$responsaveis = [];

while ($r = $resp_result->fetch_assoc()) {
    $responsaveis[] = $r;
}

$aluno['responsaveis'] = $responsaveis;

// Formatar os dados para garantir que a visualização tenha os campos corretos
$aluno['turma'] = $aluno['turma_nome'] ?? $aluno['turma_id'] ?? 'Não definido';
$aluno['unidade'] = $aluno['unidade_nome'] ?? $aluno['unidade_id'] ?? 'Não definido';

echo json_encode($aluno);
?>