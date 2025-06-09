<?php
// Iniciar sessão
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// CORREÇÃO: Verificar se o usuário logado é um professor
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'professor') {
    // Redirecionar para a página adequada com mensagem de erro
    $_SESSION['erro_login'] = "Acesso negado. Você não tem permissão para acessar esta área.";
    header('Location: ../index.php');
    exit;
}

// Configuração do banco de dados
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];


// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// Pegar informações do usuário
$usuario_id = $_SESSION["usuario_id"] ?? '';
$usuario_nome = $_SESSION["usuario_nome"] ?? '';
$usuario_foto = $_SESSION["usuario_foto"] ?? '';

// Buscar informações adicionais do professor no banco de dados
if (!empty($usuario_id)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM professor WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($professor) {
            // Se encontrou o professor, atualiza as informações da sessão
            if (empty($usuario_nome)) {
                $usuario_nome = $professor['nome'];
                $_SESSION["usuario_nome"] = $usuario_nome;
                $usuario_foto = $baseUrl . '/uploads/fotos/default.png';
            }
            
            // Adiciona outras informações do professor
            $usuario_email = $professor['email'];
            $usuario_telefone = $professor['telefone'];
        }
    } catch (PDOException $e) {
        // Log do erro
        error_log("Erro ao buscar professor: " . $e->getMessage());
    }
}

// Buscar turmas do professor
$turmas = [];
try {
    $stmt = $pdo->prepare("SELECT t.*, u.nome as nome_unidade, u.endereco as endereco_unidade, 
                         u.telefone as telefone_unidade, u.coordenador 
                         FROM turma t 
                         JOIN unidade u ON t.id_unidade = u.id 
                         WHERE t.id_professor = ?");
    $stmt->execute([$usuario_id]);
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar turmas: " . $e->getMessage());
}

// Definir a URL base do projeto
$baseUrl = '';
// Detectar URL base automaticamente
// Substitua o trecho problemático em dashboard.php com este código

// Definir a URL base do projeto
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocolo . $host . '';

// Verificar e ajustar o caminho da foto para exibição
if (!empty($professor['foto'])) {
    // Obter apenas o nome do arquivo, independente do que esteja no banco
    $filename = basename($professor['foto']);
    
    // Definir o caminho correto da foto - caminho direto e absoluto
    $usuario_foto = $baseUrl . '/uploads/fotos/' . $filename;
    
    /* Debug - para ver os diretórios verificados (pode remover depois)
    echo "Diretórios verificados:<br>";
    $possiveisDiretorios = [
        '../uploads/fotos/',
        '../../uploads/fotos/',
        '/uploads/fotos/',
        'uploads/fotos/',
        '../superacao/uploads/fotos/',
        '../../superacao/uploads/fotos/',
        './superacao/uploads/fotos/',
        '../superacao/uploads/fotos/'
    ];
    */
    foreach ($possiveisDiretorios as $dir) {
        echo htmlspecialchars($dir) . " - " . 
             (is_dir($dir) ? "Existe" : "Não existe") . "<br>";
    }
    
    // echo "Caminho final da foto: " . htmlspecialchars($usuario_foto) . "<br>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor - Escolinha de Futebol</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/style.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root {
    --primary: #E30613;        
    --primary-light: #9c0202;   
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
    background-image: url('/superacao/uploads/fotos/soccer-pattern-light.png');
    background-repeat: repeat;
    background-size: 200px;
    background-attachment: fixed;
}

.header {
    background: var(--primary);
    color: var(--white);
    padding: 0.5rem;
    box-shadow: var(--box-shadow);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1800px;
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
    color: #9c0202;
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

#modalTitle, #modalTitlePerfil, #modalTitleAlunos {
    color: var(--primary);
    margin-bottom: 20px;
    border-bottom: 2px solid var(--gray-light);
    padding-bottom: 10px;
    font-weight: 600;
}

.turma-item {
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--box-shadow);
    border-left: 4px solid var(--primary);
    transition: var(--transition);
}

.turma-item:hover {
    transform: translateY(-5px);
    box-shadow: var(--box-shadow-hover);
}

