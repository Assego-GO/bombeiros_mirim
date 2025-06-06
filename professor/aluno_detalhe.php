<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// Verificar se foi fornecido um ID de aluno
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}
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
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$aluno_id = $_GET['id'];
$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"] ?? '';
$usuario_foto = $_SESSION["usuario_foto"] ?? '';

// Definir a URL base do projeto
$baseUrl = '';
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$caminhoScript = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('/(\/aluno|\/admin|\/painel|\/professor)$/', '', $caminhoScript);
$baseUrl = $protocolo . $host . $basePath;

// Processar URL da foto do usuário
if (!empty($usuario_foto)) {
    // Remover possíveis caminhos relativos do início
    $usuario_foto = ltrim($usuario_foto, './');
    
    // Padrões de caminhos encontrados no banco de dados
    if (strpos($usuario_foto, 'http://') === 0 || strpos($usuario_foto, 'https://') === 0) {
        // URL já completa, não precisa fazer nada
    } 
    // Se começa com uploads/fotos/
    else if (strpos($usuario_foto, 'uploads/fotos/') === 0) {
        $usuario_foto = $baseUrl . '/' . $usuario_foto;
    }
    // Se começa com ../uploads/fotos/
    else if (strpos($usuario_foto, '../uploads/fotos/') === 0) {
        // Remover os ../ e usar caminho raiz
        $usuario_foto = $baseUrl . '/' . substr($usuario_foto, 3);
    }
    // Se for apenas o nome do arquivo
    else if (strpos($usuario_foto, '/') === false) {
        $usuario_foto = $baseUrl . '/uploads/fotos/' . $usuario_foto;
    }
}

