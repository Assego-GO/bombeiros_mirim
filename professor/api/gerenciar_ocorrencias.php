<?php
// api/gerenciar_ocorrencias.php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Configuração do banco de dados - CORRIGINDO O CAMINHO
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

$professor_id = $_SESSION['usuario_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cadastrar') {
        // Validar campos obrigatórios
        $turma_id = $_POST['turma_id'] ?? '';
        $aluno_id = $_POST['aluno_id'] ?? '';
        $data_ocorrencia = $_POST['data_ocorrencia'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $acoes_tomadas = $_POST['acoes_tomadas'] ?? '';
        $houve_reuniao = isset($_POST['houve_reuniao_responsaveis']) && $_POST['houve_reuniao_responsaveis'] == '1' ? 1 : 0;
        $detalhes_reuniao = $_POST['detalhes_reuniao'] ?? '';
        
        if (empty($turma_id) || empty($aluno_id) || empty($data_ocorrencia) || empty($descricao)) {
            $response['message'] = 'Todos os campos obrigatórios devem ser preenchidos';
        } else {
            try {
                // Verificar se a turma pertence ao professor
                $stmt_check = $pdo->prepare("SELECT id FROM turma WHERE id = ? AND id_professor = ?");
                $stmt_check->execute([$turma_id, $professor_id]);
                
                if ($stmt_check->rowCount() === 0) {
                    $response['message'] = 'Turma não encontrada ou não pertence a você';
                } else {
                    // Verificar se o aluno pertence à turma
                    $stmt_aluno = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM matriculas m 
                        WHERE m.aluno_id = ? AND m.turma = ? AND m.status = 'ativo'
                    ");
                    $stmt_aluno->execute([$aluno_id, $turma_id]);
                    $aluno_check = $stmt_aluno->fetch();
                    
                    if ($aluno_check['count'] == 0) {
                        $response['message'] = 'Aluno não encontrado nesta turma';
                    } else {
                        // Inserir ocorrência
                        $stmt = $pdo->prepare("
                            INSERT INTO ocorrencias 
                            (professor_id, aluno_id, turma_id, data_ocorrencia, descricao, acoes_tomadas, houve_reuniao_responsaveis, detalhes_reuniao) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        if ($stmt->execute([$professor_id, $aluno_id, $turma_id, $data_ocorrencia, $descricao, $acoes_tomadas, $houve_reuniao, $detalhes_reuniao])) {
                            $response['success'] = true;
                            $response['message'] = 'Ocorrência cadastrada com sucesso';
                            $response['ocorrencia_id'] = $pdo->lastInsertId();
                        } else {
                            $response['message'] = 'Erro ao cadastrar ocorrência';
                        }
                    }
                }
            } catch (PDOException $e) {
                $response['message'] = 'Erro ao processar solicitação: ' . $e->getMessage();
            }
        }
    } 
    elseif ($action === 'editar') {
        $ocorrencia_id = $_POST['ocorrencia_id'] ?? '';
        $turma_id = $_POST['turma_id'] ?? '';
        $aluno_id = $_POST['aluno_id'] ?? '';
        $data_ocorrencia = $_POST['data_ocorrencia'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $acoes_tomadas = $_POST['acoes_tomadas'] ?? '';
        $houve_reuniao = isset($_POST['houve_reuniao_responsaveis']) && $_POST['houve_reuniao_responsaveis'] == '1' ? 1 : 0;
        $detalhes_reuniao = $_POST['detalhes_reuniao'] ?? '';
        
        if (empty($ocorrencia_id) || empty($turma_id) || empty($aluno_id) || empty($data_ocorrencia) || empty($descricao)) {
            $response['message'] = 'Todos os campos obrigatórios devem ser preenchidos';
        } else {
            try {
                // Verificar se a ocorrência pertence ao professor
                $stmt_check = $pdo->prepare("SELECT id FROM ocorrencias WHERE id = ? AND professor_id = ?");
                $stmt_check->execute([$ocorrencia_id, $professor_id]);
                
                if ($stmt_check->rowCount() === 0) {
                    $response['message'] = 'Ocorrência não encontrada ou não pertence a você';
                } else {
                    // Atualizar ocorrência
                    $stmt = $pdo->prepare("
                        UPDATE ocorrencias 
                        SET turma_id = ?, aluno_id = ?, data_ocorrencia = ?, descricao = ?, 
                            acoes_tomadas = ?, houve_reuniao_responsaveis = ?, detalhes_reuniao = ?,
                            data_atualizacao = CURRENT_TIMESTAMP
                        WHERE id = ? AND professor_id = ?
                    ");
                    
                    if ($stmt->execute([$turma_id, $aluno_id, $data_ocorrencia, $descricao, $acoes_tomadas, $houve_reuniao, $detalhes_reuniao, $ocorrencia_id, $professor_id])) {
                        $response['success'] = true;
                        $response['message'] = 'Ocorrência atualizada com sucesso';
                    } else {
                        $response['message'] = 'Erro ao atualizar ocorrência';
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