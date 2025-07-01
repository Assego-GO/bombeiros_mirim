<?php
session_start();

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
// Configuração do banco de dados


try {
  // Conexão com o banco de dados
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
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

/* Modal Grande */
.modal-large {
    width: 90%;
    max-width: 900px;
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
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            <a href="auditoria_page.php"><i class="fas fa-clipboard-check"></i> Auditoria</a>
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
      <button class="btn btn-primary" id="galeria-fotos-btn">
    <i class="fas fa-camera"></i> Galeria de Fotos
  </button>
  <button class="btn btn-primary" id="modulo-financeiro-btn">
    <i class="fas fa-dollar-sign"></i> Módulo Financeiro
  </button>

  <button class="btn btn-primary" id="saida-btn">
  <i class="fas fa-sign-out-alt"></i> Controle de Materiais
</button>
<button class="btn btn-primary" id="comunicado-btn">
  <i class="fas fa-bullhorn"></i>Gerar Comunicado
</button>

      <button class="btn btn-primary" id="ver-relatorio-btn" onclick="window.location.href='dashboard.php'">
    <i class="fas fa-chart-bar"></i> Ver Relatório
    </button>
      <button class="btn btn-primary" id="gerar-carterinha-btn">
      <i class="fas fa-id-card"></i> Gerar Carteirinha
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script src="./js/teste1.js"></script>
<script src="./js/galeria.js"></script>
<script src="./js/financeiro.js"></script>
<script src="./js/controle.js"></script>
<script src="./js/comunicado.js"></script>

</body>
</html>