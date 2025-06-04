<?php
session_start();

// Definir mensagem de erro personalizada
$erro_mensagem = "Você não tem permissão para acessar esta área.";
$erro_detalhes = "";

// Verificar o tipo de usuário atual
if (isset($_SESSION['usuario_tipo'])) {
    switch($_SESSION['usuario_tipo']) {
        case 'aluno':
            $erro_detalhes = "Área restrita para professores e administradores.";
            break;
        case 'professor':
            $erro_detalhes = "Esta funcionalidade requer permissão de administrador.";
            break;
        default:
            $erro_detalhes = "Seu perfil de usuário não tem acesso a esta área.";
    }
} else {
    $erro_mensagem = "Sessão expirada ou não autenticada.";
    $erro_detalhes = "Faça login novamente para continuar.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado - SuperAção</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #0a2647;
            --secondary: #ffc233;
            --danger: #f64e60;
            --background: #f5f7fd;
            --text-dark: #1a2b4b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(10, 38, 71, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
            border-top: 6px solid var(--danger);
        }

        .error-icon {
            font-size: 80px;
            color: var(--danger);
            margin-bottom: 20px;
            animation: shake 0.5s;
        }

        .error-title {
            color: var(--danger);
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .error-message {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 16px;
        }

        .error-details {
            background: rgba(246, 78, 96, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #144272;
            transform: translateY(-2px);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @media (max-width: 500px) {
            .error-container {
                padding: 30px 20px;
            }

            .error-icon {
                font-size: 60px;
            }

            .error-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1 class="error-title">Acesso Negado</h1>
        <div class="error-message">
            <?php echo htmlspecialchars($erro_mensagem); ?>
        </div>
        <?php if (!empty($erro_detalhes)): ?>
            <div class="error-details">
                <?php echo htmlspecialchars($erro_detalhes); ?>
            </div>
        <?php endif; ?>
        <a href="../index.php" class="btn">
            <i class="fas fa-home"></i> Voltar para o Login
        </a>
    </div>
</body>
</html>