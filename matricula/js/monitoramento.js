// ===== SISTEMA DE MONITORAMENTO DE ATIVIDADES =====
// Arquivo: monitoramento.js
// 
// Este sistema busca dados EXCLUSIVAMENTE do banco de dados através da API:
// - Endpoint: ./api/get_atividades.php
// - Tabela: atividades (com joins em turma, unidade, professor)
// - Funcionalidades: Dashboard, listagem, calendário, edição de status
// - SEM DADOS MOCK/ESTÁTICOS - apenas dados reais do banco
//
// Estrutura do banco compatível com enum:
// nome_atividade: 'Ed. Física','Salvamento','Informática','Primeiro Socorros',
//                 'Ordem Unida','Combate a Incêndio','Ética e Cidadania',
//                 'Higiene Pessoal','Meio Ambiente','Educação no Trânsito',
//                 'Temas Transversais','Combate uso de Drogas',
//                 'ECA e Direitos Humanos','Treinamento de Formatura'

// Variáveis globais
let atividadesData = [];
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedActivity = null;

// Mapeamento de tipos de atividade com cores (baseado no enum do banco)
const tiposAtividade = {
    'Ed. Física': '#e74c3c',
    'Salvamento': '#3498db',
    'Informática': '#9b59b6',
    'Primeiro Socorros': '#e67e22',
    'Ordem Unida': '#2c3e50',
    'Combate a Incêndio': '#e74c3c',
    'Ética e Cidadania': '#27ae60',
    'Higiene Pessoal': '#f39c12',
    'Meio Ambiente': '#2ecc71',
    'Educação no Trânsito': '#f1c40f',
    'Temas Transversais': '#8e44ad',
    'Combate uso de Drogas': '#c0392b',
    'ECA e Direitos Humanos': '#16a085',
    'Treinamento de Formatura': '#34495e'
};

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Inicializando sistema de monitoramento - SOMENTE DADOS DO BANCO');
    
    // Event listeners para botões
    setupEventListeners();
    
    // Nota: Os dados são carregados apenas quando o modal é aberto
    // e vêm EXCLUSIVAMENTE do banco de dados via API
    
    console.log('✅ Sistema inicializado (dados serão carregados do banco ao abrir modal)');
});

// ===== CONFIGURAÇÃO DE EVENT LISTENERS =====
function setupEventListeners() {
    console.log('🔧 Configurando event listeners do monitoramento...');
    
    // Botão abrir modal de monitoramento
    const btnMonitoramento = document.getElementById('monitoramento-btn');
    if (btnMonitoramento) {
        btnMonitoramento.addEventListener('click', abrirModalMonitoramento);
    }
    
    // Botões fechar modais
    setupCloseButtons();
    
    // Abas do modal
    setupTabSwitching();
    
    // Calendário
    setupCalendarControls();
    
    // Filtros
    setupFilters();
}

function setupCloseButtons() {
    // Modal principal - corrigir nomes das funções
    const fecharModalMonitoramentoBtns = document.querySelectorAll('.fechar-modal-monitoramento');
    fecharModalMonitoramentoBtns.forEach(btn => {
        btn.addEventListener('click', fecharModalMonitoramento);
    });
    
    // Modal detalhes
    const fecharModalDetalhesBtns = document.querySelectorAll('.fechar-modal-detalhes');
    fecharModalDetalhesBtns.forEach(btn => {
        btn.addEventListener('click', fecharModalDetalhes);
    });
    
    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            const modalMonitoramento = document.getElementById('modal-monitoramento');
            const modalDetalhes = document.getElementById('modal-detalhes-atividade');
            
            if (modalMonitoramento && modalMonitoramento.style.display === 'block') {
                fecharModalMonitoramento();
            }
            if (modalDetalhes && modalDetalhes.style.display === 'block') {
                fecharModalDetalhes();
            }
        }
    });
}

function setupTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-monitoramento');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });
}

function setupCalendarControls() {
    const btnAnterior = document.getElementById('mes-anterior');
    const btnProximo = document.getElementById('mes-proximo');
    
    if (btnAnterior) {
        btnAnterior.addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            gerarCalendario();
        });
    }
    
    if (btnProximo) {
        btnProximo.addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            gerarCalendario();
        });
    }
}

