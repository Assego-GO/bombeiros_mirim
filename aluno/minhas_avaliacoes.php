<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require "../env_config.php";

// Verificar autenticação do aluno
$aluno_id = $_SESSION['aluno_id'];

// Conexão com o banco
$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter informações do aluno
    $query = "SELECT a.nome, a.serie, a.numero_matricula, a.foto, t.nome_turma, t.id as turma_id
              FROM alunos a
              JOIN matriculas m ON a.id = m.aluno_id
              JOIN turma t ON m.turma = t.id
              WHERE a.id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute([$aluno_id]);
    
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar se o aluno existe
    if (!$aluno) {
        // Aluno não encontrado no banco de dados
        session_destroy();
        header("Location: api/login_aluno.php?erro=aluno_nao_encontrado");
        exit;
    }
    
    $turma_id = $aluno['turma_id'];
    
    // Processar o caminho da foto
    $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $baseUrl = $serverUrl . '/superacao';
    
    if (!empty($aluno['foto'])) {
        $filename = basename($aluno['foto']);
        $fotoPath = $baseUrl . '/uploads/fotos/' . $filename;
    } else {
        $fotoPath = $baseUrl . '/uploads/fotos/default.png';
    }
    
    // Buscar todas as avaliações do aluno
    $query_avaliacoes = "SELECT a.*, p.nome as nome_professor
                         FROM avaliacoes a
                         JOIN professor p ON a.professor_id = p.id
                         WHERE a.aluno_id = ?
                         ORDER BY a.data_avaliacao DESC";
    $stmt_avaliacoes = $db->prepare($query_avaliacoes);
    $stmt_avaliacoes->execute([$aluno_id]);
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
    <title>Minhas Avaliações - <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0a2647;
            --primary-light: #144272;
            --primary-dark: #071c35;
            --secondary: #ffc233;
            --secondary-light: #ffd566;
            --secondary-dark: #e9b424;
            --accent: #34c759;
            --accent-light: #4cd377;
            --accent-dark: #26a344;
            --danger: #f64e60;
            --danger-light: #ff6b7d;
            --light: #f5f7fd;
            --light-hover: #ecf0f9;
            --dark: #1a2b4b;
            --gray: #7c8db5;
            --gray-light: #d6dff0;
            --gray-dark: #4b5e88;
            --white: #ffffff;
            --box-shadow: 0 5px 15px rgba(10, 38, 71, 0.07);
            --box-shadow-hover: 0 8px 25px rgba(10, 38, 71, 0.12);
            --box-shadow-card: 0 10px 30px rgba(10, 38, 71, 0.05);
            --border-radius: 10px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--primary);
            color: var(--dark);
            line-height: 1.6;
            background-image: radial-gradient(circle at 10% 20%, rgba(20, 66, 114, 0.4) 0%, rgba(20, 66, 114, 0.4) 50.3%, transparent 50.3%, transparent 100%),
              radial-gradient(circle at 85% 85%, rgba(20, 66, 114, 0.4) 0%, rgba(20, 66, 114, 0.4) 50.9%, transparent 50.9%, transparent 100%);
            background-attachment: fixed;
        }

        .header {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .btn-voltar {
            background-color: var(--secondary);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-voltar i {
            margin-right: 6px;
        }

        .btn-voltar:hover {
            background-color: var(--secondary-light);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-hover);
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }

        /* Perfil do Aluno */
        .aluno-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow-card);
            position: relative;
            overflow: hidden;
        }

        .aluno-foto {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 30px;
            border: 4px solid var(--secondary);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            position: relative;
        }

        .aluno-foto img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .aluno-info {
            flex: 1;
        }

        .aluno-nome {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .aluno-dados {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .aluno-dado {
            margin-right: 25px;
            margin-bottom: 5px;
            color: var(--gray-dark);
        }

        .aluno-dado strong {
            font-weight: 600;
            color: var(--dark);
        }

        .aluno-info h4 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .aluno-info p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* Card de Avaliação */
        .avaliacao-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow-card);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .avaliacao-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }

        .avaliacao-header {
            background-color: var(--primary);
            color: var(--white);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .avaliacao-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .avaliacao-professor {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .imc-badge {
            background-color: var(--white);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        .imc-normal {
            color: var(--accent-dark);
            background-color: rgba(52, 199, 89, 0.2);
        }

        .imc-abaixo {
            color: var(--warning-dark);
            background-color: rgba(255, 194, 51, 0.2);
        }

        .imc-sobrepeso {
            color: var(--warning-dark);
            background-color: rgba(255, 194, 51, 0.2);
        }

        .imc-obesidade {
            color: var(--danger);
            background-color: rgba(246, 78, 96, 0.15);
        }

        .avaliacao-body {
            padding: 20px;
        }

        /* Seções de Avaliação */
        .avaliacao-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--gray-light);
        }

        .avaliacao-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .avaliacao-section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .avaliacao-section-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.1rem;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        .comportamento .avaliacao-section-icon {
            background-color: var(--accent);
        }

        .observacoes .avaliacao-section-icon {
            background-color: var(--gray-dark);
        }

        .avaliacao-section h4 {
            color: var(--primary);
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .avaliacao-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }

        .avaliacao-item {
            padding: 15px;
            background-color: var(--light);
            border-radius: var(--border-radius);
        }

        .avaliacao-label {
            font-weight: 600;
            color: var(--primary);
            display: block;
            margin-bottom: 8px;
        }

        .avaliacao-valor {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            display: block;
            margin-bottom: 8px;
        }

        /* Barras de Progresso */
        .avaliacao-progress {
            height: 8px;
            background-color: var(--gray-light);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .fisico .avaliacao-progress-bar {
            background-color: var(--primary-light);
        }

        .comportamento .avaliacao-progress-bar {
            background-color: var(--accent);
        }

        .avaliacao-progress-bar {
            height: 100%;
            background-color: var(--primary-light);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .avaliacao-texto {
            background-color: var(--light);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 15px;
            color: var(--gray-dark);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Estado Vazio */
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(5px);
            margin: 40px 0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--secondary);
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: var(--white);
            font-weight: 600;
        }

        .empty-state p {
            color: var(--light);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Footer */
        .footer {
            background-color: var(--primary-dark);
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary);
        }

        .footer a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .footer a:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--secondary);
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s ease;
        }

        .footer a:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .aluno-profile {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .aluno-foto {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .aluno-dados {
                justify-content: center;
            }
            
            .avaliacao-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .imc-badge {
                margin-top: 10px;
            }
            
            .avaliacao-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .aluno-nome {
                font-size: 1.5rem;
            }
            
            .avaliacao-card {
                margin-bottom: 20px;
            }
            
            .avaliacao-header {
                padding: 12px 15px;
            }
            
            .avaliacao-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div>
                <i class="fas fa-futbol"></i> Superação - Ninho de Águias
            </div>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-home"></i> Início
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="aluno-profile">
            <div class="aluno-foto">
                <img src="<?php echo htmlspecialchars($fotoPath); ?>" alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" onerror="this.onerror=null; this.src='<?php echo $baseUrl; ?>/uploads/fotos/default.png';">
            </div>
            <div class="aluno-info">
                <h1 class="aluno-nome"><?php echo htmlspecialchars($aluno['nome']); ?></h1>
                <div class="aluno-dados">
                    <div class="aluno-dado"><strong>Matrícula:</strong> <?php echo htmlspecialchars($aluno['numero_matricula']); ?></div>
                    <div class="aluno-dado"><strong>Série:</strong> <?php echo htmlspecialchars($aluno['serie']); ?></div>
                    <div class="aluno-dado"><strong>Turma:</strong> <?php echo htmlspecialchars($aluno['nome_turma']); ?></div>
                </div>
                <div>
                    <h4>Minhas Avaliações</h4>
                    <p>Aqui você pode acompanhar suas avaliações feitas pelos professores.</p>
                </div>
            </div>
        </div>
        
        <?php if (empty($avaliacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Nenhuma avaliação encontrada</h3>
                <p>Você ainda não possui avaliações registradas pelos professores.</p>
            </div>
        <?php else: ?>
            <?php foreach ($avaliacoes as $avaliacao): ?>
                <div class="avaliacao-card">
                    <div class="avaliacao-header">
                        <div>
                            <h3>Avaliação de <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></h3>
                            <div class="avaliacao-professor">Professor: <?php echo htmlspecialchars($avaliacao['nome_professor']); ?></div>
                        </div>
                        <?php if (!empty($avaliacao['imc_status'])): ?>
                            <div class="imc-badge <?php
                                if ($avaliacao['imc_status'] == 'Abaixo do peso') echo 'imc-abaixo';
                                elseif ($avaliacao['imc_status'] == 'Peso normal') echo 'imc-normal';
                                elseif ($avaliacao['imc_status'] == 'Sobrepeso') echo 'imc-sobrepeso';
                                elseif ($avaliacao['imc_status'] == 'Obesidade') echo 'imc-obesidade';
                            ?>">
                                IMC: <?php echo htmlspecialchars($avaliacao['imc']); ?> - <?php echo htmlspecialchars($avaliacao['imc_status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="avaliacao-body">
                        <!-- Medidas Físicas -->
                        <?php if (!empty($avaliacao['altura']) || !empty($avaliacao['peso'])): ?>
                        <div class="avaliacao-section medidas">
                            <div class="avaliacao-section-header">
                                <div class="avaliacao-section-icon">
                                    <i class="fas fa-ruler"></i>
                                </div>
                                <h4>Medidas Físicas</h4>
                            </div>
                            <div class="avaliacao-grid">
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
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Desempenho Físico -->
                        <div class="avaliacao-section fisico">
                            <div class="avaliacao-section-header">
                                <div class="avaliacao-section-icon">
                                    <i class="fas fa-running"></i>
                                </div>
                                <h4>Desempenho Físico</h4>
                            </div>
                            <div class="avaliacao-grid">
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Velocidade</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['velocidade']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['velocidade'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Resistência</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['resistencia']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['resistencia'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Coordenação</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['coordenacao']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['coordenacao'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Agilidade</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['agilidade']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['agilidade'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Força</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['forca']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['forca'] * 10); ?>%"></div>
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
                        <div class="avaliacao-section comportamento">
                            <div class="avaliacao-section-header">
                                <div class="avaliacao-section-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4>Comportamento</h4>
                            </div>
                            <div class="avaliacao-grid">
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Participação</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['participacao']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['participacao'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Trabalho em Equipe</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['trabalho_equipe']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['trabalho_equipe'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Disciplina</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['disciplina']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['disciplina'] * 10); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="avaliacao-item">
                                    <span class="avaliacao-label">Respeito às Regras</span>
                                    <span class="avaliacao-valor"><?php echo $avaliacao['respeito_regras']; ?>/10</span>
                                    <div class="avaliacao-progress">
                                        <div class="avaliacao-progress-bar" style="width: <?php echo ($avaliacao['respeito_regras'] * 10); ?>%"></div>
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
                        <div class="avaliacao-section observacoes">
                            <div class="avaliacao-section-header">
                                <div class="avaliacao-section-icon">
                                    <i class="fas fa-comment-alt"></i>
                                </div>
                                <h4>Observações do Professor</h4>
                            </div>
                            <div class="avaliacao-texto">
                                <?php echo nl2br(htmlspecialchars($avaliacao['observacoes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Superação - Todos os direitos reservados</p>
            <p>Desenvolvido por <a href="https://www.instagram.com/assego/">@Assego</a></p>
        </div>
    </footer>
</body>
</html>