// ====================================
// SISTEMA DE UNIFORMES - BOMBEIRO MIRIM - VERSÃO CORRIGIDA
// ====================================

// Variáveis globais
let dadosUniformes = [];
let dadosAlunos = [];
let dadosTurmas = [];
let dadosUnidades = [];
let uniformesCarregados = false;

// ====================================
// CONFIGURAÇÃO DE CAMINHOS
// ====================================

// Detectar o caminho correto para as requisições
function getBasePath() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/js/')) {
        return '../uniformes.php';
    } else if (currentPath.includes('/matricula/')) {
        return './api/uniformes.php';
    } else {
        return 'uniformes.php';
    }
}

// Detectar o caminho correto para os PDFs
function getPDFBasePath() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/js/')) {
        return '../uniformes_pdf.php';
    } else if (currentPath.includes('/matricula/')) {
        return './api/uniformes_pdf.php';
    } else {
        return 'uniformes_pdf.php';
    }
}

// ====================================
// INICIALIZAÇÃO
// ====================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Inicializando Sistema de Uniformes...');
    
    // Configurar eventos do modal
    configurarEventosModal();
    
    // Configurar botão de abertura do modal
    const btnUniformes = document.getElementById('uniformes-btn');
    if (btnUniformes) {
        btnUniformes.addEventListener('click', abrirModalUniformes);
    }
    
    console.log('✅ Sistema de Uniformes inicializado');
});

// ====================================
// CONFIGURAÇÃO DE EVENTOS
// ====================================

function configurarEventosModal() {
    // Botões para fechar modal
    const closeButtons = document.querySelectorAll('.fechar-modal-uniformes');
    closeButtons.forEach(button => {
        button.addEventListener('click', fecharModalUniformes);
    });
    
    // Fechar modal clicando fora
    const modalUniformes = document.getElementById('modal-uniformes');
    if (modalUniformes) {
        modalUniformes.addEventListener('click', function(e) {
            if (e.target === modalUniformes) {
                fecharModalUniformes();
            }
        });
    }
    
    // Configurar abas
    const tabsUniformes = document.querySelectorAll('.tab-uniformes');
    tabsUniformes.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            mostrarAbaUniformes(tabName);
        });
    });
    
    // Configurar formulário de edição
    const formEditar = document.getElementById('form-editar-uniforme');
    if (formEditar) {
        formEditar.addEventListener('submit', salvarEdicaoUniforme);
    }
}

// ====================================
// FUNÇÕES PRINCIPAIS DO MODAL
// ====================================

function abrirModalUniformes() {
    console.log('🔧 Abrindo modal de uniformes...');
    
    const modal = document.getElementById('modal-uniformes');
    if (!modal) {
        console.error('❌ Modal de uniformes não encontrado');
        return;
    }
    
    modal.style.display = 'flex';
    
    // Carregar dados se ainda não foram carregados
    if (!uniformesCarregados) {
        carregarDadosUniformes();
    }
}

function fecharModalUniformes() {
    console.log('🔧 Fechando modal de uniformes...');
    
    const modal = document.getElementById('modal-uniformes');
    if (modal) {
        modal.style.display = 'none';
    }
}

function mostrarAbaUniformes(tabName) {
    console.log('🔧 Mostrando aba:', tabName);
    
    // Remover classe ativa de todas as abas
    const allTabs = document.querySelectorAll('.tab-uniformes');
    allTabs.forEach(tab => tab.classList.remove('ativo'));
    
    // Ocultar todos os conteúdos
    const allContents = document.querySelectorAll('.tab-content-uniformes');
    allContents.forEach(content => content.style.display = 'none');
    
    // Ativar aba selecionada
    const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeTab) {
        activeTab.classList.add('ativo');
    }
    
    // Mostrar conteúdo correspondente
    const activeContent = document.getElementById(`tab-${tabName}`);
    if (activeContent) {
        activeContent.style.display = 'block';
    }
    
    // Carregar dados específicos da aba
    switch(tabName) {
        case 'listagem':
            carregarListagemUniformes();
            break;
        case 'estatisticas':
            carregarEstatisticasUniformes();
            break;
        default:
            break;
    }
}

