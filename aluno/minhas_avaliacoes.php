<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se o arquivo de configuração existe
if (file_exists("../env_config.php")) {
    require "../env_config.php";
} else {
    die("Arquivo de configuração não encontrado.");
}

// Verificar autenticação do aluno
if (!isset($_SESSION['aluno_id'])) {
    session_destroy();
    header("Location: ../index.php?erro=sessao_invalida");
    exit;
}

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
        session_destroy();
        header("Location: ../index.php?erro=aluno_nao_encontrado");
        exit;
    }
    
    $turma_id = $aluno['turma_id'];
    
    // PROCESSAMENTO CORRETO DA FOTO - VERSÃO CORRIGIDA
    if (!empty($aluno['foto'])) {
        $foto_banco = $aluno['foto'];
        
        // Se já começa com ../uploads/fotos/ (como mostrado no teste)
        if (strpos($foto_banco, '../uploads/fotos/') === 0) {
            $fotoPath = $foto_banco; // Usar como está
        }
        // Se começa com uploads/fotos/ (caminho relativo à raiz)
        elseif (strpos($foto_banco, 'uploads/fotos/') === 0) {
            $fotoPath = '../' . $foto_banco;
        }
        // Se é apenas o nome do arquivo
        elseif (strpos($foto_banco, '/') === false) {
            $fotoPath = '../uploads/fotos/' . $foto_banco;
        }
        // Se já é uma URL completa
        elseif (strpos($foto_banco, 'http') === 0) {
            $fotoPath = $foto_banco;
        }
        // Outros casos - tentar construir o caminho
        else {
            $fotoPath = '../uploads/fotos/' . basename($foto_banco);
        }
        
        // Verificar se o arquivo realmente existe
        if (!file_exists($fotoPath)) {
            // Usar uma das fotos existentes como padrão (baseado no teste)
            $fotoPath = '../uploads/fotos/6842fa044feda_20250606142404.png';
            
            // Se essa também não existe, usar avatar SVG
            if (!file_exists($fotoPath)) {
                $fotoPath = 'data:image/svg+xml;base64,' . base64_encode('
                    <svg width="120" height="120" xmlns="http://www.w3.org/2000/svg">
                        <rect width="120" height="120" fill="#C41E3A"/>
                        <circle cx="60" cy="45" r="15" fill="white"/>
                        <path d="M30 90 Q60 70 90 90" stroke="white" stroke-width="3" fill="none"/>
                        <text x="60" y="110" text-anchor="middle" fill="white" font-size="10">Bombeiro</text>
                    </svg>
                ');
            }
        }
    } else {
        // Se não tem foto no banco, usar a mesma estratégia
        $fotoPath = '../uploads/fotos/6842fa044feda_20250606142404.png';
        
        if (!file_exists($fotoPath)) {
            // Avatar SVG padrão
            $fotoPath = 'data:image/svg+xml;base64,' . base64_encode('
                <svg width="120" height="120" xmlns="http://www.w3.org/2000/svg">
                    <rect width="120" height="120" fill="#C41E3A"/>
                    <circle cx="60" cy="45" r="15" fill="white"/>
                    <path d="M30 90 Q60 70 90 90" stroke="white" stroke-width="3" fill="none"/>
                    <text x="60" y="110" text-anchor="middle" fill="white" font-size="10">Bombeiro</text>
                </svg>
            ');
        }
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
            /* Cores principais do Corpo de Bombeiros de Goiás */
            --bombeiro-red: #C41E3A;
            --bombeiro-red-dark: #A01728;
            --bombeiro-red-light: #D63851;
            --bombeiro-orange: #FF6B35;
            --bombeiro-orange-light: #FF8A5B;
            --bombeiro-orange-dark: #E55A2B;
            --bombeiro-yellow: #FFB627;
            --bombeiro-yellow-light: #FFC849;
            --bombeiro-yellow-dark: #E5A322;
            
            /* Cores de apoio */
            --primary: var(--bombeiro-red);
            --primary-light: var(--bombeiro-red-light);
            --primary-dark: var(--bombeiro-red-dark);
            --secondary: var(--bombeiro-yellow);
            --secondary-light: var(--bombeiro-yellow-light);
            --secondary-dark: var(--bombeiro-yellow-dark);
            --accent: var(--bombeiro-orange);
            --accent-light: var(--bombeiro-orange-light);
            --accent-dark: var(--bombeiro-orange-dark);
            
            /* Cores de sistema */
            --success: #28a745;
            --success-light: #4cd377;
            --success-dark: #1e7e34;
            --warning: var(--bombeiro-yellow);
            --warning-light: var(--bombeiro-yellow-light);
            --warning-dark: var(--bombeiro-yellow-dark);
            --danger: #dc3545;
            --danger-light: #ff6b7d;
            --danger-dark: #c82333;
            
            /* Cores neutras */
            --light: #f8f9fa;
            --light-hover: #e9ecef;
            --dark: #2c3e50;
            --gray: #6c757d;
            --gray-light: #dee2e6;
            --gray-dark: #495057;
            --white: #ffffff;
            
            /* Efeitos */
            --box-shadow: 0 5px 15px rgba(196, 30, 58, 0.1);
            --box-shadow-hover: 0 8px 25px rgba(196, 30, 58, 0.15);
            --box-shadow-card: 0 10px 30px rgba(196, 30, 58, 0.08);
            --border-radius: 10px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Gradientes temáticos */
            --gradient-bombeiro: linear-gradient(135deg, var(--bombeiro-red) 0%, var(--bombeiro-orange) 50%, var(--bombeiro-yellow) 100%);
            --gradient-bombeiro-reverse: linear-gradient(135deg, var(--bombeiro-yellow) 0%, var(--bombeiro-orange) 50%, var(--bombeiro-red) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #fff5f0 50%, #ffe8e0 100%);
            color: var(--dark);
            line-height: 1.6;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(196, 30, 58, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 107, 53, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 182, 39, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .header {
            background: var(--gradient-bombeiro);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--bombeiro-yellow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .header-content > div:first-child {
            font-size: 1.1rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .btn-voltar {
            background: var(--white);
            color: var(--bombeiro-red);
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-voltar i {
            margin-right: 6px;
        }

        .btn-voltar:hover {
            background: var(--bombeiro-yellow);
            color: var(--bombeiro-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow-card);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--bombeiro-orange);
        }

        .aluno-profile::before {
            content: '';
            position: absolute;
            top: 0;
            right: -50px;
            width: 200px;
            height: 100%;
            background: var(--gradient-bombeiro);
            opacity: 0.05;
            transform: skewX(-15deg);
        }

        .aluno-foto {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 30px;
            border: 4px solid var(--bombeiro-orange);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
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
            z-index: 1;
        }

        .aluno-nome {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--bombeiro-red);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
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
            color: var(--bombeiro-red);
        }

        .aluno-info h4 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--bombeiro-red);
            margin-bottom: 5px;
        }

        .aluno-info p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* Card de Avaliação */
        .avaliacao-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow-card);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--bombeiro-orange);
        }

        .avaliacao-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-hover);
        }

        .avaliacao-header {
            background: var(--gradient-bombeiro);
            color: var(--white);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .avaliacao-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--bombeiro-yellow);
        }

        .avaliacao-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .avaliacao-professor {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .imc-badge {
            background: var(--white);
            color: var(--bombeiro-red);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .imc-normal {
            color: var(--success-dark);
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
        }

        .imc-abaixo {
            color: var(--bombeiro-yellow-dark);
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
        }

        .imc-sobrepeso {
            color: var(--bombeiro-orange-dark);
            background: linear-gradient(135deg, #fff0e6, #ffe8d6);
        }

        .imc-obesidade {
            color: var(--danger-dark);
            background: linear-gradient(135deg, #fdf2f2, #f8d7da);
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
            background: var(--gradient-bombeiro);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.1rem;
            box-shadow: 0 3px 8px rgba(196, 30, 58, 0.2);
        }

        .comportamento .avaliacao-section-icon {
            background: linear-gradient(135deg, var(--bombeiro-orange), var(--bombeiro-yellow));
        }

        .observacoes .avaliacao-section-icon {
            background: linear-gradient(135deg, var(--gray-dark), var(--gray));
        }

        .medidas .avaliacao-section-icon {
            background: linear-gradient(135deg, var(--bombeiro-yellow), var(--bombeiro-orange));
        }

        .avaliacao-section h4 {
            color: var(--bombeiro-red);
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
            background: linear-gradient(135deg, #fafafa, #f5f5f5);
            border-radius: var(--border-radius);
            border-left: 3px solid var(--bombeiro-orange);
            transition: var(--transition);
        }

        .avaliacao-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.1);
        }

        .avaliacao-label {
            font-weight: 600;
            color: var(--bombeiro-red);
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
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
            background: var(--gray-light);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .fisico .avaliacao-progress-bar {
            background: var(--gradient-bombeiro);
        }

        .comportamento .avaliacao-progress-bar {
            background: linear-gradient(90deg, var(--bombeiro-orange), var(--bombeiro-yellow));
        }

        .medidas .avaliacao-progress-bar {
            background: linear-gradient(90deg, var(--bombeiro-yellow), var(--bombeiro-orange));
        }

        .avaliacao-progress-bar {
            height: 100%;
            background: var(--gradient-bombeiro);
            border-radius: 4px;
            transition: width 0.8s ease;
            position: relative;
        }

        .avaliacao-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .avaliacao-texto {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 15px;
            color: var(--gray-dark);
            font-size: 0.95rem;
            line-height: 1.6;
            border-left: 3px solid var(--bombeiro-orange);
        }

        /* Estado Vazio */
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: var(--bombeiro-red);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 245, 240, 0.9));
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(5px);
            margin: 40px 0;
            border: 2px dashed var(--bombeiro-orange);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--bombeiro-orange);
            opacity: 0.7;
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: var(--bombeiro-red);
            font-weight: 600;
        }

        .empty-state p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Footer */
        .footer {
            background: var(--gradient-bombeiro);
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
            background: var(--bombeiro-yellow);
        }

        .footer a {
            color: var(--bombeiro-yellow);
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
            background: var(--bombeiro-yellow);
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

        /* Animações adicionais */
        .avaliacao-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .avaliacao-card:nth-child(1) { animation-delay: 0.1s; }
        .avaliacao-card:nth-child(2) { animation-delay: 0.2s; }
        .avaliacao-card:nth-child(3) { animation-delay: 0.3s; }
        .avaliacao-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .aluno-profile {
            animation: fadeInScale 0.8s ease forwards;
        }

        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div>
                <i class="fas fa-fire-extinguisher"></i> Bombeiro Mirim Goiás - Minhas Avaliações
            </div>
            <a href="dashboard.php" class="btn-voltar">
                <i class="fas fa-home"></i> Início
            </a>
        </div>
    </header>
    
    <div class="container">
        <div class="aluno-profile">
            <div class="aluno-foto">
                <img src="<?php echo htmlspecialchars($fotoPath); ?>" 
                     alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" 
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjQzQxRTNBIi8+CiAgICA8Y2lyY2xlIGN4PSI2MCIgY3k9IjQ1IiByPSIxNSIgZmlsbD0id2hpdGUiLz4KICAgIDxwYXRoIGQ9Ik0zMCA5MCBRNTIAKA3NIDkwIDA5MCIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIzIiBmaWxsPSJub25lIi8+CiAgICA8dGV4dCB4PSI2MCIgeT0iMTEwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxMCI+Qm9tYmVpcm88L3RleHQ+Cjwvc3ZnPg==';">
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
                    <p>Aqui você pode acompanhar suas avaliações feitas pelos professores do programa Bombeiro Mirim.</p>
                </div>
            </div>
        </div>
        
        <?php if (empty($avaliacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Nenhuma avaliação encontrada</h3>
                <p>Você ainda não possui avaliações registradas pelos professores. Continue participando das atividades!</p>
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
            <p>&copy; 2025 Programa Educacional Bombeiro Mirim - Estado de Goiás</p>
            <p>Desenvolvido por <a href="https://www.instagram.com/assego/">@Assego</a></p>
        </div>
    </footer>
</body>
</html>