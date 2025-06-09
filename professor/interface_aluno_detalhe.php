<?php
// Ativar exibição de erros para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!function_exists('debug')) {
    function debug($var, $title = '') {
        echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc; background: #f8f8f8;'>";
        if ($title) {
            echo "<h3 style='margin-top: 0;'>$title</h3>";
        }
        echo "<pre>";
        print_r($var);
        echo "</pre></div>";
    }
}

// Iniciar sessão
session_start();

// Debug das variáveis de sessão
debug($_SESSION, 'SESSION');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    die("ERRO: Usuário não está logado. Redirecionando para login...");
}

// Verificar se foi fornecido um ID de aluno
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ERRO: ID do aluno não fornecido. Redirecionando para index...");
}

// Debug das variáveis GET
debug($_GET, 'GET');

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
    echo "<div style='color:green;'>Conexão com o banco de dados estabelecida com sucesso.</div>";
} catch (PDOException $e) {
    die("ERRO na conexão com o banco de dados: " . $e->getMessage());
}

$aluno_id = $_GET['id'];
$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"] ?? '';
$usuario_foto = $_SESSION["usuario_foto"] ?? '';

// Debug das variáveis principais
debug([
    'aluno_id' => $aluno_id,
    'usuario_id' => $usuario_id,
    'usuario_nome' => $usuario_nome,
    'usuario_foto' => $usuario_foto
], 'Variáveis principais');

// Definir a URL base do projeto
$baseUrl = '';
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$caminhoScript = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('/(\/aluno|\/admin|\/painel|\/professor)$/', '', $caminhoScript);
$baseUrl = $protocolo . $host . $basePath;

debug([
    'baseUrl' => $baseUrl,
    'caminhoScript' => $caminhoScript,
    'basePath' => $basePath
], 'Variáveis de URL');

// Testar a consulta para verificar se o professor tem acesso ao aluno
try {
    $query = "
    SELECT DISTINCT a.* 
    FROM alunos a
    INNER JOIN matriculas m ON a.id = m.aluno_id
    INNER JOIN turma t ON m.turma = t.id
    WHERE a.id = ? AND t.id_professor = ?
    ";
    
    debug([$query, $aluno_id, $usuario_id], 'Consulta de verificação de acesso');
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$aluno_id, $usuario_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    debug($aluno, 'Resultado da consulta de aluno');
    
    if (!$aluno) {
        die("ERRO: O professor não tem acesso a este aluno ou o aluno não existe.");
    }
    
    echo "<div style='color:green;'>Aluno encontrado e professor tem acesso.</div>";
} catch (PDOException $e) {
    die("ERRO ao verificar acesso ao aluno: " . $e->getMessage());
}

