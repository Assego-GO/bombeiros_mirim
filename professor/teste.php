<?php
// api/buscar_perfil.php - API para buscar dados do perfil do aluno
session_start();
require_once '../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Pegar ID do aluno da sessão
$id_aluno = $_SESSION["usuario_id"];

// Resposta padrão
$response = [
    'success' => false,
    'message' => '',
    'aluno' => null,
    'endereco' => null,
    'responsaveis' => []
];

try {
    // Busca dados do aluno
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
    $stmt->bindParam(':id', $id_aluno, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Preparar foto para exibição
        if (!empty($aluno['foto'])) {
            // Se a foto começar com http:// ou https://, não altera
            if (strpos($aluno['foto'], 'http://') !== 0 && strpos($aluno['foto'], 'https://') !== 0) {
                // Remove "../" do início se existir
                $aluno['foto'] = preg_replace('/^\.\.\//', '', $aluno['foto']);
                
                // Adiciona caminho relativo se necessário
                if (strpos($aluno['foto'], '/') !== 0 && strpos($aluno['foto'], 'uploads/') !== 0) {
                    $aluno['foto'] = 'uploads/fotos/' . $aluno['foto'];
                }
            }
        }
        
        // Busca endereço do aluno
        $stmt_end = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = :aluno_id");
        $stmt_end->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
        $stmt_end->execute();
        $endereco = $stmt_end->fetch(PDO::FETCH_ASSOC);
        
        // Busca responsáveis do aluno
        $stmt_resp = $pdo->prepare("
            SELECT r.* 
            FROM responsaveis r
            INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
            WHERE ar.aluno_id = :aluno_id
        ");
        $stmt_resp->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
        $stmt_resp->execute();
        $responsaveis = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar resposta
        $response['success'] = true;
        $response['aluno'] = $aluno;
        $response['endereco'] = $endereco;
        $response['responsaveis'] = $responsaveis;
    } else {
        $response['message'] = 'Aluno não encontrado';
    }
} catch(PDOException $e) {
    $response['message'] = 'Erro ao buscar dados: ' . $e->getMessage();
}

// Enviar resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>