.turma-item h3 {
    color: var(--primary);
    margin-bottom: 15px;
    font-size: 1.2rem;
    font-weight: 600;
    border-bottom: 1px solid var(--gray-light);
    padding-bottom: 10px;
}

.turma-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 15px;
    gap: 10px;
}

.status-em-andamento {
    color: var(--accent);
    font-weight: 600;
}

.status-planejada {
    color: var(--secondary-dark);
    font-weight: 600;
}

.status-concluída, .status-concluida {
    color: var(--primary);
    font-weight: 600;
}

.status-cancelada {
    color: var(--danger);
    font-weight: 600;
}

.aluno-item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: 15px;
    border-radius: var(--border-radius);
    margin-bottom: 12px;
    background-color: var(--light);
    transition: var(--transition);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.aluno-item:hover {
    background-color: var(--light-hover);
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.aluno-foto {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    object-fit: cover;
    background-color: var(--gray-light);
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    border: 2px solid var(--white);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.aluno-foto img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.aluno-foto i {
    font-size: 22px;
    color: var(--gray);
}

.aluno-info {
    flex: 1;
    min-width: 180px;
}

.aluno-nome {
    font-weight: 600;
    font-size: 1rem;
    color: var(--dark);
    margin-bottom: 4px;
}

.aluno-dados {
    font-size: 0.85rem;
    color: var(--gray-dark);
}

.aluno-acoes {
    display: flex;
    gap: 8px;
    margin-left: auto;
    flex-wrap: wrap;
}

.aluno-acoes .btn {
    padding: 6px 12px;
    font-size: 0.85rem;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aluno-acoes .btn i {
    margin-right: 6px;
}

/* Botões específicos */
.btn-success {
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
}

.btn-info {
    background: linear-gradient(135deg, #0288d1, #0277bd);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
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

.perfil-foto-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: var(--gray-light);
    border: 3px solid var(--primary);
    box-shadow: var(--box-shadow);
}

.perfil-foto-placeholder i {
    font-size: 60px;
    color: var(--gray);
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
    text-decoration: none;
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

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.btn-ver-alunos{
    font-size: 0.9rem;
    background: #0d2d56;
    border-radius: 5px;
    color: var(--white);
    margin-left: 15px;
    width: 100px;
}
.btn-editar-turma {
    font-size: 0.9rem;
    background: #0d2d56;
    border-radius: var(--border-radius);
    color: var(--white);
    margin-left: 15px;
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
    box-shadow: 0 0 0 3px rgba(13, 86, 35, 0.1);
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

.main-footer {
    background: var(--primary);
    color: var(--white);
    padding: 15px 0;
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

.alert-info {
    background-color: rgba(0, 123, 255, 0.1);
    color: #0066cc;
    border: 1px solid rgba(0, 123, 255, 0.2);
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: var(--border-radius);
    font-weight: 500;
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

#alunosModal .modal-content {
    max-height: 90vh;
    overflow-y: auto;
    padding: 20px;
}

.alunos-section {
    margin-top: 20px;
}

@media (max-width: 992px) {
    .aluno-acoes {
        flex-wrap: wrap;
    }
    
    .aluno-acoes .btn {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
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
        flex: 0 1 100%; 
        width: 100%;
        min-height: auto;
    }
    
    .welcome-card h1 {
        font-size: 1.5rem;
    }
    
    .aluno-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .aluno-foto {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .aluno-info {
        width: 100%;
        margin-bottom: 15px;
    }
    
    .aluno-acoes {
        width: 100%;
        justify-content: flex-start;
        margin-left: 0;
    }
    
    .aluno-acoes .btn {
        flex: 1;
        max-width: calc(50% - 5px);
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
        font-size: 1.3rem;
    }
    
    .aluno-acoes {
        flex-direction: column;
    }
    
    .aluno-acoes .btn {
        width: 100%;
        max-width: none;
        margin-bottom: 6px;
        margin-right: 0;
    }
    
    #alunosModal .modal-content {
        padding: 15px 10px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 10px;
    }
    
    .logout-btn {
        align-self: flex-end;
    }
}

@media (max-width: 480px) {
    .perfil-foto, .perfil-foto-placeholder {
        width: 100px;
        height: 100px;
    }
    
    .perfil-foto-placeholder i {
        font-size: 50px;
    }
    
    .user-details h3 {
        font-size: 0.9rem;
    }
    
    .user-details p {
        font-size: 0.75rem;
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

.logo img {
    height: 100px;
    width: auto;
    padding: 3px;
    transition: transform 0.3s ease;
}
 
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="user-info">
                <div class="logo">
                    <img src="img/logobo.png" alt="Logo SuperAção">
                </div>
                <div class="user-avatar">
                <?php if (!empty($usuario_foto)): ?>
                        <img src="<?php echo htmlspecialchars($usuario_foto); ?>" alt="Foto do usuário">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($usuario_nome); ?></h3>
                    <p>Professor</p>
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
            <p>Área do Professor da Escolinha de Futebol. Aqui você pode gerenciar suas turmas, acompanhar o desenvolvimento dos alunos e acessar o calendário de treinos e competições.</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card" id="card-turmas">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h2>Minhas Turmas</h2>
                <p>Gerencie seus alunos e turmas.</p>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Avaliações</h2>
                <p>Avalie seus alunos</p>
            </div>
            
            <div class="dashboard-card" id="card-atividades">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h2>Atividades</h2>
                <p>Cadastre e gerencie atividades para suas turmas.</p>
            </div>
            

            <div class="dashboard-card" id="card-perfil">
                <div class="card-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h2>Meu Perfil</h2>
                <p>Atualize suas informações pessoais e configurações da conta.</p>
            </div>


            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Agenda</h2>
                <p>Em desenvolvimento......</p>
            </div>
            
            <!-- <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <h2>Planos de Treino</h2>
                <p>Crie e consulte planos de treinamento para suas turmas.</p>
            </div> -->
            
            
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <h2>Comunicados</h2>
                <p>Em desenvolvimento......</p>
            </div>
            
        </div>
        
        <!-- Modal de Turmas -->
        <div id="turmasModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2 id="modalTitle">Minhas Turmas</h2>
                
                <?php if (empty($turmas)): ?>
                    <div class="alert alert-info">
                        Você ainda não possui turmas atribuídas.
                    </div>
                <?php else: ?>
                    <?php foreach ($turmas as $index => $turma): ?>
                        <div class="turma-item <?php echo ($index === 0) ? 'turma-ativa' : ''; ?>" data-turma-id="<?php echo $turma['id']; ?>">
                            <h3><?php echo htmlspecialchars($turma['nome_turma']); ?></h3>
                            <div class="matricula-group">
                                <label>Unidade:</label>
                                <p><?php echo htmlspecialchars($turma['nome_unidade']); ?></p>
                            </div>
                            <div class="matricula-group">
                                <label>Horário:</label>
                                <p><?php echo htmlspecialchars($turma['horario_inicio']) . ' às ' . htmlspecialchars($turma['horario_fim']); ?></p>
                            </div>
                            <div class="matricula-group">
                                <label>Dias:</label>
                                <p><?php echo htmlspecialchars($turma['dias_aula']); ?></p>
                            </div>
                            <div class="matricula-group">
                                <label>Alunos:</label>
                                <p><?php echo htmlspecialchars($turma['matriculados']); ?> / <?php echo htmlspecialchars($turma['capacidade']); ?></p>
                            </div>
                            <div class="matricula-group">
                                <label>Status:</label>
                                <p class="status-<?php echo strtolower(str_replace(' ', '-', $turma['status'])); ?>">
                                    <?php echo htmlspecialchars($turma['status']); ?>
                                </p>
                            </div>
                            <div class="matricula-group">
                                <label>Coordenador:</label>
                                <p><?php echo htmlspecialchars($turma['coordenador']); ?></p>
                            </div>
                            
                            <div class="turma-actions">
                                <button class="btn btn-ver-alunos" data-turma-id="<?php echo $turma['id']; ?>">
                                    <i class="fas fa-users"></i> Ver Alunos
                                </button>
                               <!-- <button class="btn btn-editar-turma" data-turma-id="<?php echo $turma['id']; ?>">
                                    <i class="fas fa-edit"></i> Editar
                                </button> -->
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                        <?php if (!empty($usuario_foto)): ?>
                            <img src="<?php echo htmlspecialchars($usuario_foto); ?>" id="p-foto" class="perfil-foto" alt="Foto do professor">
                        <?php else: ?>
                            <div class="perfil-foto-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Dados Pessoais</h3>
                        <div class="data-item">
                            <strong>Nome:</strong> <span><?php echo htmlspecialchars($usuario_nome); ?></span>
                        </div>
                        <?php if (!empty($professor)): ?>
                        <div class="data-item">
                            <strong>Email:</strong> <span><?php echo htmlspecialchars($usuario_email ?? ''); ?></span>
                        </div>
                        <div class="data-item">
                            <strong>Telefone:</strong> <span><?php echo htmlspecialchars($usuario_telefone ?? ''); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Dados Profissionais</h3>
                        <div class="data-item">
                            <strong>Total de Turmas:</strong> <span><?php echo count($turmas); ?></span>
                        </div>
                        <?php if (!empty($turmas)): ?>
                        <div class="data-item">
                            <strong>Unidades:</strong> 
                            <span>
                                <?php 
                                $unidades = array_unique(array_column($turmas, 'nome_unidade')); 
                                echo htmlspecialchars(implode(', ', $unidades));
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="perfil-section">
                        <h3>Acesso ao Sistema</h3>
                        <div class="data-item">
                            <strong>ID:</strong> <span><?php echo htmlspecialchars($usuario_id); ?></span>
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
                    
                    <form id="form-editar-perfil" method="post" action="api/atualizar_professor.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $usuario_id; ?>">
                        
                        <div class="text-center">
                            <?php if (!empty($usuario_foto)): ?>
                                <img src="<?php echo htmlspecialchars($usuario_foto); ?>" id="preview-foto" class="perfil-foto" alt="Foto do professor">
                            <?php else: ?>
                                <div class="perfil-foto-placeholder" id="preview-foto-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                                <img src="" id="preview-foto" class="perfil-foto" style="display:none;" alt="Foto do professor">
                            <?php endif; ?>
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
                                        <input type="text" id="edit-nome" name="nome" value="<?php echo htmlspecialchars($usuario_nome); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-email" class="form-label">Email:</label>
                                        <input type="email" id="edit-email" name="email" value="<?php echo htmlspecialchars($usuario_email ?? ''); ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="edit-telefone" class="form-label">Telefone:</label>
                                        <input type="text" id="edit-telefone" name="telefone" value="<?php echo htmlspecialchars($usuario_telefone ?? ''); ?>" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="perfil-section">
                            <h3>Senha</h3>
                            <div class="form-group">
                                <label for="edit-senha" class="form-label">Nova senha (deixe em branco para manter):</label>
                                <input type="password" id="edit-senha" name="senha" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="edit-confirma-senha" class="form-label">Confirmar senha:</label>
                                <input type="password" id="edit-confirma-senha" name="confirma_senha" class="form-control">
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
        
      
        <div id="alunosModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeAlunosModal">&times;</span>
                <h2 id="modalTitleAlunos">Alunos da Turma</h2>
                
                <div id="alunos-lista-container">
                    <p>Carregando lista de alunos...</p>
                </div>
            </div>
        </div>
        
    </div>

    <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <i class="fas fa-futbol"></i> Bombeiros Mirins
        </div>
        <div class="footer-info">
          <p>© 2025 Projeto Bombeiro Mirim Goiás – O Projeto Bombeiro Mirim é uma iniciativa do Corpo de Bombeiros Militar do Estado de Goiás em parceria com instituições locais, voltado à formação cidadã de crianças e adolescentes.</p>
          <p>Área do Professor</p>
          <p>Desenvolvido por <a href="https://www.instagram.com/assego/" class="ftlink">@Assego</a></p>
        </div>
      </div>
    </div>
  </footer>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="js/teste1.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validation/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html> 