<?php
header("Content-Type: application/json");
ini_set('display_errors', 0);
error_reporting(E_ALL);
include "conexao.php";

try {
    // Updated SQL to count actual matriculated students
    $sql = "SELECT 
                t.id,
                t.nome_turma,
                t.id_unidade,
                t.id_professor,
                t.capacidade,
                (SELECT COUNT(*) 
                 FROM matriculas m 
                 WHERE m.turma = t.id) AS matriculados,
                t.status,
                t.dias_aula,
                t.horario_inicio,
                t.horario_fim,
                t.data_criacao,
                t.ultima_atualizacao,
                t.data_inicio,
                t.data_fim,
                u.nome AS unidade_nome,
                p.nome AS professor_nome
            FROM 
                turma t
            LEFT JOIN 
                unidade u ON t.id_unidade = u.id
            LEFT JOIN 
                professor p ON t.id_professor = p.id
            ORDER BY 
                t.nome_turma ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Erro na consulta: " . $conn->error);
    }
    
    $turmas = [];
    
    while ($row = $result->fetch_assoc()) {
        // Formatar o status para exibição
        $statusFormatado = '';
        if ($row['status'] === 'Em Andamento' || $row['status'] === '1' || $row['status'] === 1) {
            $statusFormatado = 'ATIVO';
        } else if ($row['status'] === 'Inativo' || $row['status'] === '0' || $row['status'] === 0) {
            $statusFormatado = 'INATIVO';
        } else {
            $statusFormatado = $row['status'];
        }
        
        // Formatar datas
        $dataCriacao = !empty($row['data_criacao']) ? date('Y-m-d H:i:s', strtotime($row['data_criacao'])) : null;
        $ultimaAtualizacao = !empty($row['ultima_atualizacao']) ? date('Y-m-d H:i:s', strtotime($row['ultima_atualizacao'])) : null;
        
        // Adicionar à lista de turmas
        $turmas[] = [
            'id' => $row['id'],
            'nome_turma' => $row['nome_turma'],
            'id_unidade' => $row['id_unidade'],
            'id_professor' => $row['id_professor'],
            'capacidade' => $row['capacidade'],
            'matriculados' => $row['matriculados'],
            'status' => $statusFormatado,
            'dias_aula' => $row['dias_aula'],
            'horario_inicio' => $row['horario_inicio'],
            'horario_fim' => $row['horario_fim'],
            'data_criacao' => $dataCriacao,
            'ultima_atualizacao' => $ultimaAtualizacao,
            'unidade_nome' => $row['unidade_nome'] ?: '-',
            'professor_nome' => $row['professor_nome'] ?: '-',
            'data_inicio' => $row['data_inicio'] ?: '-',
            'data_fim' => $row['data_fim'] ?: '-'
        ];
    }
    
    // Retornar o resultado como JSON
    echo json_encode($turmas);
    
} catch (Exception $e) {
    file_put_contents('debug_listar_turmas.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao listar turmas: " . $e->getMessage()
    ]);
}
?>