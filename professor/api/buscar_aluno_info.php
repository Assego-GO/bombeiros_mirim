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

// Verificar se foi enviado o ID do aluno
if (!isset($_GET['aluno_id']) || empty($_GET['aluno_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do aluno não especificado'
    ]);
    exit;
}

// Configuração do banco de dados
require "../env_config.php";

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

$aluno_id = $_GET['aluno_id'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o professor tem acesso a este aluno
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        INNER JOIN turma t ON m.turma = t.id
        WHERE a.id = ? AND t.id_professor = ?
    ");
    $stmt->execute([$aluno_id, $usuario_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Você não tem acesso a este aluno'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar acesso ao aluno: ' . $e->getMessage()
    ]);
    exit;
}

// Buscar informações do aluno
try {
    // Informações básicas do aluno
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$aluno) {
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ]);
        exit;
    }
    
    // Calcular idade
    if (!empty($aluno['data_nascimento'])) {
        $dataNascimento = new DateTime($aluno['data_nascimento']);
        $hoje = new DateTime();
        $idade = $hoje->diff($dataNascimento);
        $aluno['idade'] = $idade->y;
    } else {
        $aluno['idade'] = null;
    }
    
    // Buscar endereço
    $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar responsáveis
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM responsaveis r
        INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
        WHERE ar.aluno_id = ?
    ");
    $stmt->execute([$aluno_id]);
    $responsaveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar matrícula
    $stmt = $pdo->prepare("
        SELECT m.*, t.nome_turma, u.nome as nome_unidade
        FROM matriculas m
        INNER JOIN turma t ON m.turma = t.id
        INNER JOIN unidade u ON m.unidade = u.id
        WHERE m.aluno_id = ?
        ORDER BY m.data_matricula DESC
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar histórico de presença
    $stmt = $pdo->prepare("
        SELECT p.*, DATE_FORMAT(p.data_aula, '%d/%m/%Y') as data_formatada
        FROM presencas p
        WHERE p.aluno_id = ?
        ORDER BY p.data_aula DESC
        LIMIT 10
    ");
    $stmt->execute([$aluno_id]);
    $presencas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estatísticas de presença
    $total_aulas = count($presencas);
    $total_presencas = 0;
    
    foreach ($presencas as $presenca) {
        if ($presenca['presente'] == 1) {
            $total_presencas++;
        }
    }
    
    $taxa_presenca = $total_aulas > 0 ? round(($total_presencas / $total_aulas) * 100) : 0;
    
    // Definir a URL base do projeto
    $baseUrl = '';
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $caminhoScript = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $basePath = preg_replace('/(\/aluno|\/admin|\/painel|\/professor|\/api)$/', '', $caminhoScript);
    $baseUrl = $protocolo . $host . $basePath;
    
    // Processar URL da foto
    if (!empty($aluno['foto'])) {
        // Remover possíveis caminhos relativos do início
        $aluno['foto'] = ltrim($aluno['foto'], './');
        
        // Ajustar URL da foto
        if (strpos($aluno['foto'], 'http://') === 0 || strpos($aluno['foto'], 'https://') === 0) {
            // URL já completa, não precisa fazer nada
        } 
        // Se começa com uploads/fotos/
        else if (strpos($aluno['foto'], 'uploads/fotos/') === 0) {
            $aluno['foto'] = $baseUrl . '/' . $aluno['foto'];
        }
        // Se começa com ../uploads/fotos/
        else if (strpos($aluno['foto'], '../uploads/fotos/') === 0) {
            // Remover os ../ e usar caminho raiz
            $aluno['foto'] = $baseUrl . '/' . substr($aluno['foto'], 3);
        }
        // Se for apenas o nome do arquivo
        else if (strpos($aluno['foto'], '/') === false) {
            $aluno['foto'] = $baseUrl . '/uploads/fotos/' . $aluno['foto'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'aluno' => $aluno,
        'endereco' => $endereco,
        'responsaveis' => $responsaveis,
        'matricula' => $matricula,
        'presencas' => [
            'historico' => $presencas,
            'total_aulas' => $total_aulas,
            'total_presencas' => $total_presencas,
            'taxa_presenca' => $taxa_presenca
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar informações do aluno: ' . $e->getMessage()
    ]);
    exit;
}