<?php
// Incluir arquivo de verificação de autenticação
include_once 'includes/auth_check.php';

// Verificar se o usuário é professor
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'professor') {
    header('Location: login.php');
    exit;
}

// Verificar se o ID do aluno foi fornecido
if (!isset($_GET['aluno_id'])) {
    header('Location: alunos_turma.php');
    exit;
}

$aluno_id = $_GET['aluno_id'];
$professor_id = $_SESSION['usuario_id'];

// Incluir conexão com o banco
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Obter informações do aluno
$query = "SELECT a.nome, a.serie, a.numero_matricula 
          FROM alunos a
          WHERE a.id = ?";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $aluno_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: alunos_turma.php');
    exit;
}

$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
$page_title = "Histórico de Avaliações: " . $aluno['nome'];
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="mb-4">
        <a href="alunos_turma.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Lista de Alunos
        </a>
        <a href="avaliar_aluno.php?aluno_id=<?php echo $aluno_id; ?>&turma_id=<?php echo isset($_GET['turma_id']) ? $_GET['turma_id'] : ''; ?>" class="btn btn-primary ml-2">
            <i class="fas fa-plus-circle mr-2"></i> Nova Avaliação
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Informações do Aluno</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nome:</strong> <?php echo $aluno['nome']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Série:</strong> <?php echo $aluno['serie']; ?></p>
                    <p><strong>Matrícula:</strong> <?php echo $aluno['numero_matricula']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Histórico de Avaliações</h5>
        </div>
        <div class="card-body">
            <div id="avaliacoes-container">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando avaliações...</p>
                <!-- As avaliações serão carregadas via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para visualizar detalhes da avaliação -->