// ====================================
// FUNÇÕES DE CARREGAMENTO DE DADOS
// ====================================

async function carregarDadosUniformes() {
    console.log('🔧 Carregando dados de uniformes...');
    
    try {
        mostrarLoading();
        
        // Carregar dados em paralelo
        await Promise.all([
            carregarUniformes(),
            carregarTurmasUniformes(),
            carregarUnidadesUniformes()
        ]);
        
        uniformesCarregados = true;
        console.log('✅ Dados de uniformes carregados com sucesso');
        
        // Preencher filtros
        preencherFiltrosUniformes();
        
        // Carregar listagem inicial
        carregarListagemUniformes();
        
    } catch (error) {
        console.error('❌ Erro ao carregar dados de uniformes:', error);
        mostrarErro('Erro ao carregar dados de uniformes: ' + error.message);
    } finally {
        ocultarLoading();
    }
}

async function carregarUniformes() {
    try {
        const basePath = getBasePath();
        console.log('🔧 Fazendo requisição para:', basePath + '?action=listar_uniformes');
        
        const response = await fetch(basePath + '?action=listar_uniformes');
        
        console.log('📡 Status da resposta:', response.status);
        console.log('📡 Headers da resposta:', [...response.headers]);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Resposta não é JSON:', text.substring(0, 500));
            throw new Error('Servidor retornou HTML em vez de JSON. Verifique se o arquivo uniformes.php existe.');
        }
        
        const data = await response.json();
        
        if (data.success) {
            dadosUniformes = data.uniformes || [];
            console.log('✅ Uniformes carregados:', dadosUniformes.length);
        } else {
            throw new Error(data.message || 'Erro ao carregar uniformes');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar uniformes:', error);
        throw error;
    }
}

async function carregarTurmasUniformes() {
    try {
        const basePath = getBasePath();
        console.log('🔧 Fazendo requisição para:', basePath + '?action=listar_turmas');
        
        const response = await fetch(basePath + '?action=listar_turmas');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Resposta não é JSON:', text.substring(0, 500));
            throw new Error('Servidor retornou HTML em vez de JSON para turmas');
        }
        
        const data = await response.json();
        
        if (data.success) {
            dadosTurmas = data.turmas || [];
            console.log('✅ Turmas carregadas:', dadosTurmas.length);
        } else {
            throw new Error(data.message || 'Erro ao carregar turmas');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar turmas:', error);
        throw error;
    }
}

async function carregarUnidadesUniformes() {
    try {
        const basePath = getBasePath();
        console.log('🔧 Fazendo requisição para:', basePath + '?action=listar_unidades');
        
        const response = await fetch(basePath + '?action=listar_unidades');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Resposta não é JSON:', text.substring(0, 500));
            throw new Error('Servidor retornou HTML em vez de JSON para unidades');
        }
        
        const data = await response.json();
        
        if (data.success) {
            dadosUnidades = data.unidades || [];
            console.log('✅ Unidades carregadas:', dadosUnidades.length);
        } else {
            throw new Error(data.message || 'Erro ao carregar unidades');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar unidades:', error);
        throw error;
    }
}

// ====================================
// FUNÇÕES DE INTERFACE
// ====================================

function preencherFiltrosUniformes() {
    console.log('🔧 Preenchendo filtros de uniformes...');
    
    try {
        // Filtro de turmas
        const filtroTurma = document.getElementById('filtro-turma-uniformes');
        if (filtroTurma) {
            filtroTurma.innerHTML = '<option value="">Todas as turmas</option>';
            dadosTurmas.forEach(turma => {
                const option = document.createElement('option');
                option.value = turma.id;
                option.textContent = turma.nome;
                filtroTurma.appendChild(option);
            });
        }
        
        // Filtro de unidades
        const filtroUnidade = document.getElementById('filtro-unidade-uniformes');
        if (filtroUnidade) {
            filtroUnidade.innerHTML = '<option value="">Todas as unidades</option>';
            dadosUnidades.forEach(unidade => {
                const option = document.createElement('option');
                option.value = unidade.id;
                option.textContent = unidade.nome;
                filtroUnidade.appendChild(option);
            });
        }
        
        console.log('✅ Filtros preenchidos com sucesso');
    } catch (error) {
        console.error('❌ Erro ao preencher filtros:', error);
    }
}

