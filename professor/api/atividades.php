<?php
// atividades.php
session_start();

// Verificar se o usuário está logado e é professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Configuração do banco de dados
require "../env_config.php";

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$professor_id = $_SESSION['usuario_id'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'listar':
                    listarAtividades($pdo, $professor_id);
                    break;
                case 'detalhes':
                    if (isset($_GET['id'])) {
                        detalhesAtividade($pdo, $_GET['id'], $professor_id);
                    }
                    break;
                case 'turmas':
                    listarTurmasProfessor($pdo, $professor_id);
                    break;
            }
        }
        break;
        
    case 'POST':
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'cadastrar':
                    cadastrarAtividade($pdo, $_POST, $professor_id);
                    break;
                case 'registrar_participacao':
                    registrarParticipacao($pdo, $_POST, $professor_id);
                    break;
            }
        }
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action']) && $input['action'] === 'atualizar') {
            atualizarAtividade($pdo, $input, $professor_id);
        }
        break;
        
    case 'DELETE':
        if (isset($_GET['id'])) {
            deletarAtividade($pdo, $_GET['id'], $professor_id);
        }
        break;
}

function listarAtividades($pdo, $professor_id) {
    try {
        $sql = "SELECT a.*, t.nome_turma, u.nome as unidade_nome 
                FROM atividades a 
                JOIN turma t ON a.turma_id = t.id 
                JOIN unidade u ON t.id_unidade = u.id 
                WHERE a.professor_id = ? 
                ORDER BY a.data_atividade DESC, a.hora_inicio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$professor_id]);
        $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar participações para cada atividade
        foreach ($atividades as &$atividade) {
            $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM atividade_participacao WHERE atividade_id = ?");
            $stmt_count->execute([$atividade['id']]);
            $count = $stmt_count->fetch(PDO::FETCH_ASSOC);
            $atividade['total_participacoes'] = $count['total'];
        }
        
        echo json_encode(['success' => true, 'atividades' => $atividades]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao listar atividades: ' . $e->getMessage()]);
    }
}

function detalhesAtividade($pdo, $atividade_id, $professor_id) {
    try {
        // Buscar detalhes da atividade
        $sql = "SELECT a.*, t.nome_turma, u.nome as unidade_nome 
                FROM atividades a 
                JOIN turma t ON a.turma_id = t.id 
                JOIN unidade u ON t.id_unidade = u.id 
                WHERE a.id = ? AND a.professor_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$atividade_id, $professor_id]);
        $atividade = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$atividade) {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
            return;
        }
        
        // Buscar participações dos alunos
        $sql_participacoes = "SELECT ap.*, al.nome as aluno_nome 
                              FROM atividade_participacao ap 
                              JOIN alunos al ON ap.aluno_id = al.id 
                              WHERE ap.atividade_id = ?";
        
        $stmt_part = $pdo->prepare($sql_participacoes);
        $stmt_part->execute([$atividade_id]);
        $participacoes = $stmt_part->fetchAll(PDO::FETCH_ASSOC);
        
        $atividade['participacoes'] = $participacoes;
        
        echo json_encode(['success' => true, 'atividade' => $atividade]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()]);
    }
}

function listarTurmasProfessor($pdo, $professor_id) {
    try {
        $sql = "SELECT t.id, t.nome_turma, u.nome as unidade_nome 
                FROM turma t 
                JOIN unidade u ON t.id_unidade = u.id 
                WHERE t.id_professor = ? AND t.status = 'Em Andamento'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$professor_id]);
        $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'turmas' => $turmas]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar turmas: ' . $e->getMessage()]);
    }
}

function cadastrarAtividade($pdo, $dados, $professor_id) {
    try {
        $sql = "INSERT INTO atividades (
                    nome_atividade, turma_id, professor_id, data_atividade, 
                    hora_inicio, hora_termino, local_atividade, instrutor_responsavel, 
                    objetivo_atividade, conteudo_abordado, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planejada')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dados['nome_atividade'],
            $dados['turma_id'],
            $professor_id,
            $dados['data_atividade'],
            $dados['hora_inicio'],
            $dados['hora_termino'],
            $dados['local_atividade'],
            $dados['instrutor_responsavel'],
            $dados['objetivo_atividade'],
            $dados['conteudo_abordado']
        ]);
        
        $atividade_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Atividade cadastrada com sucesso!',
            'atividade_id' => $atividade_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar atividade: ' . $e->getMessage()]);
    }
}

function atualizarAtividade($pdo, $dados, $professor_id) {
    try {
        $sql = "UPDATE atividades SET 
                    nome_atividade = ?, data_atividade = ?, hora_inicio = ?, 
                    hora_termino = ?, local_atividade = ?, instrutor_responsavel = ?, 
                    objetivo_atividade = ?, conteudo_abordado = ?, status = ?
                WHERE id = ? AND professor_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dados['nome_atividade'],
            $dados['data_atividade'],
            $dados['hora_inicio'],
            $dados['hora_termino'],
            $dados['local_atividade'],
            $dados['instrutor_responsavel'],
            $dados['objetivo_atividade'],
            $dados['conteudo_abordado'],
            $dados['status'],
            $dados['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Atividade atualizada com sucesso!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar atividade: ' . $e->getMessage()]);
    }
}

function deletarAtividade($pdo, $atividade_id, $professor_id) {
    try {
        $sql = "DELETE FROM atividades WHERE id = ? AND professor_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$atividade_id, $professor_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Atividade excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir atividade: ' . $e->getMessage()]);
    }
}

function registrarParticipacao($pdo, $dados, $professor_id) {
    try {
        // Verificar se a atividade pertence ao professor
        $stmt_verify = $pdo->prepare("SELECT id FROM atividades WHERE id = ? AND professor_id = ?");
        $stmt_verify->execute([$dados['atividade_id'], $professor_id]);
        
        if (!$stmt_verify->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Atividade não encontrada']);
            return;
        }
        
        $sql = "INSERT INTO atividade_participacao (
                    atividade_id, aluno_id, presenca, desempenho_nota, 
                    desempenho_conceito, habilidades_desenvolvidas, 
                    comportamento, observacoes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    presenca = VALUES(presenca),
                    desempenho_nota = VALUES(desempenho_nota),
                    desempenho_conceito = VALUES(desempenho_conceito),
                    habilidades_desenvolvidas = VALUES(habilidades_desenvolvidas),
                    comportamento = VALUES(comportamento),
                    observacoes = VALUES(observacoes),
                    atualizado_em = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dados['atividade_id'],
            $dados['aluno_id'],
            $dados['presenca'],
            $dados['desempenho_nota'] ?? null,
            $dados['desempenho_conceito'] ?? null,
            json_encode($dados['habilidades_desenvolvidas'] ?? []),
            $dados['comportamento'] ?? null,
            $dados['observacoes'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Participação registrada com sucesso!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar participação: ' . $e->getMessage()]);
    }
}
?>