<div class="modal fade" id="avaliacaoModal" tabindex="-1" role="dialog" aria-labelledby="avaliacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="avaliacaoModalLabel">Detalhes da Avaliação</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="avaliacaoModalBody">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Carregar avaliações do aluno
    carregarAvaliacoes();
    
    function carregarAvaliacoes() {
        const alunoId = <?php echo $aluno_id; ?>;
        const avaliacoesContainer = document.getElementById('avaliacoes-container');
        
        fetch(`api/avaliacao/ler.php?aluno_id=${alunoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.registros && data.registros.length > 0) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-striped table-hover">';
                html += '<thead class="thead-light">';
                html += '<tr>';
                html += '<th>Data</th>';
                html += '<th>IMC</th>';
                html += '<th>Status</th>';
                html += '<th>Professor</th>';
                html += '<th>Ações</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';
                
                data.registros.forEach(avaliacao => {
                    // Formatar data
                    const dataOriginal = new Date(avaliacao.data_avaliacao);
                    const dataFormatada = dataOriginal.toLocaleDateString('pt-BR');
                    
                    html += '<tr>';
                    html += `<td>${dataFormatada}</td>`;
                    html += `<td>${avaliacao.imc || '-'}</td>`;
                    html += `<td>${avaliacao.imc_status || '-'}</td>`;
                    html += `<td>${avaliacao.nome_professor || 'Você'}</td>`;
                    html += '<td>';
                    html += `<button class="btn btn-info btn-sm mr-1" onclick="verDetalhes(${avaliacao.id})">`;
                    html += '<i class="fas fa-eye"></i> Ver';
                    html += '</button>';
                    
                    // Verificar se a avaliação é do professor atual
                    if (avaliacao.professor_id == <?php echo $professor_id; ?>) {
                        html += `<button class="btn btn-danger btn-sm" onclick="excluirAvaliacao(${avaliacao.id})">`;
                        html += '<i class="fas fa-trash-alt"></i>';
                        html += '</button>';
                    }
                    
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                
                avaliacoesContainer.innerHTML = html;
            } else {
                avaliacoesContainer.innerHTML = '<div class="alert alert-info">Nenhuma avaliação encontrada para este aluno.</div>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar avaliações:', error);
            avaliacoesContainer.innerHTML = 
                '<div class="alert alert-danger">Erro ao carregar avaliações. Tente novamente mais tarde.</div>';
        });
    }
    
    // Disponibilizar a função globalmente
    window.carregarAvaliacoes = carregarAvaliacoes;
    
    // Ver detalhes da avaliação
    window.verDetalhes = function(avaliacaoId) {
        fetch(`api/avaliacao/ler_um.php?id=${avaliacaoId}`)
        .then(response => response.json())
        .then(avaliacao => {
            // Formatar data
            const dataOriginal = new Date(avaliacao.data_avaliacao);
            const dataFormatada = dataOriginal.toLocaleDateString('pt-BR');
            
            let html = '<div class="container-fluid">';
            
            // Dados físicos
            html += '<div class="row mb-4">';
            html += '<div class="col-12">';
            html += '<h5 class="border-bottom pb-2">Dados Físicos</h5>';
            html += '</div>';
            html += '<div class="col-md-4">';
            html += `<p><strong>Data:</strong> ${dataFormatada}</p>`;
            html += `<p><strong>Altura:</strong> ${avaliacao.altura} cm</p>`;
            html += '</div>';
            html += '<div class="col-md-4">';
            html += `<p><strong>Peso:</strong> ${avaliacao.peso} kg</p>`;
            html += `<p><strong>IMC:</strong> ${avaliacao.imc}</p>`;
            html += '</div>';
            html += '<div class="col-md-4">';
            html += `<p><strong>Status IMC:</strong> ${avaliacao.imc_status}</p>`;
            html += '</div>';
            html += '</div>';
            
            // Habilidades físicas
            html += '<div class="row mb-4">';
            html += '<div class="col-12">';
            html += '<h5 class="border-bottom pb-2">Habilidades Físicas</h5>';
            html += '</div>';
            html += '<div class="col-md-6">';
            html += `<p><strong>Velocidade:</strong> ${avaliacao.velocidade}/5</p>`;
            html += `<p><strong>Resistência:</strong> ${avaliacao.resistencia}/5</p>`;
            html += `<p><strong>Coordenação:</strong> ${avaliacao.coordenacao}/5</p>`;
            html += '</div>';
            html += '<div class="col-md-6">';
            html += `<p><strong>Agilidade:</strong> ${avaliacao.agilidade}/5</p>`;
            html += `<p><strong>Força:</strong> ${avaliacao.forca}/5</p>`;
            html += '</div>';
            html += '<div class="col-12 mt-2">';
            html += `<p><strong>Detalhes do Desempenho:</strong> ${avaliacao.desempenho_detalhes || 'Não informado'}</p>`;
            html += '</div>';
            html += '</div>';
            
            // Comportamento
            html += '<div class="row mb-4">';
            html += '<div class="col-12">';
            html += '<h5 class="border-bottom pb-2">Comportamento</h5>';
            html += '</div>';
            html += '<div class="col-md-6">';
            html += `<p><strong>Participação:</strong> ${avaliacao.participacao}/10</p>`;
            html += `<p><strong>Trabalho em Equipe:</strong> ${avaliacao.trabalho_equipe}/10</p>`;
            html += '</div>';
            html += '<div class="col-md-6">';
            html += `<p><strong>Disciplina:</strong> ${avaliacao.disciplina}/10</p>`;
            html += `<p><strong>Respeito às Regras:</strong> ${avaliacao.respeito_regras}/10</p>`;
            html += '</div>';
            html += '<div class="col-12 mt-2">';
            html += `<p><strong>Notas de Comportamento:</strong> ${avaliacao.comportamento_notas || 'Não informado'}</p>`;
            html += '</div>';
            html += '</div>';
            
            // Observações
            html += '<div class="row">';
            html += '<div class="col-12">';
            html += '<h5 class="border-bottom pb-2">Observações Gerais</h5>';
            html += `<p>${avaliacao.observacoes || 'Não informado'}</p>`;
            html += '</div>';
            html += '</div>';
            
            html += '</div>'; // Fim container-fluid
            
            document.getElementById('avaliacaoModalBody').innerHTML = html;
            $('#avaliacaoModal').modal('show');
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes da avaliação:', error);
            alert('Erro ao carregar detalhes da avaliação. Tente novamente.');
        });
    };
    
    // Excluir avaliação
    window.excluirAvaliacao = function(avaliacaoId) {
        if (confirm('Tem certeza que deseja excluir esta avaliação? Esta ação não pode ser desfeita.')) {
            fetch('api/avaliacao/excluir.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: avaliacaoId })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.mensagem);
                if (data.mensagem.includes('sucesso')) {
                    carregarAvaliacoes();
                }
            })
            .catch(error => {
                console.error('Erro ao excluir avaliação:', error);
                alert('Erro ao excluir avaliação. Tente novamente.');
            });
        }
    };
});
</script>

<?php include_once 'includes/footer.php'; ?>