function setupFilters() {
    // Aplicar filtros quando mudar algum campo
    const filtros = ['#filtro-status-atividade', '#filtro-tipo-atividade', '#filtro-data-atividade'];
    filtros.forEach(selector => {
        const elemento = document.querySelector(selector);
        if (elemento) {
            elemento.addEventListener('change', aplicarFiltrosAtividades);
        }
    });
}

// ===== FUNÇÕES DO MODAL =====
function abrirModalMonitoramento() {
    console.log('📊 Abrindo modal de monitoramento - carregando do banco...');
    
    const modal = document.getElementById('modal-monitoramento');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Carregar dados EXCLUSIVAMENTE do banco
        carregarAtividades();
        
        // Mostrar tab dashboard por padrão
        switchTab('dashboard');
        
        showNotification('Conectando ao banco de dados...', 'info');
    }
}

function fecharModalMonitoramento() {
    const modal = document.getElementById('modal-monitoramento');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function fecharModalDetalhes() {
    const modal = document.getElementById('modal-detalhes-atividade');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ===== SISTEMA DE ABAS =====
function switchTab(targetTab) {
    console.log('📋 Mudando para aba:', targetTab);
    
    // Atualizar botões das abas
    const tabButtons = document.querySelectorAll('.tab-monitoramento');
    tabButtons.forEach(btn => {
        btn.classList.remove('ativo');
        if (btn.getAttribute('data-tab') === targetTab) {
            btn.classList.add('ativo');
        }
    });
    
    // Mostrar/ocultar conteúdo das abas
    const tabContents = document.querySelectorAll('.tab-content-monitoramento');
    tabContents.forEach(content => {
        content.style.display = 'none';
    });
    
    const activeContent = document.getElementById(`tab-${targetTab}`);
    if (activeContent) {
        activeContent.style.display = 'block';
    }
    
    // Executar ações específicas de cada aba
    switch(targetTab) {
        case 'dashboard':
            atualizarDashboard();
            break;
        case 'atividades':
            carregarTodasAtividades();
            break;
        case 'calendario':
            gerarCalendario();
            break;
    }
}

// ===== CARREGAMENTO DE DADOS =====
async function carregarAtividades() {
    console.log('📥 Carregando atividades do banco de dados...');
    
    try {
        showLoading(true);
        
        const response = await fetch('./api/get_atividades.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Mapear dados do banco para o formato esperado
            atividadesData = data.atividades.map(atividade => ({
                id: atividade.id,
                nome_atividade: atividade.nome_atividade,
                tipo_atividade: atividade.nome_atividade, // Campo enum do banco
                data_atividade: atividade.data_atividade,
                hora_inicio: atividade.hora_inicio,
                hora_fim: atividade.hora_termino, // Mapear hora_termino para hora_fim
                local: atividade.local_atividade,
                instrutor: atividade.instrutor_responsavel,
                turma: atividade.turma_nome || `Turma ${atividade.turma_id}`,
                objetivo: atividade.objetivo_atividade,
                conteudo_abordado: atividade.conteudo_abordado,
                status: atividade.status,
                unidade_nome: atividade.unidade_nome,
                professor_nome: atividade.professor_nome,
                criado_em: atividade.criado_em,
                atualizado_em: atividade.atualizado_em
            }));
            
            console.log('✅ Atividades carregadas do banco:', atividadesData.length);
            console.log('📊 Estatísticas:', data.stats);
            atualizarDashboard();
            
            showNotification(`${atividadesData.length} atividades carregadas do banco!`, 'success');
        } else {
            console.error('❌ Erro ao carregar atividades:', data.message);
            showNotification(`Erro: ${data.message}`, 'error');
            atividadesData = []; // Array vazio se não conseguir carregar
            atualizarDashboard();
        }
        
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        showNotification('Erro de conexão com o banco de dados.', 'error');
        atividadesData = []; // Array vazio se houver erro
        atualizarDashboard();
    } finally {
        showLoading(false);
    }
}



// ===== DASHBOARD =====
function atualizarDashboard() {
    console.log('📊 Atualizando dashboard...');
    
    // Contar atividades por status
    const contadores = {
        planejadas: atividadesData.filter(a => a.status === 'planejada').length,
        em_andamento: atividadesData.filter(a => a.status === 'em_andamento').length,
        concluidas: atividadesData.filter(a => a.status === 'concluida').length,
        canceladas: atividadesData.filter(a => a.status === 'cancelada').length
    };
    
    // Atualizar cards de resumo
    atualizarCardsResumo(contadores);
    
    // Atualizar seções de atividades
    atualizarSecoesAtividades();
}

function atualizarCardsResumo(contadores) {
    const elementos = {
        'total-planejadas': contadores.planejadas,
        'total-em-andamento': contadores.em_andamento,
        'total-concluidas': contadores.concluidas,
        'total-canceladas': contadores.canceladas
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        }
    });
}

