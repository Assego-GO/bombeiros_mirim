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
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocolo . $host . '';

// Verificar e ajustar o caminho da foto para exibição
if (!empty($professor['foto'])) {
    // Obter apenas o nome do arquivo, independente do que esteja no banco
    $filename = basename($professor['foto']);
    
    // Definir o caminho correto da foto - caminho direto e absoluto
    $usuario_foto = $baseUrl . '/uploads/fotos/' . $filename;
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
    <link rel="stylesheet" type="text/css" href="css/dashboard.css"/>
   
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

            <div class="dashboard-card" id="card-galeria">
                <div class="card-icon">
                    <i class="fas fa-image"></i>
                </div>
                <h2>Galeria de Fotos</h2>
                <p>Adicione fotos e videos as turmas.</p>
                </div>

            <div class="dashboard-card" id="card-perfil">
                <div class="card-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h2>Meu Perfil</h2>
                <p>Atualize suas informações pessoais e configurações da conta.</p>
            </div>

            <div class="dashboard-card" id="card-ocorrencias" style="cursor: pointer;">
            <div class="card-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Gerar Ocorrência</h2>
            <p>Informe uma ocorrência com aluno e turma.</p>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Agenda</h2>
                <p>Em desenvolvimento......</p>
            </div>
            
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal de Atividades (Principal) -->
        <div id="atividadesModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeAtividadesModal">&times;</span>
                <h2 id="modalTitleAtividades">Minhas Atividades</h2>
                
                <div style="margin-bottom: 20px; text-align: right;">
                    <button id="btn-nova-atividade" class="btn">
                        <i class="fas fa-plus"></i> Nova Atividade
                    </button>
                </div>
                
                <div id="atividades-lista-container">
                    <p>Carregando atividades...</p>
                </div>
            </div>
        </div>

       <!-- Modal de Cadastro/Edição de Atividade -->
<div id="cadastroAtividadeModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeCadastroAtividadeModal">&times;</span>
        <h2 id="modalTitleCadastroAtividade">Nova Atividade</h2>
        
        <div id="mensagem-atividade"></div>
        
        <form id="form-atividade" method="post">
            <input type="hidden" name="action" value="cadastrar">
            <input type="hidden" id="atividade_id" name="atividade_id" value="">
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="nome_atividade" class="form-label">Tipo de Atividade <span style="color: red;">*</span></label>
                        <select id="nome_atividade" name="nome_atividade" class="form-control" required>
                            <option value="">Selecione o tipo de atividade</option>
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
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="turma_id" class="form-label">Turma <span style="color: red;">*</span></label>
                        <select id="turma_id" name="turma_id" class="form-control" required>
                            <option value="">Selecione uma turma</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="data_atividade" class="form-label">Data <span style="color: red;">*</span></label>
                        <input type="date" id="data_atividade" name="data_atividade" class="form-control" required>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="local_atividade" class="form-label">Local <span style="color: red;">*</span></label>
                        <input type="text" id="local_atividade" name="local_atividade" class="form-control" required 
                               placeholder="Ex: Quadra esportiva, Sala de aula...">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="hora_inicio" class="form-label">Hora Início <span style="color: red;">*</span></label>
                        <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" required>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="hora_termino" class="form-label">Hora Término <span style="color: red;">*</span></label>
                        <input type="time" id="hora_termino" name="hora_termino" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="instrutor_responsavel" class="form-label">Instrutor Responsável <span style="color: red;">*</span></label>
                <input type="text" id="instrutor_responsavel" name="instrutor_responsavel" class="form-control" required
                       placeholder="Nome do instrutor que conduzirá a atividade">
            </div>
            
            <div class="form-group">
                <label for="objetivo_atividade" class="form-label">Objetivo da Atividade <span style="color: red;">*</span></label>
                <textarea id="objetivo_atividade" name="objetivo_atividade" class="form-control" rows="3" required
                          placeholder="Descreva os objetivos que se pretende alcançar com esta atividade..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="conteudo_abordado" class="form-label">Conteúdo Abordado <span style="color: red;">*</span></label>
                <textarea id="conteudo_abordado" name="conteudo_abordado" class="form-control" rows="3" required
                          placeholder="Descreva o conteúdo que será abordado na atividade..."></textarea>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Salvar Atividade
                </button>
                <button type="button" id="btn-cancelar-atividade" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

        <!-- Modal de Detalhes da Atividade -->
        <div id="detalhesAtividadeModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeDetalhesAtividadeModal">&times;</span>
                <h2 id="modalTitleDetalhesAtividade">Detalhes da Atividade</h2>
                
                <div id="detalhes-atividade-container">
                    <p>Carregando detalhes...</p>
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
        
        <!-- Modal de Alunos -->
        <div id="alunosModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeAlunosModal">&times;</span>
                <h2 id="modalTitleAlunos">Alunos da Turma</h2>
                
                <div id="alunos-lista-container">
                    <p>Carregando lista de alunos...</p>
                </div>
            </div>
        </div>

        <!-- Modal de Ocorrências -->
        <div id="ocorrenciasModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeOcorrenciasModal">&times;</span>
                <h2 id="modalTitleOcorrencias">Gerar Ocorrência</h2>
                
                <div style="margin-bottom: 20px; text-align: right;">
                    <button id="btn-nova-ocorrencia" class="btn">
                        <i class="fas fa-plus"></i> Nova Ocorrência
                    </button>
                    <button id="btn-listar-ocorrencias" class="btn btn-secondary">
                        <i class="fas fa-list"></i> Listar Ocorrências
                    </button>
                </div>
                
                <div id="ocorrencias-container">
                    <p>Selecione uma opção acima para começar.</p>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro de Ocorrência -->
        <div id="cadastroOcorrenciaModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeCadastroOcorrenciaModal">&times;</span>
                <h2 id="modalTitleCadastroOcorrencia">Nova Ocorrência</h2>
                
                <div id="mensagem-ocorrencia"></div>
                
                <form id="form-ocorrencia" method="post">
                    <input type="hidden" name="action" value="cadastrar">
                    <input type="hidden" id="ocorrencia_id" name="ocorrencia_id" value="">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="turma_ocorrencia" class="form-label">Turma <span style="color: red;">*</span></label>
                                <select id="turma_ocorrencia" name="turma_id" class="form-control" required>
                                    <option value="">Selecione uma turma</option>
                                    <?php foreach ($turmas as $turma): ?>
                                        <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="aluno_ocorrencia" class="form-label">Aluno <span style="color: red;">*</span></label>
                                <select id="aluno_ocorrencia" name="aluno_id" class="form-control" required>
                                    <option value="">Primeiro selecione uma turma</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="data_ocorrencia" class="form-label">Data da Ocorrência <span style="color: red;">*</span></label>
                                <input type="date" id="data_ocorrencia" name="data_ocorrencia" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao_ocorrencia" class="form-label">Descrição da Ocorrência <span style="color: red;">*</span></label>
                        <textarea id="descricao_ocorrencia" name="descricao" class="form-control" rows="4" required
                                  placeholder="Descreva detalhadamente o que aconteceu..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="acoes_tomadas" class="form-label">Ações Tomadas</label>
                        <textarea id="acoes_tomadas" name="acoes_tomadas" class="form-control" rows="3"
                                  placeholder="Descreva as ações que foram tomadas em relação à ocorrência..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="houve_reuniao" name="houve_reuniao_responsaveis" value="1">
                                <span class="checkmark"></span>
                                Houve reunião com os responsáveis?
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="detalhes_reuniao_group" style="display: none;">
                        <label for="detalhes_reuniao" class="form-label">Detalhes da Reunião</label>
                        <textarea id="detalhes_reuniao" name="detalhes_reuniao" class="form-control" rows="3"
                                  placeholder="Descreva os detalhes da reunião com os responsáveis..."></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Salvar Ocorrência
                        </button>
                        <button type="button" id="btn-cancelar-ocorrencia" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de Lista de Ocorrências -->
        <div id="listaOcorrenciasModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeListaOcorrenciasModal">&times;</span>
                <h2 id="modalTitleListaOcorrencias">Minhas Ocorrências</h2>
                
                <div id="lista-ocorrencias-container">
                    <p>Carregando ocorrências...</p>
                </div>
            </div>
        </div>

        <!-- Modal de Detalhes da Ocorrência -->
        <div id="detalhesOcorrenciaModal" class="modal">
            <div class="modal-content">
                <span class="close" id="closeDetalhesOcorrenciaModal">&times;</span>
                <h2 id="modalTitleDetalhesOcorrencia">Detalhes da Ocorrência</h2>
                
                <div id="detalhes-ocorrencia-container">
                    <p>Carregando detalhes...</p>
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
    <script src="js/dashboard.js"></script>
    <script src="js/galeria.js"></script>
    <script src="js/ocorrencias.js"></script>
</body>
</html>