<?php
// api/buscar_perfil.php
session_start();
require_once 'conexao.php';

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

// Definir a URL base do projeto para fotos
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$caminhoScript = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Subir um nível para sair da pasta 'api'
$basePath = preg_replace('/(\/aluno|\/admin|\/painel)$/', '', $caminhoScript);
$baseUrl = $protocolo . $host . $basePath;

try {
    // Busca dados do aluno
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
    $stmt->bindParam(':id', $id_aluno, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Preparar foto para exibição
        if (!empty($aluno['foto'])) {
            // Remover possíveis caminhos relativos do início
            $aluno['foto'] = ltrim($aluno['foto'], './');
            
            // Ajustar o caminho para apontar para a pasta correta
            if (strpos($aluno['foto'], 'http://') !== 0 && strpos($aluno['foto'], 'https://') !== 0) {
                // Se começa com uploads/fotos/
                if (strpos($aluno['foto'], 'uploads/fotos/') === 0) {
                    $aluno['foto'] = $baseUrl . '/' . $aluno['foto'];
                }
                // Se começa com ../uploads/fotos/
                else if (strpos($aluno['foto'], '../uploads/fotos/') === 0) {
                    $aluno['foto'] = $baseUrl . '/' . substr($aluno['foto'], 3);
                }
                // Se começa com /uploads/fotos/
                else if (strpos($aluno['foto'], '/uploads/fotos/') === 0) {
                    $aluno['foto'] = $baseUrl . $aluno['foto'];
                }
                // Se for apenas o nome do arquivo
                else if (strpos($aluno['foto'], '/') === false) {
                    $aluno['foto'] = $baseUrl . '/uploads/fotos/' . $aluno['foto'];
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