// Verificar se o professor tem acesso a este aluno através das turmas
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.* 
        FROM alunos a
        INNER JOIN matriculas m ON a.id = m.aluno_id
        INNER JOIN turma t ON m.turma = t.id
        WHERE a.id = ? AND t.id_professor = ?
    ");
    $stmt->execute([$aluno_id, $usuario_id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        // Aluno não encontrado ou professor não tem acesso
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao verificar acesso ao aluno: " . $e->getMessage());
}

// Processar URL da foto do aluno
if (!empty($aluno['foto'])) {
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
}

// Buscar endereço do aluno
try {
    $stmt = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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
} catch (PDOException $e) {
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
} catch (PDOException $e) {
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
} catch (PDOException $e) {
    // Ignorar erro se a tabela já existir
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
} catch (PDOException $e) {
    $presencas = [];
}

// Verificar se um formulário foi enviado para registrar presença
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_presenca'])) {
    $turma_id = $_POST['turma_id'] ?? '';
    $presente = isset($_POST['presente']) ? 1 : 0;
    $data_aula = $_POST['data_aula'] ?? date('Y-m-d');
    $observacao = $_POST['observacao'] ?? '';

    try {
        // Verificar se já existe registro para esta data
        $stmt = $pdo->prepare("
            SELECT id FROM presencas 
            WHERE aluno_id = ? AND turma_id = ? AND data_aula = ?
        ");
        $stmt->execute([$aluno_id, $turma_id, $data_aula]);
        $presenca_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($presenca_existente) {
            // Atualizar registro existente
            $stmt = $pdo->prepare("
                UPDATE presencas 
                SET presente = ?, observacao = ?, professor_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$presente, $observacao, $usuario_id, $presenca_existente['id']]);
        } else {
            // Criar novo registro
            $stmt = $pdo->prepare("
                INSERT INTO presencas (aluno_id, turma_id, professor_id, data_aula, presente, observacao)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$aluno_id, $turma_id, $usuario_id, $data_aula, $presente, $observacao]);
        }
        
        header("Location: novo_aluno_detalhe.php?id=$aluno_id&success=1");
        exit;
        
    } catch (PDOException $e) {
        $erro_mensagem = "Erro ao registrar presença: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detalhes do Aluno | Bombeiros Mirim</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/detalhe_alunos.css">
   
</head>
<body>

    <header class="main-header">
        <div class="header-container">
            <div class="app-title">
                <i class="fas fa-graduation-cap me-1"></i>Bombeiros Mirim
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Header da Página -->
        <div class="page-header">
            <div>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                    <li class="breadcrumb-item active">Detalhes do Aluno</li>
                </ol>
                <h1 class="page-title">Detalhes do Aluno</h1>
            </div>
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
            <div>Presença registrada com sucesso!</div>
        </div>
        <?php endif; ?>

        <?php if (isset($erro_mensagem)): ?>
        <div class="alert alert-danger">
            <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div><?php echo $erro_mensagem; ?></div>
        </div>
        <?php endif; ?>

        <!-- Informações do Aluno -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="section-header section-navy">
                        <div class="section-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="section-title">Perfil do Aluno</h3>
                    </div>
                    <div class="card-body">
                        <div class="profile-section">
                            <img src="<?php echo !empty($aluno['foto']) ? $aluno['foto'] : $baseUrl . '/uploads/fotos/default.png'; ?>" alt="Foto do aluno" class="profile-img">
                            <h3 class="profile-name"><?php echo htmlspecialchars($aluno['nome']); ?></h3>
                            <div class="profile-matricula">Matrícula: <?php echo htmlspecialchars($aluno['numero_matricula']); ?></div>
                            <?php if (isset($matricula) && $matricula): ?>
                            <span class="profile-badge badge-status-<?php echo $matricula['status']; ?>">
                                <?php if($matricula['status'] == 'ativo'): ?>
                                    <i class="fas fa-check-circle me-1"></i>
                                <?php elseif($matricula['status'] == 'inativo'): ?>
                                    <i class="fas fa-times-circle me-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-clock me-1"></i>
                                <?php endif; ?>
                                <?php echo ucfirst(htmlspecialchars($matricula['status'])); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="section-header section-blue">
                        <div class="section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="section-title">Informações Pessoais</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Data de Nascimento</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">RG/CPF</div>
                                <div class="info-value">
                                    <?php 
                                    if (!empty($aluno['rg']) || !empty($aluno['cpf'])) {
                                        if (!empty($aluno['rg'])) echo "RG: " . htmlspecialchars($aluno['rg']);
                                        if (!empty($aluno['rg']) && !empty($aluno['cpf'])) echo "<br>";
                                        if (!empty($aluno['cpf'])) echo "CPF: " . htmlspecialchars($aluno['cpf']);
                                    } else {
                                        echo "Não informado";
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Escola</div>
                                <div class="info-value"><?php echo htmlspecialchars($aluno['escola']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Série</div>
                                <div class="info-value"><?php echo htmlspecialchars($aluno['serie']); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Informações de Saúde</div>
                            <div class="info-value">
                                <?php 
                                if (!empty($aluno['info_saude'])) {
                                    echo nl2br(htmlspecialchars($aluno['info_saude']));
                                } else {
                                    echo "Não informado";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Endereço e Matrícula -->
        <div class="row">
            <!-- Endereço -->
            <div class="col-md-6">
                <div class="card">
                    <div class="section-header section-blue">
                        <div class="section-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3 class="section-title">Endereço</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($endereco): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">CEP</div>
                                <div class="info-value"><?php echo htmlspecialchars($endereco['cep']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Logradouro</div>
                                <div class="info-value"><?php echo htmlspecialchars($endereco['logradouro']); ?>, <?php echo htmlspecialchars($endereco['numero']); ?></div>
                            </div>
                            
                            <?php if (!empty($endereco['complemento'])): ?>
                            <div class="info-item">
                                <div class="info-label">Complemento</div>
                                <div class="info-value"><?php echo htmlspecialchars($endereco['complemento']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <div class="info-label">Bairro</div>
                                <div class="info-value"><?php echo htmlspecialchars($endereco['bairro']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Cidade</div>
                                <div class="info-value"><?php echo htmlspecialchars($endereco['cidade']); ?></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-map-marked-alt"></i></div>
                            <p>Nenhum endereço cadastrado.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="section-header section-amber">
                        <div class="section-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="section-title">Matrícula</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($matricula): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Unidade</div>
                                <div class="info-value"><?php echo htmlspecialchars($matricula['nome_unidade']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Turma</div>
                                <div class="info-value"><?php echo htmlspecialchars($matricula['nome_turma']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Data da Matrícula</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="profile-badge badge-status-<?php echo $matricula['status']; ?>">
                                        <?php echo ucfirst(htmlspecialchars($matricula['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Consentimento</div>
                                <div class="info-value">
                                    <?php if ($matricula['consentimento'] == 1): ?>
                                        <span style="color: var(--accent)"><i class="fas fa-check-circle"></i> Sim</span>
                                    <?php else: ?>
                                        <span style="color: var(--danger)"><i class="fas fa-times-circle"></i> Não</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-user-slash"></i></div>
                            <p>Nenhuma matrícula ativa encontrada.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="section-header section-slate">
                <div class="section-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="section-title">Responsáveis</h3>
            </div>
            <div class="card-body">
                <?php if ($responsaveis && count($responsaveis) > 0): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Parentesco</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Documentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responsaveis as $resp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resp['nome']); ?></td>
                                <td><?php echo htmlspecialchars($resp['parentesco']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($resp['telefone']); ?>
                                    <?php if (!empty($resp['whatsapp'])): ?>
                                    <div><small style="color: var(--accent)"><i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($resp['whatsapp']); ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($resp['email']) ? htmlspecialchars($resp['email']) : 'Não informado'; ?></td>
                                <td>
                                    <?php 
                                    if (!empty($resp['rg'])) echo 'RG: ' . htmlspecialchars($resp['rg']); 
                                    if (!empty($resp['cpf'])) echo '<br>CPF: ' . htmlspecialchars($resp['cpf']);
                                    if (empty($resp['rg']) && empty($resp['cpf'])) echo 'Não informado';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-user-slash"></i></div>
                    <p>Nenhum responsável cadastrado.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
</body>
</html>