// Funcionalidade para gerenciar carteirinhas

document.addEventListener('DOMContentLoaded', function() {
    // Botão para gerar carteirinhas
    const gerarCarteirinhaBtn = document.getElementById('gerar-carterinha-btn');
    if (gerarCarteirinhaBtn) {
        gerarCarteirinhaBtn.addEventListener('click', gerarCarteirinha);
    }

    // Botão para selecionar todos os alunos
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#matriculas-body input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
});

// Função para gerar carteirinha
function gerarCarteirinha() {
    // Obter alunos selecionados
    const checkboxes = document.querySelectorAll('#matriculas-body input[type="checkbox"]:checked');
    
    if (checkboxes.length === 0) {
        alert('Por favor, selecione pelo menos um aluno para gerar a carteirinha.');
        return;
    }
    
    // Coletar IDs dos alunos selecionados
    const alunosIds = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    // Mostrar overlay de carregamento
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    // Criar formulário para envio via POST
    const form = document.createElement('form');
    form.method = 'POST';
    
    // Ajustar caminho para o correto
    form.action = '/superacao/matricula/api/gerar_carteirinha.php';
    form.style.display = 'none';
    
    // Adicionar campo de IDs dos alunos
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'alunos_ids';
    input.value = alunosIds.join(',');
    
    form.appendChild(input);
    document.body.appendChild(form);
    
    // Enviar para download
    form.submit();
    
    // Esconder overlay após um pequeno delay
    setTimeout(() => {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }, 2000);
    
    // Limpar formulário
    setTimeout(() => {
        document.body.removeChild(form);
    }, 1000);
}

// Função para carregar dados da tabela de alunos
function carregarDadosAlunos() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    // Fazer requisição para API
    fetch('api/listar_alunos.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro de rede: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Preencher tabela com dados
            preencherTabelaAlunos(data);
            
            // Atualizar contador de resultados
            const totalResults = document.getElementById('total-results');
            if (totalResults) {
                totalResults.textContent = data.length;
            }
            
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados:', error);
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            alert('Erro ao carregar dados dos alunos. Por favor, tente novamente.');
        });
}

// Função para preencher a tabela com dados dos alunos
function preencherTabelaAlunos(alunos) {
    const tbody = document.getElementById('matriculas-body');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    alunos.forEach(aluno => {
        const tr = document.createElement('tr');
        
        // Estrutura da linha conforme o layout da tabela
        tr.innerHTML = `
            <td><input type="checkbox" value="${aluno.id}" /></td>
            <td>${aluno.nome}</td>
            <td>${aluno.responsavel || '-'}</td>
            <td>${aluno.contato || '-'}</td>
            <td>${aluno.unidade || aluno.escola || '-'}</td>
            <td>${aluno.turma || 'Sub-' + aluno.serie + ' Manhã' || '-'}</td>
            <td>${formatarData(aluno.data_matricula)}</td>
            <td>${aluno.fonte || '-'}</td>
            <td><span class="status-badge ${aluno.status}">${aluno.status}</span></td>
            <td class="actions">
                <button class="btn-icon view" data-id="${aluno.id}" title="Visualizar"><i class="fas fa-eye"></i></button>
                <button class="btn-icon edit" data-id="${aluno.id}" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn-icon delete" data-id="${aluno.id}" title="Excluir"><i class="fas fa-trash"></i></button>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
    
    // Adicionar eventos aos botões
    adicionarEventosBotoes();
}

// Função auxiliar para formatar data
function formatarData(dataString) {
    if (!dataString) return '-';
    
    try {
        const data = new Date(dataString);
        return data.toLocaleDateString('pt-BR');
    } catch (e) {
        console.error('Erro ao formatar data:', e);
        return dataString; // Retorna a string original se houver erro
    }
}

// Inicializar eventos para botões da tabela
function adicionarEventosBotoes() {
    // Botões de visualizar
    const viewButtons = document.querySelectorAll('.btn-icon.view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const alunoId = this.getAttribute('data-id');
            // Implementar visualização do aluno
            //console.log('Visualizar aluno ID:', alunoId);
        });
    });
    
    // Botões de editar
    const editButtons = document.querySelectorAll('.btn-icon.edit');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const alunoId = this.getAttribute('data-id');
            // Implementar edição do aluno
            //console.log('Editar aluno ID:', alunoId);
        });
    });
    
    // Botões de excluir
    const deleteButtons = document.querySelectorAll('.btn-icon.delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const alunoId = this.getAttribute('data-id');
            // Implementar exclusão do aluno
            if (confirm('Tem certeza que deseja excluir este aluno?')) {
                //console.log('Excluir aluno ID:', alunoId);
                // Implementar chamada para API de exclusão
            }
        });
    });
}

// Inicializar carregamento de dados ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    carregarDadosAlunos();
    
    // Outros eventos iniciais da página...
});