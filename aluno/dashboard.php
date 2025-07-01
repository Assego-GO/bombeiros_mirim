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
    <title>Painel do Aluno - Bombeiro Mirim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/comunicados.css">
    <link rel="stylesheet" href="css/comunicados-badge.css">
    <link rel="stylesheet" href="css/leitura-badge.css">
    
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
                    <h3>
                        <?php echo htmlspecialchars($usuario_nome); ?>
                        <span class="bombeiro-badge">🚒 Bombeiro</span>
                    </h3>
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
            <h1>
                <i class="fas fa-fire-extinguisher"></i>
                Bem-vindo, <?php echo htmlspecialchars($usuario_nome); ?>!
            </h1>
            <p>🚒 Área do Bombeiro Mirim. Aqui você pode acessar suas informações e acompanhar seu desenvolvimento no projeto.</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card" id="card-matricula">
                <div class="card-icon">
                    <i class="fas fa-id-badge"></i>
                </div>
                <h2>Minha Matrícula</h2>
                <p>Veja os dados da sua matrícula no programa Bombeiro Mirim.</p>
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
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2>Avaliações</h2>
                <p>Veja suas avaliações e progresso no curso.</p>
            </div>
            
            
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Atividades</h2>
                <p>Acompanhe as atividades e exercícios práticos de bombeiro.</p>
            </div>

            <div class="dashboard-card" id="card-galeria">
                <div class="card-icon">
                    <i class="fas fa-image"></i>
                </div>
                <h2>Galeria de Fotos</h2>
                <p>Veja suas fotos e de sua turma</p>
                </div>

            
          <!-- Card de comunicados - VERSÃO CORRIGIDA (remova o badge do HTML) -->
            <div class="dashboard-card" id="card-comunicados">
                <div class="card-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h2>Comunicados</h2>
                <p>Veja comunicados dos seus orientadores</p>
            </div>

            
            <div class="dashboard-card" id="card-questionario">
                <div class="card-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h2>Questionário</h2>
                <p>Responda a essas perguntas para a melhoria do nosso projeto</p>
            </div>
            
        
        </div>
        
        <!-- Modal de Matrícula -->
        <div id="gerenciaModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <h2 id="modalTitle">🚒 Minha Matrícula - Bombeiro Mirim</h2>
                                
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
                    <h2 id="modalTitlePerfil">🚒 Meu Perfil - Bombeiro Mirim</h2>
                    
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
                    <h2>🚒 Editar Perfil - Bombeiro Mirim</h2>
                    
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
        
        <!-- Modal de Atividades -->
        <div id="atividadesModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeAtividadesModal">&times;</span>
                <h2 id="modalTitleAtividades">🏃‍♂️ Minhas Atividades - Bombeiro Mirim</h2>
                
                <div class="atividades-turma-info">
                    <div class="matricula-group">
                        <label>Turma:</label>
                        <p id="atividades-turma-nome"></p>
                    </div>
                    <div class="matricula-group">
                        <label>Unidade:</label>
                        <p id="atividades-unidade-nome"></p>
                    </div>
                </div>
                
                <div id="atividades-lista">
                    <!-- Atividades serão carregadas aqui via JavaScript -->
                </div>
            </div>
        </div>

        <div id="comunicadosModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeComunicadosModal">&times;</span>
                <h2 id="modalTitleComunicados">📢 Comunicados - Bombeiro Mirim</h2>
                
                <div class="comunicados-turma-info">
                    <div class="matricula-group">
                        <label>Turma:</label>
                        <p id="comunicados-turma-nome"></p>
                    </div>
                    <div class="matricula-group">
                        <label>Unidade:</label>
                        <p id="comunicados-unidade-nome"></p>
                    </div>
                </div>
                
                <div id="comunicados-lista">
                    <!-- Comunicados serão carregados aqui via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <i class="fas fa-fire-extinguisher"></i> Bombeiro Mirim - Salvando Vidas
        </div>
        <div class="footer-info">
          <p>© 2024 Projeto Bombeiro Mirim - O Projeto é uma iniciativa da ASSEGO – Associação dos Subtenentes e Sargentos da PM e BM do Estado de Goiás</p>
          <p>🚒 Painel do Aluno - Sistema de Gerenciamento</p>
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
    <script src="./js/comunicados.js"></script>
</body>
</html>