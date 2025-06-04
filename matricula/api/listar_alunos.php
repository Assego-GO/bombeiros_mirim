<?php
// API/listar_alunos.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Conexão com o banco de dados
require_once('conexao.php');

// Construir consulta
$query = "
    SELECT 
        a.id,
        a.nome,
        a.data_nascimento,
        a.escola as unidade,
        a.serie,
        a.numero_matricula,
        a.data_matricula,
        CASE 
            WHEN NOW() <= DATE_ADD(a.data_matricula, INTERVAL 7 DAY) THEN 'pendente'
            ELSE 'ativo'
        END as status
    FROM 
        alunos a
";

// Adicionar filtros se estiverem presentes na requisição
$filtros = [];
$params = [];
$paramTypes = '';

// Filtro por nome
if (isset($_GET['aluno']) && !empty($_GET['aluno'])) {
    $filtros[] = "a.nome LIKE ?";
    $params[] = "%" . $_GET['aluno'] . "%";
    $paramTypes .= 's';
}

// Filtro por unidade
if (isset($_GET['unidade']) && !empty($_GET['unidade'])) {
    $filtros[] = "a.escola = ?";
    $params[] = $_GET['unidade'];
    $paramTypes .= 's';
}

// Filtro por turma/série
if (isset($_GET['turma']) && !empty($_GET['turma'])) {
    $filtros[] = "a.serie = ?";
    $params[] = $_GET['turma'];
    $paramTypes .= 's';
}

// Filtro por status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    if ($_GET['status'] === 'ativo') {
        $filtros[] = "NOW() > DATE_ADD(a.data_matricula, INTERVAL 7 DAY)";
    } elseif ($_GET['status'] === 'pendente') {
        $filtros[] = "NOW() <= DATE_ADD(a.data_matricula, INTERVAL 7 DAY)";
    }
}

// Filtro por data
if (isset($_GET['data_inicial']) && !empty($_GET['data_inicial'])) {
    $filtros[] = "DATE(a.data_matricula) >= ?";
    $params[] = $_GET['data_inicial'];
    $paramTypes .= 's';
}

if (isset($_GET['data_final']) && !empty($_GET['data_final'])) {
    $filtros[] = "DATE(a.data_matricula) <= ?";
    $params[] = $_GET['data_final'];
    $paramTypes .= 's';
}

// Aplicar filtros à consulta
if (!empty($filtros)) {
    $query .= " WHERE " . implode(" AND ", $filtros);
}

// Ordenação e limite
$query .= " ORDER BY a.data_matricula DESC";

// Preparar statement
$stmt = $conn->prepare($query);

// Bind params se houver
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

// Executar consulta
$stmt->execute();
$resultado = $stmt->get_result();

// Array para armazenar os resultados
$alunos = [];

// Processar resultados
while ($aluno = $resultado->fetch_assoc()) {
    // Formatar dados
    $formattedAluno = [
        'id' => $aluno['id'],
        'nome' => $aluno['nome'],
        'data_nascimento' => $aluno['data_nascimento'],
        'unidade' => $aluno['unidade'],
        'turma' => 'Sub ' . $aluno['serie'],
        'numero_matricula' => $aluno['numero_matricula'],
        'data_matricula' => $aluno['data_matricula'],
        'status' => $aluno['status'],
        'responsavel' => '', // Este campo seria preenchido com dados de uma tabela de responsáveis
        'contato' => '', // Este campo seria preenchido com dados de contato
        'fonte' => '-'
    ];
    
    $alunos[] = $formattedAluno;
}

// Fechar conexão
$stmt->close();
$conn->close();

// Retornar JSON
header('Content-Type: application/json');
echo json_encode($alunos);