function carregarListagemUniformes() {
    console.log('🔧 Carregando listagem de uniformes...');
    
    try {
        const container = document.getElementById('uniformes-content');
        if (!container) {
            console.error('❌ Container de uniformes não encontrado');
            return;
        }
        
        if (dadosUniformes.length === 0) {
            container.innerHTML = `
                <div class="sem-uniformes">
                    <i class="fas fa-tshirt"></i>
                    <h3>Nenhum uniforme encontrado</h3>
                    <p>Não há dados de uniformes cadastrados</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        dadosUniformes.forEach(uniforme => {
            const status = determinarStatusUniforme(uniforme);
            html += criarItemUniforme(uniforme, status);
        });
        
        container.innerHTML = html;
        
        // Atualizar contador
        const contador = document.getElementById('total-uniformes');
        if (contador) {
            contador.textContent = dadosUniformes.length;
        }
        
        console.log('✅ Listagem carregada com sucesso');
    } catch (error) {
        console.error('❌ Erro ao carregar listagem:', error);
    }
}

function criarItemUniforme(uniforme, status) {
    return `
        <div class="uniforme-item">
            <div class="aluno-info-item">
                <div class="aluno-nome">${uniforme.aluno_nome || 'N/A'}</div>
                <div class="aluno-matricula">${uniforme.numero_matricula || 'N/A'}</div>
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
            <div class="status-uniforme ${status.toLowerCase()}">
                ${status}
            </div>
            <div class="acoes-uniforme">
                <button class="btn-editar-uniforme" onclick="editarUniforme(${uniforme.id})">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
    `;
}

function determinarStatusUniforme(uniforme) {
    const temCamisa = uniforme.tamanho_camisa && uniforme.tamanho_camisa !== '';
    const temCalca = uniforme.tamanho_calca && uniforme.tamanho_calca !== '';
    const temCalcado = uniforme.tamanho_calcado && uniforme.tamanho_calcado !== '';
    
    if (temCamisa && temCalca && temCalcado) {
        return 'Completo';
    } else if (!temCamisa && !temCalca && !temCalcado) {
        return 'Pendente';
    } else {
        return 'Incompleto';
    }
}

// ====================================
// FUNÇÕES DE EDIÇÃO
// ====================================

function editarUniforme(alunoId) {
    console.log('🔧 Editando uniforme do aluno:', alunoId);
    
    try {
        const uniforme = dadosUniformes.find(u => u.id == alunoId);
        if (!uniforme) {
            console.error('❌ Uniforme não encontrado');
            return;
        }
        
        // Preencher dados do modal
        document.getElementById('edit-aluno-id').value = uniforme.id;
        document.getElementById('edit-aluno-nome').textContent = uniforme.aluno_nome;
        document.getElementById('edit-aluno-turma').textContent = uniforme.turma_nome || 'Sem turma';
        document.getElementById('edit-aluno-matricula').textContent = uniforme.numero_matricula || 'N/A';
        
        // Preencher campos de tamanho
        document.getElementById('edit-tamanho-camisa').value = uniforme.tamanho_camisa || '';
        document.getElementById('edit-tamanho-calca').value = uniforme.tamanho_calca || '';
        document.getElementById('edit-tamanho-calcado').value = uniforme.tamanho_calcado || '';
        
        // Mostrar modal de edição
        const modal = document.getElementById('modal-editar-uniforme');
        if (modal) {
            modal.style.display = 'flex';
        }
        
        // Configurar eventos do modal de edição
        configurarEventosModalEdicao();
        
    } catch (error) {
        console.error('❌ Erro ao editar uniforme:', error);
        mostrarErro('Erro ao carregar dados do uniforme');
    }
}

function configurarEventosModalEdicao() {
    const modal = document.getElementById('modal-editar-uniforme');
    if (!modal) return;
    
    // Botões para fechar
    const closeButtons = modal.querySelectorAll('.fechar-modal-editar-uniforme');
    closeButtons.forEach(button => {
        button.addEventListener('click', fecharModalEdicao);
    });
    
    // Fechar clicando fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fecharModalEdicao();
        }
    });
}

function fecharModalEdicao() {
    const modal = document.getElementById('modal-editar-uniforme');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function salvarEdicaoUniforme(event) {
    event.preventDefault();
    
    console.log('🔧 Salvando edição de uniforme...');
    
    try {
        mostrarLoading();
        
        const formData = new FormData(event.target);
        formData.append('action', 'atualizar_uniforme');
        
        const basePath = getBasePath();
        const response = await fetch(basePath, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarSucesso('Uniforme atualizado com sucesso!');
            fecharModalEdicao();
            
            // Recarregar dados
            await carregarUniformes();
            carregarListagemUniformes();
        } else {
            throw new Error(data.message || 'Erro ao salvar uniforme');
        }
        
    } catch (error) {
        console.error('❌ Erro ao salvar uniforme:', error);
        mostrarErro('Erro ao salvar alterações do uniforme');
    } finally {
        ocultarLoading();
    }
}

// ====================================
// FUNÇÕES DE FILTRO
// ====================================

function filtrarUniformes() {
    console.log('🔧 Aplicando filtros de uniformes...');
    
    try {
        const filtroNome = document.getElementById('filtro-nome-aluno').value.toLowerCase();
        const filtroTurma = document.getElementById('filtro-turma-uniformes').value;
        const filtroUnidade = document.getElementById('filtro-unidade-uniformes').value;
        const filtroStatus = document.getElementById('filtro-status-uniformes').value;
        
        let uniformesFiltrados = dadosUniformes.filter(uniforme => {
            // Filtro por nome
            if (filtroNome && !uniforme.aluno_nome.toLowerCase().includes(filtroNome)) {
                return false;
            }
            
            // Filtro por turma
            if (filtroTurma && uniforme.turma_id != filtroTurma) {
                return false;
            }
            
            // Filtro por unidade
            if (filtroUnidade && uniforme.unidade_id != filtroUnidade) {
                return false;
            }
            
            // Filtro por status
            if (filtroStatus) {
                const status = determinarStatusUniforme(uniforme).toLowerCase();
                if (status !== filtroStatus) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Atualizar listagem com dados filtrados
        exibirUniformesFiltrados(uniformesFiltrados);
        
        console.log('✅ Filtros aplicados:', uniformesFiltrados.length, 'resultados');
        
    } catch (error) {
        console.error('❌ Erro ao aplicar filtros:', error);
        mostrarErro('Erro ao aplicar filtros');
    }
}

function exibirUniformesFiltrados(uniformesFiltrados) {
    const container = document.getElementById('uniformes-content');
    if (!container) return;
    
    if (uniformesFiltrados.length === 0) {
        container.innerHTML = `
            <div class="sem-uniformes">
                <i class="fas fa-search"></i>
                <h3>Nenhum resultado encontrado</h3>
                <p>Tente ajustar os filtros para encontrar uniformes</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    uniformesFiltrados.forEach(uniforme => {
        const status = determinarStatusUniforme(uniforme);
        html += criarItemUniforme(uniforme, status);
    });
    
    container.innerHTML = html;
    
    // Atualizar contador
    const contador = document.getElementById('total-uniformes');
    if (contador) {
        contador.textContent = uniformesFiltrados.length;
    }
}

function limparFiltrosUniformes() {
    console.log('🔧 Limpando filtros de uniformes...');
    
    try {
        document.getElementById('filtro-nome-aluno').value = '';
        document.getElementById('filtro-turma-uniformes').value = '';
        document.getElementById('filtro-unidade-uniformes').value = '';
        document.getElementById('filtro-status-uniformes').value = '';
        
        // Recarregar listagem completa
        carregarListagemUniformes();
        
        console.log('✅ Filtros limpos');
    } catch (error) {
        console.error('❌ Erro ao limpar filtros:', error);
    }
}

// ====================================
// FUNÇÕES DE ESTATÍSTICAS
// ====================================

async function carregarEstatisticasUniformes() {
    console.log('🔧 Carregando estatísticas de uniformes...');
    
    try {
        mostrarLoading();
        
        const basePath = getBasePath();
        const response = await fetch(basePath + '?action=estatisticas');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            const stats = data.estatisticas;
            
            // Atualizar cards de estatísticas
            atualizarCardsEstatisticas(stats);
            
            // Gerar gráficos
            gerarGraficosEstatisticas(stats);
            
            console.log('✅ Estatísticas carregadas com sucesso');
        } else {
            throw new Error(data.message || 'Erro ao carregar estatísticas');
        }
        
    } catch (error) {
        console.error('❌ Erro ao carregar estatísticas:', error);
        mostrarErro('Erro ao carregar estatísticas');
    } finally {
        ocultarLoading();
    }
}

function atualizarCardsEstatisticas(stats) {
    const elementos = {
        'total-camisas': stats.total_camisas || 0,
        'total-calcas': stats.total_calcas || 0,
        'total-calcados': stats.total_calcados || 0,
        'uniformes-completos': stats.uniformes_completos || 0
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        }
    });
}

