<?php
// Iniciar sessão
session_start();

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION["logado"]) && $_SESSION["logado"] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Aluno - Projeto Superação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .logo{
            text-align: center;
            margin-bottom: 20px;
        }
        
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        input:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        .btn {
            background-color: #1a5276;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #1a5276;
        }
        
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        
        .error {
            background-color: #ffe0e0;
            color: #d83030;
        }
        
        .success {
            background-color: #e0ffe0;
            color: #30a030;
        }
        
        .hidden {
            display: none;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f0f0f0;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .tab.active {
            background-color: #1a5276;
            color: white;
            font-weight: 600;
        }
        
        .tab:first-child {
            border-radius: 5px 0 0 5px;
        }
        
        .tab:last-child {
            border-radius: 0 5px 5px 0;
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
        
        .prefix-input {
            display: flex;
            align-items: center;
        }
        
        .prefix {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 5px 0 0 5px;
            padding: 12px;
            color: #555;
            font-weight: 600;
        }
        
        .prefix-input input {
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="./img/logo.png" alt="Logo SuperAção" style="width: 100px; height: auto;"/>
        <h1>Login</h1>
    </div>
        
        <div class="tabs">
            <div class="tab active" id="verificar-tab">Verificar Aluno</div>
            <div class="tab" id="login-tab">Fazer Login</div>
        </div>
        
        <!-- Formulário de Verificação de CPF do Responsável -->
        <div id="verificar-form">
            <div class="form-group">
                <label for="cpf-verificar">CPF do Responsável:</label>
                <input type="text" id="cpf-verificar" placeholder="Digite o CPF do responsável (ex: 123.456.789-00)" maxlength="14">
            </div>
            
            <button class="btn" id="btn-verificar">Verificar Aluno</button>
            
            <div id="verificar-message" class="message hidden"></div>
        </div>
        
        <!-- Formulário de Cadastro de Senha -->
        <div id="cadastro-form" class="hidden">
            <div class="form-group">
                <label>Número de Matrícula:</label>
                <div class="prefix-input">
                    <span class="prefix">SA</span>
                    <input type="text" id="matricula-cadastro" disabled>
                </div>
            </div>
            
            <div class="form-group">
                <label for="nome-cadastro">Nome do Aluno:</label>
                <input type="text" id="nome-cadastro" disabled>
            </div>
            
            <div class="form-group">
                <label for="nome-responsavel-cadastro">Nome do Responsável:</label>
                <input type="text" id="nome-responsavel-cadastro" disabled>
            </div>
            
            <div class="form-group">
                <label for="senha-cadastro">Defina sua Senha:</label>
                <input type="password" id="senha-cadastro" placeholder="Mínimo de 6 caracteres">
            </div>
            
            <div class="form-group">
                <label for="confirmar-senha">Confirme sua Senha:</label>
                <input type="password" id="confirmar-senha" placeholder="Digite a senha novamente">
            </div>
            
            <button class="btn" id="btn-cadastrar">Cadastrar Senha</button>
            
            <div id="cadastro-message" class="message hidden"></div>
        </div>
        
        <!-- Formulário de Login -->
        <div id="login-form" class="hidden">
            <div class="form-group">
                <label for="matricula-login">Número de Matrícula:</label>
                <div class="prefix-input">
                    <span class="prefix">SA</span>
                    <input type="text" id="matricula-login" placeholder="Digite o número da matrícula (ex: 20259999)" maxlength="8">
                </div>
            </div>
            
            <div class="form-group">
                <label for="senha-login">Senha:</label>
                <input type="password" id="senha-login" placeholder="Digite sua senha">
            </div>
            
            <button class="btn" id="btn-login">Entrar</button>
            
            <div id="login-message" class="message hidden"></div>
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
// Cadastrar senha
btnCadastrar.addEventListener('click', function() { // Removido o parêntese extra
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