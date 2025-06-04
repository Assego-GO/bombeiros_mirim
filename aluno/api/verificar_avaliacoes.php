<?php
// Iniciar sessão
session_start();
// Verificar se o usuário está logado
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
    // Se não estiver logado, retorna erro
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Recuperar ID do aluno da sessão
$aluno_id = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : '';

if (empty($aluno_id)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID do aluno não encontrado na sessão'
    ]);
    exit;
}

require "../../env_config.php";


$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    // Conexão com o banco de dados
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se existem avaliações para este aluno
    $query = "SELECT COUNT(*) as total FROM avaliacoes WHERE aluno_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$aluno_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tem_avaliacoes = ($result['total'] > 0);
    
    // Retornar resultado
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'tem_avaliacoes' => $tem_avaliacoes,
        'total_avaliacoes' => $result['total']
    ]);
} catch(PDOException $e) {
    // Em caso de erro na conexão ou consulta
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar avaliações: ' . $e->getMessage()
    ]);
}
?>