<?php
session_start();

// Verificar se o usuário está logado e é um aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    // Definir mensagem de erro na sessão
    $_SESSION['erro_login'] = "Você precisa estar logado como aluno para acessar esta página.";
    
    // Redirecionar para a página de login do aluno
    header("Location: ../index.php");
    exit;
}

$usuario_nome = $_SESSION["usuario_nome"];
$usuario_matricula = $_SESSION["usuario_matricula"];
$usuario_foto = isset($_SESSION["usuario_foto"]) ? $_SESSION["usuario_foto"] : '';
$usuario_id = isset($_SESSION["usuario_id"]) ? $_SESSION["usuario_id"] : '';
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
    <title>Painel do Aluno - Projeto Superação</title>
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

/* Header */
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

/* Container */
.container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem;
    flex: 1;
}

/* Welcome Card */
.welcome-card {
    background-color: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    margin-bottom: 2rem;
    transition: box-shadow 0.3s ease, transform 0.3s ease;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.welcome-card:hover {
    box-shadow: var(--box-shadow-hover);
    transform: translateY(-5px);
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, var(--secondary-light), var(--secondary));
    opacity: 0.05;
    border-radius: 50%;
    transform: translate(30%, -30%);
    z-index: 0;
}

.welcome-card h1 {
    color: var(--primary);
    margin-bottom: 1rem;
    font-size: 1.8rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.welcome-card p {
    color: var(--gray-dark);
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.dashboard-card {
    background-color: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.dashboard-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--box-shadow-hover);
}

.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(to right, var(--primary), var(--primary-light));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.dashboard-card:hover::before {
    opacity: 1;
}

.dashboard-card:nth-child(2)::before {
    background: linear-gradient(to right, var(--accent), var(--accent-light));
}

.dashboard-card:nth-child(3)::before {
    background: linear-gradient(to right, var(--secondary), var(--secondary-light));
}

.card-icon {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: var(--white);
    width: 50px;
    height: 50px;
    border-radius: var(--border-radius);
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    box-shadow: var(--box-shadow);
    position: relative;
    transition: all 0.3s ease;
}

.dashboard-card:nth-child(2) .card-icon {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
}

.dashboard-card:nth-child(3) .card-icon {
    background: linear-gradient(135deg, var(--secondary), var(--secondary-light));
}

.dashboard-card h2 {
    color: var(--dark);
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
    font-weight: 600;
    transition: color 0.3s ease;
}

.dashboard-card:hover h2 {
    color: var(--primary);
}

.dashboard-card p {
    color: var(--gray);
    font-size: 0.9rem;
}

.dashboard-card::after {
    content: '\f054';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    bottom: 20px;
    right: 20px;
    color: var(--gray-light);
    transition: transform 0.3s ease, color 0.3s ease;
    opacity: 0;
    transform: translateX(-10px);
}

.dashboard-card:hover::after {
    opacity: 1;
    transform: translateX(0);
    color: var(--primary);
}

.modal, .perfil-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
    backdrop-filter: blur(4px);
}

.modal-content, .perfil-content {
    background-color: var(--white);
    margin: 5% auto;
    padding: 25px;
    width: 80%;
    max-width: 600px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow-hover);
    max-height: 90vh;
    overflow-y: auto;
    animation: modal-fade-in 0.3s ease;
}

