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
            --primary: #1e3a8a;
            --secondary: #ffc52e;
            --gradient-bg: linear-gradient(135deg, #0f2350 0%, #234a9c 100%);
            --card-bg: rgba(255, 255, 255, 0.9);
            --input-bg: rgba(255, 255, 255, 0.8);
            --shadow-color: rgba(14, 30, 62, 0.2);
            --text-primary: #0c1e3e;
            --text-secondary: #566b8f;
            --danger: #f64e60;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: var(--gradient-bg);
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
            background: linear-gradient(45deg, var(--secondary), #ffad0a);
            opacity: 0.2;
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
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }
        
        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px var(--shadow-color);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            transform: translateY(0);
            animation: cardAppear 0.8s ease-out;
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
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 120px;
            height: auto;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 5px 15px rgba(30, 58, 138, 0.3));
            transition: all 0.4s;
        }
        
        .logo-img:hover {
            transform: scale(1.08) rotate(3deg);
        }
        
        .logo-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 197, 46, 0.4) 0%, rgba(255, 197, 46, 0) 70%);
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
                opacity: 0.4;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
                opacity: 0.2;
            }
        }
        
        .app-title {
            font-size: 2.6rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), #3863c5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .app-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 30px;
        }
        
        /* Formulário */
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
            border: 2px solid rgba(206, 212, 218, 0.5);
            border-radius: 12px;
            padding: 16px 20px 16px 55px;
            font-size: 15px;
            transition: all 0.3s;
            color: var(--text-primary);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(30, 58, 138, 0.15);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #a0aec0;
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
        
        .btn-login {
            width: 100%;
            background: linear-gradient(45deg, var(--primary), #3863c5);
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
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
            letter-spacing: 0.5px;
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: 0.5s;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.35);
        }
        
        .btn-login:hover:before {
            transform: translateX(100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Estilo para mensagem de erro */
        .error-message {
            background-color: rgba(246, 78, 96, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid rgba(246, 78, 96, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: shake 0.5s;
        }

        .error-message i {
            margin-right: 10px;
            font-size: 20px;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
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
        
        @media (max-width: 520px) {
            .login-container {
                padding: 35px 25px;
                margin: 0 20px;
            }
            
            .logo-img {
                width: 100px;
            }
            
            .app-title {
                font-size: 2.2rem;
            }
            
            .form-control {
                padding: 14px 14px 14px 50px;
            }
            
            .shape-1, .shape-2, .shape-3, .shape-4 {
                opacity: 0.15;
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
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-wrapper">
                <div class="logo-glow"></div>
                <img src="./img/logo.png" alt="Logo SuperAção" class="logo-img">
            </div>
            <h1 class="app-title">Superação</h1>
            <p class="app-subtitle">Entre para acessar sua conta</p>
        </div>
        
        <?php if (!empty($erro_mensagem)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
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
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        
        <div class="login-footer">
            &copy; <?= date('Y') ?> SuperAção - Todos os direitos reservados
        </div>
    </div>
</body>
</html>