// Verificar estrutura das tabelas
try {
    echo "<h3>Verificando estrutura das tabelas</h3>";
    
    // Verificar tabela alunos
    $stmt = $pdo->prepare("DESCRIBE alunos");
    $stmt->execute();
    $alunos_estrutura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug($alunos_estrutura, 'Estrutura da tabela alunos');
    
    // Verificar tabela matriculas
    $stmt = $pdo->prepare("DESCRIBE matriculas");
    $stmt->execute();
    $matriculas_estrutura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug($matriculas_estrutura, 'Estrutura da tabela matriculas');
    
    // Verificar tabela turma
    $stmt = $pdo->prepare("DESCRIBE turma");
    $stmt->execute();
    $turma_estrutura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug($turma_estrutura, 'Estrutura da tabela turma');
    
    // Verificar se existe a tabela enderecos
    try {
        $stmt = $pdo->prepare("DESCRIBE enderecos");
        $stmt->execute();
        echo "<div style='color:green;'>Tabela enderecos existe.</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red;'>Tabela enderecos não existe: " . $e->getMessage() . "</div>";
    }
    
    // Verificar se existe a tabela responsaveis
    try {
        $stmt = $pdo->prepare("DESCRIBE responsaveis");
        $stmt->execute();
        echo "<div style='color:green;'>Tabela responsaveis existe.</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red;'>Tabela responsaveis não existe: " . $e->getMessage() . "</div>";
    }
    
    // Verificar se existe a tabela aluno_responsavel
    try {
        $stmt = $pdo->prepare("DESCRIBE aluno_responsavel");
        $stmt->execute();
        echo "<div style='color:green;'>Tabela aluno_responsavel existe.</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red;'>Tabela aluno_responsavel não existe: " . $e->getMessage() . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao verificar estrutura das tabelas: " . $e->getMessage() . "</div>";
}

// Processar URL da foto do aluno
if (!empty($aluno['foto'])) {
    $aluno_foto_original = $aluno['foto'];
    // Remover possíveis caminhos relativos do início
    $aluno['foto'] = ltrim($aluno['foto'], './');
    
    // Padrões de caminhos encontrados no banco de dados
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
    
    debug([
        'foto_original' => $aluno_foto_original,
        'foto_processada' => $aluno['foto']
    ], 'Processamento da foto do aluno');
}

// Buscar endereço do aluno
try {
    $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
    debug($endereco, 'Endereço do aluno');
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao buscar endereço do aluno: " . $e->getMessage() . "</div>";
    $endereco = null;
}

// Buscar responsáveis do aluno
try {
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM responsaveis r
        INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
        WHERE ar.aluno_id = ?
    ");
    $stmt->execute([$aluno_id]);
    $responsaveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug($responsaveis, 'Responsáveis do aluno');
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao buscar responsáveis do aluno: " . $e->getMessage() . "</div>";
    $responsaveis = [];
}

// Buscar matrícula do aluno
try {
    $stmt = $pdo->prepare("
        SELECT m.*, t.nome_turma, u.nome as nome_unidade
        FROM matriculas m
        INNER JOIN turma t ON m.turma = t.id
        INNER JOIN unidade u ON m.unidade = u.id
        WHERE m.aluno_id = ? AND t.id_professor = ?
        ORDER BY m.data_matricula DESC
        LIMIT 1
    ");
    $stmt->execute([$aluno_id, $usuario_id]);
    $matricula = $stmt->fetch(PDO::FETCH_ASSOC);
    debug($matricula, 'Matrícula do aluno');
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao buscar matrícula do aluno: " . $e->getMessage() . "</div>";
    $matricula = null;
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
    echo "<div style='color:green;'>Tabela presencas verificada/criada com sucesso.</div>";
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao criar tabela de presenças: " . $e->getMessage() . "</div>";
}

// Buscar histórico de presença do aluno (últimas 10)
try {
    $stmt = $pdo->prepare("
        SELECT p.*, DATE_FORMAT(p.data_aula, '%d/%m/%Y') as data_formatada
        FROM presencas p
        WHERE p.aluno_id = ? AND p.professor_id = ?
        ORDER BY p.data_aula DESC
        LIMIT 10
    ");
    $stmt->execute([$aluno_id, $usuario_id]);
    $presencas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug($presencas, 'Presenças do aluno');
} catch (PDOException $e) {
    echo "<div style='color:red;'>ERRO ao buscar presenças: " . $e->getMessage() . "</div>";
    $presencas = [];
}

// Verificar se o arquivo da interface existe
$interface_file = 'interface_aluno_detalhe.php';
if (file_exists($interface_file)) {
    echo "<div style='color:green;'>Arquivo de interface '$interface_file' encontrado.</div>";
    echo "<h3>Vamos tentar carregar a interface agora:</h3>";
    echo "<div style='border: 2px solid blue; padding: 10px; margin: 10px 0;'>";
    echo "---- INÍCIO DO CONTEÚDO DA INTERFACE ----<br>";
    
    // Capturar o output do include
    ob_start();
    include($interface_file);
    $interface_output = ob_get_clean();
    
    // Verificar se foi gerado algum output
    if (empty($interface_output)) {
        echo "<div style='color:red;'>A interface não gerou nenhum output!</div>";
    } else {
        echo $interface_output;
    }
    
    echo "<br>---- FIM DO CONTEÚDO DA INTERFACE ----";
    echo "</div>";
} else {
    echo "<div style='color:red;'>ERRO: Arquivo de interface '$interface_file' não encontrado!</div>";
    
    // Tentar listar arquivos no diretório atual
    echo "<h3>Arquivos no diretório atual:</h3>";
    $files = scandir('.');
    debug($files, 'Lista de arquivos');
}

// Tente obter o conteúdo do arquivo de interface para análise, se ele existir
if (file_exists($interface_file)) {
    $interface_content = file_get_contents($interface_file);
    if ($interface_content !== false) {
        echo "<h3>Primeiras 1000 caracteres do arquivo de interface:</h3>";
        echo "<pre>" . htmlspecialchars(substr($interface_content, 0, 1000)) . "...</pre>";
    }
}
?>0506451650165441540403520+560+65+2+++++
++++