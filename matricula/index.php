<?php
// Configurações de sessão para produção
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Exige HTTPS em produção
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configurar domínio do cookie para o site específico
$domain = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($domain, 'assego.com.br') !== false) {
    ini_set('session.cookie_domain', '.assego.com.br'); // Permite subdomínios
}

// Headers de segurança para produção
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// HSTS só se tiver SSL (recomendado para produção)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

session_start();
error_reporting(E_ALL);

// Carregar configurações do .env
function loadEnv($file) {
    if (!file_exists($file)) return;
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

loadEnv('.env');

// Função para obter IP real
function getRealUserIP() {
    $ipHeaders = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if ($header === 'HTTP_X_FORWARDED_FOR') {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Criar diretório de segurança
function createSecurityDir() {
    $dir = 'security';
    if (!file_exists($dir)) {
        @mkdir($dir, 0750, true);
        @file_put_contents($dir . '/.htaccess', "Deny from all\n");
    }
    return $dir;
}

// Verificar tentativas de login
function checkLoginAttempts($ip) {
    $securityDir = createSecurityDir();
    $file = $securityDir . '/attempts_' . md5($ip) . '.json';
    
    if (!file_exists($file)) {
        return ['blocked' => false, 'attempts' => 0];
    }
    
    $content = @file_get_contents($file);
    if (!$content) {
        return ['blocked' => false, 'attempts' => 0];
    }
    
    $data = json_decode($content, true);
    if (!$data) {
        return ['blocked' => false, 'attempts' => 0];
    }
    
    $timePassed = time() - ($data['last_attempt'] ?? 0);
    
    // Limpar após 15 minutos
    if ($timePassed > 900) {
        @unlink($file);
        return ['blocked' => false, 'attempts' => 0];
    }
    
    // Verificar se está bloqueado
    if (($data['attempts'] ?? 0) >= 5) {
        $remainingTime = 900 - $timePassed;
        return [
            'blocked' => true, 
            'attempts' => $data['attempts'], 
            'remaining_time' => $remainingTime
        ];
    }
    
    return ['blocked' => false, 'attempts' => $data['attempts'] ?? 0];
}

// Registrar tentativa falhada
function recordFailedAttempt($ip) {
    $securityDir = createSecurityDir();
    $file = $securityDir . '/attempts_' . md5($ip) . '.json';
    
    $data = ['attempts' => 0, 'first_attempt' => time()];
    
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content) {
            $existing = json_decode($content, true);
            if ($existing) {
                $data = $existing;
            }
        }
    }
    
    $data['attempts'] = ($data['attempts'] ?? 0) + 1;
    $data['last_attempt'] = time();
    
    @file_put_contents($file, json_encode($data));
    
    return $data['attempts'];
}

// Detectar bot via honeypot
function detectBot() {
    $honeypotFields = ['website', 'email_address', 'phone'];
    
    foreach ($honeypotFields as $field) {
        if (!empty($_POST[$field])) {
            // Log do bot detectado
            $ip = getRealUserIP();
            $securityDir = createSecurityDir();
            $logFile = $securityDir . '/security.log';
            $logEntry = date('Y-m-d H:i:s') . " - BOT_DETECTED - IP: $ip - Field: $field - Value: " . $_POST[$field] . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            return true;
        }
    }
    return false;
}

// Verificar Turnstile no servidor
function verifyTurnstile($token) {
    if (empty($token)) {
        return false;
    }
    
    $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '1x0000000000000000000000000000000AA';
    $ip = getRealUserIP();
    
    $data = [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $ip
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    
    if ($result === false) {
        // Se falhar a verificação (rede, etc), logs mas permite (para não quebrar o sistema)
        error_log("Turnstile verification failed - network error");
        return true; // Permite em caso de falha técnica
    }
    
    $response = json_decode($result, true);
    
    // Com chaves de teste (1x000...), sempre retorna success
    // Com chaves reais, faz verificação real
    return isset($response['success']) && $response['success'] === true;
}
function checkLocation($ip) {
    // Cache simples
    $securityDir = createSecurityDir();
    $cacheFile = $securityDir . '/geo_' . md5($ip) . '.json';
    
    // Verificar cache (válido por 24h)
    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 86400) {
        $cached = @file_get_contents($cacheFile);
        if ($cached) {
            $data = json_decode($cached, true);
            return $data;
        }
    }
    
    // IPs locais sempre permitidos
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return ['allowed' => true, 'city' => 'LOCAL', 'region' => 'LOCAL'];
    }
    
    // Tentar API de geolocalização
    $apiUrl = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city";
    $context = stream_context_create([
        'http' => ['timeout' => 3, 'user_agent' => 'BombeiroMirim-Sistema/1.0 (bombeiromirim.assego.com.br)']
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            $city = $data['city'] ?? 'UNKNOWN';
            $region = $data['region'] ?? 'UNKNOWN';
            $country = $data['countryCode'] ?? 'UN';
            
            // Verificar se é de Goiânia/GO/Brasil
            $allowedCities = [
                'Goiânia', 'Goiania', 'Aparecida de Goiânia', 'Aparecida de Goiania',
                'Senador Canedo', 'Trindade', 'Goianira', 'Hidrolândia', 'Hidrolandia'
            ];
            
            $isAllowed = ($country === 'BR' && $region === 'GO' && 
                         in_array($city, $allowedCities)) || 
                         in_array($city, ['LOCAL', 'UNKNOWN']);
            
            $result = [
                'allowed' => $isAllowed,
                'city' => $city,
                'region' => $region,
                'country' => $country
            ];
            
            // Salvar no cache
            @file_put_contents($cacheFile, json_encode($result));
            return $result;
        }
    }
    
    // Se falhou, permitir (não bloquear por falha técnica)
    $result = ['allowed' => true, 'city' => 'UNKNOWN', 'region' => 'UNKNOWN'];
    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}

