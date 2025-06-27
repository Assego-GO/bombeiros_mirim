<?php
session_start();
header('Content-Type: application/json');

// Verifica se o usuário está logado e é um aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Faça login como aluno.']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Inclui o arquivo de conexão com o banco de dados
require_once "conexao.php";

// Validação dos campos obrigatórios
$camposObrigatorios = [
    'como_conheceu',
    'autorizacao_imagem',
    'engajamento_projetos',
    'grau_satisfacao',
    'disposicao_multiplicador',
    'autoriza_contato'
];

$errors = [];

foreach ($camposObrigatorios as $campo) {
    if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
        $errors[] = "O campo '$campo' é obrigatório";
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitização dos dados
$aluno_id = $_SESSION['usuario_id'];
$como_conheceu = trim($_POST['como_conheceu']);
$autorizacao_imagem = $_POST['autorizacao_imagem'];
$vinculo_comunidade = isset($_POST['vinculo_comunidade']) ? trim($_POST['vinculo_comunidade']) : null;
$engajamento_projetos = $_POST['engajamento_projetos'];
$grau_satisfacao = $_POST['grau_satisfacao'];
$principais_beneficios = isset($_POST['principais_beneficios']) ? trim($_POST['principais_beneficios']) : null;
$sugestoes_criticas = isset($_POST['sugestoes_criticas']) ? trim($_POST['sugestoes_criticas']) : null;
$disposicao_multiplicador = $_POST['disposicao_multiplicador'];
$autoriza_contato = $_POST['autoriza_contato'];

// Validação dos valores dos campos radio e select
$valoresValidos = [
    'autorizacao_imagem' => ['sim', 'nao'],
    'engajamento_projetos' => ['sim', 'nao'],
    'grau_satisfacao' => ['muito_satisfeito', 'satisfeito', 'neutro', 'insatisfeito', 'muito_insatisfeito'],
    'disposicao_multiplicador' => ['sim', 'nao'],
    'autoriza_contato' => ['sim', 'nao']
];

foreach ($valoresValidos as $campo => $valores) {
    if (!in_array(${$campo}, $valores)) {
        echo json_encode(['success' => false, 'message' => "Valor inválido para o campo '$campo'"]);
        exit;
    }
}

try {
    // Verifica se já existe um questionário para este aluno
    $stmt = $pdo->prepare("SELECT id FROM questionarios WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    
    if ($stmt->fetch()) {
        // Atualiza o questionário existente
        $sql = "UPDATE questionarios SET 
                como_conheceu = ?,
                autorizacao_imagem = ?,
                vinculo_comunidade = ?,
                engajamento_projetos = ?,
                grau_satisfacao = ?,
                principais_beneficios = ?,
                sugestoes_criticas = ?,
                disposicao_multiplicador = ?,
                autoriza_contato = ?,
                data_atualizacao = NOW()
                WHERE aluno_id = ?";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $como_conheceu,
            $autorizacao_imagem,
            $vinculo_comunidade,
            $engajamento_projetos,
            $grau_satisfacao,
            $principais_beneficios,
            $sugestoes_criticas,
            $disposicao_multiplicador,
            $autoriza_contato,
            $aluno_id
        ]);
        
        $message = 'Questionário atualizado com sucesso!';
    } else {
        // Insere um novo questionário
        $sql = "INSERT INTO questionarios (
                    aluno_id,
                    como_conheceu,
                    autorizacao_imagem,
                    vinculo_comunidade,
                    engajamento_projetos,
                    grau_satisfacao,
                    principais_beneficios,
                    sugestoes_criticas,
                    disposicao_multiplicador,
                    autoriza_contato,
                    data_criacao,
                    data_atualizacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $aluno_id,
            $como_conheceu,
            $autorizacao_imagem,
            $vinculo_comunidade,
            $engajamento_projetos,
            $grau_satisfacao,
            $principais_beneficios,
            $sugestoes_criticas,
            $disposicao_multiplicador,
            $autoriza_contato
        ]);
        
        $message = 'Questionário enviado com sucesso!';
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar questionário']);
}
?>