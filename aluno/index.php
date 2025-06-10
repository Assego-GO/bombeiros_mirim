<?php
session_start();


function carregarEnv($caminho = '.env') {
    if (!file_exists($caminho)) {
        return false;
    }
    
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) {
            continue;
        }
        
        list($nome, $valor) = explode('=', $linha, 2);
        $nome = trim($nome);
        $valor = trim($valor);
        
        if (!array_key_exists($nome, $_SERVER) && !array_key_exists($nome, $_ENV)) {
            putenv(sprintf('%s=%s', $nome, $valor));
            $_ENV[$nome] = $valor;
            $_SERVER[$nome] = $valor;
        }
    }
    return true;
}


carregarEnv();


define('RECAPTCHA_SECRET_KEY', getenv('KEY_V3_SECRET'));
define('RECAPTCHA_SITE_KEY', getenv('KEY_V3_SITE'));


function validarRecaptcha($token, $action = '', $score_minimo = 0.5) {
    if (empty($token)) {
        return ['success' => false, 'message' => 'Token não fornecido'];
    }
    
    $secret_key = RECAPTCHA_SECRET_KEY;
    $url = "https://www.google.com/recaptcha/api/siteverify";
    
    $data = [
        'secret' => $secret_key,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'message' => 'Erro ao validar token'];
    }
    
    $response_data = json_decode($response, true);
    
    if (!$response_data['success']) {
        $error_codes = $response_data['error-codes'] ?? [];
        return ['success' => false, 'message' => 'Token inválido: ' . implode(', ', $error_codes)];
    }
    
    if (!empty($action) && isset($response_data['action']) && $response_data['action'] !== $action) {
        return ['success' => false, 'message' => 'Action não confere'];
    }
    
    if (isset($response_data['score']) && $response_data['score'] < $score_minimo) {
        return ['success' => false, 'message' => 'Score muito baixo: ' . $response_data['score']];
    }
    
    return [
        'success' => true, 
        'score' => $response_data['score'] ?? 1.0,
        'action' => $response_data['action'] ?? '',
        'message' => 'Token válido'
    ];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    $system_token = $_POST['system_token'] ?? '';
    if (empty($system_token)) {
        echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
        exit;
    }
    
    $api_configs = [
        'verificar_cpf' => ['action' => 'verificar_cpf', 'score' => 0.5],
        'cadastrar_senha' => ['action' => 'cadastrar_senha', 'score' => 0.6],
        'login' => ['action' => 'login', 'score' => 0.6]
    ];
    
    $api_name = $_GET['api'];
    
    if (!isset($api_configs[$api_name])) {
        echo json_encode(['status' => 'error', 'message' => 'API não encontrada']);
        exit;
    }
    
    $config = $api_configs[$api_name];
    
    $resultado = validarRecaptcha($system_token, $config['action'], $config['score']);
    
    if (!$resultado['success']) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Verificação de segurança falhou: ' . $resultado['message']
        ]);
        exit;
    }
    
    $_SESSION['system_validated'] = true;
    $_SESSION['system_score'] = $resultado['score'];
    
    unset($_POST['system_token']);
    
    $api_file = "api/{$api_name}.php";
    if (file_exists($api_file)) {
        ob_start();
        include $api_file;
        $response = ob_get_clean();
        echo $response;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'API não encontrada']);
    }
    exit;
}