// Gerar CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ========================================
// PROCESSAMENTO PRINCIPAL
// ========================================

$ip = getRealUserIP();

// Verificação de sessão existente
if (isset($_SESSION['usuario_id'])) {
    $session_timeout = 3600;
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > $session_timeout) {
        session_destroy();
        session_start();
        $_SESSION['erro_login'] = "Sua sessão expirou. Faça login novamente.";
    } else {
        $_SESSION['last_activity'] = time();
        
        if ($_SESSION['usuario_tipo'] == 'admin') {
            header('Location: painel.php');
            exit;
        } elseif ($_SESSION['usuario_tipo'] == 'professor') {
            header('Location: ../professor/dashboard.php');
            exit;
        } elseif ($_SESSION['usuario_tipo'] == 'aluno') {
            header('Location: ../aluno/dashboard.php');
            exit;
        }
    }
}

// Verificações de segurança
$login_status = checkLoginAttempts($ip);
$location_check = checkLocation($ip);
$location_blocked = !$location_check['allowed'];

// Verificar honeypot no POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (detectBot()) {
        $_SESSION['erro_login'] = "Verificação de segurança falhou. Tente novamente.";
        header('Location: index.php');
        exit;
    }
    
    if ($location_blocked) {
        $_SESSION['erro_login'] = "Acesso restrito a usuários de Goiânia e região.";
        header('Location: index.php');
        exit;
    }
    
    // VERIFICAR TURNSTILE NO SERVIDOR
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
    if (!verifyTurnstile($turnstile_token)) {
        $_SESSION['erro_login'] = "Verificação de segurança Turnstile falhou. Tente novamente.";
        header('Location: index.php');
        exit;
    }
}

// Recuperar e processar mensagem de erro
$erro_mensagem = isset($_SESSION['erro_login']) ? $_SESSION['erro_login'] : '';

// Registrar tentativa se houve erro
if (!empty($erro_mensagem)) {
    $attempts = recordFailedAttempt($ip);
    
    // Atualizar status após registrar
    $login_status = checkLoginAttempts($ip);
}

// Limpar mensagem de erro
unset($_SESSION['erro_login']);

// Obter chaves do Turnstile
$turnstile_site_key = $_ENV['TURNSTILE_SITE_KEY'] ?? '1x00000000000000000000AA';
$turnstile_secret_key = $_ENV['TURNSTILE_SECRET_KEY'] ?? '1x0000000000000000000000000000000AA';

// Log das chaves para debug (só primeiros caracteres por segurança)
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
error_log("Turnstile keys loaded for domain: $domain - Site: " . substr($turnstile_site_key, 0, 8) . "... Secret: " . substr($turnstile_secret_key, 0, 8) . "...");

