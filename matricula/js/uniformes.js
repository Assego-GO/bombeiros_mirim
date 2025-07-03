// ===== SISTEMA DE UNIFORMES =====

// Variáveis globais
let uniformesData = [];
let turmasData = [];
let unidadesData = [];
let uniformesFiltrados = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎽 Sistema de Uniformes inicializado');
    
    // Event listeners
    setupUniformesEventListeners();
    
    // Carregar dados iniciais
    carregarDadosIniciais();
});

// Configurar event listeners
function setupUniformesEventListeners() {
    // Botão para abrir modal
    const btnUniformes = document.getElementById('uniformes-btn');
    if (btnUniformes) {
        btnUniformes.addEventListener('click', abrirModalUniformes);
    }
    
    // Botões de fechar modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('fechar-modal-uniformes')) {
            fecharModalUniformes();
        }
        if (e.target.classList.contains('fechar-modal-editar-uniforme')) {
            fecharModalEditarUniforme();
        }
    });
    
    // Controle de abas
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('tab-uniformes')) {
            trocarAbaUniformes(e.target);
        }
    });
    
    // Form de edição
    const formEditarUniforme = document.getElementById('form-editar-uniforme');
    if (formEditarUniforme) {
        formEditarUniforme.addEventListener('submit', salvarEdicaoUniforme);
    }
    
    // Fechar modal ao clicar no backdrop
    document.addEventListener('click', function(e) {
        if (e.target.id === 'modal-uniformes') {
            fecharModalUniformes();
        }
        if (e.target.id === 'modal-editar-uniforme') {
            fecharModalEditarUniforme();
        }
    });
    
    // Filtros em tempo real
    const filtroNome = document.getElementById('filtro-nome-aluno');
    if (filtroNome) {
        filtroNome.addEventListener('input', debounce(filtrarUniformes, 300));
    }
    
    const filtroTurma = document.getElementById('filtro-turma-uniformes');
    if (filtroTurma) {
        filtroTurma.addEventListener('change', filtrarUniformes);
    }
    
    const filtroUnidade = document.getElementById('filtro-unidade-uniformes');
    if (filtroUnidade) {
        filtroUnidade.addEventListener('change', filtrarUniformes);
    }
    
    const filtroStatus = document.getElementById('filtro-status-uniformes');
    if (filtroStatus) {
        filtroStatus.addEventListener('change', filtrarUniformes);
    }
}

// Carregar dados iniciais
async function carregarDadosIniciais() {
    try {
        await Promise.all([
            carregarUniformes(),
            carregarTurmasUniformes(),
            carregarUnidadesUniformes()
        ]);
    } catch (error) {
        console.error('❌ Erro ao carregar dados iniciais:', error);
    }
}

// Abrir modal de uniformes
function abrirModalUniformes() {
    console.log('🎽 Abrindo modal de uniformes');
    const modal = document.getElementById('modal-uniformes');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Carregar dados e mostrar primeira aba
        carregarUniformes();
        mostrarAbaUniformes('listagem');
    }
}

