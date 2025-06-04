<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// Verificar se os parâmetros necessários foram fornecidos
if (!isset($_GET['aluno_id']) || !isset($_GET['turma_id'])) {
    header('Location: dashboard.php');
    exit;
}

$aluno_id = $_GET['aluno_id'];
$turma_id = $_GET['turma_id'];
$professor_id = $_SESSION['usuario_id'];

// Conexão com o banco
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se o professor é responsável pela turma
    $query_auth = "SELECT * FROM turma WHERE id = ? AND id_professor = ?";
    $stmt_auth = $db->prepare($query_auth);
    $stmt_auth->execute([$turma_id, $professor_id]);
    
    if ($stmt_auth->rowCount() == 0) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Obter informações do aluno
    $query = "SELECT a.nome, a.serie, a.numero_matricula, t.nome_turma 
              FROM alunos a
              JOIN matriculas m ON a.id = m.aluno_id
              JOIN turma t ON m.turma = t.id
              WHERE a.id = ? AND m.turma = ?";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $aluno_id);
    $stmt->bindParam(2, $turma_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header('Location: alunos_turma.php?turma_id=' . $turma_id);
        exit;
    }

    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar todas as avaliações do aluno nesta turma
    $query_avaliacoes = "SELECT * FROM avaliacoes 
                         WHERE aluno_id = ? AND turma_id = ? 
                         ORDER BY data_avaliacao DESC";
    $stmt_avaliacoes = $db->prepare($query_avaliacoes);
    $stmt_avaliacoes->execute([$aluno_id, $turma_id]);
    $avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações do Aluno: <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .avaliacao-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid #0d2d56;
        }
        
        .avaliacao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .avaliacao-header {
            background-color: #0d2d56;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .avaliacao-body {
            padding: 20px;
        }
        
        .avaliacao-section {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .avaliacao-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .avaliacao-section h4 {
            color: #0d2d56;
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .avaliacao-data {
            display: flex;
            flex-wrap: wrap;
        }
        
        .avaliacao-item {
            width: 33.333%;
            padding: 8px 15px;
        }
        
        .avaliacao-label {
            font-weight: 600;
            color: #64748b;
            display: block;
            margin-bottom: 5px;
        }
        
        .avaliacao-valor {
            font-size: 1.1rem;
        }
        
        .avaliacao-texto {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .progress {
            height: 8px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        
        .btn-nova-avaliacao {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #34c759;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .btn-nova-avaliacao:hover {
            transform: scale(1.1);
            background-color: #26a344;
        }
        
        .btn-nova-avaliacao i {
            font-size: 24px;
        }
        
        @media (max-width: 768px) {
            .avaliacao-item {
                width: 50%;
            }
        }
        
        @media (max-width: 576px) {
            .avaliacao-item {
                width: 100%;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d9e6;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #0d2d56;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #64748b;
            max-width: 500px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="user-info">
                <a href="./dashboard.php" class="btn btn-sm text-white">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
            </div>
            
            <a href="alunos_turma.php?turma_id=<?php echo $turma_id; ?>" class="btn btn-sm text-white">
                <i class="fas fa-users"></i> Voltar para Lista de Alunos
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="welcome-card">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Avaliações de <?php echo htmlspecialchars($aluno['nome']); ?></h1>
                <div>
                    <a href="avaliar_aluno.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo $turma_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Nova Avaliação
                    </a>
                    <?php if (!empty($avaliacoes)): ?>
                        <a href="gerar_pdf.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo $turma_id; ?>" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-file-pdf"></i> Gerar Relatório PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <p class="mt-3">
                <strong>Matrícula:</strong> <?php echo htmlspecialchars($aluno['numero_matricula']); ?> | 
                <strong>Série:</strong> <?php echo htmlspecialchars($aluno['serie']); ?> | 
                <strong>Turma:</strong> <?php echo htmlspecialchars($aluno['nome_turma']); ?>
            </p>
        </div>
        
        <?php if (empty($avaliacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Nenhuma avaliação encontrada</h3>
                <p>Este aluno ainda não possui avaliações registradas nesta turma.</p>
                <a href="avaliar_aluno.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo $turma_id; ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Realizar Primeira Avaliação
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($avaliacoes as $avaliacao): ?>
                <div class="avaliacao-card">
                    <div class="avaliacao-header">
                        <h3>Avaliação de <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></h3>
                        <div>
                            <a href="avaliar_aluno.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo $turma_id; ?>&avaliacao_id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="gerar_pdf.php?avaliacao_id=<?php echo $avaliacao['id']; ?>" target="_blank" class="btn btn-sm btn-light">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="avaliacao-body">
                        <!-- Medidas Físicas -->
                        <div class="avaliacao-section">
                            <h4><i class="fas fa-ruler"></i> Medidas Físicas</h4>
                            <div class="avaliacao-data">
                                <?php if (!empty($avaliacao['altura'])): ?>
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Altura</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['altura']; ?> cm</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($avaliacao['peso'])): ?>
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Peso</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['peso']; ?> kg</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($avaliacao['imc'])): ?>
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">IMC</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['imc']; ?> (<?php echo $avaliacao['imc_status']; ?>)</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Desempenho Físico -->
                        <div class="avaliacao-section">
                            <h4><i class="fas fa-running"></i> Desempenho Físico</h4>
                            <div class="avaliacao-data">
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Velocidade</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['velocidade']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($avaliacao['velocidade'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['velocidade']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Resistência</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['resistencia']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($avaliacao['resistencia'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['resistencia']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Coordenação</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['coordenacao']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($avaliacao['coordenacao'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['coordenacao']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Agilidade</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['agilidade']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($avaliacao['agilidade'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['agilidade']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Força</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['forca']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($avaliacao['forca'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['forca']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($avaliacao['desempenho_detalhes'])): ?>
                            <div class="avaliacao-texto">
                                <?php echo nl2br(htmlspecialchars($avaliacao['desempenho_detalhes'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comportamento -->
                        <div class="avaliacao-section">
                            <h4><i class="fas fa-users"></i> Comportamento</h4>
                            <div class="avaliacao-data">
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Participação</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['participacao']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($avaliacao['participacao'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['participacao']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Trabalho em Equipe</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['trabalho_equipe']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($avaliacao['trabalho_equipe'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['trabalho_equipe']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Disciplina</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['disciplina']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($avaliacao['disciplina'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['disciplina']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Respeito às Regras</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['respeito_regras']; ?>/10</span>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($avaliacao['respeito_regras'] * 10); ?>%" aria-valuenow="<?php echo $avaliacao['respeito_regras']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($avaliacao['comportamento_notas'])): ?>
                            <div class="avaliacao-texto">
                                <?php echo nl2br(htmlspecialchars($avaliacao['comportamento_notas'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Observações -->
                        <?php if (!empty($avaliacao['observacoes'])): ?>
                        <div class="avaliacao-section">
                            <h4><i class="fas fa-comment-alt"></i> Observações</h4>
                            <div class="avaliacao-texto">
                                <?php echo nl2br(htmlspecialchars($avaliacao['observacoes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <a href="avaliar_aluno.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo $turma_id; ?>" class="btn-nova-avaliacao">
            <i class="fas fa-plus"></i>
        </a>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <i class="fas fa-futbol"></i> Escolinha de Futebol
                </div>
                <div class="footer-info">
                    <p>&copy; 2025 Escolinha de Futebol - Todos os direitos reservados</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>