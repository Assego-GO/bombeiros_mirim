<?php
session_start();

// ===== CONFIGURAÇÃO DE TIMEZONE E CHARSET BRASIL =====
// Definir fuso horário do Brasil (Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Configurar locale para português brasileiro
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// Configurar charset para UTF-8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
  header('Location: index.php');
  exit;
}
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
  // Conexão com o banco de dados com configurações UTF-8 e timezone
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
  ]);
  
  // Configurar timezone do MySQL para o Brasil
  $pdo->exec("SET time_zone = '-03:00'");
  
  // Verificar se o usuário é um administrador
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'admin'");
  $stmt->execute([$_SESSION['usuario_id']]);

  if ($stmt->rowCount() == 0) {
    // Não é um administrador
    header('Location: ../aluno/dashboard.php');
    exit;
  }
  
} catch(PDOException $e) {
  // Em caso de erro no banco de dados
  die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_tipo = 'Administrador';
$usuario_foto = './img/usuarios/' . ($_SESSION['usuario_foto'] ?? 'default.png');

// Função auxiliar para formatar data/hora brasileira
function formatarDataHoraBrasil($datetime, $formato = 'd/m/Y H:i:s') {
    if (empty($datetime)) return '';
    
    $dt = new DateTime($datetime);
    $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    return $dt->format($formato);
}

// Função para obter data/hora atual do Brasil
function agora() {
    $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    return $dt->format('Y-m-d H:i:s');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bombeiro Mirim - Módulo de Matrículas</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="./css/matricula.css"/>
    <style>
    .user-info-container {
      position: relative;
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    .user-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }
    .dropdown-menu {
      position: absolute;
      top: 55px;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: none;
      z-index: 999;
    }
    .dropdown-menu.show {
      display: block;
    }
    .dropdown-menu a {
      display: block;
      padding: 10px 15px;
      color: #333;
      text-decoration: none;
    }
    .dropdown-menu a:hover {
      background: #f0f0f0;
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

/* CSS para Sistema de Comunicados */

/* Garantir que todos os inputs tenham a mesma estilização */
.form-group input,
.form-group select,
.form-group textarea,
.form-group input[list] {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background-color: #fff;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus,
.form-group input[list]:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

/* ===== ESTILOS MELHORADOS PARA CHECKBOX ===== */

/* Container do checkbox customizado */
.checkbox-container {
    display: flex !important;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    margin: 0 !important;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    user-select: none;
    width: fit-content !important;
    max-width: 300px;
}

.checkbox-container:hover {
    background: #e9ecef;
    border-color: #dc3545;
}

/* Input checkbox oculto */
.checkbox-container input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    margin: 0;
    padding: 0;
}

/* Estilo do checkbox customizado */
.checkbox-container::before {
    content: '';
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border: 2px solid #ced4da;
    border-radius: 4px;
    background: white;
    transition: all 0.3s ease;
    flex-shrink: 0;
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 12px;
    color: white;
}

/* Estado checado */
.checkbox-container input[type="checkbox"]:checked + .checkbox-label::before,
.checkbox-container:has(input[type="checkbox"]:checked)::before {
    background: #dc3545;
    border-color: #dc3545;
    content: '\f00c'; /* Ícone de check do Font Awesome */
}

/* Label do checkbox */
.checkbox-label {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    line-height: 1.4;
    margin: 0 !important;
    width: auto !important;
    padding: 0 !important;
}

/* Estado ativo quando checado */
.checkbox-container:has(input[type="checkbox"]:checked) {
    background: #fff5f5;
    border-color: #dc3545;
}

.checkbox-container:has(input[type="checkbox"]:checked) .checkbox-label {
    color: #dc3545;
    font-weight: 600;
}

/* Estado de foco para acessibilidade */
.checkbox-container input[type="checkbox"]:focus + .checkbox-label::before,
.checkbox-container:has(input[type="checkbox"]:focus)::before {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
}

/* Versão alternativa com switch (opcional) */
.switch-checkbox {
    display: flex !important;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    margin: 0 !important;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    user-select: none;
    width: fit-content !important;
    max-width: 300px;
}

.switch-checkbox input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.switch-slider {
    position: relative;
    width: 44px;
    height: 24px;
    background: #ced4da;
    border-radius: 24px;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.switch-slider::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.switch-checkbox input[type="checkbox"]:checked + .switch-slider {
    background: #dc3545;
}

.switch-checkbox input[type="checkbox"]:checked + .switch-slider::before {
    transform: translateX(20px);
}

.switch-checkbox:hover {
    background: #e9ecef;
    border-color: #dc3545;
}

.switch-checkbox:has(input[type="checkbox"]:checked) {
    background: #fff5f5;
    border-color: #dc3545;
}

/* Modal Grande */
.modal-large {
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-medium {
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

/* Abas do Modal */
.modal-tabs {
    display: flex;
    border-bottom: 2px solid #f0f0f0;
    background: #fafafa;
    margin: 0 -20px;
    padding: 0 20px;
}

.tab-comunicado {
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-comunicado:hover {
    color: var(--primary);
    background: #f9f9f9;
}

.tab-comunicado.ativo {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: white;
}

/* Conteúdo das Abas */
.tab-content-comunicado {
    padding: 20px 0;
}

/* Formulário de Comunicado */
#form-comunicado .form-group {
    margin-bottom: 20px;
}

#form-comunicado label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

#form-comunicado input,
#form-comunicado textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

#form-comunicado input:focus,
#form-comunicado textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

#conteudo-comunicado {
    resize: vertical;
    min-height: 150px;
    font-family: inherit;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

/* Header dos Comunicados */
.comunicados-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.comunicados-header h3 {
    margin: 0;
    color: #333;
}

.comunicados-stats {
    color: #666;
    font-size: 14px;
}

/* Lista de Comunicados */
.lista-comunicados {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}

.lista-comunicados::-webkit-scrollbar {
    width: 6px;
}

.lista-comunicados::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.lista-comunicados::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.lista-comunicados::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Item de Comunicado */
.comunicado-item {
    background: white;
    border: 2px solid #f0f0f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.comunicado-item:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.comunicado-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.comunicado-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
    flex: 1;
    margin-right: 15px;
}

.comunicado-acoes {
    display: flex;
    gap: 8px;
}

.comunicado-acoes .btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: 2px solid #dc3545;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #c82333;
}

/* Preview do Comunicado */
.comunicado-preview {
    color: #666;
    line-height: 1.5;
    margin-bottom: 15px;
    font-size: 14px;
}

/* Footer do Comunicado */
.comunicado-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #888;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.autor {
    font-weight: 500;
}

.data {
    color: #aaa;
}

/* Sem Comunicados */
.sem-comunicados {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.sem-comunicados i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ddd;
}

.sem-comunicados p {
    font-size: 16px;
    margin: 0;
}

/* Modal de Visualização */
.comunicado-visualizacao h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 24px;
    line-height: 1.3;
}

.comunicado-meta {
    display: flex;
    gap: 30px;
    margin-bottom: 25px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 14px;
}

.autor-info,
.data-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
}

.autor-info i,
.data-info i {
    color: var(--primary);
}

.comunicado-conteudo {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    line-height: 1.7;
    font-size: 15px;
    color: #333;
}

/* Notificações */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 350px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #28a745;
}

.notification-success i {
    color: #28a745;
}

.notification-info {
    border-left: 4px solid #17a2b8;
}

.notification-info i {
    color: #17a2b8;
}

/* correcao do modal de monitoramento */
/* ===== CENTRALIZAÇÃO ESPECÍFICA DOS MODAIS DE MONITORAMENTO ===== */

/* Modal de Monitoramento Principal */
#modal-monitoramento.modal-backdrop {
    position: fixed;
    margin-right: 40px;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex ;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}

#modal-monitoramento .modal-large {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow-y: auto;
    margin: 0;
    transform: none;
    animation: modalAppear 0.3s ease;
}

/* Modal de Detalhes da Atividade */
#modal-detalhes-atividade.modal-backdrop {
    position: fixed;
    margin-left: 40px;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex ;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}

#modal-detalhes-atividade .modal-medium {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    margin: 0;
    transform: none;
    animation: modalAppear 0.3s ease;
}

@keyframes modalAppear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsividade para mobile */
@media (max-width: 768px) {
    #modal-monitoramento .modal-large,
    #modal-detalhes-atividade .modal-medium {
        width: 95%;
        max-height: 95vh;
    }
    
    #modal-monitoramento.modal-backdrop,
    #modal-detalhes-atividade.modal-backdrop {
        padding: 10px;
    }
}

/* ===== ESTILOS DO MODAL DE MONITORAMENTO ===== */

