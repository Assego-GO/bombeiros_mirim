<?php


function buscarMatriculaAluno($matricula) {
   
    require_once "conexao.php";

    
    
    $sql = "SELECT 
                a.numero_matricula,
                a.nome AS nome_aluno,
                a.data_matricula,
                m.status AS status_matricula,
                t.nome_turma,
                u.nome AS nome_unidade,
                u.coordenador,
                u.telefone,
                u.endereco
            FROM 
                alunos a
            JOIN 
                matriculas m ON a.id = m.aluno_id
            JOIN 
                turma t ON m.turma = t.id
            JOIN 
                unidade u ON m.unidade = u.id
            WHERE 
                a.numero_matricula = :matricula";

    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':matricula', $matricula);
    $stmt->execute();
    $resposta = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'message' => 'Sucesso',
        'matricula' => $matricula,
        'resposta' => $resposta
    ]);
    
    return $resposta;
}
?>

