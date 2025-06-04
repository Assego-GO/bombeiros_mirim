<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include "conexao.php";

try {
    // Base da consulta SQL
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
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    // Filtro por nome de aluno
    if (isset($_GET['aluno']) && !empty($_GET['aluno'])) {
        $sql .= " AND a.nome LIKE ?";
        $params[] = "%" . $_GET['aluno'] . "%";
        $types .= "s";
    }
    
    // Filtro por unidade
    if (isset($_GET['unidade']) && !empty($_GET['unidade'])) {
        $sql .= " AND m.unidade = ?";
        $params[] = $_GET['unidade'];
        $types .= "i";
    }
    
    // Filtro por turma
    if (isset($_GET['turma']) && !empty($_GET['turma'])) {
        $sql .= " AND m.turma = ?";
        $params[] = $_GET['turma'];
        $types .= "i";
    }
    
    // Filtro por status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $sql .= " AND m.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    
    // Filtro por data inicial
    if (isset($_GET['data_inicial']) && !empty($_GET['data_inicial'])) {
        $sql .= " AND DATE(m.data_matricula) >= ?";
        $params[] = $_GET['data_inicial'];
        $types .= "s";
    }
    
    // Filtro por data final
    if (isset($_GET['data_final']) && !empty($_GET['data_final'])) {
        $sql .= " AND DATE(m.data_matricula) <= ?";
        $params[] = $_GET['data_final'];
        $types .= "s";
    }
    
    // Ordenação
    $sql .= " ORDER BY a.nome ASC";
    
    $stmt = $conn->prepare($sql);
    
    // Vincular parâmetros apenas se houver algum
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $matriculas = [];
    while ($row = $result->fetch_assoc()) {
        $matriculas[] = $row;
    }
    
    echo json_encode($matriculas);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao filtrar matrículas: " . $e->getMessage()
    ]);
}
?>