function atualizarSecoesAtividades() {
    const hoje = new Date().toISOString().split('T')[0];
    
    // Atividades em andamento
    const emAndamento = atividadesData.filter(a => a.status === 'em_andamento');
    renderizarListaAtividades('atividades-em-andamento', emAndamento);
    atualizarContadorSecao('count-em-andamento', emAndamento.length);
    
    // Próximas atividades (planejadas para hoje ou futuro)
    const proximas = atividadesData.filter(a => 
        a.status === 'planejada' && a.data_atividade >= hoje
    ).slice(0, 5);
    renderizarListaAtividades('atividades-proximas', proximas);
    atualizarContadorSecao('count-proximas', proximas.length);
    
    // Concluídas hoje
    const concluidasHoje = atividadesData.filter(a => 
        a.status === 'concluida' && a.data_atividade === hoje
    );
    renderizarListaAtividades('atividades-concluidas-hoje', concluidasHoje);
    atualizarContadorSecao('count-concluidas-hoje', concluidasHoje.length);
}

function atualizarContadorSecao(elementId, count) {
    const elemento = document.getElementById(elementId);
    if (elemento) {
        elemento.textContent = count;
    }
}

function renderizarListaAtividades(containerId, atividades) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (atividades.length === 0) {
        container.innerHTML = `
            <div class="sem-atividades">
                <i class="fas fa-calendar-times"></i>
                <p>Nenhuma atividade encontrada</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = atividades.map(atividade => criarItemAtividade(atividade)).join('');
}

function criarItemAtividade(atividade) {
    const dataFormatada = formatarData(atividade.data_atividade);
    const statusClass = atividade.status.replace('_', '-');
    const statusTexto = formatarStatus(atividade.status);
    
    return `
        <div class="atividade-item" onclick="abrirDetalhesAtividade(${atividade.id})">
            <div class="atividade-header">
                <h4 class="atividade-titulo">${atividade.nome_atividade}</h4>
                <span class="status-badge ${statusClass}">${statusTexto}</span>
            </div>
            <div class="atividade-detalhes">
                <div class="detalhe-item">
                    <i class="fas fa-calendar"></i>
                    <span>${dataFormatada}</span>
                </div>
                <div class="detalhe-item">
                    <i class="fas fa-clock"></i>
                    <span>${atividade.hora_inicio} - ${atividade.hora_fim}</span>
                </div>
                <div class="detalhe-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${atividade.local}</span>
                </div>
                <div class="detalhe-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>${atividade.instrutor}</span>
                </div>
                <div class="detalhe-item">
                    <i class="fas fa-users"></i>
                    <span>${atividade.turma}</span>
                </div>
                <div class="detalhe-item">
                    <i class="fas fa-tag"></i>
                    <span>${atividade.tipo_atividade}</span>
                </div>
            </div>
        </div>
    `;
}

// ===== TODAS AS ATIVIDADES =====
function carregarTodasAtividades() {
    console.log('📋 Carregando todas as atividades...');
    
    aplicarFiltrosAtividades();
}

function aplicarFiltrosAtividades() {
    const status = document.getElementById('filtro-status-atividade')?.value || '';
    const tipo = document.getElementById('filtro-tipo-atividade')?.value || '';
    const data = document.getElementById('filtro-data-atividade')?.value || '';
    
    let atividadesFiltradas = [...atividadesData];
    
    // Aplicar filtros
    if (status) {
        atividadesFiltradas = atividadesFiltradas.filter(a => a.status === status);
    }
    
    if (tipo) {
        atividadesFiltradas = atividadesFiltradas.filter(a => a.tipo_atividade === tipo);
    }
    
    if (data) {
        atividadesFiltradas = atividadesFiltradas.filter(a => a.data_atividade === data);
    }
    
    // Ordenar por data (mais recentes primeiro)
    atividadesFiltradas.sort((a, b) => new Date(b.data_atividade) - new Date(a.data_atividade));
    
    renderizarListaAtividades('todas-atividades', atividadesFiltradas);
    
    console.log(`🔍 Filtros aplicados: ${atividadesFiltradas.length} de ${atividadesData.length} atividades`);
}

function limparFiltrosAtividades() {
    document.getElementById('filtro-status-atividade').value = '';
    document.getElementById('filtro-tipo-atividade').value = '';
    document.getElementById('filtro-data-atividade').value = '';
    
    aplicarFiltrosAtividades();
    showNotification('Filtros limpos!', 'info');
}

// ===== CALENDÁRIO =====
function gerarCalendario() {
    console.log('📅 Gerando calendário para:', currentMonth + 1, currentYear);
    
    const container = document.getElementById('calendario-atividades');
    const mesAtualElement = document.getElementById('mes-atual');
    
    if (!container || !mesAtualElement) return;
    
    // Atualizar título do mês
    const nomesMeses = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    mesAtualElement.textContent = `${nomesMeses[currentMonth]} ${currentYear}`;
    
    // Gerar estrutura do calendário
    container.innerHTML = '';
    
    // Cabeçalho dos dias da semana
    const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    diasSemana.forEach(dia => {
        const diaElement = document.createElement('div');
        diaElement.className = 'dia-calendario header-dia';
        diaElement.textContent = dia;
        container.appendChild(diaElement);
    });
    
    // Calcular primeiro dia do mês e dias no mês
    const primeiroDia = new Date(currentYear, currentMonth, 1);
    const ultimoDia = new Date(currentYear, currentMonth + 1, 0);
    const diasNoMes = ultimoDia.getDate();
    const diaSemanaInicio = primeiroDia.getDay();
    
    // Dias vazios no início
    for (let i = 0; i < diaSemanaInicio; i++) {
        const diaVazio = document.createElement('div');
        diaVazio.className = 'dia-calendario vazio';
        container.appendChild(diaVazio);
    }
    
    // Dias do mês
    for (let dia = 1; dia <= diasNoMes; dia++) {
        const diaElement = document.createElement('div');
        diaElement.className = 'dia-calendario';
        
        const dataAtual = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        
        // Número do dia
        const numeroDiv = document.createElement('div');
        numeroDiv.className = 'dia-numero';
        numeroDiv.textContent = dia;
        diaElement.appendChild(numeroDiv);
        
        // Atividades do dia
        const atividadesDoDia = atividadesData.filter(a => a.data_atividade === dataAtual);
        atividadesDoDia.forEach(atividade => {
            const atividadeDiv = document.createElement('div');
            atividadeDiv.className = `atividade-calendario ${atividade.status}`;
            atividadeDiv.textContent = atividade.nome_atividade;
            atividadeDiv.title = `${atividade.nome_atividade} - ${atividade.hora_inicio}`;
            atividadeDiv.onclick = (e) => {
                e.stopPropagation();
                abrirDetalhesAtividade(atividade.id);
            };
            diaElement.appendChild(atividadeDiv);
        });
        
        container.appendChild(diaElement);
    }
}

// ===== DETALHES DA ATIVIDADE =====
function abrirDetalhesAtividade(id) {
    console.log('🔍 Abrindo detalhes da atividade:', id);
    
    const atividade = atividadesData.find(a => a.id == id);
    if (!atividade) {
        showNotification('Atividade não encontrada!', 'error');
        return;
    }
    
    selectedActivity = atividade;
    
    // Preencher modal de detalhes
    preencherDetalhesAtividade(atividade);
    
    // Abrir modal
    const modal = document.getElementById('modal-detalhes-atividade');
    if (modal) {
        modal.style.display = 'block';
    }
}

function preencherDetalhesAtividade(atividade) {
    const elementos = {
        'detalhe-nome-atividade': atividade.nome_atividade,
        'detalhe-data-horario': `${formatarData(atividade.data_atividade)} das ${atividade.hora_inicio} às ${atividade.hora_fim}`,
        'detalhe-local': atividade.local,
        'detalhe-instrutor': atividade.instrutor,
        'detalhe-turma': atividade.turma,
        'detalhe-objetivo': atividade.objetivo,
        'detalhe-conteudo': atividade.conteudo_abordado
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor;
        }
    });
    
    // Status badge
    const statusBadge = document.getElementById('detalhe-status-badge');
    if (statusBadge) {
        const statusClass = atividade.status.replace('_', '-');
        statusBadge.className = `status-badge ${statusClass}`;
        statusBadge.textContent = formatarStatus(atividade.status);
    }
}

// ===== ATUALIZAÇÃO DE STATUS =====
async function atualizarStatusAtividade(atividadeId, novoStatus) {
    try {
        const response = await fetch('./api/get_atividades.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'atualizar_status',
                id: atividadeId,
                status: novoStatus
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Status atualizado no banco:', novoStatus);
            return true;
        } else {
            console.error('❌ Erro ao atualizar status:', data.message);
            return false;
        }
    } catch (error) {
        console.error('❌ Erro na requisição de atualização:', error);
        return false;
    }
}

function editarStatusAtividade() {
    if (!selectedActivity) return;
    
    const novosStatus = {
        'planejada': 'em_andamento',
        'em_andamento': 'concluida',
        'concluida': 'planejada',
        'cancelada': 'planejada'
    };
    
    const novoStatus = novosStatus[selectedActivity.status] || 'planejada';
    
    // Tentar atualizar no banco
    atualizarStatusAtividade(selectedActivity.id, novoStatus).then(sucesso => {
        if (sucesso) {
            // Atualizar localmente apenas se a atualização no banco foi bem-sucedida
            selectedActivity.status = novoStatus;
            
            // Atualizar na lista local
            const index = atividadesData.findIndex(a => a.id === selectedActivity.id);
            if (index !== -1) {
                atividadesData[index] = selectedActivity;
            }
            
            // Atualizar interface
            preencherDetalhesAtividade(selectedActivity);
            atualizarDashboard();
            
            showNotification(`Status alterado para: ${formatarStatus(novoStatus)}`, 'success');
        } else {
            showNotification('Erro ao atualizar status no banco de dados.', 'error');
        }
    });
}

// ===== EXPORTAÇÃO =====
function exportarRelatorioAtividades() {
    console.log('📊 Exportando relatório de atividades...');
    
    const dados = {
        data_geracao: new Date().toLocaleString('pt-BR'),
        total_atividades: atividadesData.length,
        resumo: {
            planejadas: atividadesData.filter(a => a.status === 'planejada').length,
            em_andamento: atividadesData.filter(a => a.status === 'em_andamento').length,
            concluidas: atividadesData.filter(a => a.status === 'concluida').length,
            canceladas: atividadesData.filter(a => a.status === 'cancelada').length
        },
        atividades: atividadesData
    };
    
    // Criar arquivo JSON para download
    const blob = new Blob([JSON.stringify(dados, null, 2)], {
        type: 'application/json'
    });
    
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `relatorio_atividades_${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showNotification('Relatório exportado com sucesso!', 'success');
}

// ===== FUNÇÕES UTILITÁRIAS =====
function formatarData(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('pt-BR');
}

function formatarStatus(status) {
    const statusMap = {
        'planejada': 'Planejada',
        'em_andamento': 'Em Andamento',
        'concluida': 'Concluída',
        'cancelada': 'Cancelada'
    };
    return statusMap[status] || status;
}

function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

function showNotification(message, type = 'info') {
    // Remove notificação anterior se existir
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                 type === 'error' ? 'fas fa-exclamation-circle' : 
                 'fas fa-info-circle';
    
    notification.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar com animação
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===== FUNÇÕES GLOBAIS =====
window.abrirDetalhesAtividade = abrirDetalhesAtividade;
window.aplicarFiltrosAtividades = aplicarFiltrosAtividades;
window.limparFiltrosAtividades = limparFiltrosAtividades;
window.editarStatusAtividade = editarStatusAtividade;
window.exportarRelatorioAtividades = exportarRelatorioAtividades;
window.atualizarStatusAtividade = atualizarStatusAtividade;
window.carregarAtividades = carregarAtividades;

console.log('🎯 monitoramento.js carregado - SOMENTE DADOS DO BANCO!');