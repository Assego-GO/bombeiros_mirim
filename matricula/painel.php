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
  <link rel="stylesheet" href="./css/painel.css"/>
    <style>
   
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
            <label>Data Início</label>
            <input type="date" name="data_inicio" required />
          </div>

           <div class="form-group">
            <label>Data Fim</label>
            <input type="date" name="data_fim" required />
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

<!-- Modal de Detalhes do Aluno no Ranking -->
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