// Fechar modal de uniformes
function fecharModalUniformes() {
    const modal = document.getElementById('modal-uniformes');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Fechar modal de edição
function fecharModalEditarUniforme() {
    const modal = document.getElementById('modal-editar-uniforme');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Controle de abas
function trocarAbaUniformes(tabElement) {
    const tabName = tabElement.dataset.tab;
    
    // Remover classe ativo de todas as abas
    document.querySelectorAll('.tab-uniformes').forEach(tab => {
        tab.classList.remove('ativo');
    });
    
    // Adicionar classe ativo na aba clicada
    tabElement.classList.add('ativo');
    
    // Mostrar conteúdo da aba
    mostrarAbaUniformes(tabName);
}

// Mostrar conteúdo da aba
function mostrarAbaUniformes(tabName) {
    // Ocultar todas as abas
    document.querySelectorAll('.tab-content-uniformes').forEach(content => {
        content.style.display = 'none';
    });
    
    // Mostrar aba selecionada
    const tabContent = document.getElementById(`tab-${tabName}`);
    if (tabContent) {
        tabContent.style.display = 'block';
    }
    
    // Carregar conteúdo específico da aba
    switch(tabName) {
        case 'listagem':
            carregarUniformes();
            break;
        case 'relatorios':
            // Conteúdo estático, não precisa carregar
            break;
        case 'estatisticas':
            carregarEstatisticasUniformes();
            break;
    }
}

// Carregar dados de uniformes
async function carregarUniformes() {
    try {
        mostrarLoading();
        
        const response = await fetch('./api/uniformes.php?action=listar_uniformes');
        const data = await response.json();
        
        if (data.success) {
            uniformesData = data.uniformes;
            uniformesFiltrados = [...uniformesData];
            renderizarListaUniformes();
            atualizarContadorUniformes();
        } else {
            mostrarNotificacao('Erro ao carregar uniformes: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar uniformes:', error);
        mostrarNotificacao('Erro ao carregar dados dos uniformes', 'error');
    } finally {
        ocultarLoading();
    }
}

// Carregar turmas para filtros
async function carregarTurmasUniformes() {
    try {
        const response = await fetch('./api/uniformes.php?action=listar_turmas');
        const data = await response.json();
        
        if (data.success) {
            turmasData = data.turmas;
            populateSelectTurmas();
        }
    } catch (error) {
        console.error('❌ Erro ao carregar turmas:', error);
    }
}

// Carregar unidades para filtros
async function carregarUnidadesUniformes() {
    try {
        const response = await fetch('./api/uniformes.php?action=listar_unidades');
        const data = await response.json();
        
        if (data.success) {
            unidadesData = data.unidades;
            populateSelectUnidades();
        }
    } catch (error) {
        console.error('❌ Erro ao carregar unidades:', error);
    }
}

// Popular select de turmas
function populateSelectTurmas() {
    const select = document.getElementById('filtro-turma-uniformes');
    if (select) {
        select.innerHTML = '<option value="">Todas as turmas</option>';
        turmasData.forEach(turma => {
            const option = document.createElement('option');
            option.value = turma.id;
            option.textContent = turma.nome;
            select.appendChild(option);
        });
    }
}

// Popular select de unidades
function populateSelectUnidades() {
    const select = document.getElementById('filtro-unidade-uniformes');
    if (select) {
        select.innerHTML = '<option value="">Todas as unidades</option>';
        unidadesData.forEach(unidade => {
            const option = document.createElement('option');
            option.value = unidade.id;
            option.textContent = unidade.nome;
            select.appendChild(option);
        });
    }
}

// Renderizar lista de uniformes
function renderizarListaUniformes() {
    const container = document.getElementById('uniformes-content');
    if (!container) return;
    
    if (uniformesFiltrados.length === 0) {
        container.innerHTML = `
            <div class="sem-uniformes">
                <i class="fas fa-tshirt"></i>
                <h3>Nenhum uniforme encontrado</h3>
                <p>Não há registros de uniformes para os filtros selecionados</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = uniformesFiltrados.map(uniforme => `
        <div class="uniforme-item">
            <div class="aluno-info-item">
                <div class="aluno-nome">${uniforme.aluno_nome}</div>
                <div class="aluno-matricula">${uniforme.numero_matricula}</div>
            </div>
            <div class="turma-info">${uniforme.turma_nome || 'Sem turma'}</div>
            <div class="tamanho-badge ${uniforme.tamanho_camisa ? 'definido' : 'indefinido'}">
                ${uniforme.tamanho_camisa || 'N/D'}
            </div>
            <div class="tamanho-badge ${uniforme.tamanho_calca ? 'definido' : 'indefinido'}">
                ${uniforme.tamanho_calca || 'N/D'}
            </div>
            <div class="tamanho-badge ${uniforme.tamanho_calcado ? 'definido' : 'indefinido'}">
                ${uniforme.tamanho_calcado || 'N/D'}
            </div>
            <div class="status-uniforme ${getStatusUniforme(uniforme)}">
                ${getStatusUniformeText(uniforme)}
            </div>
            <div class="acoes-uniforme">
                <button class="btn-editar-uniforme" onclick="editarUniforme(${uniforme.id})">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    `).join('');
}

// Obter status do uniforme
function getStatusUniforme(uniforme) {
    const temCamisa = uniforme.tamanho_camisa;
    const temCalca = uniforme.tamanho_calca;
    const temCalcado = uniforme.tamanho_calcado;
    
    if (temCamisa && temCalca && temCalcado) {
        return 'completo';
    } else if (!temCamisa && !temCalca && !temCalcado) {
        return 'pendente';
    } else {
        return 'incompleto';
    }
}

// Obter texto do status
function getStatusUniformeText(uniforme) {
    const status = getStatusUniforme(uniforme);
    switch(status) {
        case 'completo': return 'Completo';
        case 'incompleto': return 'Incompleto';
        case 'pendente': return 'Pendente';
        default: return 'Pendente';
    }
}

// Filtrar uniformes
function filtrarUniformes() {
    const filtroNome = document.getElementById('filtro-nome-aluno')?.value.toLowerCase() || '';
    const filtroTurma = document.getElementById('filtro-turma-uniformes')?.value || '';
    const filtroUnidade = document.getElementById('filtro-unidade-uniformes')?.value || '';
    const filtroStatus = document.getElementById('filtro-status-uniformes')?.value || '';
    
    uniformesFiltrados = uniformesData.filter(uniforme => {
        const matchNome = uniforme.aluno_nome.toLowerCase().includes(filtroNome);
        const matchTurma = !filtroTurma || uniforme.turma_id == filtroTurma;
        const matchUnidade = !filtroUnidade || uniforme.unidade_id == filtroUnidade;
        const matchStatus = !filtroStatus || getStatusUniforme(uniforme) === filtroStatus;
        
        return matchNome && matchTurma && matchUnidade && matchStatus;
    });
    
    renderizarListaUniformes();
    atualizarContadorUniformes();
}

// Limpar filtros
function limparFiltrosUniformes() {
    document.getElementById('filtro-nome-aluno').value = '';
    document.getElementById('filtro-turma-uniformes').value = '';
    document.getElementById('filtro-unidade-uniformes').value = '';
    document.getElementById('filtro-status-uniformes').value = '';
    
    uniformesFiltrados = [...uniformesData];
    renderizarListaUniformes();
    atualizarContadorUniformes();
}

// Atualizar contador
function atualizarContadorUniformes() {
    const contador = document.getElementById('total-uniformes');
    if (contador) {
        contador.textContent = uniformesFiltrados.length;
    }
}

// Editar uniforme
function editarUniforme(alunoId) {
    const uniforme = uniformesData.find(u => u.id === alunoId);
    if (!uniforme) return;
    
    // Preencher modal de edição
    document.getElementById('edit-aluno-id').value = uniforme.id;
    document.getElementById('edit-aluno-nome').textContent = uniforme.aluno_nome;
    document.getElementById('edit-aluno-turma').textContent = uniforme.turma_nome || 'Sem turma';
    document.getElementById('edit-aluno-matricula').textContent = uniforme.numero_matricula;
    
    // Preencher tamanhos atuais
    document.getElementById('edit-tamanho-camisa').value = uniforme.tamanho_camisa || '';
    document.getElementById('edit-tamanho-calca').value = uniforme.tamanho_calca || '';
    document.getElementById('edit-tamanho-calcado').value = uniforme.tamanho_calcado || '';
    
    // Mostrar modal
    const modal = document.getElementById('modal-editar-uniforme');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// Salvar edição do uniforme
async function salvarEdicaoUniforme(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'atualizar_uniforme');
    
    try {
        mostrarLoading();
        
        const response = await fetch('./api/uniformes.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacao('Uniforme atualizado com sucesso!', 'success');
            fecharModalEditarUniforme();
            carregarUniformes(); // Recarregar lista
        } else {
            mostrarNotificacao('Erro ao atualizar uniforme: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('❌ Erro ao atualizar uniforme:', error);
        mostrarNotificacao('Erro ao atualizar uniforme', 'error');
    } finally {
        ocultarLoading();
    }
}

// Carregar estatísticas
async function carregarEstatisticasUniformes() {
    try {
        const response = await fetch('./api/uniformes.php?action=estatisticas');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.estatisticas;
            
            // Atualizar cards de estatísticas
            document.getElementById('total-camisas').textContent = stats.total_camisas;
            document.getElementById('total-calcas').textContent = stats.total_calcas;
            document.getElementById('total-calcados').textContent = stats.total_calcados;
            document.getElementById('uniformes-completos').textContent = stats.uniformes_completos;
            
            // Atualizar gráficos (implementação simplificada)
            atualizarGraficosEstatisticas(stats);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar estatísticas:', error);
    }
}

// Atualizar gráficos (implementação simplificada)
function atualizarGraficosEstatisticas(stats) {
    // Gráfico de camisas
    const graficoCamisas = document.getElementById('grafico-camisas');
    if (graficoCamisas && stats.distribuicao_camisas) {
        graficoCamisas.innerHTML = Object.entries(stats.distribuicao_camisas)
            .map(([tamanho, quantidade]) => `
                <div style="margin: 5px 0;">
                    <strong>${tamanho.toUpperCase()}:</strong> ${quantidade} unidades
                </div>
            `).join('');
    }
    
    // Gráfico de calças
    const graficoCalcas = document.getElementById('grafico-calcas');
    if (graficoCalcas && stats.distribuicao_calcas) {
        graficoCalcas.innerHTML = Object.entries(stats.distribuicao_calcas)
            .map(([tamanho, quantidade]) => `
                <div style="margin: 5px 0;">
                    <strong>${tamanho.toUpperCase()}:</strong> ${quantidade} unidades
                </div>
            `).join('');
    }
    
    // Gráfico de calçados
    const graficoCalcados = document.getElementById('grafico-calcados');
    if (graficoCalcados && stats.distribuicao_calcados) {
        graficoCalcados.innerHTML = Object.entries(stats.distribuicao_calcados)
            .map(([tamanho, quantidade]) => `
                <div style="margin: 5px 0;">
                    <strong>${tamanho}:</strong> ${quantidade} unidades
                </div>
            `).join('');
    }
}

// ===== FUNÇÕES DE RELATÓRIOS =====

// Relatório geral
function gerarRelatorioGeralUniformes() {
    const params = new URLSearchParams({
        action: 'relatorio_geral',
        formato: 'pdf'
    });
    
    window.open(`./api/uniformes.php?${params}`, '_blank');
}

// Relatório por turma
function gerarRelatorioPorTurma() {
    const params = new URLSearchParams({
        action: 'relatorio_por_turma',
        formato: 'pdf'
    });
    
    window.open(`./api/uniformes.php?${params}`, '_blank');
}

// Relatório de tamanhos
function gerarRelatorioTamanhos() {
    const params = new URLSearchParams({
        action: 'relatorio_tamanhos',
        formato: 'pdf'
    });
    
    window.open(`./api/uniformes.php?${params}`, '_blank');
}

// Relatório de incompletos
function gerarRelatorioIncompletos() {
    const params = new URLSearchParams({
        action: 'relatorio_incompletos',
        formato: 'pdf'
    });
    
    window.open(`./api/uniformes.php?${params}`, '_blank');
}

// Exportar todos os relatórios
function exportarTodosRelatorios() {
    const params = new URLSearchParams({
        action: 'exportar_todos',
        formato: 'zip'
    });
    
    window.open(`./api/uniformes.php?${params}`, '_blank');
}

// ===== FUNÇÕES AUXILIARES =====

// Debounce para filtros
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Mostrar loading
function mostrarLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'flex';
    }
}

// Ocultar loading
function ocultarLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'none';
    }
}

// Mostrar notificação
function mostrarNotificacao(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type} show`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Adicionar ao documento
    document.body.appendChild(notification);
    
    // Remover após 5 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

console.log('🎽 Sistema de Uniformes carregado com sucesso!');