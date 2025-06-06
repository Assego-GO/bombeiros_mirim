<?php
error_reporting(E_ALL);

session_start();

// Verifica se há uma mensagem de erro na sessão
$erro_mensagem = isset($_SESSION['erro_login']) ? $_SESSION['erro_login'] : '';
// Limpa a mensagem de erro da sessão após recuperá-la
unset($_SESSION['erro_login']);

// Verificação de sessão existente
if (isset($_SESSION['usuario_id'])) {
    // Se for admin, redireciona para o painel administrativo
    if ($_SESSION['usuario_tipo'] == 'admin') {
        header('Location: painel.php');
        exit;
    } 
    // Se for professor, redireciona para o dashboard do professor
    elseif ($_SESSION['usuario_tipo'] == 'professor') {
        header('Location: ../professor/dashboard.php');
        exit;
    }
    elseif ($_SESSION['usuario_tipo'] == 'aluno') {
        header('Location: ../aluno/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Superação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        }
        
        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 15px 40px rgba(243, 156, 18, 0.4),
                0 8px 25px rgba(232, 212, 36, 0.3);
        }
        
        .btn-login:hover::before {
            opacity: 1;
        }
        
        .btn-login:hover::after {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:active {
            transform: translateY(-1px);
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
                <img src="./img/logobo.png" alt="Logo SuperAção" class="logo-img">
            </div>
            <h1 class="app-title">Bombeiro Mirim</h1>
            <p class="app-subtitle">Entre para acessar sua conta</p>
        </div>
        
        <?php if (!empty($erro_mensagem)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo htmlspecialchars($erro_mensagem); ?>
            </div>
        <?php endif; ?>
        
        <form action="verificar_login.php" method="POST" class="login-form">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Digite seu email" required>
                <div class="icon-wrapper">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" name="senha" class="form-control" placeholder="Digite sua senha" required>
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <span>Entrar</span>
            </button>
        </form>
        
        <div class="login-footer">
            &copy; <?= date('Y') ?> Bombeiro Mirim - Todos os direitos reservados
        </div>
    </div>
</body>
</html>