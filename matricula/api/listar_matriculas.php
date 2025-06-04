<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "conexao.php";

$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        m.id AS matricula_id,
        m.data_matricula,
        m.status,
        m.turma AS turma_id,
        m.unidade AS unidade_id,
        t.nome_turma AS turma,
        u.nome AS unidade,
        (SELECT GROUP_CONCAT(r.nome SEPARATOR ', ')
         FROM aluno_responsavel ar
         JOIN responsaveis r ON ar.responsavel_id = r.id
         WHERE ar.aluno_id = a.id
        ) AS responsaveis
    FROM alunos a
    LEFT JOIN matriculas m ON a.id = m.aluno_id
    LEFT JOIN turma t ON m.turma = t.id
    LEFT JOIN unidade u ON m.unidade = u.id
";

$result = $conn->query($sql);
$dados = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Se não for possível resolver o nome da turma/unidade, use o ID como fallback
        if (!$row['turma'] && isset($row['turma_id'])) {
            $row['turma'] = 'Turma ID: ' . $row['turma_id'];
        }
        
        if (!$row['unidade'] && isset($row['unidade_id'])) {
            $row['unidade'] = 'Unidade ID: ' . $row['unidade_id'];
        }
        
        $dados[] = $row;
    }
} else {
    // Adicione log de erro
    error_log("Erro na consulta SQL: " . $conn->error);
}

header('Content-Type: application/json');
echo json_encode($dados);
?>