function gerarGraficosEstatisticas(stats) {
    // Gráfico de camisas
    const graficoCamisas = document.getElementById('grafico-camisas');
    if (graficoCamisas && stats.distribuicao_camisas) {
        graficoCamisas.innerHTML = criarGraficoDistribuicao(stats.distribuicao_camisas);
    }
    
    // Gráfico de calças
    const graficoCalcas = document.getElementById('grafico-calcas');
    if (graficoCalcas && stats.distribuicao_calcas) {
        graficoCalcas.innerHTML = criarGraficoDistribuicao(stats.distribuicao_calcas);
    }
    
    // Gráfico de calçados
    const graficoCalcados = document.getElementById('grafico-calcados');
    if (graficoCalcados && stats.distribuicao_calcados) {
        graficoCalcados.innerHTML = criarGraficoDistribuicao(stats.distribuicao_calcados);
    }
}

function criarGraficoDistribuicao(distribuicao) {
    let html = '<div class="distribuicao-lista">';
    
    Object.entries(distribuicao).forEach(([tamanho, quantidade]) => {
        html += `
            <div class="distribuicao-item">
                <span class="tamanho">${tamanho.toUpperCase()}</span>
                <span class="quantidade">${quantidade}</span>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// ====================================
// FUNÇÕES DE RELATÓRIOS - ATUALIZADAS PARA PDF
// ====================================

function gerarRelatorioGeralUniformes() {
    console.log('🔧 Gerando relatório geral de uniformes...');
    
    try {
        const pdfPath = getPDFBasePath();
        const url = pdfPath + '?action=relatorio_geral';
        
        console.log('📄 Abrindo relatório:', url);
        window.open(url, '_blank');
        
        // Mostrar feedback visual
        mostrarSucesso('Relatório geral sendo gerado...');
        
    } catch (error) {
        console.error('❌ Erro ao gerar relatório geral:', error);
        mostrarErro('Erro ao gerar relatório geral: ' + error.message);
    }
}

function gerarRelatorioPorTurma() {
    console.log('🔧 Gerando relatório por turma...');
    
    try {
        const pdfPath = getPDFBasePath();
        const url = pdfPath + '?action=relatorio_por_turma';
        
        console.log('📄 Abrindo relatório:', url);
        window.open(url, '_blank');
        
        mostrarSucesso('Relatório por turma sendo gerado...');
        
    } catch (error) {
        console.error('❌ Erro ao gerar relatório por turma:', error);
        mostrarErro('Erro ao gerar relatório por turma: ' + error.message);
    }
}

function gerarRelatorioTamanhos() {
    console.log('🔧 Gerando relatório de tamanhos...');
    
    try {
        const pdfPath = getPDFBasePath();
        const url = pdfPath + '?action=relatorio_tamanhos';
        
        console.log('📄 Abrindo relatório:', url);
        window.open(url, '_blank');
        
        mostrarSucesso('Relatório de tamanhos sendo gerado...');
        
    } catch (error) {
        console.error('❌ Erro ao gerar relatório de tamanhos:', error);
        mostrarErro('Erro ao gerar relatório de tamanhos: ' + error.message);
    }
}

function gerarRelatorioIncompletos() {
    console.log('🔧 Gerando relatório de incompletos...');
    
    try {
        const pdfPath = getPDFBasePath();
        const url = pdfPath + '?action=relatorio_incompletos';
        
        console.log('📄 Abrindo relatório:', url);
        window.open(url, '_blank');
        
        mostrarSucesso('Relatório de incompletos sendo gerado...');
        
    } catch (error) {
        console.error('❌ Erro ao gerar relatório de incompletos:', error);
        mostrarErro('Erro ao gerar relatório de incompletos: ' + error.message);
    }
}

function exportarTodosRelatorios() {
    console.log('🔧 Exportando todos os relatórios...');
    
    try {
        const pdfPath = getPDFBasePath();
        const url = pdfPath + '?action=exportar_todos';
        
        console.log('📄 Abrindo página de exportação:', url);
        window.open(url, '_blank');
        
        mostrarSucesso('Abrindo página de exportação...');
        
    } catch (error) {
        console.error('❌ Erro ao exportar relatórios:', error);
        mostrarErro('Erro ao exportar relatórios: ' + error.message);
    }
}

// ====================================
// FUNÇÕES DE RELATÓRIOS COM FILTROS - NOVAS
// ====================================

function gerarRelatorioComFiltros(action) {
    console.log('🔧 Gerando relatório com filtros:', action);
    
    try {
        const pdfPath = getPDFBasePath();
        
        // Obter filtros ativos
        const turmaId = document.getElementById('filtro-turma-uniformes')?.value || '';
        const unidadeId = document.getElementById('filtro-unidade-uniformes')?.value || '';
        
        // Construir URL com filtros
        let url = pdfPath + '?action=' + action;
        
        if (turmaId) {
            url += '&turma_id=' + encodeURIComponent(turmaId);
        }
        
        if (unidadeId) {
            url += '&unidade_id=' + encodeURIComponent(unidadeId);
        }
        
        console.log('📄 Abrindo relatório filtrado:', url);
        window.open(url, '_blank');
        
        mostrarSucesso('Relatório com filtros sendo gerado...');
        
    } catch (error) {
        console.error('❌ Erro ao gerar relatório com filtros:', error);
        mostrarErro('Erro ao gerar relatório com filtros: ' + error.message);
    }
}

// Versões com filtros dos relatórios
function gerarRelatorioGeralFiltrado() {
    gerarRelatorioComFiltros('relatorio_geral');
}

function gerarRelatorioPorTurmaFiltrado() {
    gerarRelatorioComFiltros('relatorio_por_turma');
}

function gerarRelatorioIncompletosFiltrado() {
    gerarRelatorioComFiltros('relatorio_incompletos');
}

// ====================================
// FUNÇÕES DE TESTE - NOVAS
// ====================================

async function testarRelatoriosUniformes() {
    console.log('🔧 Testando sistema de relatórios...');
    
    try {
        const pdfPath = getPDFBasePath();
        
        // Teste simples de conectividade
        const response = await fetch(pdfPath + '?action=relatorio_geral', {
            method: 'HEAD' // Apenas verificar se o arquivo existe
        });
        
        if (response.ok) {
            console.log('✅ Sistema de relatórios funcionando corretamente');
            mostrarSucesso('Sistema de relatórios pronto para uso!');
        } else {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de relatórios:', error);
        mostrarErro('Sistema de relatórios indisponível: ' + error.message);
    }
}

// ====================================
// FUNÇÕES UTILITÁRIAS
// ====================================

function mostrarLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'flex';
    } else {
        console.log('🔄 Carregando...');
    }
}

function ocultarLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'none';
    }
}

function mostrarSucesso(mensagem) {
    console.log('✅ Sucesso:', mensagem);
    
    // Implementar notificação mais elegante se disponível
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: mensagem,
            timer: 3000,
            showConfirmButton: false
        });
    } else if (typeof toastr !== 'undefined') {
        toastr.success(mensagem);
    } else {
        // Fallback para alert simples
        alert('✅ ' + mensagem);
    }
}

function mostrarErro(mensagem) {
    console.error('❌ Erro:', mensagem);
    
    // Implementar notificação mais elegante se disponível
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: mensagem,
            confirmButtonColor: '#dc3545'
        });
    } else if (typeof toastr !== 'undefined') {
        toastr.error(mensagem);
    } else {
        // Fallback para alert simples
        alert('❌ ' + mensagem);
    }
}

// ====================================
// FUNÇÃO DE TESTE ORIGINAL
// ====================================

async function testarConexaoUniformes() {
    try {
        const basePath = getBasePath();
        console.log('🔧 Testando conexão com:', basePath);
        
        const response = await fetch(basePath + '?action=listar_uniformes');
        console.log('📡 Status:', response.status);
        console.log('📡 Headers:', [...response.headers]);
        
        const text = await response.text();
        console.log('📡 Resposta:', text.substring(0, 1000));
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
    }
}

// ====================================
// FUNÇÕES GLOBAIS (compatibilidade)
// ====================================

// Adicionar funções ao escopo global para debug
window.testarConexaoUniformes = testarConexaoUniformes;
window.testarRelatoriosUniformes = testarRelatoriosUniformes;

// Exportar funções para uso global
window.abrirModalUniformes = abrirModalUniformes;
window.fecharModalUniformes = fecharModalUniformes;
window.filtrarUniformes = filtrarUniformes;
window.limparFiltrosUniformes = limparFiltrosUniformes;
window.editarUniforme = editarUniforme;

// Funções de relatórios
window.gerarRelatorioGeralUniformes = gerarRelatorioGeralUniformes;
window.gerarRelatorioPorTurma = gerarRelatorioPorTurma;
window.gerarRelatorioTamanhos = gerarRelatorioTamanhos;
window.gerarRelatorioIncompletos = gerarRelatorioIncompletos;
window.exportarTodosRelatorios = exportarTodosRelatorios;

// Funções com filtros
window.gerarRelatorioGeralFiltrado = gerarRelatorioGeralFiltrado;
window.gerarRelatorioPorTurmaFiltrado = gerarRelatorioPorTurmaFiltrado;
window.gerarRelatorioIncompletosFiltrado = gerarRelatorioIncompletosFiltrado;
window.gerarRelatorioComFiltros = gerarRelatorioComFiltros;

// ====================================
// LOG DE CARREGAMENTO
// ====================================

console.log('🔥 Sistema de Uniformes carregado e pronto para uso!');
console.log('📋 Relatórios disponíveis:');
console.log('  - gerarRelatorioGeralUniformes()');
console.log('  - gerarRelatorioPorTurma()');
console.log('  - gerarRelatorioTamanhos()');
console.log('  - gerarRelatorioIncompletos()');
console.log('  - exportarTodosRelatorios()');
console.log('🔧 Para testar sistema: testarConexaoUniformes()');
console.log('🔧 Para testar relatórios: testarRelatoriosUniformes()');

// ====================================
// INICIALIZAÇÃO AUTOMÁTICA DE TESTES (OPCIONAL)
// ====================================

// Executar teste automático após carregamento (remover em produção se necessário)
setTimeout(() => {
    if (typeof testarRelatoriosUniformes === 'function') {
        // testarRelatoriosUniformes(); // Descomente para teste automático
    }
}, 2000);