// Sistema pronto para produção
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bombeiro Mirim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        :root {
            --primary: #E30613;
            --secondary:rgb(252, 34, 34);
            --accent: #e8d424;
            --tertiary:rgb(170, 43, 28);
            --dark: #2c3e50;
            --blue-accent: #1a5276;
            --gradient-bg: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --input-bg: rgba(255, 255, 255, 0.9);
            --shadow-color: rgba(231, 76, 60, 0.3);
            --text-primary: #2c3e50;
            --text-secondary: #34495e;
            --red-glow: rgba(231, 76, 60, 0.4);
            --yellow-glow: rgba(243, 156, 18, 0.4);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #E30613;
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
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 350px;
            height: 350px;
            top: -150px;
            right: -100px;
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: -80px;
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            bottom: 30%;
            right: 10%;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            animation-delay: 6s;
        }
        
        .shape-4 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            animation-delay: 9s;
        }
        
        .shape-5 {
            width: 80px;
            height: 80px;
            top: 60%;
            left: 80%;
            background: linear-gradient(45deg, var(--accent), var(--secondary));
            animation-delay: 12s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-30px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translateY(-15px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translateY(-45px) rotate(270deg) scale(1.05);
            }
        }
        
        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px var(--shadow-color),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            padding: 50px 40px;
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
            border: 2px solid transparent;
            background-clip: padding-box;
            animation: cardAppear 1s ease-out;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 25px;
            padding: 2px;
            background: linear-gradient(45deg, var(--secondary), var(--accent), var(--secondary));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: exclude;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            z-index: -1;
        }
        
        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
        }
        
        .logo-img {
            width: 130px;
            height: auto;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 8px 20px rgba(243, 156, 18, 0.4));
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .logo-img:hover {
            transform: scale(1.1) rotate(5deg);
            filter: drop-shadow(0 12px 30px rgba(243, 156, 18, 0.6));
        }
        
        .logo-glow {
            position: absolute;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, var(--yellow-glow) 0%, transparent 70%);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            animation: logoGlow 3s infinite;
        }
        
        .logo-glow::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(232, 212, 36, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            animation: logoGlow 3s infinite reverse;
        }
        
        @keyframes logoGlow {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.6;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.3);
                opacity: 0.3;
            }
        }
        
        .app-title {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--secondary), var(--accent), var(--secondary));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
            animation: gradientShift 4s ease-in-out infinite;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }
        
        .app-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 30px;
            opacity: 0.8;
        }
        
        .login-form {
            position: relative;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 2px solid rgba(243, 156, 18, 0.3);
            border-radius: 15px;
            padding: 18px 20px 18px 60px;
            font-size: 15px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-primary);
            backdrop-filter: blur(10px);
            box-shadow: 
                0 5px 15px rgba(243, 156, 18, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 
                0 8px 25px rgba(243, 156, 18, 0.2),
                0 0 0 3px rgba(243, 156, 18, 0.1);
            outline: none;
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: rgba(243, 156, 18, 0.6);
            font-weight: 400;
        }
        
        .icon-wrapper {
            position: absolute;
            top: 50%;
            left: 22px;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s;
        }
        
        .form-group i {
            color: var(--secondary);
            font-size: 18px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-control:focus + .icon-wrapper i {
            color: var(--accent);
            transform: scale(1.2) rotate(10deg);
        }
        
        /* CAMPOS HONEYPOT - INVISÍVEIS */
        .honeypot {
            position: absolute !important;
            left: -9999px !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            tabindex: -1 !important;
        }
        
        .security-wrapper {
            margin: 25px 0;
            padding: 15px;
            background: rgba(46, 204, 113, 0.1);
            border: 2px solid rgba(46, 204, 113, 0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .security-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: #27ae60;
            font-size: 13px;
            font-weight: 500;
        }
        
        .security-info i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .turnstile-wrapper {
            display: flex;
            justify-content: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            border-radius: 15px;
            padding: 18px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 
                0 10px 30px rgba(243, 156, 18, 0.3),
                0 5px 15px rgba(232, 212, 36, 0.2);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #f1c40f, var(--accent));
            opacity: 0;
            transition: opacity 0.4s;
            z-index: 1;
        }
        
        .btn-login::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
            z-index: 2;
        }
        
        .btn-login span {
            position: relative;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-4px);
            box-shadow: 
                0 15px 40px rgba(243, 156, 18, 0.4),
                0 8px 25px rgba(232, 212, 36, 0.3);
        }
        
        .btn-login:hover:not(:disabled)::before {
            opacity: 1;
        }
        
        .btn-login:hover:not(:disabled)::after {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .loading-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-message {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(192, 57, 43, 0.05));
            color: var(--primary);
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid rgba(231, 76, 60, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: errorShake 0.6s ease-in-out;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.1);
        }

        .error-message i {
            margin-right: 12px;
            font-size: 18px;
            animation: errorPulse 1s infinite;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        
        @keyframes errorPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .blocked-message {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 193, 7, 0.05));
            color: #f57c00;
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid rgba(255, 152, 0, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.1);
        }

        .blocked-message i {
            margin-right: 12px;
            font-size: 18px;
        }
        
        .location-blocked-message {
            background: linear-gradient(135deg, rgba(233, 6, 19, 0.1), rgba(170, 43, 28, 0.05));
            color: var(--primary);
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid rgba(233, 6, 19, 0.3);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(233, 6, 19, 0.2);
        }

        .location-blocked-message i {
            margin-right: 12px;
            font-size: 18px;
        }
        
        .attempts-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #f57c00;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 35px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            position: relative;
            padding-top: 25px;
            font-weight: 500;
            opacity: 0.8;
        }
        
        .login-footer:before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--secondary), var(--accent), transparent);
            border-radius: 2px;
        }
        
        /* Responsividade */
        @media (max-width: 520px) {
            .login-container {
                padding: 40px 30px;
                margin: 0 20px;
                max-width: 400px;
            }
            
            .logo-img {
                width: 110px;
            }
            
            .app-title {
                font-size: 2.4rem;
            }
            
            .form-control {
                padding: 16px 16px 16px 55px;
                font-size: 14px;
            }
            
            .btn-login {
                padding: 16px;
                font-size: 14px;
            }
            
            .shape-1, .shape-2, .shape-3, .shape-4, .shape-5 {
                opacity: 0.1;
            }
        }
        
        @media (max-width: 400px) {
            .login-container {
                padding: 35px 25px;
            }
            
            .app-title {
                font-size: 2rem;
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
        <div class="shape shape-5"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-wrapper">
                <div class="logo-glow"></div>
                <img src="./img/logobo.png" alt="Logo Bombeiro Mirim" class="logo-img">
            </div>
            <h1 class="app-title">Bombeiro Mirim</h1>
            <p class="app-subtitle">Entre para acessar sua conta</p>
        </div>
        
        <?php if ($login_status['blocked']): ?>
            <div class="blocked-message">
                <i class="fas fa-clock"></i>
                Acesso temporariamente bloqueado por segurança. Tente novamente em <?php echo ceil($login_status['remaining_time'] / 60); ?> minuto(s).
            </div>
        <?php elseif ($location_blocked): ?>
            <div class="location-blocked-message">
                <i class="fas fa-map-marker-alt"></i>
                Acesso restrito a usuários de Goiânia e região metropolitana. 
                (Detectado: <?php echo htmlspecialchars($location_check['city']); ?>)
            </div>
        <?php elseif ($login_status['attempts'] > 0): ?>
            <div class="attempts-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Atenção: <?php echo $login_status['attempts']; ?> tentativa(s) de login detectada(s). 
                Restam <?php echo (5 - $login_status['attempts']); ?> tentativa(s) antes do bloqueio.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($erro_mensagem)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo htmlspecialchars($erro_mensagem); ?>
            </div>
        <?php endif; ?>
        
        <form action="verificar_login.php" method="POST" class="login-form" id="loginForm">
            <!-- Token CSRF para segurança -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- CAMPOS HONEYPOT INVISÍVEIS - DETECTAR BOTS -->
            <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
            <input type="email" name="email_address" class="honeypot" tabindex="-1" autocomplete="off">
            <input type="tel" name="phone" class="honeypot" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       placeholder="Digite seu email" 
                       required 
                       maxlength="255"
                       autocomplete="email"
                       id="email"
                       <?php echo ($login_status['blocked'] || $location_blocked) ? 'disabled' : ''; ?>>
                <div class="icon-wrapper">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" 
                       name="senha" 
                       class="form-control" 
                       placeholder="Digite sua senha" 
                       required 
                       maxlength="255"
                       autocomplete="current-password"
                       id="senha"
                       <?php echo ($login_status['blocked'] || $location_blocked) ? 'disabled' : ''; ?>>
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <?php if (!$login_status['blocked'] && !$location_blocked): ?>
            <div class="security-wrapper">
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                   
                </div>
                <div class="turnstile-wrapper">
                    <div class="cf-turnstile" 
                         data-sitekey="<?php echo htmlspecialchars($turnstile_site_key); ?>"
                         data-callback="onTurnstileSuccess"
                         data-expired-callback="onTurnstileExpired"
                         data-error-callback="onTurnstileError">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" 
                    class="btn-login" 
                    id="submitBtn" 
                    <?php echo ($login_status['blocked'] || $location_blocked) ? 'disabled' : ''; ?>>
                <span>
                    <div class="loading-spinner" id="loadingSpinner"></div>
                    <?php 
                        if ($login_status['blocked']) {
                            echo 'Bloqueado';
                        } elseif ($location_blocked) {
                            echo 'Localização Restrita';
                        } else {
                            echo 'Entrar';
                        }
                    ?>
                </span>
            </button>
        </form>
        
        <div class="login-footer">
            &copy; <?= date('Y') ?> Bombeiro Mirim - Todos os direitos reservados
        </div>
    </div>

    <script>
        /*
        CONFIGURAÇÃO PARA DOMÍNIO: bombeiromirim.assego.com.br
        
        1. TURNSTILE (Cloudflare):
           - Criar novo site com domínio: bombeiromirim.assego.com.br
           - Gerar chaves específicas para este domínio
           - Atualizar .env com as chaves reais
        
        2. SSL/HTTPS:
           - Certificado SSL obrigatório
           - Redirect HTTP → HTTPS
           - HSTS habilitado
        
        3. CHAVES DE TESTE vs REAIS:
           - Teste: 1x00000000000000000000AA (sempre passa)
           - Real: 0x4AAAxxxxxxxxxxxxxxxxx (verificação real)
           
        4. COOKIES/SESSÃO:
           - Configurado para .assego.com.br
           - Secure cookies (HTTPS only)
           - SameSite Strict
        */
        
        let turnstileVerified = false;
        let formSubmitting = false;
        
        const isBlocked = <?php echo ($login_status['blocked'] || $location_blocked) ? 'true' : 'false'; ?>;
        
        if (isBlocked) {
            document.getElementById('submitBtn').disabled = true;
        }
        
        function onTurnstileSuccess(token) {
            if (!isBlocked) {
                turnstileVerified = true;
                updateSubmitButton();
            }
        }
        
        function onTurnstileExpired() {
            turnstileVerified = false;
            updateSubmitButton();
        }
        
        function onTurnstileError() {
            turnstileVerified = false;
            updateSubmitButton();
            if (!isBlocked) {
                showError('Erro na verificação de segurança. Recarregue a página.');
            }
        }
        
        function updateSubmitButton() {
            if (isBlocked) return;
            
            const submitBtn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value.trim();
            
            if (turnstileVerified && email && senha && isValidEmail(email) && senha.length >= 6) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email) && email.length <= 255;
        }
        
        if (!isBlocked) {
            document.getElementById('email').addEventListener('input', function() {
                this.value = this.value.toLowerCase();
                updateSubmitButton();
            });
            
            document.getElementById('senha').addEventListener('input', updateSubmitButton);
            
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                if (formSubmitting || isBlocked) {
                    e.preventDefault();
                    return;
                }
                
                const submitBtn = document.getElementById('submitBtn');
                const spinner = document.getElementById('loadingSpinner');
                const email = document.getElementById('email').value.trim();
                const senha = document.getElementById('senha').value.trim();
                
                if (!turnstileVerified) {
                    e.preventDefault();
                    showError('Complete a verificação de segurança.');
                    return;
                }
                
                if (!email || !senha) {
                    e.preventDefault();
                    showError('Preencha todos os campos.');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showError('Email inválido.');
                    return;
                }
                
                if (senha.length < 6) {
                    e.preventDefault();
                    showError('Senha deve ter pelo menos 6 caracteres.');
                    return;
                }
                
                formSubmitting = true;
                submitBtn.disabled = true;
                spinner.style.display = 'inline-block';
                submitBtn.querySelector('span').innerHTML = '<div class="loading-spinner" style="display: inline-block;"></div>Entrando...';
            });
        }
        
        function showError(message) {
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(errorDiv, form);
            
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
        
        // Proteção honeypot
        document.querySelectorAll('.honeypot').forEach(field => {
            field.addEventListener('input', function() {
                console.log('Bot detectado tentando preencher honeypot');
                this.form.style.display = 'none';
            });
        });
        
        // Proteção contra paste
        document.querySelectorAll('input:not(.honeypot)').forEach(input => {
            input.addEventListener('paste', function(e) {
                setTimeout(() => {
                    if (this.value.length > 255) {
                        this.value = this.value.substring(0, 255);
                        showError('Texto colado muito longo, foi truncado.');
                    }
                }, 10);
            });
        });
        
        // Contador regressivo
        <?php if ($login_status['blocked']): ?>
        let timeLeft = <?php echo $login_status['remaining_time']; ?>;
        
        function updateCountdown() {
            if (timeLeft <= 0) {
                location.reload();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
            
            const blockedMsg = document.querySelector('.blocked-message');
            if (blockedMsg) {
                blockedMsg.innerHTML = `<i class="fas fa-clock"></i> Acesso bloqueado. Desbloqueio em: ${timeString}`;
            }
            
            timeLeft--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>
    </script>
</body>
</html>