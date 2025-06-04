<?php
// Iniciar sessão
session_start();

// Headers para JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Verificar se é um método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Obter dados do POST (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['aluno_id']) || !isset($data['turma_id']) || !isset($data['presente'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados incompletos'
    ]);
    exit;
}

$aluno_id = $data['aluno_id'];
$turma_id = $data['turma_id'];
$presente = (int)$data['presente']; // 0 = ausente, 1 = presente
$professor_id = $_SESSION['usuario_id'];
$data_atual = date('Y-m-d');

// Configuração do banco de dados
require "../../env_config.php";
$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar se o professor tem acesso à turma
try {
    $stmt = $pdo->prepare("SELECT id FROM turma WHERE id = ? AND id_professor = ?");
    $stmt->execute([$turma_id, $professor_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Você não tem acesso a esta turma'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar acesso à turma: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar se o aluno está matriculado na turma
try {
    $stmt = $pdo->prepare("
        SELECT id FROM matriculas 
        WHERE aluno_id = ? AND turma = ? AND status != 'inativo'
    ");
    $stmt->execute([$aluno_id, $turma_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não está matriculado nesta turma'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar matrícula do aluno: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar se precisamos criar a tabela de presença caso ela não exista
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS presencas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            turma_id INT NOT NULL,
            professor_id INT NOT NULL,
            data_aula DATE NOT NULL,
            presente TINYINT(1) NOT NULL DEFAULT 0,
            observacao TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_presenca (aluno_id, turma_id, data_aula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ");
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar tabela de presenças: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar se já existe um registro para este aluno, turma e data
try {
    $stmt = $pdo->prepare("
        SELECT id FROM presencas 
        WHERE aluno_id = ? AND turma_id = ? AND data_aula = ?
    ");
    $stmt->execute([$aluno_id, $turma_id, $data_atual]);
    $presenca = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($presenca) {
        // Atualizar o registro existente
        $stmt = $pdo->prepare("
            UPDATE presencas 
            SET presente = ?, professor_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$presente, $professor_id, $presenca['id']]);
    } else {
        // Criar um novo registro
        $stmt = $pdo->prepare("
            INSERT INTO presencas (aluno_id, turma_id, professor_id, data_aula, presente)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$aluno_id, $turma_id, $professor_id, $data_atual, $presente]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $presente ? 'Presença registrada' : 'Ausência registrada',
        'presente' => $presente
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar presença: ' . $e->getMessage()
    ]);
    exit;
}