/* Abas do Modal de Monitoramento */
.tab-monitoramento {
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-monitoramento:hover {
    color: var(--primary);
    background: #f9f9f9;
}

.tab-monitoramento.ativo {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: white;
}

/* Conteúdo das Abas de Monitoramento */
.tab-content-monitoramento {
    padding: 20px 0;
}

/* Cards de Resumo */
.cards-resumo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card-resumo {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.3s ease;
}

.card-resumo:hover {
    transform: translateY(-2px);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.card-resumo.planejadas .card-icon {
    background: linear-gradient(135deg, #ffa726, #ff7043);
}

.card-resumo.em-andamento .card-icon {
    background: linear-gradient(135deg, #42a5f5, #1e88e5);
}

.card-resumo.concluidas .card-icon {
    background: linear-gradient(135deg, #66bb6a, #43a047);
}

.card-resumo.canceladas .card-icon {
    background: linear-gradient(135deg, #ef5350, #e53935);
}

.card-info h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.card-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-weight: 500;
}

/* Seções de Status */
.status-sections {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.status-section {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-header {
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: white;
}

.section-header.planejadas {
    background: linear-gradient(135deg, #ffa726, #ff7043);
}

.section-header.em-andamento {
    background: linear-gradient(135deg, #42a5f5, #1e88e5);
}

.section-header.concluidas {
    background: linear-gradient(135deg, #66bb6a, #43a047);
}

.section-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header .count {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

/* Lista de Atividades */
.atividades-lista {
    padding: 20px;
    max-height: 300px;
    overflow-y: auto;
}

.atividade-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.atividade-item:hover {
    border-color: var(--primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.atividade-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.atividade-titulo {
    font-weight: 600;
    color: #333;
    margin: 0;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.planejada {
    background: #fff3cd;
    color: #856404;
}

.status-badge.em_andamento {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.concluida {
    background: #d4edda;
    color: #155724;
}

.status-badge.cancelada {
    background: #f8d7da;
    color: #721c24;
}

.atividade-detalhes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    font-size: 14px;
    color: #666;
}

.detalhe-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.detalhe-item i {
    color: var(--primary);
    width: 16px;
}

/* Filtros */
.filtros-atividades {
    display: flex;
    gap: 15px;
    align-items: end;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.filtro-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filtro-item label {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.filtro-item select,
.filtro-item input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

/* Lista de todas as atividades */
.todas-atividades-lista {
    max-height: 500px;
    overflow-y: auto;
}

/* Calendário */
.calendario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 10px;
}

.calendario-header h3 {
    margin: 0;
    color: #333;
}

.calendario {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.dia-calendario {
    background: white;
    padding: 10px;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dia-calendario.header-dia {
    background: #f8f9fa;
    min-height: auto;
    padding: 10px;
    text-align: center;
}

.dia-calendario.vazio {
    background: #f8f9fa;
}

.dia-numero {
    font-weight: 600;
    color: #333;
}

.atividade-calendario {
    background: var(--primary);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    cursor: pointer;
}

.atividade-calendario.planejada {
    background: #ffa726;
}

.atividade-calendario.em_andamento {
    background: #42a5f5;
}

.atividade-calendario.concluida {
    background: #66bb6a;
}

.atividade-calendario.cancelada {
    background: #ef5350;
}

/* Modal de Detalhes da Atividade */
.detalhes-atividade {
    padding: 20px 0;
}

.detalhes-atividade .atividade-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.detalhes-atividade .atividade-header h2 {
    margin: 0;
    color: #333;
}

.atividade-info {
    display: grid;
    gap: 20px;
}

.info-grupo h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-grupo p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.acoes-atividade {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

/* Estados vazios */
.sem-atividades {
    text-align: center;
    padding: 40px 20px;
    color: #888;
}

.sem-atividades i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ddd;
}

/* Responsividade */
@media (max-width: 768px) {
    .modal-large {
        width: 95%;
        margin: 20px auto;
    }

    .modal-tabs {
        flex-direction: column;
    }

    .tab-comunicado {
        text-align: center;
        border-bottom: none;
        border-right: 3px solid transparent;
    }

    .tab-comunicado.ativo {
        border-right-color: var(--primary);
        border-bottom: none;
    }

    .comunicado-header {
        flex-direction: column;
        gap: 15px;
    }

    .comunicado-header h3 {
        margin-right: 0;
    }

    .comunicado-meta {
        flex-direction: column;
        gap: 10px;
    }

    .form-actions {
        flex-direction: column;
    }

    .notification {
        left: 20px;
        right: 20px;
        max-width: none;
        transform: translateY(-100px);
    }

    .notification.show {
        transform: translateY(0);
    }
    
    .cards-resumo {
        grid-template-columns: 1fr;
    }
    
    .filtros-atividades {
        flex-direction: column;
        align-items: stretch;
    }
    
    .atividade-detalhes {
        grid-template-columns: 1fr;
    }
    
    .calendario {
        font-size: 12px;
    }
    
    .dia-calendario {
        min-height: 80px;
        padding: 5px;
    }

    /* Checkbox responsivo */
    .checkbox-container,
    .switch-checkbox {
        max-width: none;
        width: 100% !important;
    }
}

/* ===== ESTILOS DO MODAL DE RANKING ===== */

/* Modal de Ranking Principal */
#modal-ranking.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}

#modal-ranking .modal-large {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    overflow-y: auto;
    margin: 0;
    transform: none;
    animation: modalAppear 0.3s ease;
}

/* Abas do Modal de Ranking */
.tab-ranking {
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-ranking:hover {
    color: var(--primary);
    background: #f9f9f9;
}

.tab-ranking.ativo {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: white;
}

/* Conteúdo das Abas */
.tab-content-ranking {
    padding: 20px 0;
}

/* Controles do Ranking */
.ranking-controles {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filtros-ranking {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filtro-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filtro-item label {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.filtro-item select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

/* Header do Ranking */
.ranking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.ranking-header h3 {
    margin: 0;
    color: #333;
}

.ranking-stats {
    color: #666;
    font-size: 14px;
}

/* Container do Ranking */
.ranking-container {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 10px;
}

.ranking-container::-webkit-scrollbar {
    width: 6px;
}

.ranking-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.ranking-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

/* Turma Container */
.turma-ranking {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 25px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.turma-header {
    background: linear-gradient(135deg, var(--primary), #0056b3);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.turma-header h4 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.turma-stats {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

/* Lista de Alunos */
.alunos-ranking {
    padding: 0;
}

/* Item do Aluno no Ranking */
.aluno-ranking-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.aluno-ranking-item:hover {
    background: #f8f9fa;
}

.aluno-ranking-item:last-child {
    border-bottom: none;
}

/* Posição no Ranking */
.posicao-ranking {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 700;
    font-size: 18px;
    margin-right: 15px;
    flex-shrink: 0;
}

.posicao-ranking.primeiro {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #b8860b;
    border: 3px solid #ffd700;
}

.posicao-ranking.segundo {
    background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
    color: #696969;
    border: 3px solid #c0c0c0;
}

.posicao-ranking.terceiro {
    background: linear-gradient(135deg, #cd7f32, #daa520);
    color: #8b4513;
    border: 3px solid #cd7f32;
}

.posicao-ranking.normal {
    background: #f8f9fa;
    color: #495057;
    border: 2px solid #e9ecef;
}

/* Informações do Aluno */
.aluno-info {
    flex: 1;
    margin-right: 15px;
}

.aluno-nome {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    font-size: 16px;
}

.aluno-detalhes {
    font-size: 14px;
    color: #666;
    display: flex;
    gap: 20px;
}

/* Nota Final */
.nota-final {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 15px;
}

.nota-valor {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
}

.nota-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

/* Badge de Prêmio */
.premio-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.premio-badge.ouro {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #b8860b;
}

.premio-badge.prata {
    background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
    color: #696969;
}

.premio-badge.bronze {
    background: linear-gradient(135deg, #cd7f32, #daa520);
    color: #8b4513;
}

.premio-badge.participante {
    background: #e9ecef;
    color: #6c757d;
}

/* Container de Premiados */
.ranking-container.premiados .aluno-ranking-item {
    padding: 20px;
    margin-bottom: 15px;
    background: white;
    border: 2px solid transparent;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ranking-container.premiados .aluno-ranking-item.primeiro {
    border-color: #ffd700;
    background: linear-gradient(135deg, #fff9c4, #ffffff);
}

.ranking-container.premiados .aluno-ranking-item.segundo {
    border-color: #c0c0c0;
    background: linear-gradient(135deg, #f5f5f5, #ffffff);
}

.ranking-container.premiados .aluno-ranking-item.terceiro {
    border-color: #cd7f32;
    background: linear-gradient(135deg, #ffeaa7, #ffffff);
}

/* Estatísticas Container */
.estatisticas-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.estatistica-turma {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.estatistica-turma h4 {
    margin: 0 0 15px 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.estatistica-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.estatistica-item:last-child {
    border-bottom: none;
}

.estatistica-label {
    color: #666;
    font-size: 14px;
}

.estatistica-valor {
    font-weight: 600;
    color: var(--primary);
}

/* Relatórios Específicos */
.relatorios-especificos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.relatorio-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.relatorio-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.relatorio-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary), #0056b3);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.relatorio-info h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.relatorio-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

/* Modal de Detalhes do Aluno */
#modal-detalhes-aluno-ranking .modal-medium {
    width: 90%;
    max-width: 700px;
}

.aluno-ranking-detalhes {
    padding: 20px 0;
}

.aluno-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.aluno-foto {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary), #0056b3);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    flex-shrink: 0;
}

.aluno-info {
    flex: 1;
}

.aluno-info h2 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 24px;
}

.aluno-info p {
    margin: 4px 0;
    color: #666;
    font-size: 14px;
}

.aluno-premio {
    display: flex;
    align-items: center;
}

/* Notas Detalhadas */
.notas-detalhadas {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.notas-detalhadas h4 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 18px;
}

.nota-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    gap: 15px;
}

.nota-item:last-child {
    margin-bottom: 0;
}

.nota-label {
    min-width: 200px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.nota-barra {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.barra-progresso {
    flex: 1;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progresso {
    height: 100%;
    background: linear-gradient(135deg, var(--primary), #0056b3);
    border-radius: 10px;
    transition: width 0.5s ease;
    position: relative;
}

.progresso::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shine 2s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.nota-valor {
    min-width: 50px;
    text-align: right;
    font-weight: 600;
    color: var(--primary);
}

/* Nota Final Especial */
.nota-final {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border: 2px solid var(--primary);
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.media-final-badge {
    background: linear-gradient(135deg, var(--primary), #0056b3);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 18px;
    font-weight: 700;
}

/* Histórico do Aluno */
.historico-aluno {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
}

.historico-aluno h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #dee2e6;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    color: #495057;
    font-size: 14px;
}

.info-item span {
    color: var(--primary);
    font-weight: 600;
}

/* Estados Vazios */
.sem-ranking {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.sem-ranking i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ddd;
}

.sem-ranking h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.sem-ranking p {
    margin: 0;
    color: #888;
}

/* Loading States */
.ranking-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #666;
}

.ranking-loading i {
    margin-right: 10px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsividade */
@media (max-width: 768px) {
    #modal-ranking .modal-large {
        width: 95%;
        margin: 10px;
    }
    
    .filtros-ranking {
        flex-direction: column;
        align-items: stretch;
    }
    
    .aluno-ranking-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .posicao-ranking {
        width: 40px;
        height: 40px;
        font-size: 14px;
        margin-right: 0;
    }
    
    .aluno-detalhes {
        flex-direction: column;
        gap: 5px;
    }
    
    .nota-final {
        flex-direction: column;
        gap: 10px;
    }
    
    .aluno-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .nota-item {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .nota-label {
        min-width: auto;
    }
    
    .relatorios-especificos {
        grid-template-columns: 1fr;
    }
    
    .estatisticas-container {
        grid-template-columns: 1fr;
    }
}

/* Animações */
@keyframes modalAppear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.aluno-ranking-item {
    animation: slideInLeft 0.3s ease;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* ===== ESTILOS DO MODAL DE UNIFORMES ===== */

/* Modal Principal */
#modal-uniformes .modal-large {
    max-width: 1400px;
    width: 95%;
}

/* Abas */
.tab-uniformes {
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-uniformes:hover {
    color: var(--primary);
    background: #f9f9f9;
}

.tab-uniformes.ativo {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: white;
}

.tab-content-uniformes {
    padding: 20px 0;
}

/* Filtros */
.uniformes-filtros {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filtros-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filtro-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 150px;
}

.filtro-item label {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.filtro-item input,
.filtro-item select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.filtro-actions {
    display: flex;
    gap: 10px;
}

/* Contador */
.uniformes-contador {
    background: #e9ecef;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
    color: #495057;
}

/* Lista de Uniformes */
.uniformes-lista {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.uniformes-header {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr 1.5fr;
    gap: 15px;
    background: var(--primary);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 14px;
}

.header-col {
    display: flex;
    align-items: center;
}

.uniforme-item {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr 1.5fr;
    gap: 15px;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.3s ease;
}

.uniforme-item:hover {
    background: #f8f9fa;
}

.uniforme-item:last-child {
    border-bottom: none;
}

.aluno-info-item {
    display: flex;
    flex-direction: column;
}

.aluno-nome {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.aluno-matricula {
    font-size: 12px;
    color: #666;
}

.turma-info {
    color: #495057;
    font-weight: 500;
}

.tamanho-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    text-align: center;
    min-width: 30px;
}

.tamanho-badge.definido {
    background: #d4edda;
    color: #155724;
}

.tamanho-badge.indefinido {
    background: #f8d7da;
    color: #721c24;
}

.status-uniforme {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-uniforme.completo {
    background: #d4edda;
    color: #155724;
}

.status-uniforme.incompleto {
    background: #fff3cd;
    color: #856404;
}

.status-uniforme.pendente {
    background: #f8d7da;
    color: #721c24;
}

.acoes-uniforme {
    display: flex;
    gap: 8px;
}

.btn-editar-uniforme {
    background: var(--primary);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s ease;
}

.btn-editar-uniforme:hover {
    background: #0056b3;
}

/* Modal de Edição */
#modal-editar-uniforme .modal-medium {
    max-width: 500px;
}

.aluno-info-edit {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.aluno-avatar {
    width: 60px;
    height: 60px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.aluno-dados h3 {
    margin: 0 0 5px 0;
    color: #333;
}

.aluno-dados p {
    margin: 2px 0;
    color: #666;
    font-size: 14px;
}

.uniformes-inputs {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.uniformes-inputs .form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.uniformes-inputs label {
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.uniformes-inputs select {
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.uniformes-inputs select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}

/* Relatórios */
.relatorios-header {
    text-align: center;
    margin-bottom: 30px;
}

.relatorios-header h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
}

.relatorios-header p {
    margin: 0;
    color: #666;
    font-size: 16px;
}

.relatorios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.relatorio-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.relatorio-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.relatorio-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary), #0056b3);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.relatorio-info h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.relatorio-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

/* Estatísticas */
.estatisticas-header {
    text-align: center;
    margin-bottom: 30px;
}

.estatisticas-header h3 {
    margin: 0;
    color: #333;
    font-size: 24px;
}

.estatisticas-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card-estatistica {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.3s ease;
}

.card-estatistica:hover {
    transform: translateY(-2px);
}

.card-estatistica .card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    background: linear-gradient(135deg, var(--primary), #0056b3);
}

.card-estatistica .card-info h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.card-estatistica .card-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-weight: 500;
}

/* Gráficos */
.graficos-tamanhos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.grafico-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.grafico-container h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.grafico {
    min-height: 200px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-style: italic;
    padding: 20px;
    text-align: center;
}

/* Estados Vazios */
.sem-uniformes {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.sem-uniformes i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ddd;
}

.sem-uniformes h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.sem-uniformes p {
    margin: 0;
    color: #888;
}

/* Responsividade */
@media (max-width: 768px) {
    #modal-uniformes .modal-large {
        width: 95%;
        margin: 10px;
    }
    
    .filtros-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filtro-item {
        min-width: auto;
    }
    
    .uniformes-header,
    .uniforme-item {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .uniformes-header {
        display: none;
    }
    
    .uniforme-item {
        display: block;
        padding: 15px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .aluno-info-edit {
        flex-direction: column;
        text-align: center;
    }
    
    .relatorios-grid {
        grid-template-columns: 1fr;
    }
    
    .estatisticas-cards {
        grid-template-columns: 1fr;
    }
    
    .graficos-tamanhos {
        grid-template-columns: 1fr;
    }
}

/* ===== ESTILOS DO MODAL DE ALUNOS ===== */

/* Modal Principal de Alunos */
#modal-alunos .modal-large {
    max-width: 1400px;
    width: 95%;
}

/* Abas do Modal de Alunos */
.tab-alunos {
    background: none;
    border: none;
    padding: 15px 20px;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-alunos:hover {
    color: var(--primary);
    background: #f9f9f9;
}

.tab-alunos.ativo {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: white;
}

.tab-content-alunos {
    padding: 20px 0;
}

/* Filtros do Modal de Alunos */
.alunos-filtros {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filtros-row-alunos {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filtro-item-alunos {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 200px;
}

.filtro-item-alunos label {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.filtro-item-alunos input,
.filtro-item-alunos select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.filtro-actions-alunos {
    display: flex;
    gap: 10px;
}

/* Contador de Alunos */
.alunos-contador {
    background: #e9ecef;
    padding: 10px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: 500;
    color: #495057;
}

/* Lista de Alunos */
.alunos-lista {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-height: 500px;
    overflow-y: auto;
}

.aluno-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.3s ease;
}

.aluno-item:hover {
    background: #f8f9fa;
}

.aluno-item:last-child {
    border-bottom: none;
}

/* Foto do Aluno */
.aluno-foto {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
    flex-shrink: 0;
    border: 2px solid #e9ecef;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

/* Informações do Aluno */
.aluno-info-item {
    flex: 1;
    margin-right: 15px;
}

.aluno-nome {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
    font-size: 16px;
}

.aluno-detalhes {
    font-size: 14px;
    color: #666;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.aluno-detalhes span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.aluno-detalhes i {
    color: var(--primary);
    width: 14px;
}

/* Status do Aluno */
.aluno-status {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 15px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.status-badge.ativo {
    background: #d4edda;
    color: #155724;
}

.status-badge.inativo {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.pendente {
    background: #fff3cd;
    color: #856404;
}

.aluno-matricula {
    font-size: 11px;
    color: #888;
    text-align: center;
}

/* Ações do Aluno */
.acoes-aluno {
    display: flex;
    gap: 8px;
}

.btn-ver-detalhes {
    background: var(--primary);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s ease;
}

.btn-ver-detalhes:hover {
    background: #0056b3;
}

.btn-editar-aluno {
    background: #28a745;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s ease;
}

.btn-editar-aluno:hover {
    background: #218838;
}

/* Modal de Detalhes do Aluno */
#modal-detalhes-aluno .modal-large {
    max-width: 900px;
}

.aluno-detalhes-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.aluno-foto-grande {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
    flex-shrink: 0;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
}

.aluno-info-detalhes {
    flex: 1;
}

.aluno-info-detalhes h2 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 28px;
}

.aluno-info-detalhes .info-basica {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.info-basica span {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.info-basica i {
    color: var(--primary);
    width: 16px;
}

/* Seções de Detalhes */
.detalhes-secoes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.secao-detalhes {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.secao-detalhes h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.secao-detalhes h4 i {
    color: var(--primary);
}

.info-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item strong {
    color: #495057;
    font-size: 14px;
    flex: 1;
}

.info-item span {
    color: #333;
    font-weight: 500;
    text-align: right;
}

/* Seção de Responsáveis */
.responsaveis-lista {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.responsavel-item {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #e9ecef;
}

.responsavel-item:last-child {
    margin-bottom: 0;
}

.responsavel-item h5 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.responsavel-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.responsavel-info span {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.responsavel-info i {
    color: var(--primary);
    width: 16px;
}

/* Documentos */
.documentos-lista {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.documento-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 12px;
}

.documento-item.disponivel {
    background: #d4edda;
    border-color: #c3e6cb;
}

.documento-item.indisponivel {
    background: #f8d7da;
    border-color: #f5c6cb;
}

.documento-item i {
    font-size: 20px;
    color: var(--primary);
}

.documento-item.disponivel i {
    color: #155724;
}

.documento-item.indisponivel i {
    color: #721c24;
}

.documento-info {
    flex: 1;
}

.documento-info strong {
    display: block;
    color: #333;
    font-size: 14px;
    margin-bottom: 2px;
}

.documento-info span {
    color: #666;
    font-size: 12px;
}

/* Estados Vazios */
.sem-alunos {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.sem-alunos i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ddd;
}

.sem-alunos h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.sem-alunos p {
    margin: 0;
    color: #888;
}

/* Loading */
.alunos-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #666;
}

.alunos-loading i {
    margin-right: 10px;
    animation: spin 1s linear infinite;
}

/* Responsividade */
@media (max-width: 768px) {
    #modal-alunos .modal-large {
        width: 95%;
        margin: 10px;
    }
    
    .filtros-row-alunos {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filtro-item-alunos {
        min-width: auto;
    }
    
    .aluno-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .aluno-foto {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .aluno-detalhes {
        flex-direction: column;
        gap: 5px;
    }
    
    .aluno-detalhes-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .aluno-foto-grande {
        width: 100px;
        height: 100px;
        font-size: 40px;
    }
    
    .detalhes-secoes {
        grid-template-columns: 1fr;
    }
    
    .info-basica {
        grid-template-columns: 1fr;
    }
    
    .responsavel-info {
        grid-template-columns: 1fr;
    }
    
    .documentos-lista {
        grid-template-columns: 1fr;
    }
}
  </style>
</head>
<body>
  <header>
    <div class="header-content">
      <div class="logo">
      <img src="./img/logobo.png" alt="Logo SuperAção" style="width: 100px; height: auto;"/>
        <div>
          <h1>Bombeiro Mirim</h1>
          <small>Painel Administrativo</small>
        </div>
      </div>
      <div class="header-actions">
        
        <div class="user-info-container" onclick="document.getElementById('user-menu').classList.toggle('show');">
          <img src="<?= $usuario_foto ?>" alt="Foto do usuário" class="user-photo">
          <div>
            <div class="user-name"><?= $usuario_nome ?></div>
            <small><?= $usuario_tipo ?></small>
          </div>
          <div id="user-menu" class="dropdown-menu">
            <!--<a href="#"><i class="fas fa-cog"></i> Configurações</a> -->
            
            <a href="auditoria_page.php"><i class="fas fa-clipboard-check"></i> Auditoria</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="page-header">
      <h1><i class="fas fa-users"></i> Gerenciamento de Matrículas</h1>
      <p>Cadastre e gerencie matrículas de alunos, turmas e unidades</p>
    </div>


    <div class="filter-section">
      <div class="filter-header">
        <h3><i class="fas fa-filter"></i> Filtros</h3>
        <button id="toggle-filter" class="btn btn-outline btn-sm">
          <span class="filter-icon"><i class="fas fa-search"></i></span> Mostrar/Ocultar Filtros
        </button>
      </div>
      
      <div id="filter-container" class="filter-container" style="display: none;">
        <form id="filter-form">
          <div class="filter-row">
            <div class="filter-item">
              <label>Aluno</label>
              <input type="text" name="aluno" placeholder="Nome do aluno">
            </div>

            
            <div class="filter-item">
              <label>Unidade</label>
              <select name="unidade" id="filtro-unidade">
                <option value="">Todas</option>
              </select>
            </div>
            
            <div class="filter-item">
              <label>Turma</label>
              <select name="turma" id="filtro-turma">
                <option value="">Todas</option>
              </select>
            </div>
          </div>
          
          <div class="filter-row">
            <div class="filter-item">
              <label>Status</label>
              <select name="status">
                <option value="">Todos</option>
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
                <option value="pendente">Pendente</option>
              </select>
            </div>
            
            <div class="filter-item">
              <label>Data Inicial</label>
              <input type="date" name="data_inicial">
            </div>
            
            <div class="filter-item">
              <label>Data Final</label>
              <input type="date" name="data_final">
            </div>
          </div>
          
          <div class="filter-actions">
            <button type="button" id="limpar-filtros" class="btn btn-outline">
              <i class="fas fa-eraser"></i> Limpar Filtros
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search"></i> Filtrar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Contador de resultados -->
    <div class="results-counter">
      <span id="total-results">0</span> resultados encontrados
    </div>

    <div class="action-buttons">
      <button class="btn btn-primary" id="nova-turma-btn">
        <i class="fas fa-chalkboard"></i> Nova Turma
      </button>
      <button class="btn btn-primary" id="nova-unidade-btn">
        <i class="fas fa-building"></i> Nova Unidade
      </button>
      <button class="btn btn-primary" id="novo-professor-btn">
        <i class="fas fa-user-tie"></i> Novo Professor(a)
      </button>
      <button class="btn btn-primary" id="ver-aluno-btn">
    <i class="fas fa-user-graduate"></i> Ver Alunos(a)
    </button>

      <button class="btn btn-primary" id="galeria-fotos-btn">
    <i class="fas fa-camera"></i> Galeria de Fotos
  </button>
  <button class="btn btn-primary" id="ranking-btn">
  <i class="fas fa-trophy"></i> Ranking dos Alunos
</button>
  <button class="btn btn-primary" id="modulo-financeiro-btn">
    <i class="fas fa-dollar-sign"></i> Módulo Financeiro
  </button>
  <button class="btn btn-primary" id="saida-btn">
  <i class="fas fa-sign-out-alt"></i> Controle de Materiais
</button>
<button class="btn btn-primary" id="uniformes-btn">
  <i class="fas fa-tshirt"></i> Uniformes
</button>

<button class="btn btn-primary" id="monitoramento-btn">
  <i class="fas fa-chart-line"></i> Monitoramento de Atividades
</button>
<button class="btn btn-primary" id="comunicado-btn">
  <i class="fas fa-bullhorn"></i>Gerar Comunicado
</button>

      <button class="btn btn-primary" id="ver-relatorio-btn" onclick="window.location.href='dashboard.php'">
    <i class="fas fa-chart-bar"></i> Ver Relatório
    </button>
      <!-- <button class="btn btn-primary" id="gerar-carterinha-btn">
      <i class="fas fa-id-card"></i> Gerar Carteirinha
    </button> -->
    </button>
 <button class="btn btn-primary" id="ocorrencias-btn" onclick="abrirModalOcorrencias()">
    <i class="fas fa-file-pdf"></i> Ocorrências
</button>
      <button class="btn btn-success" id="gerar-pdf">
        <i class="fas fa-file-pdf"></i> Gerar PDF de Matrículas
      </button>

    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all" /></th>
            <th>Nome do Aluno</th>
            <th>Responsável</th>
            <th></th>
            <th>Unidade</th>
            <th>Turma</th>
            <th>Data da Matrícula</th>
            <th></th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody id="matriculas-body">
          <!-- Dados preenchidos dinamicamente -->
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <button class="btn btn-outline btn-sm pagination-btn" disabled>
        <i class="fas fa-chevron-left"></i> Anterior
      </button>
      <span class="pagination-info">Página 1 de 1</span>
      <button class="btn btn-outline btn-sm pagination-btn" disabled>
        Próxima <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>

  <!-- Modal Nova Turma -->
  <div id="nova-turma-modal" class="modal-backdrop" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <span><i class="fas fa-chalkboard"></i> Nova Turma</span>
        <button onclick="document.getElementById('nova-turma-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body">
        <form id="nova-turma-form">
          <div class="form-group">
            <label>Nome da Turma</label>
            <input type="text" name="nome_turma" placeholder="Ex: Turma A - Matutino" required />
          </div>

          <div class="form-group">
            <label>Unidade</label>
            <select id="unidade" name="unidade" required>
              <option value="">Selecione uma unidade</option>
            </select>
          </div>

          <div class="form-group">
            <label>Professor Responsável</label>
            <select name="professor_responsavel" required>
              <option value="">Selecione um professor</option>
            </select>
          </div>

          <div class="form-group">
            <label>Data de Início</label>
            <input type="date" name="data_inicio" required />
          </div>

          <div class="form-group">
            <label class="checkbox-container">
              <input type="checkbox" id="status-active" />
              <span class="checkbox-label">Ativar turma imediatamente</span>
            </label>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline" 
                onclick="document.getElementById('nova-turma-modal').style.display='none'">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Nova Unidade -->
  <div id="nova-unidade-modal" class="modal-backdrop" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <span><i class="fas fa-building"></i> Nova Unidade</span>
        <button onclick="document.getElementById('nova-unidade-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body">
        <form id="nova-unidade-form">
          <div class="form-group">
            <label>Nome da Unidade</label>
            <input type="text" name="nome" placeholder="Nome da Unidade" required />
          </div>
          
          <div class="form-group">
              <label>Unidade CRBM</label>
              <select name="unidade-crbm" id="unidade-crbm">
                <option value="">Clique e escolha uma unidade CRBM</option>
                <option value="goiania">1º Comando Regional Bombeiro Militar - Goiânia -Comando Bombeiro Militar da Capital - CBC</option>
                <option value="rioVerde">2º Comando Regional Bombeiro Militar - Rio Verde</option>
                <option value="anapolis">3º Comando Regional Bombeiro Militar - Anápolis</option>
                <option value="luziania">4º Comando Regional Bombeiro Militar - Luziânia</option>
                <option value="aparecidaDeGoiania">5º Comando Regional Bombeiro Militar – Aparecida de Goiânia</option>
                <option value="goias">6º Comando Regional Bombeiro Militar - Goiás</option>
                <option value="caldasNovas">7º Comando Regional Bombeiro Militar – Caldas Novas</option>
                <option value="uruacu">8º Comando Regional Bombeiro Militar - Uruaçu</option>
                <option value="Formosa">9º Comando Regional Bombeiro Militar - Formosa</option>
              </select>
            </div>
          
          <div class="form-group">
            <label>Endereço</label>
            <input type="text" name="endereco" placeholder="Endereço completo" required />
          </div>
          
          <div class="form-group">
            <label>Telefone</label>
            <input type="text" name="telefone" placeholder="(00) 0000-0000" />
          </div>
          
          <div class="form-group">
            <label>Comandante da Unidade</label>
            <input type="text" name="coordenador" placeholder="Nome do coordenador" />
          </div>

          <div class="form-group">
            <label>Cidade</label>
            <input list="lista-cidades" name="cidade" id="cidade" placeholder="Digite a cidade" />
            <datalist id="lista-cidades"></datalist>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline" 
                onclick="document.getElementById('nova-unidade-modal').style.display='none'">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Novo Professor -->
  <div id="novo-professor-modal" class="modal-backdrop" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <span><i class="fas fa-user-tie"></i> Novo Professor</span>
        <button onclick="document.getElementById('novo-professor-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body">
        <form id="novo-professor-form">
          <div class="form-group">
            <label>Nome do Professor</label>
            <input type="text" name="nome" placeholder="Nome completo" required />
          </div>
          
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="email@exemplo.com" />
          </div>

          <div class="form-group">
            <label>Senha</label>
            <input type="password" name="senha" placeholder="Senha do professor" />
          </div>

    
          <div class="form-group">
            <label>Telefone</label>
            <input type="text" name="telefone" placeholder="(00) 00000-0000" />
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-outline" 
                onclick="document.getElementById('novo-professor-modal').style.display='none'">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Visualização -->
  <div id="view-details-modal" class="modal-backdrop" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <span><i class="fas fa-info-circle"></i> Detalhes da Matrícula</span>
        <button onclick="document.getElementById('view-details-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body" id="detalhes-matricula">
        <!-- Conteúdo dinâmico -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="document.getElementById('view-details-modal').style.display='none'">
          Fechar
        </button>
      </div>
    </div>
  </div>

  <!-- Modal Editar Matrícula -->
  <div id="edit-matricula-modal" class="modal-backdrop" style="display: none;">
    <div class="modal">
      <div class="modal-header">
        <span><i class="fas fa-edit"></i> Editar Matrícula</span>
        <button onclick="document.getElementById('edit-matricula-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body">
        <form id="edit-matricula-form">
          <input type="hidden" id="editar-id" name="id" />
  
          <div class="form-group">
            <label>Nome do Aluno</label>
            <input type="text" name="aluno_nome" readonly class="readonly-field" />
          </div>

          
  
          <div class="form-group">
            <label>Unidade</label>
            <select name="unidade" id="unidade-editar" required></select>
          </div>
  
          <div class="form-group">
            <label>Turma</label>
            <select name="turma" id="turma-editar" required></select>
          </div>
  
          <div class="form-group">
            <label>Data da Matrícula</label>
            <input type="date" name="data_matricula" required />
          </div>
  
          <div class="form-group">
            <label>Status</label>
            <select name="status" required>
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
              <option value="pendente">Pendente</option>
            </select>
          </div>


          
          <div class="form-group">
            <label>Responsáveis</label>
            <div id="responsaveis-editar" class="responsaveis-list">
              <!-- Lista dos responsáveis será preenchida dinamicamente -->
            </div>
          </div>
          
          <div class="form-group">
              <label>Status no programa</label>
              <select name="status-programa" id="status-programa">
                <option value="">Todas</option>
                <option value="novato">Novato</option>
                <option value="monitor">Monitor</option>
              </select>
            </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline" 
                onclick="document.getElementById('edit-matricula-modal').style.display='none'">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar Alterações
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Principal de Alunos -->
  <div id="modal-alunos" class="modal-backdrop" style="display: none;">
      <div class="modal modal-large">
          <div class="modal-header">
              <span><i class="fas fa-user-graduate"></i> Visualizar Alunos</span>
              <button class="fechar-modal-alunos">×</button>
          </div>
          
          <div class="modal-body">
              <!-- Filtros -->
              <div class="alunos-filtros">
                  <div class="filtros-row-alunos">
                      <div class="filtro-item-alunos">
                          <label>Buscar por Nome</label>
                          <input type="text" id="filtro-nome-aluno" placeholder="Nome do aluno">
                      </div>
                      
                      <div class="filtro-item-alunos">
                          <label>Turma</label>
                          <select id="filtro-turma-alunos">
                              <option value="">Todas as turmas</option>
                          </select>
                      </div>
                      
                      <div class="filtro-item-alunos">
                          <label>Status</label>
                          <select id="filtro-status-alunos">
                              <option value="">Todos</option>
                              <option value="ativo">Ativo</option>
                              <option value="inativo">Inativo</option>
                              <option value="pendente">Pendente</option>
                          </select>
                      </div>
                      
                      <div class="filtro-actions-alunos">
                          <button class="btn btn-primary btn-sm" onclick="filtrarAlunos()">
                              <i class="fas fa-search"></i> Filtrar
                          </button>
                          <button class="btn btn-outline btn-sm" onclick="limparFiltrosAlunos()">
                              <i class="fas fa-eraser"></i> Limpar
                          </button>
                      </div>
                  </div>
              </div>

              <!-- Contador de Resultados -->
              <div class="alunos-contador">
                  <span id="total-alunos">0</span> alunos encontrados
              </div>

              <!-- Lista de Alunos -->
              <div id="lista-alunos" class="alunos-lista">
                  <!-- Será preenchido dinamicamente -->
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-alunos">
                  <i class="fas fa-times"></i> Fechar
              </button>
              <button class="btn btn-success" onclick="exportarAlunosExcel()">
                  <i class="fas fa-file-excel"></i> Exportar Excel
              </button>
          </div>
      </div>
  </div>

  <!-- Modal de Detalhes do Aluno -->
  <div id="modal-detalhes-aluno" class="modal-backdrop" style="display: none;">
      <div class="modal modal-large">
          <div class="modal-header">
              <span><i class="fas fa-info-circle"></i> Detalhes do Aluno</span>
              <button class="fechar-modal-detalhes-aluno">×</button>
          </div>
          
          <div class="modal-body">
              <div class="aluno-detalhes-header">
                  <div class="aluno-foto-grande">
                      <i class="fas fa-user-circle"></i>
                  </div>
                  <div class="aluno-info-detalhes">
                      <h2 id="detalhes-nome-aluno"></h2>
                      <div class="info-basica">
                          <span><i class="fas fa-id-card"></i> <strong>Matrícula:</strong> <span id="detalhes-matricula"></span></span>
                          <span><i class="fas fa-birthday-cake"></i> <strong>Data de Nascimento:</strong> <span id="detalhes-nascimento"></span></span>
                          <span><i class="fas fa-venus-mars"></i> <strong>Gênero:</strong> <span id="detalhes-genero"></span></span>
                          <span><i class="fas fa-calendar-alt"></i> <strong>Data da Matrícula:</strong> <span id="detalhes-data-matricula"></span></span>
                      </div>
                  </div>
              </div>

              <div class="detalhes-secoes">
                  <!-- Seção Escolar -->
                  <div class="secao-detalhes">
                      <h4><i class="fas fa-school"></i> Informações Escolares</h4>
                      <div class="info-lista">
                          <div class="info-item">
                              <strong>Escola:</strong>
                              <span id="detalhes-escola"></span>
                          </div>
                          <div class="info-item">
                              <strong>Série:</strong>
                              <span id="detalhes-serie"></span>
                          </div>
                          <div class="info-item">
                              <strong>Diretor:</strong>
                              <span id="detalhes-diretor"></span>
                          </div>
                          <div class="info-item">
                              <strong>Telefone da Escola:</strong>
                              <span id="detalhes-telefone-escola"></span>
                          </div>
                      </div>
                  </div>

                  <!-- Seção Médica -->
                  <div class="secao-detalhes">
                      <h4><i class="fas fa-medkit"></i> Informações Médicas</h4>
                      <div class="info-lista">
                          <div class="info-item">
                              <strong>Tipo Sanguíneo:</strong>
                              <span id="detalhes-tipo-sanguineo"></span>
                          </div>
                          <div class="info-item">
                              <strong>Criança Atípica:</strong>
                              <span id="detalhes-crianca-atipica"></span>
                          </div>
                          <div class="info-item">
                              <strong>Alergias/Condições:</strong>
                              <span id="detalhes-alergias"></span>
                          </div>
                          <div class="info-item">
                              <strong>Medicação Contínua:</strong>
                              <span id="detalhes-medicacao"></span>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Seção de Responsáveis -->
              <div class="secao-detalhes">
                  <h4><i class="fas fa-users"></i> Responsáveis</h4>
                  <div id="detalhes-responsaveis" class="responsaveis-lista">
                      <!-- Será preenchido dinamicamente -->
                  </div>
              </div>

              <!-- Seção de Documentos -->
              <div class="secao-detalhes">
                  <h4><i class="fas fa-file-alt"></i> Documentos</h4>
                  <div id="detalhes-documentos" class="documentos-lista">
                      <!-- Será preenchido dinamicamente -->
                  </div>
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-detalhes-aluno">
                  <i class="fas fa-times"></i> Fechar
              </button>
              <button class="btn btn-primary" onclick="editarAluno()">
                  <i class="fas fa-edit"></i> Editar Aluno
              </button>
          </div>
      </div>
  </div>

<!-- Modal de Ranking dos Alunos -->
<div id="modal-ranking" class="modal-backdrop" style="display: none;">
    <div class="modal modal-large">
        <div class="modal-header">
            <span><i class="fas fa-trophy"></i> Ranking dos Alunos por Turma</span>
            <button class="fechar-modal-ranking">×</button>
        </div>
        
        <!-- Abas do Modal -->
        <div class="modal-tabs">
            <button class="tab-ranking ativo" data-tab="geral">
                <i class="fas fa-list-ol"></i> Ranking Geral
            </button>
            <button class="tab-ranking" data-tab="premiados">
                <i class="fas fa-medal"></i> Premiados (Top 3)
            </button>
            <button class="tab-ranking" data-tab="estatisticas">
                <i class="fas fa-chart-bar"></i> Estatísticas
            </button> 
            
        </div>

        <div class="modal-body">
            <!-- Controles de Filtro -->
            <div class="ranking-controles">
                <div class="filtros-ranking">
                    <div class="filtro-item">
                        <label>Turma</label>
                        <select id="filtro-turma-ranking">
                            <option value="">Todas as Turmas</option>
                        </select>
                    </div>
                    
                    <div class="filtro-item">
                        <label>Período</label>
                        <select id="filtro-periodo-ranking">
                            <option value="atual">Período Atual</option>
                            <option value="2025-S1">2025 - 1º Semestre</option>
                            <option value="2025-S2">2025 - 2º Semestre</option>
                            <option value="2024-S2">2024 - 2º Semestre</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-primary btn-sm" onclick="atualizarRanking()">
                        <i class="fas fa-sync-alt"></i> Atualizar
                    </button>
                    
                    <button class="btn btn-success btn-sm" onclick="calcularNovoRanking()">
                        <i class="fas fa-calculator"></i> Calcular Ranking
                    </button>
                </div>
            </div>

            <!-- Aba Ranking Geral -->
            <div id="tab-geral" class="tab-content-ranking">
                <div class="ranking-header">
                    <h3>Ranking Completo por Turma</h3>
                    <div class="ranking-stats">
                        <span id="total-alunos-ranking">0 alunos avaliados</span>
                    </div>
                </div>
                
                <div id="ranking-geral-container" class="ranking-container">
                    <!-- Será preenchido dinamicamente -->
                </div>
            </div>

            <!-- Aba Premiados (Top 3) -->
            <div id="tab-premiados" class="tab-content-ranking" style="display: none;">
                <div class="ranking-header">
                    <h3>🏆 Alunos Premiados - Top 3 por Turma</h3>
                    <div class="ranking-stats">
                        <span id="total-premiados">0 alunos premiados</span>
                    </div>
                </div>
                
                <div id="ranking-premiados-container" class="ranking-container premiados">
                    <!-- Será preenchido dinamicamente -->
                </div>
            </div>

            <!-- Aba Estatísticas -->
            <div id="tab-estatisticas" class="tab-content-ranking" style="display: none;">
                <div class="ranking-header">
                    <h3>📊 Estatísticas por Turma</h3>
                </div>
                
                <div id="estatisticas-container" class="estatisticas-container">
                    <!-- Cards de estatísticas -->
                </div>
            </div>

            <!-- Aba Relatórios Específicos -->
            <div id="tab-relatorios" class="tab-content-ranking" style="display: none;">
                <div class="ranking-header">
                    <h3>📋 Relatórios Específicos</h3>
                </div>
                
                <div class="relatorios-especificos">
                    <div class="relatorio-card" onclick="gerarRelatorioFisico()">
                        <div class="relatorio-icon">
                            <i class="fas fa-running"></i>
                        </div>
                        <div class="relatorio-info">
                            <h4>Melhor Desempenho Físico</h4>
                            <p>Ranking baseado em velocidade, resistência, coordenação, agilidade e força</p>
                        </div>
                    </div>
                    
                    <div class="relatorio-card" onclick="gerarRelatorioComportamento()">
                        <div class="relatorio-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="relatorio-info">
                            <h4>Melhor Comportamento</h4>
                            <p>Ranking baseado em participação, trabalho em equipe e disciplina</p>
                        </div>
                    </div>
                    
                    <div class="relatorio-card" onclick="gerarRelatorioPresenca()">
                        <div class="relatorio-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="relatorio-info">
                            <h4>Melhor Presença</h4>
                            <p>Ranking baseado na taxa de presença em atividades</p>
                        </div>
                    </div>
                    
                    <div class="relatorio-card" onclick="gerarRelatorioCompleto()">
                        <div class="relatorio-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="relatorio-info">
                            <h4>Relatório Completo</h4>
                            <p>Relatório detalhado com todas as métricas e notas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-outline fechar-modal-ranking">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button class="btn btn-success" onclick="exportarRankingPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
            <!-- <button class="btn btn-primary" onclick="gerarCertificados()">
                <i class="fas fa-certificate"></i> Gerar Certificados
            </button> -->
        </div>
    </div>
</div>

<div id="modal-detalhes-aluno-ranking" class="modal-backdrop" style="display: none;">
    <div class="modal modal-medium">
        <div class="modal-header">
            <span><i class="fas fa-user-graduate"></i> Detalhes do Aluno</span>
            <button class="fechar-modal-detalhes-aluno">×</button>
        </div>
        
        <div class="modal-body">
            <div class="aluno-ranking-detalhes">
                <div class="aluno-header">
                    <div class="aluno-foto">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="aluno-info">
                        <h2 id="detalhe-nome-aluno"></h2>
                        <p><strong>Turma:</strong> <span id="detalhe-turma-aluno"></span></p>
                        <p><strong>Posição:</strong> <span id="detalhe-posicao-aluno"></span></p>
                    </div>
                    <div class="aluno-premio">
                        <span id="detalhe-premio-aluno" class="premio-badge"></span>
                    </div>
                </div>
                
                <div class="notas-detalhadas">
                    <h4>📊 Notas Detalhadas</h4>
                    
                    <div class="nota-item">
                        <span class="nota-label">💪 Desempenho Físico:</span>
                        <div class="nota-barra">
                            <div class="barra-progresso">
                                <div id="barra-fisica" class="progresso"></div>
                            </div>
                            <span id="nota-fisica-valor" class="nota-valor">0.0</span>
                        </div>
                    </div>
                    
                    <div class="nota-item">
                        <span class="nota-label">🤝 Comportamento (Avaliações):</span>
                        <div class="nota-barra">
                            <div class="barra-progresso">
                                <div id="barra-comportamento-aval" class="progresso"></div>
                            </div>
                            <span id="nota-comportamento-aval-valor" class="nota-valor">0.0</span>
                        </div>
                    </div>
                    
                    <div class="nota-item">
                        <span class="nota-label">📚 Desempenho em Atividades:</span>
                        <div class="nota-barra">
                            <div class="barra-progresso">
                                <div id="barra-atividades" class="progresso"></div>
                            </div>
                            <span id="nota-atividades-valor" class="nota-valor">0.0</span>
                        </div>
                    </div>
                    
                    <div class="nota-item">
                        <span class="nota-label">😊 Comportamento (Atividades):</span>
                        <div class="nota-barra">
                            <div class="barra-progresso">
                                <div id="barra-comportamento-ativ" class="progresso"></div>
                            </div>
                            <span id="nota-comportamento-ativ-valor" class="nota-valor">0.0</span>
                        </div>
                    </div>
                    
                    <div class="nota-item">
                        <span class="nota-label">📅 Taxa de Presença:</span>
                        <div class="nota-barra">
                            <div class="barra-progresso">
                                <div id="barra-presenca" class="progresso"></div>
                            </div>
                            <span id="nota-presenca-valor" class="nota-valor">0%</span>
                        </div>
                    </div>
                    
                    <div class="nota-final">
                        <span class="nota-label">🏆 MÉDIA FINAL:</span>
                        <span id="media-final-valor" class="media-final-badge">0.0</span>
                    </div>
                </div>
                
                <div class="historico-aluno">
                    <h4>📈 Informações Adicionais</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Total de Avaliações:</strong>
                            <span id="total-avaliacoes-aluno">0</span>
                        </div>
                        <div class="info-item">
                            <strong>Total de Atividades:</strong>
                            <span id="total-atividades-aluno">0</span>
                        </div>
                        <div class="info-item">
                            <strong>Data do Cálculo:</strong>
                            <span id="data-calculo-aluno">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-outline fechar-modal-detalhes-aluno">
                <i class="fas fa-times"></i> Fechar
            </button>
            <!-- <button class="btn btn-primary" onclick="gerarCertificadoIndividual()">
                <i class="fas fa-certificate"></i> Gerar Certificado
            </button> -->
        </div>
    </div>
</div>


  <!-- Modal Principal de Comunicados -->
  <div id="modal-comunicados" class="modal-backdrop" style="display: none;">
      <div class="modal modal-large">
          <div class="modal-header">
              <span><i class="fas fa-bullhorn"></i> Gerenciar Comunicados</span>
              <button class="fechar-modal-comunicado">×</button>
          </div>
          
          <!-- Abas do Modal -->
          <div class="modal-tabs">
              <button class="tab-comunicado ativo" data-tab="criar">
                  <i class="fas fa-plus"></i> Criar Comunicado
              </button>
              <button class="tab-comunicado" data-tab="listar">
                  <i class="fas fa-list"></i> Todos os Comunicados
              </button>
          </div>

          <div class="modal-body">
              <!-- Aba Criar/Editar Comunicado -->
              <div id="tab-criar" class="tab-content-comunicado">
                  <form id="form-comunicado">
                      <div class="form-group">
                          <label for="titulo-comunicado">Título do Comunicado</label>
                          <input type="text" id="titulo-comunicado" name="titulo" required 
                                 placeholder="Digite o título do comunicado">
                      </div>

                      <div class="form-group">
                          <label for="conteudo-comunicado">Conteúdo</label>
                          <textarea id="conteudo-comunicado" name="conteudo" required 
                                    rows="10" placeholder="Digite o conteúdo do comunicado..."></textarea>
                      </div>

                      <div class="form-actions">
                          <button type="button" id="btn-cancelar-comunicado" class="btn btn-outline" style="display: none;">
                              <i class="fas fa-times"></i> Cancelar
                          </button>
                          <button type="submit" class="btn btn-primary">
                              <i class="fas fa-save"></i> Criar Comunicado
                          </button>
                      </div>
                  </form>
              </div>

              <!-- Aba Listar Comunicados -->
              <div id="tab-listar" class="tab-content-comunicado" style="display: none;">
                  <div class="comunicados-header">
                      <h3>Comunicados Criados</h3>
                      <div class="comunicados-stats">
                          <span id="total-comunicados">0 comunicados</span>
                      </div>
                  </div>
                  
                  <div id="lista-comunicados" class="lista-comunicados">
                      <!-- Lista será preenchida dinamicamente -->
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal de Visualização do Comunicado -->
  <div id="modal-visualizar-comunicado" class="modal-backdrop" style="display: none;">
      <div class="modal modal-medium">
          <div class="modal-header">
              <span><i class="fas fa-eye"></i> Visualizar Comunicado</span>
              <button class="fechar-modal-visualizar">×</button>
          </div>
          
          <div class="modal-body">
              <div class="comunicado-visualizacao">
                  <h2 id="visualizar-titulo"></h2>
                  <div class="comunicado-meta">
                      <span class="autor-info">
                          <i class="fas fa-user"></i> 
                          <strong>Autor:</strong> <span id="visualizar-autor"></span>
                      </span>
                      <span class="data-info">
                          <i class="fas fa-calendar"></i> 
                          <strong>Data:</strong> <span id="visualizar-data"></span>
                      </span>
                  </div>
                  <div class="comunicado-conteudo">
                      <p id="visualizar-conteudo"></p>
                  </div>
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-visualizar">
                  <i class="fas fa-times"></i> Fechar
              </button>
          </div>
      </div>
  </div>



  <!-- Modal Principal de Ocorrências - Admin -->
<div id="modalOcorrenciasAdmin" class="modal-backdrop" style="display: none;">
    <div class="modal modal-extra-large">
        <div class="modal-header">
            <span><i class="fas fa-exclamation-triangle"></i> Gerenciar Ocorrências</span>
            <button id="closeOcorrenciasAdminModal">×</button>
        </div>
        
        <div class="modal-body">
            <!-- Filtros -->
            <div class="filtros-section">
                <h3><i class="fas fa-filter"></i> Filtros</h3>
                <form id="filtros-ocorrencias">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label>Professor</label>
                            <select id="filtro-professor" name="professor_id">
                                <option value="">Todos os professores</option>
                                <!-- Populado via JavaScript -->
                            </select>
                        </div>
                        
                
  
    
                        
                        <div class="filter-item">
                            <label>Status Feedback</label>
                            <select id="filtro-status-feedback" name="status_feedback">
                                <option value="">Todos</option>
                                <option value="sem_feedback">Pendentes</option>
                                <option value="com_feedback">Com Feedback</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-item">
                            <label>Data Início</label>
                            <input type="date" id="filtro-data-inicio" name="data_inicio">
                        </div>
                        
                        <div class="filter-item">
                            <label>Data Fim</label>
                            <input type="date" id="filtro-data-fim" name="data_fim">
                        </div>
                        
                        <div class="filter-item filter-actions">
                            <button type="button" id="btn-aplicar-filtros" class="btn btn-primary">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                            <button type="button" id="btn-limpar-filtros" class="btn btn-outline">
                                <i class="fas fa-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Lista de Ocorrências -->
            <div id="lista-ocorrencias-admin">
                <p>Carregando ocorrências...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Ocorrência - Admin -->
<div id="detalhesOcorrenciaAdminModal" class="modal-backdrop" style="display: none;">
    <div class="modal modal-large">
        <div class="modal-header">
            <span><i class="fas fa-info-circle"></i> Detalhes da Ocorrência</span>
            <button id="closeDetalhesOcorrenciaAdminModal">×</button>
        </div>
        
        <div class="modal-body">
            <div id="detalhes-ocorrencia-admin">
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Feedback -->
<div id="feedbackModal" class="modal-backdrop" style="display: none;">
    <div class="modal modal-medium">
        <div class="modal-header">
            <span><i class="fas fa-comment-dots"></i> <span id="modal-feedback-title">Feedback da Administração</span></span>
            <button id="closeFeedbackModal">×</button>
        </div>
        
        <div class="modal-body">
            <div id="mensagem-feedback"></div>
            
            <form id="form-feedback">
                <input type="hidden" id="feedback-action" name="action" value="adicionar_feedback">
                <input type="hidden" id="feedback-ocorrencia-id" name="ocorrencia_id" value="">
                
                <div class="form-group">
                    <label for="feedback-texto" class="form-label">
                        Feedback da Administração <span style="color: red;">*</span>
                    </label>
                    <textarea id="feedback-texto" name="feedback_admin" class="form-control" rows="6" required
                              placeholder="Digite o feedback sobre esta ocorrência..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Feedback
                    </button>
                    <button type="button" id="btn-cancelar-feedback" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

  <!-- Modal Monitoramento de Atividades -->
  <div id="modal-monitoramento" class="modal-backdrop" style="display: none;">
      <div class="modal modal-large">
          <div class="modal-header">
              <span><i class="fas fa-chart-line"></i> Monitoramento de Atividades</span>
              <button class="fechar-modal-monitoramento">×</button>
          </div>
          
          <!-- Abas do Modal -->
          <div class="modal-tabs">
              <button class="tab-monitoramento ativo" data-tab="dashboard">
                  <i class="fas fa-tachometer-alt"></i> Dashboard
              </button>
              <button class="tab-monitoramento" data-tab="atividades">
                  <i class="fas fa-list"></i> Todas as Atividades
              </button>
              <button class="tab-monitoramento" data-tab="calendario">
                  <i class="fas fa-calendar"></i> Calendário
              </button>
          </div>

          <div class="modal-body">
              <!-- Aba Dashboard -->
              <div id="tab-dashboard" class="tab-content-monitoramento">
                  <!-- Cards de Resumo -->
                  <div class="cards-resumo">
                      <div class="card-resumo planejadas">
                          <div class="card-icon">
                              <i class="fas fa-clock"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-planejadas">0</h3>
                              <p>Atividades Planejadas</p>
                          </div>
                      </div>
                      
                      <div class="card-resumo em-andamento">
                          <div class="card-icon">
                              <i class="fas fa-play"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-em-andamento">0</h3>
                              <p>Em Andamento</p>
                          </div>
                      </div>
                      
                      <div class="card-resumo concluidas">
                          <div class="card-icon">
                              <i class="fas fa-check"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-concluidas">0</h3>
                              <p>Concluídas</p>
                          </div>
                      </div>
                      
                      <div class="card-resumo canceladas">
                          <div class="card-icon">
                              <i class="fas fa-times"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-canceladas">0</h3>
                              <p>Canceladas</p>
                          </div>
                      </div>
                  </div>

                  <!-- Atividades por Status -->
                  <div class="status-sections">
                      <!-- Atividades em Andamento -->
                      <div class="status-section">
                          <div class="section-header em-andamento">
                              <h3><i class="fas fa-play"></i> Atividades em Andamento</h3>
                              <span class="count" id="count-em-andamento">0</span>
                          </div>
                          <div id="atividades-em-andamento" class="atividades-lista">
                              <!-- Preenchido dinamicamente -->
                          </div>
                      </div>

                      <!-- Próximas Atividades -->
                      <div class="status-section">
                          <div class="section-header planejadas">
                              <h3><i class="fas fa-clock"></i> Próximas Atividades</h3>
                              <span class="count" id="count-proximas">0</span>
                          </div>
                          <div id="atividades-proximas" class="atividades-lista">
                              <!-- Preenchido dinamicamente -->
                          </div>
                      </div>

                      <!-- Atividades Concluídas Hoje -->
                      <div class="status-section">
                          <div class="section-header concluidas">
                              <h3><i class="fas fa-check"></i> Concluídas Hoje</h3>
                              <span class="count" id="count-concluidas-hoje">0</span>
                          </div>
                          <div id="atividades-concluidas-hoje" class="atividades-lista">
                              <!-- Preenchido dinamicamente -->
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Aba Todas as Atividades -->
              <div id="tab-atividades" class="tab-content-monitoramento" style="display: none;">
                  <!-- Filtros -->
                  <div class="filtros-atividades">
                      <div class="filtro-item">
                          <label>Status</label>
                          <select id="filtro-status-atividade">
                              <option value="">Todos</option>
                              <option value="planejada">Planejadas</option>
                              <option value="em_andamento">Em Andamento</option>
                              <option value="concluida">Concluídas</option>
                              <option value="cancelada">Canceladas</option>
                          </select>
                      </div>
                      
                      <div class="filtro-item">
                          <label>Tipo de Atividade</label>
                          <select id="filtro-tipo-atividade">
                              <option value="">Todas</option>
                              <option value="Ed. Física">Ed. Física</option>
                              <option value="Salvamento">Salvamento</option>
                              <option value="Informática">Informática</option>
                              <option value="Primeiro Socorros">Primeiro Socorros</option>
                              <option value="Ordem Unida">Ordem Unida</option>
                              <option value="Combate a Incêndio">Combate a Incêndio</option>
                              <option value="Ética e Cidadania">Ética e Cidadania</option>
                              <option value="Higiene Pessoal">Higiene Pessoal</option>
                              <option value="Meio Ambiente">Meio Ambiente</option>
                              <option value="Educação no Trânsito">Educação no Trânsito</option>
                              <option value="Temas Transversais">Temas Transversais</option>
                              <option value="Combate uso de Drogas">Combate uso de Drogas</option>
                              <option value="ECA e Direitos Humanos">ECA e Direitos Humanos</option>
                              <option value="Treinamento de Formatura">Treinamento de Formatura</option>
                          </select>
                      </div>
                      
                      <div class="filtro-item">
                          <label>Data</label>
                          <input type="date" id="filtro-data-atividade">
                      </div>
                      
                      <button class="btn btn-primary btn-sm" onclick="aplicarFiltrosAtividades()">
                          <i class="fas fa-filter"></i> Filtrar
                      </button>
                      
                      <button class="btn btn-outline btn-sm" onclick="limparFiltrosAtividades()">
                          <i class="fas fa-eraser"></i> Limpar
                      </button>
                  </div>

                  <!-- Lista de Todas as Atividades -->
                  <div id="todas-atividades" class="todas-atividades-lista">
                      <!-- Preenchido dinamicamente -->
                  </div>
              </div>

              <!-- Aba Calendário -->
              <div id="tab-calendario" class="tab-content-monitoramento" style="display: none;">
                  <div class="calendario-header">
                      <button class="btn btn-outline btn-sm" id="mes-anterior">
                          <i class="fas fa-chevron-left"></i>
                      </button>
                      <h3 id="mes-atual"></h3>
                      <button class="btn btn-outline btn-sm" id="mes-proximo">
                          <i class="fas fa-chevron-right"></i>
                      </button>
                  </div>
                  
                  <div id="calendario-atividades" class="calendario">
                      <!-- Calendário será gerado dinamicamente -->
                  </div>
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-monitoramento">
                  <i class="fas fa-times"></i> Fechar
              </button>
              <button class="btn btn-success" onclick="exportarRelatorioAtividades()">
                  <i class="fas fa-download"></i> Exportar Relatório
              </button>
          </div>
      </div>
  </div>

  <!-- Modal Detalhes da Atividade -->
  <div id="modal-detalhes-atividade" class="modal-backdrop" style="display: none;">
      <div class="modal modal-medium">
          <div class="modal-header">
              <span><i class="fas fa-info-circle"></i> Detalhes da Atividade</span>
              <button class="fechar-modal-detalhes">×</button>
          </div>
          
          <div class="modal-body">
              <div class="detalhes-atividade">
                  <div class="atividade-header">
                      <h2 id="detalhe-nome-atividade"></h2>
                      <span id="detalhe-status-badge" class="status-badge"></span>
                  </div>
                  
                  <div class="atividade-info">
                      <div class="info-grupo">
                          <h4><i class="fas fa-calendar"></i> Data e Horário</h4>
                          <p id="detalhe-data-horario"></p>
                      </div>
                      
                      <div class="info-grupo">
                          <h4><i class="fas fa-map-marker-alt"></i> Local</h4>
                          <p id="detalhe-local"></p>
                      </div>
                      
                      <div class="info-grupo">
                          <h4><i class="fas fa-chalkboard-teacher"></i> Instrutor</h4>
                          <p id="detalhe-instrutor"></p>
                      </div>
                      
                      <div class="info-grupo">
                          <h4><i class="fas fa-users"></i> Turma</h4>
                          <p id="detalhe-turma"></p>
                      </div>
                      
                      <div class="info-grupo">
                          <h4><i class="fas fa-bullseye"></i> Objetivo</h4>
                          <p id="detalhe-objetivo"></p>
                      </div>
                      
                      <div class="info-grupo">
                          <h4><i class="fas fa-book"></i> Conteúdo Abordado</h4>
                          <p id="detalhe-conteudo"></p>
                      </div>
                  </div>
                  
                  <div class="acoes-atividade">
                      <button class="btn btn-primary btn-sm" onclick="editarStatusAtividade()">
                          <i class="fas fa-edit"></i> Alterar Status
                      </button>
                  </div>
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-detalhes">
                  <i class="fas fa-times"></i> Fechar
              </button>
          </div>
      </div>
  </div>

  <!-- Modal Principal de Uniformes -->
  <div id="modal-uniformes" class="modal-backdrop" style="display: none;">
      <div class="modal modal-large">
          <div class="modal-header">
              <span><i class="fas fa-tshirt"></i> Gerenciar Uniformes</span>
              <button class="fechar-modal-uniformes">×</button>
          </div>
          
          <!-- Abas do Modal -->
          <div class="modal-tabs">
              <button class="tab-uniformes ativo" data-tab="listagem">
                  <i class="fas fa-list"></i> Listagem de Uniformes
              </button>
             <!-- <button class="tab-uniformes" data-tab="estatisticas">
                  <i class="fas fa-chart-pie"></i> Estatísticas
              </button> -->
          </div>

          <div class="modal-body">
              <!-- Aba Listagem -->
              <div id="tab-listagem" class="tab-content-uniformes">
                  <!-- Filtros -->
                  <div class="uniformes-filtros">
                      <div class="filtros-row">
                          <div class="filtro-item">
                              <label>Buscar Aluno</label>
                              <input type="text" id="filtro-nome-aluno" placeholder="Nome do aluno">
                          </div>
                          
                          <div class="filtro-item">
                              <label>Turma</label>
                              <select id="filtro-turma-uniformes">
                                  <option value="">Todas as turmas</option>
                              </select>
                          </div>
                          
                          <div class="filtro-item">
                              <label>Unidade</label>
                              <select id="filtro-unidade-uniformes">
                                  <option value="">Todas as unidades</option>
                              </select>
                          </div>
                          
                          <div class="filtro-item">
                              <label>Status</label>
                              <select id="filtro-status-uniformes">
                                  <option value="">Todos</option>
                                  <option value="completo">Completo</option>
                                  <option value="incompleto">Incompleto</option>
                                  <option value="pendente">Pendente</option>
                              </select>
                          </div>
                          
                          <div class="filtro-actions">
                              <button class="btn btn-primary btn-sm" onclick="filtrarUniformes()">
                                  <i class="fas fa-search"></i> Filtrar
                              </button>
                              <button class="btn btn-outline btn-sm" onclick="limparFiltrosUniformes()">
                                  <i class="fas fa-eraser"></i> Limpar
                              </button>
                          </div>
                      </div>
                  </div>

                  <!-- Contador de Resultados -->
                  <div class="uniformes-contador">
                      <span id="total-uniformes">0</span> alunos encontrados
                  </div>

                  <!-- Lista de Uniformes -->
                  <div id="lista-uniformes" class="uniformes-lista">
                      <div class="uniformes-header">
                          <div class="header-col">Aluno</div>
                          <div class="header-col">Turma</div>
                          <div class="header-col">Camisa</div>
                          <div class="header-col">Calça</div>
                          <div class="header-col">Calçado</div>
                          <div class="header-col">Status</div>
                          <div class="header-col">Ações</div>
                      </div>
                      
                      <div id="uniformes-content">
                          <!-- Será preenchido dinamicamente -->
                      </div>
                  </div>
              </div>

              <!-- Aba Relatórios -->
              <div id="tab-relatorios" class="tab-content-uniformes" style="display: none;">
                  <div class="relatorios-header">
                      <h3>📊 Relatórios de Uniformes</h3>
                      <p>Gere relatórios detalhados sobre os uniformes dos alunos</p>
                  </div>
                  
                  <div class="relatorios-grid">
                      <div class="relatorio-card" onclick="gerarRelatorioGeralUniformes()">
                          <div class="relatorio-icon">
                              <i class="fas fa-file-alt"></i>
                          </div>
                          <div class="relatorio-info">
                              <h4>Relatório Geral</h4>
                              <p>Lista completa de todos os alunos e seus uniformes</p>
                          </div>
                      </div>
                      
                      <div class="relatorio-card" onclick="gerarRelatorioPorTurma()">
                          <div class="relatorio-icon">
                              <i class="fas fa-users"></i>
                          </div>
                          <div class="relatorio-info">
                              <h4>Relatório por Turma</h4>
                              <p>Relatório organizado por turmas</p>
                          </div>
                      </div>
                      
                      <div class="relatorio-card" onclick="gerarRelatorioTamanhos()">
                          <div class="relatorio-icon">
                              <i class="fas fa-ruler"></i>
                          </div>
                          <div class="relatorio-info">
                              <h4>Relatório de Tamanhos</h4>
                              <p>Quantidades por tamanho para compras</p>
                          </div>
                      </div>
                      
                      <div class="relatorio-card" onclick="gerarRelatorioIncompletos()">
                          <div class="relatorio-icon">
                              <i class="fas fa-exclamation-triangle"></i>
                          </div>
                          <div class="relatorio-info">
                              <h4>Pendentes</h4>
                              <p>Alunos com dados incompletos</p>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Aba Estatísticas -->
              <div id="tab-estatisticas" class="tab-content-uniformes" style="display: none;">
                  <div class="estatisticas-header">
                      <h3>📈 Estatísticas de Uniformes</h3>
                  </div>
                  
                  <div class="estatisticas-cards">
                      <div class="card-estatistica">
                          <div class="card-icon">
                              <i class="fas fa-tshirt"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-camisas">0</h3>
                              <p>Total de Camisas</p>
                          </div>
                      </div>
                      
                      <div class="card-estatistica">
                          <div class="card-icon">
                              <i class="fas fa-user-friends"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-calcas">0</h3>
                              <p>Total de Calças</p>
                          </div>
                      </div>
                      
                      <div class="card-estatistica">
                          <div class="card-icon">
                              <i class="fas fa-shoe-prints"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="total-calcados">0</h3>
                              <p>Total de Calçados</p>
                          </div>
                      </div>
                      
                      <div class="card-estatistica">
                          <div class="card-icon">
                              <i class="fas fa-check-circle"></i>
                          </div>
                          <div class="card-info">
                              <h3 id="uniformes-completos">0</h3>
                              <p>Uniformes Completos</p>
                          </div>
                      </div>
                  </div>
                  
                  <!-- Gráficos de Tamanhos -->
                  <div class="graficos-tamanhos">
                      <div class="grafico-container">
                          <h4>📊 Distribuição de Tamanhos - Camisas</h4>
                          <div id="grafico-camisas" class="grafico">Carregando estatísticas...</div>
                      </div>
                      
                      <div class="grafico-container">
                          <h4>📊 Distribuição de Tamanhos - Calças</h4>
                          <div id="grafico-calcas" class="grafico">Carregando estatísticas...</div>
                      </div>
                      
                      <div class="grafico-container">
                          <h4>📊 Distribuição de Tamanhos - Calçados</h4>
                          <div id="grafico-calcados" class="grafico">Carregando estatísticas...</div>
                      </div>
                  </div>
              </div>
          </div>
          
          <div class="modal-footer">
              <button class="btn btn-outline fechar-modal-uniformes">
                  <i class="fas fa-times"></i> Fechar
              </button>
              <button class="btn btn-success" onclick="exportarTodosRelatorios()">
                  <i class="fas fa-download"></i> Exportar Todos
              </button>
          </div>
      </div>
  </div>

  <!-- Modal de Edição de Uniforme -->
  <div id="modal-editar-uniforme" class="modal-backdrop" style="display: none;">
      <div class="modal modal-medium">
          <div class="modal-header">
              <span><i class="fas fa-edit"></i> Editar Uniforme</span>
              <button class="fechar-modal-editar-uniforme">×</button>
          </div>
          
          <div class="modal-body">
              <form id="form-editar-uniforme">
                  <input type="hidden" id="edit-aluno-id" name="aluno_id">
                  
                  <div class="aluno-info-edit">
                      <div class="aluno-avatar">
                          <i class="fas fa-user-circle"></i>
                      </div>
                      <div class="aluno-dados">
                          <h3 id="edit-aluno-nome"></h3>
                          <p id="edit-aluno-turma"></p>
                          <p id="edit-aluno-matricula"></p>
                      </div>
                  </div>
                  
                  <div class="uniformes-inputs">
                      <div class="form-group">
                          <label for="edit-tamanho-camisa">
                              <i class="fas fa-tshirt"></i> Tamanho da Camisa
                          </label>
                          <select id="edit-tamanho-camisa" name="tamanho_camisa" required>
                              <option value="">Selecione o tamanho</option>
                              <option value="pp">PP</option>
                              <option value="p">P</option>
                              <option value="m">M</option>
                              <option value="g">G</option>
                              <option value="gg">GG</option>
                              <option value="xgg">XGG</option>
                          </select>
                      </div>
                      
                      <div class="form-group">
                          <label for="edit-tamanho-calca">
                              <i class="fas fa-user-friends"></i> Tamanho da Calça
                          </label>
                          <select id="edit-tamanho-calca" name="tamanho_calca" required>
                              <option value="">Selecione o tamanho</option>
                              <option value="pp">PP</option>
                              <option value="p">P</option>
                              <option value="m">M</option>
                              <option value="g">G</option>
                              <option value="gg">GG</option>
                              <option value="xgg">XGG</option>
                              <option value="4">4</option>
                              <option value="6">6</option>
                              <option value="8">8</option>
                              <option value="10">10</option>
                              <option value="12">12</option>
                              <option value="14">14</option>
                              <option value="16">16</option>
                          </select>
                      </div>
                      
                      <div class="form-group">
                          <label for="edit-tamanho-calcado">
                              <i class="fas fa-shoe-prints"></i> Tamanho do Calçado
                          </label>
                          <select id="edit-tamanho-calcado" name="tamanho_calcado" required>
                              <option value="">Selecione o tamanho</option>
                              <option value="28">28</option>
                              <option value="29">29</option>
                              <option value="30">30</option>
                              <option value="31">31</option>
                              <option value="32">32</option>
                              <option value="33">33</option>
                              <option value="34">34</option>
                              <option value="35">35</option>
                              <option value="36">36</option>
                              <option value="37">37</option>
                              <option value="38">38</option>
                              <option value="39">39</option>
                              <option value="40">40</option>
                              <option value="41">41</option>
                              <option value="42">42</option>
                              <option value="43">43</option>
                              <option value="44">44</option>
                              <option value="45">45</option>
                          </select>
                      </div>
                  </div>
                  
                  <div class="form-actions">
                      <button type="button" class="btn btn-outline fechar-modal-editar-uniforme">
                          <i class="fas fa-times"></i> Cancelar
                      </button>
                      <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> Salvar Alterações
                      </button>
                  </div>
              </form>
          </div>
      </div>
  </div>

  <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <i class="fas fa-fire-extinguisher"></i> Bombeiro Mirim do Estado de Goiás
        </div>
        <div class="footer-info">
          <p>© 2024 Projeto Bombeiro Mirim – Associação dos Subtenentes e Sargentos da PM e BM do Estado de Goiás</p>
          <p>Painel de Gerenciamento de Matrículas</p>
          <p>Desenvolvido por <a href="https://www.instagram.com/assego/" class="ftlink">@Assego</a></p>
        </div>
      </div>
    </div>
  </footer>
    
  <!-- Loading overlay -->
  <div id="loading-overlay" class="loading-overlay" style="display: none;">
    <div class="spinner-container">
      <div class="spinner"></div>
      <p>Carregando...</p>
    </div>
  </div>

<script>
window.IS_ADMIN = true;
window.usuarioNome = '<?= addslashes($usuario_nome) ?>';
window.usuarioId = <?= $_SESSION['usuario_id'] ?? 6 ?>;
console.log('🔧 Usuário identificado como admin:', window.IS_ADMIN);
console.log('👤 Usuário:', window.usuarioNome, 'ID:', window.usuarioId);
</script>

<script>
  window.addEventListener('click', function(e) {
    const menu = document.getElementById('user-menu');
    const userInfo = document.querySelector('.user-info-container');
    if (menu && !userInfo.contains(e.target)) {
      menu.classList.remove('show');
    }
  });

  // Carregar cidades quando a página carregar - usando a função do JavaScript principal
  document.addEventListener('DOMContentLoaded', function() {
    // A função carregarCidadesIBGE está no arquivo teste1.js
    if (typeof carregarCidadesIBGE === 'function') {
      carregarCidadesIBGE('lista-cidades'); // Para o modal de Nova Unidade
    }
  });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script src="./js/teste1.js"></script>
<script src="./js/galeria.js"></script>
<script src="./js/financeiro.js"></script>
<script src="./js/controle.js"></script>
<script src="./js/comunicado.js"></script>
<script src="./js/admin_ocorrencias.js"></script>
<script src="./js/atividades.js"></script>
<script src="./js/monitoramento.js"></script>
<script src="./js/ranking.js"></script>
<script src="./js/uniformes.js"></script>
<script src="./js/alunos.js"></script>
</body>
</html>