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


.financeiro-tabs {
  display: flex;
  border-bottom: 2px solid #e0e0e0;
  margin-bottom: 20px;
}

.tab-btn {
  background: none;
  border: none;
  padding: 12px 20px;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #666;
}

.tab-btn.active {
  color: #e74c3c;
  border-bottom-color: #e74c3c;
  background: rgba(231, 76, 60, 0.1);
}

.tab-btn:hover {
  background: rgba(231, 76, 60, 0.05);
  color: #e74c3c;
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.estoque-header, .romaneio-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.estoque-filtros, .romaneio-filtros {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
}

.romaneio-actions {
  display: flex;
  gap: 10px;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
}

.status-disponivel {
  background: #d4edda;
  color: #155724;
}

.status-baixo {
  background: #fff3cd;
  color: #856404;
}

.status-esgotado {
  background: #f8d7da;
  color: #721c24;
}

.modal[style*="max-width: 1200px"] .modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .financeiro-tabs {
    flex-wrap: wrap;
  }
  
  .tab-btn {
    flex: 1;
    min-width: 120px;
  }
  
  .estoque-filtros, .romaneio-filtros {
    flex-direction: column;
  }
  
  .romaneio-actions {
    flex-direction: column;
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
      <button class="btn btn-primary" id="novo-professor-btn" onclick="window.location.href='dashboard.php'">
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
            <label>Coordenador</label>
            <input type="text" name="coordenador" placeholder="Nome do coordenador" />
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

  <!-- Modal Módulo Financeiro CORRIGIDO -->
  <div id="modulo-financeiro-modal" class="modal-backdrop" style="display: none;">
    <div class="modal" style="max-width: 1200px; width: 95%;">
      <div class="modal-header">
        <span><i class="fas fa-dollar-sign"></i> Módulo Financeiro - Controle de Materiais</span>
        <button onclick="document.getElementById('modulo-financeiro-modal').style.display='none'">×</button>
      </div>
      <div class="modal-body">
        
        <!-- Abas do Módulo CORRIGIDAS -->
        <div class="financeiro-tabs">
          <button class="tab-btn active" data-tab="entrada">
            <i class="fas fa-plus-circle"></i> Entrada de Materiais
          </button>
          <button class="tab-btn" data-tab="saida">
            <i class="fas fa-minus-circle"></i> Saída de Materiais
          </button>
          <button class="tab-btn" data-tab="estoque">
            <i class="fas fa-boxes"></i> Controle de Estoque
          </button>
          <button class="tab-btn" data-tab="romaneio">
            <i class="fas fa-clipboard-list"></i> Romaneio de Uniformes
          </button>
        </div>

        <!-- Conteúdo das Abas CORRIGIDO -->
        <div class="tab-content">
          
          <!-- Aba Entrada de Materiais -->
          <div id="entrada-tab" class="tab-pane active">
            <form id="entrada-material-form">
              <div class="form-row">
                <div class="form-group">
                  <label>Tipo de Material</label>
                  <select name="tipo_material" id="tipo-material-entrada" required>
                    <option value="">Selecione o tipo</option>
                    <option value="uniforme">Uniforme</option>
                    <option value="material_didatico">Material Didático</option>
                    <option value="equipamento">Equipamento</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Item</label>
                  <select name="item" id="item-entrada" required>
                    <option value="">Selecione primeiro o tipo</option>
                  </select>
                </div>
                
                <div class="form-group" id="tamanho-grupo-entrada" style="display: none;">
                  <label>Tamanho</label>
                  <select name="tamanho" id="tamanho-entrada">
                    <option value="">Selecione o tamanho</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Quantidade</label>
                  <input type="number" name="quantidade" min="1" required />
                </div>
                
                <div class="form-group">
                  <label>Valor Unitário (R$)</label>
                  <input type="number" name="valor_unitario" step="0.01" min="0" />
                </div>
                
                <div class="form-group">
                  <label>Fornecedor</label>
                  <input type="text" name="fornecedor" placeholder="Nome do fornecedor" />
                </div>
              </div>

              <div class="form-group">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Observações sobre a entrada..."></textarea>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-plus"></i> Registrar Entrada
                </button>
              </div>
            </form>
          </div>

          <!-- Aba Saída de Materiais -->
          <div id="saida-tab" class="tab-pane">
            <form id="saida-material-form">
              <div class="form-row">
                <div class="form-group">
                  <label>Tipo de Material</label>
                  <select name="tipo_material" id="tipo-material-saida" required>
                    <option value="">Selecione o tipo</option>
                    <option value="uniforme">Uniforme</option>
                    <option value="material_didatico">Material Didático</option>
                    <option value="equipamento">Equipamento</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Item</label>
                  <select name="item" id="item-saida" required>
                    <option value="">Selecione primeiro o tipo</option>
                  </select>
                </div>
                
                <div class="form-group" id="tamanho-grupo-saida" style="display: none;">
                  <label>Tamanho</label>
                  <select name="tamanho" id="tamanho-saida">
                    <option value="">Selecione o tamanho</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Aluno</label>
                  <select name="aluno_id" id="aluno-saida" required>
                    <option value="">Carregando alunos...</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Turma</label>
                  <select name="turma_id" id="turma-saida" required>
                    <option value="">Selecione o aluno primeiro</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Quantidade</label>
                  <input type="number" name="quantidade" min="1" required />
                </div>
              </div>

              <div class="form-group">
                <label>Motivo da Saída</label>
                <select name="motivo" required>
                  <option value="">Selecione o motivo</option>
                  <option value="entrega_uniforme">Entrega de Uniforme</option>
                  <option value="reposicao">Reposição</option>
                  <option value="material_didatico">Material Didático</option>
                  <option value="evento">Evento/Atividade</option>
                  <option value="outros">Outros</option>
                </select>
              </div>

              <div class="form-group">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Observações sobre a saída..."></textarea>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-danger">
                  <i class="fas fa-minus"></i> Registrar Saída
                </button>
              </div>
            </form>
          </div>

          <!-- Aba Controle de Estoque -->
          <div id="estoque-tab" class="tab-pane">
            <div class="estoque-header">
              <h3><i class="fas fa-boxes"></i> Estoque Atual</h3>
              <button class="btn btn-primary" id="atualizar-estoque">
                <i class="fas fa-sync"></i> Atualizar
              </button>
            </div>

            <div class="estoque-filtros">
              <select id="filtro-tipo-estoque">
                <option value="">Todos os tipos</option>
                <option value="uniforme">Uniformes</option>
                <option value="material_didatico">Material Didático</option>
                <option value="equipamento">Equipamentos</option>
              </select>
              
              <input type="text" id="filtro-item-estoque" placeholder="Buscar item..." />
            </div>

            <div class="table-container">
              <table id="tabela-estoque">
                <thead>
                  <tr>
                    <th>Tipo</th>
                    <th>Item</th>
                    <th>Tamanho</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Dados carregados dinamicamente -->
                </tbody>
              </table>
            </div>
          </div>

          <!-- Aba Romaneio de Uniformes -->
          <div id="romaneio-tab" class="tab-pane">
            <div class="romaneio-header">
              <h3><i class="fas fa-clipboard-list"></i> Romaneio de Uniformes</h3>
              <div class="romaneio-actions">
                <button class="btn btn-success" id="gerar-romaneio">
                  <i class="fas fa-file-excel"></i> Gerar Romaneio Excel
                </button>
                <button class="btn btn-primary" id="imprimir-romaneio">
                  <i class="fas fa-print"></i> Imprimir
                </button>
              </div>
            </div>

            <div class="romaneio-filtros">
              <select id="filtro-turma-romaneio">
                <option value="">Todas as turmas</option>
              </select>
              
              <select id="filtro-item-romaneio">
                <option value="">Todos os itens</option>
                <option value="calça">Calça</option>
                <option value="camiseta">Camiseta</option>
                <option value="calção">Calção</option>
                <option value="calçado">Calçado</option>
                <option value="maiô">Maiô</option>
                <option value="sunga">Sunga</option>
              </select>
              
              <button class="btn btn-outline" id="filtrar-romaneio">
                <i class="fas fa-filter"></i> Filtrar
              </button>
            </div>

            <div class="table-container">
              <table id="tabela-romaneio">
                <thead>
                  <tr>
                    <th>Aluno</th>
                    <th>Turma</th>
                    <th>Calça</th>
                    <th>Camiseta</th>
                    <th>Calção</th>
                    <th>Calçado</th>
                    <th>Maiô/Sunga</th>
                    <th>Data Entrega</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Dados carregados dinamicamente -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
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
console.log('🔧 Usuário identificado como admin:', window.IS_ADMIN);
</script>

<script>
  window.addEventListener('click', function(e) {
    const menu = document.getElementById('user-menu');
    const userInfo = document.querySelector('.user-info-container');
    if (menu && !userInfo.contains(e.target)) {
      menu.classList.remove('show');
    }
  });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script src="./js/teste1.js"></script>
<script src="./js/galeria.js"></script>
<script src="./js/financeiro.js"></script>
</body>
</html>