// Verifica se o usuário já está logado
if (isset($_SESSION['usuario_id'])) {
    // Redireciona com base no tipo de usuário
    switch($_SESSION['usuario_tipo']) {
        case 'admin':
            header('Location: ../matricula/painel.php');
            exit;
        case 'professor':
            header('Location: ../professor/dashboard.php');
            exit;
        case 'aluno':
            header('Location: ./aluno/dashboard.php');
            exit;
        default:
            // Caso o tipo de usuário não seja reconhecido
            session_destroy();
            header('Location: index.php');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Aluno - Bombeiro Mirim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        :root {
            /* Cores dos Bombeiros */
            --primary: #E30613;
            --primary-light: #FF2D3A;
            --primary-dark: #B8050F;
            --secondary: #ffc233;
            --secondary-light: #ffd566;
            --secondary-dark: #e9b424;
            --gradient-bg: linear-gradient(135deg, #E30613 0%, #FF2D3A 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: rgba(255, 255, 255, 0.9);
            --shadow-color: rgba(227, 6, 19, 0.2);
            --text-primary: #1a1a1a;
            --text-secondary: #666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #B8050F;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--secondary), var(--secondary-light));
            opacity: 0.3;
            animation: float 15s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: -80px;
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            bottom: 30%;
            right: 10%;
            animation-delay: 6s;
        }
        
        .shape-4 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 9s;
        }
        
        .fire-shape {
            position: absolute;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, rgba(255, 194, 51, 0.4), rgba(255, 122, 0, 0.4));
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            top: 15%;
            right: 20%;
            animation: sparkle 3s infinite;
        }
        
        @keyframes sparkle {
            0%, 100% { opacity: 0.4; transform: scale(1) rotate(0deg); }
            50% { opacity: 0.8; transform: scale(1.2) rotate(180deg); }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }
        
        .container {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 25px 50px var(--shadow-color);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 10;
            transform: translateY(0);
            animation: cardAppear 0.8s ease-out;
            border: 2px solid rgba(255, 194, 51, 0.3);
        }
        
        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .logo-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 194, 51, 0.5) 0%, rgba(255, 194, 51, 0) 70%);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.5;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
                opacity: 0.3;
            }
        }
        
        .logo img {
            width: 100px;
            height: auto;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 5px 15px rgba(227, 6, 19, 0.4));
            transition: all 0.4s;
        }
        
        .logo img:hover {
            transform: scale(1.08) rotate(3deg);
        }
        
        h1 {
            font-size: 2.6rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
            text-align: center;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .bombeiro-icon {
            font-size: 2rem;
            color: var(--secondary);
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .app-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 25px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.15);
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 15px 10px;
            background-color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            color: var(--text-primary);
            border: 2px solid transparent;
        }
        
        .tab.active {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            font-weight: 600;
            border-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 6, 19, 0.3);
        }
        
        .tab:hover:not(.active) {
            background-color: rgba(255, 194, 51, 0.2);
            color: var(--primary);
            transform: translateY(-1px);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 2px solid rgba(255, 194, 51, 0.3);
            border-radius: 12px;
            padding: 16px 20px 16px 55px;
            font-size: 15px;
            transition: all 0.3s;
            color: var(--text-primary);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 8px rgba(227, 6, 19, 0.08);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(227, 6, 19, 0.2);
            outline: none;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .form-control::placeholder {
            color: #888;
        }
        
        .icon-wrapper {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-group i {
            color: var(--primary);
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .form-control:focus + .icon-wrapper i {
            color: var(--secondary);
            transform: scale(1.1);
        }
        
        .btn {
            width: 100%;
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(227, 6, 19, 0.3);
            letter-spacing: 0.5px;
            border: 2px solid transparent;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(227, 6, 19, 0.4);
            border-color: var(--secondary);
        }
        
        .btn:hover:before {
            transform: translateX(100%);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .message {
            margin-top: 20px;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error {
            background-color: rgba(246, 78, 96, 0.2);
            color: #e53e3e;
            border: 1px solid rgba(229, 62, 62, 0.3);
        }
        
        .success {
            background-color: rgba(52, 199, 89, 0.2);
            color: #38a169;
            border: 1px solid rgba(56, 161, 105, 0.3);
        }
        
        .hidden {
            display: none;
        }
        
        .prefix-input {
            display: flex;
            align-items: center;
        }
        
        .prefix {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-light));
            border: 2px solid rgba(255, 194, 51, 0.5);
            border-right: none;
            border-radius: 12px 0 0 12px;
            padding: 16px;
            color: var(--primary-dark);
            font-weight: 700;
        }
        
        .prefix-input input {
            border-radius: 0 12px 12px 0;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            position: relative;
            padding-top: 20px;
        }
        
        .login-footer:before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
        }
        
        .fire-effect {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 20px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 194, 51, 0.2) 25%, 
                rgba(227, 6, 19, 0.2) 50%, 
                rgba(255, 194, 51, 0.2) 75%, 
                transparent 100%);
            border-radius: 0 0 20px 20px;
            animation: flicker 2s infinite alternate;
        }
        
        @keyframes flicker {
            0% { opacity: 0.5; }
            100% { opacity: 0.8; }
        }
        
        .grecaptcha-badge {
            visibility: hidden;
        }
        
        @media (max-width: 520px) {
            .container {
                padding: 30px 20px;
                margin: 0 20px;
            }
            
            .logo img {
                width: 80px;
            }
            
            h1 {
                font-size: 2.2rem;
            }
            
            .form-control {
                padding: 14px 14px 14px 50px;
            }
            
            .shape-1, .shape-2, .shape-3, .shape-4 {
                opacity: 0.2;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="fire-shape"></div>
    </div>

    <div class="container">
        <div class="fire-effect"></div>
        <div class="logo">
            <div class="logo-wrapper">
                <div class="logo-glow"></div>
                <img src="./img/logobo.png" alt="Logo Bombeiro Mirim">
            </div>
            <h1>
                <i class="fas fa-fire-extinguisher bombeiro-icon"></i>
                Bombeiro Mirim
            </h1>
            <p class="app-subtitle">🚒 Entre para acessar sua conta</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" id="verificar-tab">
                <i class="fas fa-search"></i> Verificar Aluno
            </div>
            <div class="tab" id="login-tab">
                <i class="fas fa-sign-in-alt"></i> Fazer Login
            </div>
        </div>
        
        <!-- Formulário de Verificação de CPF do Responsável -->
        <div id="verificar-form">
            <div class="form-group">
                <input type="text" id="cpf-verificar" class="form-control" placeholder="Digite o CPF do responsável" maxlength="14">
                <div class="icon-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-verificar">
                <i class="fas fa-search"></i> Verificar Aluno
            </button>
            
            <div id="verificar-message" class="message hidden"></div>
        </div>
        
        <!-- Formulário de Cadastro de Senha -->
        <div id="cadastro-form" class="hidden">
            <div class="form-group">
                <div class="">
                    <input type="text" id="matricula-cadastro" class="form-control" disabled>
                </div>
                <div class="icon-wrapper">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="text" id="nome-cadastro" class="form-control" disabled>
                <div class="icon-wrapper">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="text" id="nome-responsavel-cadastro" class="form-control" disabled>
                <div class="icon-wrapper">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" id="senha-cadastro" class="form-control" placeholder="🔐 Senha - Mínimo de 6 caracteres">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" id="confirmar-senha" class="form-control" placeholder="🔐 Confirme sua senha">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-cadastrar">
                <i class="fas fa-user-plus"></i> Cadastrar Senha
            </button>
            
            <div id="cadastro-message" class="message hidden"></div>
        </div>
        
        <!-- Formulário de Login -->
        <div id="login-form" class="hidden">
            <div class="form-group">
                    <input type="text" id="matricula-login" class="form-control" placeholder=" Digite o número da matrícula" maxlength="8">
                <div class="icon-wrapper">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" id="senha-login" class="form-control" placeholder=" Digite sua senha">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            
            <div id="login-message" class="message hidden"></div>
        </div>
        
        <div class="login-footer">
            🚒 &copy; <?= date('Y') ?> Bombeiro Mirim - Todos os direitos reservados
        </div>
    </div>
    
    <script>
        const RECAPTCHA_SITE_KEY = '<?= RECAPTCHA_SITE_KEY ?>';
        
        function getReCaptchaToken(action) {
            return new Promise((resolve, reject) => {
                grecaptcha.ready(function() {
                    grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: action})
                        .then(function(token) {
                            resolve(token);
                        })
                        .catch(function(error) {
                            console.error('Erro no sistema:', error);
                            reject(error);
                        });
                });
            });
        }
        
        // Função para formatar CPF enquanto o usuário digita
        function formatarCPF(input) {
            let cpf = input.value.replace(/\D/g, '');
            
            if (cpf.length > 11) {
                cpf = cpf.substring(0, 11);
            }
            
            if (cpf.length > 9) {
                cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
            } else if (cpf.length > 6) {
                cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})/, "$1.$2.$3");
            } else if (cpf.length > 3) {
                cpf = cpf.replace(/(\d{3})(\d{3})/, "$1.$2");
            }
            
            input.value = cpf;
        }
        
        // Aplicar formatação ao campo de CPF
        document.getElementById('cpf-verificar').addEventListener('input', function() {
            formatarCPF(this);
        });
        
        // Elementos do DOM
        const verificarTab = document.getElementById('verificar-tab');
        const loginTab = document.getElementById('login-tab');
        const verificarForm = document.getElementById('verificar-form');
        const cadastroForm = document.getElementById('cadastro-form');
        const loginForm = document.getElementById('login-form');
        const btnVerificar = document.getElementById('btn-verificar');
        const btnCadastrar = document.getElementById('btn-cadastrar');
        const btnLogin = document.getElementById('btn-login');
        
        // Mensagens
        const verificarMessage = document.getElementById('verificar-message');
        const cadastroMessage = document.getElementById('cadastro-message');
        const loginMessage = document.getElementById('login-message');
        
        // Alternar entre as abas
        verificarTab.addEventListener('click', function() {
            verificarTab.classList.add('active');
            loginTab.classList.remove('active');
            verificarForm.classList.remove('hidden');
            cadastroForm.classList.add('hidden');
            loginForm.classList.add('hidden');
            limparMensagens();
        });
        
        loginTab.addEventListener('click', function() {
            loginTab.classList.add('active');
            verificarTab.classList.remove('active');
            loginForm.classList.remove('hidden');
            verificarForm.classList.add('hidden');
            cadastroForm.classList.add('hidden');
            limparMensagens();
        });
        
        // Verificar CPF do responsável
        btnVerificar.addEventListener('click', async function() {
            const cpf = document.getElementById('cpf-verificar').value.trim();
            
            if (!cpf) {
                mostrarMensagem(verificarMessage, 'Por favor, digite o CPF do responsável.', 'error');
                return;
            }
            
            try {
                const systemToken = await getReCaptchaToken('verificar_cpf');
                
                const originalText = btnVerificar.innerHTML;
                btnVerificar.innerHTML = '<div class="loading"></div> Verificando...';
                btnVerificar.disabled = true;
                
                const response = await fetch('?api=verificar_cpf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cpf=' + encodeURIComponent(cpf) + '&system_token=' + encodeURIComponent(systemToken)
                });
                
                const data = await response.json();
                
                btnVerificar.innerHTML = originalText;
                btnVerificar.disabled = false;
                
                if (data.status === 'success') {
                    document.getElementById('matricula-cadastro').value = data.aluno.numero_matricula.replace('SA', '');
                    document.getElementById('nome-cadastro').value = data.aluno.nome;
                    document.getElementById('nome-responsavel-cadastro').value = data.responsavel.nome;
                    
                    verificarForm.classList.add('hidden');
                    cadastroForm.classList.remove('hidden');
                    mostrarMensagem(cadastroMessage, data.message, 'success');
                } else {
                    mostrarMensagem(verificarMessage, data.message, 'error');
                }
            } catch (error) {
                btnVerificar.innerHTML = originalText;
                btnVerificar.disabled = false;
                mostrarMensagem(verificarMessage, 'Erro ao verificar CPF. Tente novamente.', 'error');
                console.error('Erro:', error);
            }
        });
        
        // Cadastrar senha
        btnCadastrar.addEventListener('click', async function() {
            const senha = document.getElementById('senha-cadastro').value;
            const confirmarSenha = document.getElementById('confirmar-senha').value;
            
            if (!senha || senha.length < 6) {
                mostrarMensagem(cadastroMessage, 'A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }
            
            if (senha !== confirmarSenha) {
                mostrarMensagem(cadastroMessage, 'As senhas não coincidem.', 'error');
                return;
            }
            
            try {
                const systemToken = await getReCaptchaToken('cadastrar_senha');
                
                const originalText = btnCadastrar.innerHTML;
                btnCadastrar.innerHTML = '<div class="loading"></div> Cadastrando...';
                btnCadastrar.disabled = true;
                
                const response = await fetch('?api=cadastrar_senha', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'senha=' + encodeURIComponent(senha) + 
                          '&confirmar_senha=' + encodeURIComponent(confirmarSenha) +
                          '&system_token=' + encodeURIComponent(systemToken)
                });
                
                const data = await response.json();
                
                btnCadastrar.innerHTML = originalText;
                btnCadastrar.disabled = false;
                
                mostrarMensagem(cadastroMessage, data.message, data.status);
                
                if (data.status === 'success') {
                    setTimeout(function() {
                        loginTab.click();
                        document.getElementById('matricula-login').value = document.getElementById('matricula-cadastro').value;
                    }, 2000);
                }
            } catch (error) {
                btnCadastrar.innerHTML = originalText;
                btnCadastrar.disabled = false;
                mostrarMensagem(cadastroMessage, 'Erro ao cadastrar senha. Tente novamente.', 'error');
                console.error('Erro:', error);
            }
        });
        
        // Fazer login
        btnLogin.addEventListener('click', async function() {
            const matricula = document.getElementById('matricula-login').value.trim();
            const senha = document.getElementById('senha-login').value;
            
            if (!matricula || !senha) {
                mostrarMensagem(loginMessage, 'Por favor, preencha todos os campos.', 'error');
                return;
            }
            
            try {
                const systemToken = await getReCaptchaToken('login');
                
                const originalText = btnLogin.innerHTML;
                btnLogin.innerHTML = '<div class="loading"></div> Entrando...';
                btnLogin.disabled = true;
                
                const response = await fetch('?api=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'matricula=' + encodeURIComponent(matricula) + 
                          '&senha=' + encodeURIComponent(senha) +
                          '&system_token=' + encodeURIComponent(systemToken)
                });
                
                const data = await response.json();
                
                btnLogin.innerHTML = originalText;
                btnLogin.disabled = false;
                
                mostrarMensagem(loginMessage, data.message, data.status);
                
                if (data.status === 'success' && data.redirect) {
                    setTimeout(function() {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            } catch (error) {
                btnLogin.innerHTML = originalText;
                btnLogin.disabled = false;
                mostrarMensagem(loginMessage, 'Erro ao fazer login. Tente novamente.', 'error');
                console.error('Erro:', error);
            }
        });
        
        // Funções auxiliares
        function mostrarMensagem(elemento, texto, tipo) {
            elemento.textContent = texto;
            elemento.className = 'message ' + (tipo === 'success' ? 'success' : 'error');
            elemento.classList.remove('hidden');
        }
        
        function limparMensagens() {
            verificarMessage.classList.add('hidden');
            cadastroMessage.classList.add('hidden');
            loginMessage.classList.add('hidden');
        }
        
        grecaptcha.ready(function() {
            console.log('Sistema carregado com sucesso!');
        });
    </script>
</body>
</html>