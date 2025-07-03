<?php
// api/gerenciar_feedback_admin.php
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar feedbacks.']);
    exit;
}

// Configuração do banco de dados
if (file_exists("../env_config.php")) {
    require "../env_config.php";
} else if (file_exists("../../env_config.php")) {
    require "../../env_config.php"; 
} else if (file_exists("env_config.php")) {
    require "env_config.php";
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Arquivo de configuração não encontrado']);
    exit;
}

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit;
}

$admin_id = $_SESSION['usuario_id'];
$admin_nome = $_SESSION['usuario_nome'] ?? 'Administrador';
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'adicionar_feedback' || $action === 'editar_feedback') {
        $ocorrencia_id = $_POST['ocorrencia_id'] ?? '';
        $feedback_admin = $_POST['feedback_admin'] ?? '';
        
        if (empty($ocorrencia_id) || empty($feedback_admin)) {
            $response['message'] = 'ID da ocorrência e feedback são obrigatórios';
        } else {
            try {
                // Verificar se a ocorrência existe
                $stmt_check = $pdo->prepare("SELECT id, professor_id FROM ocorrencias WHERE id = ?");
                $stmt_check->execute([$ocorrencia_id]);
                $ocorrencia = $stmt_check->fetch();
                
                if (!$ocorrencia) {
                    $response['message'] = 'Ocorrência não encontrada';
                } else {
                    // Atualizar/adicionar feedback
                    $stmt = $pdo->prepare("
                        UPDATE ocorrencias 
                        SET feedback_admin = ?, 
                            feedback_data = NOW(), 
                            feedback_admin_id = ?, 
                            feedback_admin_nome = ?
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$feedback_admin, $admin_id, $admin_nome, $ocorrencia_id])) {
                        $response['success'] = true;
                        $response['message'] = $action === 'adicionar_feedback' ? 
                            'Feedback adicionado com sucesso' : 
                            'Feedback atualizado com sucesso';
                        
                        // Registrar na auditoria (se existir)
                        try {
                            $stmt_audit = $pdo->prepare("
                                INSERT INTO auditoria 
                                (usuario_id, usuario_nome, acao, tabela, registro_id, dados_novos, ip_address) 
                                VALUES (?, ?, ?, 'ocorrencias', ?, ?, ?)
                            ");
                            $acao = $action === 'adicionar_feedback' ? 'ADICIONAR_FEEDBACK' : 'EDITAR_FEEDBACK';
                            $dados_novos = json_encode([
                                'feedback_admin' => $feedback_admin,
                                'feedback_data' => date('Y-m-d H:i:s'),
                                'feedback_admin_nome' => $admin_nome
                            ]);
                            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            
                            $stmt_audit->execute([$admin_id, $admin_nome, $acao, $ocorrencia_id, $dados_novos, $ip]);
                        } catch (Exception $e) {
                            // Se der erro na auditoria, não falha a operação principal
                        }
                    } else {
                        $response['message'] = 'Erro ao salvar feedback';
                    }
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao processar solicitação: ' . $e->getMessage();
            }
        }
    }
    elseif ($action === 'remover_feedback') {
        $ocorrencia_id = $_POST['ocorrencia_id'] ?? '';
        
        if (empty($ocorrencia_id)) {
            $response['message'] = 'ID da ocorrência é obrigatório';
        } else {
            try {
                // Verificar se a ocorrência existe
                $stmt_check = $pdo->prepare("SELECT id FROM ocorrencias WHERE id = ?");
                $stmt_check->execute([$ocorrencia_id]);
                
                if ($stmt_check->rowCount() === 0) {
                    $response['message'] = 'Ocorrência não encontrada';
                } else {
                    // Remover feedback
                    $stmt = $pdo->prepare("
                        UPDATE ocorrencias 
                        SET feedback_admin = NULL, 
                            feedback_data = NULL, 
                            feedback_admin_id = NULL, 
                            feedback_admin_nome = NULL
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$ocorrencia_id])) {
                        $response['success'] = true;
                        $response['message'] = 'Feedback removido com sucesso';
                    } else {
                        $response['message'] = 'Erro ao remover feedback';
                    }
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao processar solicitação: ' . $e->getMessage();
            }
        }
    }
    else {
        $response['message'] = 'Ação não reconhecida';
    }
} else {
    $response['message'] = 'Método não permitido';
}

header('Content-Type: application/json');
echo json_encode($response);