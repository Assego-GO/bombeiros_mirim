<?php
// Iniciar sessão
session_start();

// Verificar se o status está definido na sessão
$usuario_status = isset($_SESSION["usuario_status"]) ? $_SESSION["usuario_status"] : '';

// Se o status não for pendente, redireciona para o dashboard
if ($usuario_status !== 'pendente') {
    header("Location: dashboard.php");
    exit;
}

// Pegar informações do usuário
$usuario_nome = $_SESSION["usuario_nome"];
$usuario_matricula = $_SESSION["usuario_matricula"];
$usuario_foto = isset($_SESSION["usuario_foto"]) ? $_SESSION["usuario_foto"] : '';

// Definir a URL base do projeto
$baseUrl = '';
// Detectar URL base automaticamente
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$caminhoScript = dirname($_SERVER['SCRIPT_NAME']);
// Remover '/aluno' ou outras subpastas do caminho se existirem
$basePath = preg_replace('/(\/aluno|\/admin|\/painel)$/', '', $caminhoScript);
$baseUrl = $protocolo . $host . $basePath;

// Verificar e ajustar o caminho da foto para exibição
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
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrícula Pendente - Projeto Superação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        :root {
            --primary: #0d2d56;
            --primary-light: #1e4d92;
            --primary-dark: #071e3a;
            --secondary: #ffc233;
            --secondary-light: #ffd566;
            --secondary-dark: #d9a012;
            --accent: #34c759;
            --accent-light: #4cd377;
            --accent-dark: #26a344;
            --danger: #ff3b30;
            --danger-light: #ff6259;
            --light: #f5f7fa;
            --light-hover: #e9ecef;
            --dark: #1c2b41;
            --gray: #8e9aaf;
            --gray-light: #d1d9e6;
            --gray-dark: #64748b;
            --white: #ffffff;

            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --box-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --transition: all 0.25s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            font-size: 14px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--white);
            padding: 1rem;
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
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--white);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 1rem;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-avatar i {
            font-size: 20px;
            color: var(--primary);
        }

        .user-details h3 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
            font-weight: 600;
            color: var(--white);
        }

        .user-details p {
            font-size: 0.8rem;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.8);
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: var(--box-shadow);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.5rem;
            flex: 1;
        }

        .pending-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 10px 30px rgba(13, 45, 86, 0.1);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 800px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(209, 217, 230, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .pending-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(13, 45, 86, 0.15);
        }

        .pending-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(to right, var(--secondary), var(--secondary-light));
        }

        /* TA RODANDO A AMPULHETA*/
        .hourglass-icon {
            color: var(--secondary);
            font-size: 100px;
            margin-bottom: 2.5rem;
            display: inline-block;
            animation: rotate 3.8s infinite ;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .pending-card h1 {
            color: var(--primary);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
        }

        .status-badge {
            display: inline-block;
            background-color: rgba(255, 194, 51, 0.15);
            color: var(--secondary-dark);
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            margin: 1.5rem 0 2.5rem 0;
            border: 1px solid rgba(217, 160, 18, 0.2);
            box-shadow: 0 3px 10px rgba(255, 194, 51, 0.1);
            animation: fadeInUp 1s ease;
            font-size: 1.1rem;
        }

        .status-badge i {
            margin-right: 0.6rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pending-card p {
            color: var(--gray-dark);
            line-height: 1.8;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            max-width: 80%;
        }

        .pending-details {
            background-color: var(--light);
            border-radius: var(--border-radius-lg);
            padding: 1.8rem;
            margin: 1.8rem 0;
            text-align: left;
            border-left: 4px solid var(--secondary);
            box-shadow: 0 4px 15px rgba(13, 45, 86, 0.05);
            width: 100%;
        }

        .pending-details h3 {
            color: var(--primary);
            margin-bottom: 1.2rem;
            font-size: 1.3rem;
            font-weight: 600;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 0.8rem;
            display: flex;
            align-items: center;
        }

        .pending-details h3 i {
            margin-right: 0.8rem;
            color: var(--secondary);
        }

        .detail-item {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(142, 154, 175, 0.2);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-item strong {
            min-width: 180px;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .detail-item strong i {
            margin-right: 0.6rem;
            color: var(--secondary);
            width: 20px;
            text-align: center;
        }

        .detail-item span {
            color: var(--dark);
            font-weight: 500;
        }

        .refresh-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.9rem 1.8rem;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            margin-top: 1.5rem;
            box-shadow: 0 4px 15px rgba(13, 45, 86, 0.2);
        }

        .refresh-btn i {
            margin-right: 0.6rem;
        }

        .refresh-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(13, 45, 86, 0.25);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }

        .refresh-btn:active {
            transform: translateY(1px);
        }

        .main-footer {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--white);
            padding: 20px 0;
            margin-top: auto;
            position: relative;
            text-align: center;
            width: 100%;
            bottom: 0;
            z-index: 100;
        }

        .main-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--secondary);
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 600;
        }

        .footer-brand i {
            color: var(--secondary);
        }

        .footer-info {
            font-size: 14px;
            opacity: 0.9;
        }

        .footer-info p {
            margin-bottom: 5px;
        }

        .ftlink {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
        }

        .ftlink:after {
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

        .ftlink:hover {
            color: var(--secondary-light);
        }

        .ftlink:hover:after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        @media (max-width: 768px) {
            .pending-card {
                padding: 1.5rem;
                margin: 1rem;
            }

            .hourglass-icon {
                font-size: 70px;
                margin-bottom: 1.5rem;
            }

            .pending-card h1 {
                font-size: 2rem;
                margin-bottom: 1.5rem;
            }

            .status-badge {
                padding: 0.6rem 1.2rem;
                margin: 1rem 0 2rem 0;
                font-size: 1rem;
            }

            .detail-item {
                flex-direction: column;
                margin-bottom: 1rem;
            }

            .detail-item strong {
                margin-bottom: 0.3rem;
            }

            .pending-card p {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($usuario_foto)): ?>
                        <img src="<?php echo htmlspecialchars($usuario_foto); ?>" alt="Foto do usuário">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($usuario_nome); ?></h3>
                    <p>Matrícula: <?php echo htmlspecialchars($usuario_matricula); ?></p>
                </div>
            </div>

            <a href="api/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </header>

    <div class="container">
        <div class="pending-card">

            <i class="fas fa-hourglass-half hourglass-icon"></i>

            <h1>Matrícula Pendente</h1>

            <!-- Badge de status abaixo do título -->
            <div class="status-badge">
                <i class="fas fa-clock"></i> Aguardando Aprovação
            </div>

            <p>Olá <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>, sua matrícula no Projeto <strong>Superação</strong>  está <strong>pendente de aprovação</strong>.</p>

            <p>Nossos coordenadores estão analisando sua inscrição e você será notificado assim que sua matrícula for aprovada. Este processo normalmente leva alguns dias úteis.</p>

            <div class="pending-details">
                <h3><i class="fas fa-file-alt"></i> Detalhes da Matrícula</h3>

                <div class="detail-item">
                    <strong><i class="fas fa-id-card"></i> Número da Matrícula:</strong>

                    <span><?php echo htmlspecialchars($usuario_matricula); ?></span>
                </div>

                <div class="detail-item">
                    <strong><i class="fas fa-user"></i>Nome:  </strong>
                    <span><?php echo htmlspecialchars($usuario_nome); ?></span>

                </div>

                <?php
                // Verificar se existem dados de matrícula na sessão
                if (isset($_SESSION["usuario_unidade"]) && isset($_SESSION["usuario_turma"])):

                    // Incluir arquivo de configuração para acessar o banco de dados
                    require_once __DIR__ . '/config.php';

                    // Buscar dados da unidade
                    $unidade_id = $_SESSION["usuario_unidade"];
                    $turma_id = $_SESSION["usuario_turma"];
                    $unidade_nome = "Não disponível";
                    $turma_nome = "Não disponível";

                    try {
                        // Consultar nome da unidade
                        $stmt = $conn->prepare("SELECT nome FROM unidade WHERE id = ?");
                        $stmt->bind_param("i", $unidade_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $unidade = $result->fetch_assoc();
                            $unidade_nome = $unidade["nome"];
                        }

                        // Consultar nome da turma
                        $stmt = $conn->prepare("SELECT nome_turma FROM turma WHERE id = ?");
                        $stmt->bind_param("i", $turma_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $turma = $result->fetch_assoc();
                            $turma_nome = $turma["nome_turma"];
                        }
                    } catch (Exception $e) {
                        // Em caso de erro, mantém os valores padrão
                    }
                ?>
                    <div class="detail-item">
                        <strong><i class="fas fa-building"></i> Unidade:</strong>
                        <span><?php echo htmlspecialchars($unidade_nome); ?></span>
                    </div>

                    <div class="detail-item">
                        <strong><i class="fas fa-users"></i> Turma:</strong>
                        <span><?php echo htmlspecialchars($turma_nome); ?></span>
                    </div>
                <?php endif; ?>

                <div class="detail-item">
                    <strong><i class="fas fa-info-circle"></i> Status:</strong>
                    <span style="color: #856404; font-weight: 600;">Pendente de Aprovação</span>
                </div>
            </div>

            <p>Para mais informações, entre em contato com a coordenação do projeto ou aguarde o contato da nossa equipe.</p>

            <button class="refresh-btn" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i> Verificar Status Novamente
            </button>
        </div>
    </div>

    

    <script>
        // Verifica o status a cada 5 minutos
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutos
    </script>
</body>

</html>