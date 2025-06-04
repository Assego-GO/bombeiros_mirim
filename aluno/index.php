<?php
session_start();

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
            header('Location: ./aluno/dashboard.php'); // Corrigido para dashboard.php
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
    <title>Área do Aluno - Projeto Superação</title>
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
        
        .container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px var(--shadow-color);
            padding: 40px;
            width: 100%;
            max-width: 500px;
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
        
        .logo img {
            width: 100px;
            height: auto;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 5px 15px rgba(30, 58, 138, 0.3));
            transition: all 0.4s;
        }
        
        .logo img:hover {
            transform: scale(1.08) rotate(3deg);
        }
        
        h1 {
            font-size: 2.6rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), #3863c5);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
            text-align: center;
            letter-spacing: -0.5px;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 15px 10px;
            background-color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .tab.active {
            background: linear-gradient(45deg, var(--primary), #3863c5);
            color: white;
            font-weight: 600;
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
        
        .btn {
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
        
        .btn:before {
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
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.35);
        }
        
        .btn:hover:before {
            transform: translateX(100%);
        }
        
        .btn:active {
            transform: translateY(0);
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
            background-color: rgba(255, 129, 130, 0.2);
            color: #e53e3e;
            border: 1px solid rgba(229, 62, 62, 0.3);
        }
        
        .success {
            background-color: rgba(72, 187, 120, 0.2);
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
            background-color: rgba(226, 232, 240, 0.8);
            border: 2px solid rgba(206, 212, 218, 0.5);
            border-right: none;
            border-radius: 12px 0 0 12px;
            padding: 16px;
            color: var(--primary);
            font-weight: 600;
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

    <div class="container">
        <div class="logo">
            <div class="logo-wrapper">
                <div class="logo-glow"></div>
                <img src="./img/logo.png" alt="Logo SuperAção">
            </div>
            <h1>Superação</h1>
            <p class="app-subtitle">Entre para acessar sua conta</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" id="verificar-tab">Verificar Aluno</div>
            <div class="tab" id="login-tab">Fazer Login</div>
        </div>
        
        <!-- Formulário de Verificação de CPF do Responsável -->
        <div id="verificar-form">
            <div class="form-group">
                <input type="text" id="cpf-verificar" class="form-control" placeholder="Digite o CPF do responsável" maxlength="14">
                <div class="icon-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-verificar">Verificar Aluno</button>
            
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
                <input type="password" id="senha-cadastro" class="form-control" placeholder="Mínimo de 6 caracteres">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" id="confirmar-senha" class="form-control" placeholder="Digite a senha novamente">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-cadastrar">Cadastrar Senha</button>
            
            <div id="cadastro-message" class="message hidden"></div>
        </div>
        
        <!-- Formulário de Login -->
        <div id="login-form" class="hidden">
            <div class="form-group">
                    <input type="text" id="matricula-login" class="form-control" placeholder="Digite o número da matrícula" maxlength="8">
                <div class="icon-wrapper">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" id="senha-login" class="form-control" placeholder="Digite sua senha">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button class="btn" id="btn-login">Entrar</button>
            
            <div id="login-message" class="message hidden"></div>
        </div>
        
        <div class="login-footer">
            &copy; <?= date('Y') ?> SuperAção - Todos os direitos reservados
        </div>
    </div>
    
    <script>
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
        btnVerificar.addEventListener('click', function() {
            const cpf = document.getElementById('cpf-verificar').value.trim();
            
            if (!cpf) {
                mostrarMensagem(verificarMessage, 'Por favor, digite o CPF do responsável.', 'error');
                return;
            }
            
            // Adicionar animação de carregamento
            const originalText = btnVerificar.innerHTML;
            btnVerificar.innerHTML = '<div class="loading"></div> Verificando...';
            btnVerificar.disabled = true;
            
            // Enviar requisição AJAX
            fetch('api/verificar_cpf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'cpf=' + encodeURIComponent(cpf)
            })
            .then(response => response.json())
            .then(data => {
                // Restaurar botão
                btnVerificar.innerHTML = originalText;
                btnVerificar.disabled = false;
                
                if (data.status === 'success') {
                    // Preenche os dados no formulário de cadastro
                    document.getElementById('matricula-cadastro').value = data.aluno.numero_matricula.replace('SA', '');
                    document.getElementById('nome-cadastro').value = data.aluno.nome;
                    document.getElementById('nome-responsavel-cadastro').value = data.responsavel.nome;
                    
                    // Mostra o formulário de cadastro
                    verificarForm.classList.add('hidden');
                    cadastroForm.classList.remove('hidden');
                    mostrarMensagem(cadastroMessage, data.message, 'success');
                } else {
                    mostrarMensagem(verificarMessage, data.message, 'error');
                }
            })
            .catch(error => {
                // Restaurar botão
                btnVerificar.innerHTML = originalText;
                btnVerificar.disabled = false;
                
                mostrarMensagem(verificarMessage, 'Erro ao verificar CPF. Tente novamente.', 'error');
                console.error('Erro:', error);
            });
        });
        
        // Cadastrar senha
        btnCadastrar.addEventListener('click', function() {
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
            
            // Adicionar animação de carregamento
            const originalText = btnCadastrar.innerHTML;
            btnCadastrar.innerHTML = '<div class="loading"></div> Cadastrando...';
            btnCadastrar.disabled = true;
            
            // Enviar requisição AJAX
            fetch('api/cadastrar_senha.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'senha=' + encodeURIComponent(senha) + '&confirmar_senha=' + encodeURIComponent(confirmarSenha)
            })
            .then(response => {
                // Para depuração, você pode examinar a resposta bruta
                console.log('Resposta bruta status:', response.status);
                return response.json(); // Converte para JSON apenas uma vez
            })
            .then(data => {
                console.log('Dados JSON:', data); // Para depuração
                // Restaurar botão
                btnCadastrar.innerHTML = originalText;
                btnCadastrar.disabled = false;
                
                mostrarMensagem(cadastroMessage, data.message, data.status);
                
                if (data.status === 'success') {
                    // Redirecionar para a tela de login após 2 segundos
                    setTimeout(function() {
                        loginTab.click();
                        document.getElementById('matricula-login').value = document.getElementById('matricula-cadastro').value;
                    }, 2000);
                }
            })
            .catch(error => {
                // Restaurar botão
                btnCadastrar.innerHTML = originalText;
                btnCadastrar.disabled = false;
                
                mostrarMensagem(cadastroMessage, 'Erro ao cadastrar senha. Tente novamente.', 'error');
                console.error('Erro:', error);
            });
        });
        
        // Fazer login
        btnLogin.addEventListener('click', function() {
            const matricula = document.getElementById('matricula-login').value.trim();
            const senha = document.getElementById('senha-login').value;
            
            if (!matricula || !senha) {
                mostrarMensagem(loginMessage, 'Por favor, preencha todos os campos.', 'error');
                return;
            }
            
            // Adicionar animação de carregamento
            const originalText = btnLogin.innerHTML;
            btnLogin.innerHTML = '<div class="loading"></div> Entrando...';
            btnLogin.disabled = true;
            
            // Enviar requisição AJAX
            fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'matricula=' + encodeURIComponent(matricula) + '&senha=' + encodeURIComponent(senha)
            })
            .then(response => response.json())
            .then(data => {
                // Restaurar botão
                btnLogin.innerHTML = originalText;
                btnLogin.disabled = false;
                
                mostrarMensagem(loginMessage, data.message, data.status);
                
                if (data.status === 'success' && data.redirect) {
                    // Redirecionar para a página de dashboard
                    setTimeout(function() {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            })
            .catch(error => {
                // Restaurar botão
                btnLogin.innerHTML = originalText;
                btnLogin.disabled = false;
                
                mostrarMensagem(loginMessage, 'Erro ao fazer login. Tente novamente.', 'error');
                console.error('Erro:', error);
            });
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
    </script>
</body>
</html>