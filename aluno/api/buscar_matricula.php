<?php

include "conexao.php";

session_start();

if(!isset($_SESSION['usuario_matricula'])){
    echo "Id do aluno não fornecido";
    exit;
}


$aluno_matricula = $_SESSION['usuario_matricula'];

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
            a.numero_matricula = :matricula" ;

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':matricula', 
$aluno_matricula);
$stmt->execute();

$resposta = $stmt->fetch(PDO::FETCH_ASSOC);


if($resposta){
    echo json_encode(
        [
        'success' => true,
         'dados' =>[
            'nome' => $resposta['nome_aluno'],
            'numero_matricula' => $resposta['numero_matricula'],
            'data_matricula' => $resposta['data_matricula'],
            'status' => $resposta['status_matricula'],
            'nome_turma' => $resposta['nome_turma'],
            'nome_unidade' => $resposta['nome_unidade'],
            'coordenador' => $resposta['coordenador'],
            'telefone_unidade' => $resposta['telefone'],
            'endereco_unidade' => $resposta['endereco'],
            ]
        ]
        );
   
}else{
    echo json_encode(
        [
            'success' => false,
            'message' => 'A busca não retornou nada',
            'matricula' => $aluno_matricula
        ]
        );
}


?>