@keyframes modal-fade-in {
    from { opacity: 0; transform: translateY(-20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.close {
    color: var(--gray);
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease, transform 0.3s ease;
}

.close:hover {
    color: var(--danger);
    transform: rotate(90deg);
}

#modalTitle, #modalTitlePerfil {
    color: var(--primary);
    margin-bottom: 20px;
    border-bottom: 2px solid var(--gray-light);
    padding-bottom: 10px;
    font-weight: 600;
}

.matricula-group {
    margin-bottom: 15px;
    display: flex;
    border-bottom: 1px solid var(--gray-light);
    padding-bottom: 12px;
    transition: background-color 0.2s ease;
}

.matricula-group:hover {
    background-color: var(--light);
}

.matricula-group label {
    font-weight: 600;
    width: 180px;
    color: var(--gray-dark);
}

.matricula-group p {
    margin: 0;
    flex: 1;
    color: var(--dark);
}

#m-status-matricula {
    font-weight: 600;
}

.status-ativo {
    color: var(--accent);
}

.status-pendente {
    color: var(--secondary-dark);
}

.status-inativo {
    color: var(--danger);
}

.perfil-foto {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 20px;
    display: block;
    border: 3px solid var(--primary);
    box-shadow: var(--box-shadow);
    transition: transform 0.3s ease;
}

.perfil-foto:hover {
    transform: scale(1.05);
}

.perfil-section {
    margin-bottom: 25px;
}

.perfil-section h3 {
    color: var(--primary);
    border-bottom: 1px solid var(--gray-light);
    padding-bottom: 10px;
    margin-bottom: 15px;
    font-size: 18px;
    font-weight: 600;
}

.data-item {
    margin-bottom: 12px;
    display: flex;
    padding: 8px 0;
}

.data-item:not(:last-child) {
    border-bottom: 1px dashed var(--gray-light);
}

.data-item strong {
    font-weight: 600;
    color: var(--gray-dark);
    min-width: 150px;
    display: inline-block;
}

.responsavel-item {
    background-color: var(--light);
    padding: 15px;
    border-radius: var(--border-radius);
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn {
    display: inline-block;
    font-weight: 500;
    color: var(--white);
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border: none;
    padding: 10px 16px;
    font-size: 14px;
    line-height: 1.5;
    border-radius: var(--border-radius);
    transition: all 0.3s;
    margin-right: 8px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--box-shadow-hover);
}

.btn:active {
    transform: translateY(-1px);
}

.btn-secondary {
    background: linear-gradient(135deg, var(--gray), var(--gray-dark));
}

.text-center {
    text-align: center;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--gray-dark);
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 14px;
    transition: all 0.2s ease;
    background-color: var(--light);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(13, 45, 86, 0.1);
    background-color: var(--white);
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.form-col {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 10px;
}

/* Footer Atualizado */
.main-footer {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: var(--white);
    padding: 20px 0;
    margin-top: auto;
    position: relative;
    text-align: center;
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

/* Alerts */
.alert {
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: var(--border-radius);
    font-weight: 500;
}

.alert-success {
    background-color: rgba(52, 199, 89, 0.1);
    color: var(--accent);
    border: 1px solid rgba(52, 199, 89, 0.2);
}

.alert-danger {
    background-color: rgba(255, 59, 48, 0.1);
    color: var(--danger);
    border: 1px solid rgba(255, 59, 48, 0.2);
}

.responsavel-form-item {
    background-color: var(--light);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    border: 1px solid var(--gray-light);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.responsavel-form-item h4 {
    color: var(--primary);
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-light);
}

@media (max-width: 768px) {
    .form-col {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .data-item {
        flex-direction: column;
    }
    
    .data-item strong {
        margin-bottom: 5px;
    }
    
    .matricula-group {
        flex-direction: column;
    }
    
    .matricula-group label {
        margin-bottom: 5px;
        width: 100%;
    }
    
    .modal-content, .perfil-content {
        width: 95%;
        padding: 20px 15px;
    }
    
    .dashboard-card {
        min-height: auto;
    }
}

@media (max-width: 576px) {
    .container {
        padding: 1rem;
    }
    
    .welcome-card {
        padding: 1.5rem;
    }
    
    .welcome-card h1 {
        font-size: 1.5rem;
    }
}

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--light);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-light);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
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
        <div class="welcome-card">
            <h1>Bem-vindo, <?php echo htmlspecialchars($usuario_nome); ?>!</h1>
            <p>Área do aluno. Aqui você pode acessar suas informações.</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card" id="card-matricula">
                <div class="card-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h2>Minha Matrícula</h2>
                <p>Veja os dados da sua matrícula.</p>
            </div>

            <!--
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Notas e Desempenho</h2>
                <p>Faça o envio do seu Boletim escolar.</p>
            </div> -->
            <!--
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Calendário</h2>
                <p>Veja o calendário de aulas, eventos e datas importantes.</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h2>Atividades</h2>
                <p>Acompanhe suas atividades, trabalhos e projetos pendentes.</p>
            </div> -->
            
            <div class="dashboard-card" id="card-perfil">
                <div class="card-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h2>Meu Perfil</h2>
                <p>Atualize suas informações pessoais e configurações da conta.</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Avaliações</h2>
                <p>Veja sua avaliação</p>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <h2>Mensagens</h2>
                <p>Em desenvolvimento.....</p>
            </div>
            
        </div>
        
        <!-- Modal de Matrícula -->
        <div id="gerenciaModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2 id="modalTitle">Minha matrícula</h2>
                                
                <div class="matricula-group">
                    <label>Nome:</label>
                    <p id="m-nome-aluno"></p>
                </div>
                <div class="matricula-group">
                    <label>Número matrícula:</label>
                    <p id="m-matricula-aluno"></p>
                </div>
                <div class="matricula-group">
                    <label>Data matrícula:</label>
                    <p id="m-data-matricula"></p>
                </div>
                <div class="matricula-group">
                    <label>Status matrícula:</label>
                    <p id="m-status-matricula"></p>
                </div>
                <div class="matricula-group">
                    <label>Unidade:</label>
                    <p id="m-unidade"></p>
                </div>
                <div class="matricula-group">
                    <label>Endereço:</label>
                    <p id="m-unidade-endereco"></p>
                </div>
                <div class="matricula-group">
                    <label>Telefone:</label>
                    <p id="m-unidade-telefone"></p>
                </div>
                <div class="matricula-group">
                    <label>Coordenador:</label>
                    <p id="m-unidade-coordenador"></p>
                </div>
                <div class="matricula-group">
                    <label>Turma:</label>
                    <p id="m-turma"></p>
                </div>
            </div>
        </div>
        
        <!-- Modal de Perfil -->
        <div id="perfilModal" class="perfil-modal">
            <div class="perfil-content">
                <span class="close" id="closePerfilModal">&times;</span>
                
                <!-- Seção de visualização do perfil -->
                <div id="visualizar-perfil">
                    <h2 id="modalTitlePerfil">Meu Perfil</h2>
                    
                    <div class="text-center">
                        <img src="" id="p-foto" class="perfil-foto" alt="Foto do aluno">
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Dados Pessoais</h3>
                        <div class="data-item">
                            <strong>Nome:</strong> <span id="p-nome"></span>
                        </div>
                        <div class="data-item">
                            <strong>Data de Nascimento:</strong> <span id="p-data-nascimento"></span>
                        </div>
                        <div class="data-item">
                            <strong>RG:</strong> <span id="p-rg"></span>
                        </div>
                        <div class="data-item">
                            <strong>CPF:</strong> <span id="p-cpf"></span>
                        </div>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Dados Escolares</h3>
                        <div class="data-item">
                            <strong>Escola:</strong> <span id="p-escola"></span>
                        </div>
                        <div class="data-item">
                            <strong>Série:</strong> <span id="p-serie"></span>
                        </div>
                        <div class="data-item">
                            <strong>Número de Matrícula:</strong> <span id="p-matricula"></span>
                        </div>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Informações de Saúde</h3>
                        <div class="data-item">
                            <p id="p-info-saude"></p>
                        </div>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Endereço</h3>
                        <div class="data-item">
                            <p id="p-endereco"></p>
                        </div>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Responsáveis</h3>
                        <div id="p-responsaveis-container">
                            <!-- Responsáveis serão inseridos aqui via JavaScript -->
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" id="btn-editar-perfil" class="btn">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </button>
                        
                    </div>
                </div>
                
                <!-- Seção de edição do perfil -->
                <div id="editar-perfil" style="display:none;">
                    <h2>Editar Perfil</h2>
                    
                    <div id="mensagem-resultado"></div>
                    
                    <form id="form-editar-perfil" enctype="multipart/form-data">
                        <input type="hidden" id="aluno-id" name="aluno_id" value="<?php echo $usuario_id; ?>">
                        
                        <div class="text-center">
                            <img src="" id="preview-foto" class="perfil-foto" alt="Foto do aluno">
                            <div class="form-group">
                                <label for="foto" class="form-label">Alterar foto:</label>
                                <input type="file" id="foto" name="foto" class="form-control">
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Dados Pessoais</h3>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-nome" class="form-label">Nome:</label>
                                        <input type="text" id="edit-nome" name="nome" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-data-nascimento" class="form-label">Data de Nascimento:</label>
                                        <input type="date" id="edit-data-nascimento" name="data_nascimento" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-rg" class="form-label">RG:</label>
                                        <input type="text" id="edit-rg" name="rg" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-cpf" class="form-label">CPF:</label>
                                        <input type="text" id="edit-cpf" name="cpf" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Dados Escolares</h3>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-escola" class="form-label">Escola:</label>
                                        <input type="text" id="edit-escola" name="escola" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-serie" class="form-label">Série:</label>
                                        <input type="text" id="edit-serie" name="serie" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Informações de Saúde</h3>
                            <div class="form-group">
                                <textarea id="edit-info-saude" name="info_saude" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Senha</h3>
                            <div class="form-group">
                                <label for="edit-senha" class="form-label">Nova senha (deixe em branco para manter):</label>
                                <input type="password" id="edit-senha" name="senha" class="form-control">
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Endereço</h3>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-cep" class="form-label">CEP:</label>
                                        <input type="text" id="edit-cep" name="cep" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-logradouro" class="form-label">Logradouro:</label>
                                        <input type="text" id="edit-logradouro" name="logradouro" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-numero" class="form-label">Número:</label>
                                        <input type="text" id="edit-numero" name="numero" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-complemento" class="form-label">Complemento:</label>
                                        <input type="text" id="edit-complemento" name="complemento" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-bairro" class="form-label">Bairro:</label>
                                        <input type="text" id="edit-bairro" name="bairro" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-cidade" class="form-label">Cidade:</label>
                                        <input type="text" id="edit-cidade" name="cidade" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="perfil-section">
                        <h3>Responsáveis</h3>
                        <div id="responsaveis-form-container">
                            <!-- Será preenchido via JavaScript com os formulários de edição dos responsáveis -->
                        </div>
                    </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                            <button type="button" id="btn-cancelar-edicao" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <i class="fas fa-graduation-cap"></i> Superação - Ninho de Águias
        </div>
        <div class="footer-info">
          <p>© 2024 Projeto SuperAção - O Projeto Superação é uma iniciativa da ASSEGO – Associação dos Subtenentes e Sargentos da PM e BM do Estado de Goiás</p>
          <p>Painel de Gerenciamento de Matrículas</p>
          <p>Desenvolvido por <a href="https://www.instagram.com/assego/" class="ftlink">@Assego</a></p>
        </div>
      </div>
    </div>
  </footer>
    
    <script>
        // Debug para mostrar o caminho da foto (remover em produção)
        <?php if (!empty($usuario_foto)): ?>
        //console.log('Caminho da foto: <?php echo $usuario_foto; ?>');
        <?php endif; ?>
    </script>

    <script src="./js/dashboard.js"></script>
</body>
</html>