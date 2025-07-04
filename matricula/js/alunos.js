// ===== GERENCIAMENTO DE ALUNOS - VERSÃO CORRIGIDA COMPLETA =====

// Variáveis globais
let alunosData = [];
let alunosDataFiltered = [];
let currentPage = 1;
let itemsPerPage = 10;
let totalPages = 1;

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeAlunosModal();
    console.log('✅ Sistema de alunos inicializado - versão corrigida');
});

function initializeAlunosModal() {
    // Botão para abrir modal de alunos
    const btnVerAlunos = document.getElementById('ver-aluno-btn');
    if (btnVerAlunos) {
        btnVerAlunos.addEventListener('click', abrirModalAlunos);
    }

    // Event listeners para filtros
    setupAlunosEventListeners();
}

function setupAlunosEventListeners() {
    // Filtros do modal de alunos
    document.addEventListener('change', function(e) {
        if (e.target.matches('#filtro-status-alunos, #filtro-unidade-alunos, #filtro-turma-alunos, #filtro-genero-alunos, #filtro-status-programa-alunos')) {
            filtrarAlunos();
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.matches('#filtro-nome-aluno, #filtro-cpf-aluno, #filtro-matricula-aluno, #filtro-escola-aluno')) {
            // Debounce para campos de texto
            clearTimeout(e.target.timeout);
            e.target.timeout = setTimeout(() => {
                filtrarAlunos();
            }, 500);
        }
    });

    // Botões de ação
    document.addEventListener('click', function(e) {
        if (e.target.matches('.fechar-modal-alunos')) {
            fecharModalAlunos();
        }
        
        if (e.target.matches('.btn-ver-detalhes-aluno')) {
            const alunoId = e.target.dataset.alunoId;
            abrirDetalhesAluno(alunoId);
        }

        if (e.target.matches('.btn-editar-aluno')) {
            const alunoId = e.target.dataset.alunoId;
            editarAluno(alunoId);
        }

        if (e.target.matches('.btn-exportar-alunos')) {
            exportarAlunos();
        }

        if (e.target.matches('.btn-limpar-filtros-alunos')) {
            limparFiltrosAlunos();
        }

        if (e.target.matches('.fechar-modal-detalhes-aluno')) {
            fecharModalDetalhesAluno();
        }

        // Paginação
        if (e.target.matches('.pagina-alunos')) {
            const pagina = parseInt(e.target.dataset.pagina);
            irParaPagina(pagina);
        }

        if (e.target.matches('.btn-pagina-anterior')) {
            if (currentPage > 1) {
                irParaPagina(currentPage - 1);
            }
        }

        if (e.target.matches('.btn-pagina-proxima')) {
            if (currentPage < totalPages) {
                irParaPagina(currentPage + 1);
            }
        }

        // Alternar filtros
        if (e.target.matches('.btn-toggle-filtros-alunos')) {
            toggleFiltrosAlunos();
        }
    });
}

// ===== FUNÇÃO CORRIGIDA: abrirModalAlunos =====
async function abrirModalAlunos() {
    try {
        // ✅ Verificação se função existe
        if (typeof showLoading === 'function') showLoading();

        // Criar o modal se não existir
        if (!document.getElementById('modal-alunos')) {
            criarModalAlunos();
        }

        // Carregar dados iniciais
        await Promise.all([
            carregarUnidadesSelect('filtro-unidade-alunos'),
            carregarTurmasSelect('filtro-turma-alunos'),
            carregarAlunos()
        ]);

        // Mostrar o modal
        document.getElementById('modal-alunos').style.display = 'flex';

    } catch (error) {
        console.error('Erro ao abrir modal de alunos:', error);
        // ✅ Fallback para showNotification
        if (typeof showNotification === 'function') {
            showNotification('Erro ao carregar dados dos alunos: ' + error.message, 'error');
        } else {
            alert('Erro ao carregar dados dos alunos: ' + error.message);
        }
    } finally {
        if (typeof hideLoading === 'function') hideLoading();
    }
}

function criarModalAlunos() {
    const modalHTML = `
        <div id="modal-alunos" class="modal-backdrop" style="display: none;">
            <div class="modal modal-extra-large">
                <div class="modal-header">
                    <span><i class="fas fa-user-graduate"></i> Gerenciar Alunos</span>
                    <button class="fechar-modal-alunos">×</button>
                </div>
                
                <div class="modal-body">
                    <!-- Controles Superiores -->
                    <div class="alunos-controles">
                        <div class="controles-row">
                            <div class="controles-left">
                                <button class="btn btn-primary btn-sm btn-toggle-filtros-alunos">
                                    <i class="fas fa-filter"></i> Filtros
                                </button>
                                <button class="btn btn-success btn-sm btn-exportar-alunos">
                                    <i class="fas fa-download"></i> Exportar
                                </button>
                            </div>
                            <div class="controles-right">
                                <span id="contador-alunos" class="contador-resultados">0 alunos encontrados</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div id="filtros-alunos" class="filtros-section" style="display: none;">
                        <h4><i class="fas fa-search"></i> Filtros de Busca</h4>
                        <div class="filtros-grid">
                            <div class="filtro-item">
                                <label>Nome do Aluno</label>
                                <input type="text" id="filtro-nome-aluno" placeholder="Digite o nome">
                            </div>
                            
                            <div class="filtro-item">
                                <label>CPF</label>
                                <input type="text" id="filtro-cpf-aluno" placeholder="000.000.000-00">
                            </div>
                            
                            <div class="filtro-item">
                                <label>Matrícula</label>
                                <input type="text" id="filtro-matricula-aluno" placeholder="SA20XX">
                            </div>
                            
                            <div class="filtro-item">
                                <label>Escola</label>
                                <input type="text" id="filtro-escola-aluno" placeholder="Nome da escola">
                            </div>
                            
                            <div class="filtro-item">
                                <label>Unidade</label>
                                <select id="filtro-unidade-alunos">
                                    <option value="">Todas as unidades</option>
                                </select>
                            </div>
                            
                            <div class="filtro-item">
                                <label>Turma</label>
                                <select id="filtro-turma-alunos">
                                    <option value="">Todas as turmas</option>
                                </select>
                            </div>
                            
                            <div class="filtro-item">
                                <label>Status</label>
                                <select id="filtro-status-alunos">
                                    <option value="">Todos</option>
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                    <option value="pendente">Pendente</option>
                                </select>
                            </div>
                            
                            <div class="filtro-item">
                                <label>Gênero</label>
                                <select id="filtro-genero-alunos">
                                    <option value="">Todos</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="feminino">Feminino</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            
                            <div class="filtro-item">
                                <label>Status no Programa</label>
                                <select id="filtro-status-programa-alunos">
                                    <option value="">Todos</option>
                                    <option value="novato">Novato</option>
                                    <option value="monitor">Monitor</option>
                                </select>
                            </div>
                            
                            <div class="filtro-item">
                                <label>Data Inicial</label>
                                <input type="date" id="filtro-data-inicial-alunos">
                            </div>
                            
                            <div class="filtro-item">
                                <label>Data Final</label>
                                <input type="date" id="filtro-data-final-alunos">
                            </div>
                            
                            <div class="filtro-item filtro-actions">
                                <button class="btn btn-outline btn-sm btn-limpar-filtros-alunos">
                                    <i class="fas fa-eraser"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div id="estatisticas-alunos" class="estatisticas-cards">
                        <div class="card-stat">
                            <div class="stat-icon ativo">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-ativos">0</h3>
                                <p>Ativos</p>
                            </div>
                        </div>
                        
                        <div class="card-stat">
                            <div class="stat-icon pendente">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-pendentes">0</h3>
                                <p>Pendentes</p>
                            </div>
                        </div>
                        
                        <div class="card-stat">
                            <div class="stat-icon genero">
                                <i class="fas fa-venus-mars"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-masculino">0</h3>
                                <p>Masculino</p>
                            </div>
                        </div>
                        
                        <div class="card-stat">
                            <div class="stat-icon genero">
                                <i class="fas fa-venus-mars"></i>
                            </div>
                            <div class="stat-info">
                                <h3 id="total-feminino">0</h3>
                                <p>Feminino</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabela de Alunos -->
                    <div class="alunos-tabela-container">
                        <div class="table-responsive">
                            <table class="table-alunos">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nome</th>
                                        <th>Matrícula</th>
                                        <th>Idade</th>
                                        <th>Turma/Unidade</th>
                                        <th>Status</th>
                                        <th>Responsáveis</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-alunos-body">
                                    <!-- Preenchido dinamicamente -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <div class="paginacao-alunos">
                            <div class="paginacao-info">
                                <span id="info-paginacao">Mostrando 0 de 0 resultados</span>
                            </div>
                            <div class="paginacao-controles">
                                <button class="btn btn-outline btn-sm btn-pagina-anterior">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </button>
                                <div id="paginas-numeradas" class="paginas-numeradas">
                                    <!-- Números das páginas -->
                                </div>
                                <button class="btn btn-outline btn-sm btn-pagina-proxima">
                                    Próxima <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="btn btn-outline fechar-modal-alunos">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Detalhes do Aluno -->
        <div id="modal-detalhes-aluno" class="modal-backdrop" style="display: none;">
            <div class="modal modal-large">
                <div class="modal-header">
                    <span><i class="fas fa-user-circle"></i> Detalhes do Aluno</span>
                    <button class="fechar-modal-detalhes-aluno">×</button>
                </div>
                
                <div class="modal-body">
                    <div id="conteudo-detalhes-aluno">
                        <!-- Preenchido dinamicamente -->
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="btn btn-outline fechar-modal-detalhes-aluno">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                    <button class="btn btn-primary" id="btn-editar-aluno-detalhes">
                        <i class="fas fa-edit"></i> Editar Aluno
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// ===== FUNÇÃO CORRIGIDA: carregarAlunos =====
async function carregarAlunos() {
    try {
        console.log('🔄 Carregando lista de alunos...');
        
        const filtros = obterFiltrosAtivos();
        const params = new URLSearchParams(filtros);
        params.append('action', 'listar');

        console.log('📡 Enviando requisição para:', `api/alunos_operations.php?${params}`);

        const response = await fetch(`api/alunos_operations.php?${params}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        console.log('📡 Status da resposta:', response.status);
        
        // ✅ Ler resposta como texto primeiro para debug
        const responseText = await response.text();
        console.log('📄 Resposta como texto:', responseText.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            throw new Error('Resposta inválida do servidor: ' + responseText.substring(0, 100));
        }

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar alunos');
        }

        console.log('✅ Dados carregados:', data.total, 'alunos');

        alunosData = data.data;
        alunosDataFiltered = [...alunosData];
        
        // Atualizar interface
        atualizarTabelaAlunos();
        atualizarEstatisticas();
        atualizarContador();

    } catch (error) {
        console.error('❌ Erro ao carregar alunos:', error);
        if (typeof showNotification === 'function') {
            showNotification('Erro ao carregar lista de alunos: ' + error.message, 'error');
        } else {
            alert('Erro ao carregar lista de alunos: ' + error.message);
        }
        
        // ✅ Limpar tabela em caso de erro
        const tbody = document.getElementById('tabela-alunos-body');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="sem-dados">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Erro ao carregar dados: ${error.message}</p>
                    </td>
                </tr>
            `;
        }
    }
}

// ===== FUNÇÃO CORRIGIDA: obterFiltrosAtivos =====
function obterFiltrosAtivos() {
    const filtros = {};
    
    // ✅ Mapeamento correto dos campos
    const campos = [
        { id: 'filtro-nome-aluno', key: 'nome' },
        { id: 'filtro-cpf-aluno', key: 'cpf' },
        { id: 'filtro-matricula-aluno', key: 'numero_matricula' },
        { id: 'filtro-escola-aluno', key: 'escola' },
        { id: 'filtro-status-alunos', key: 'status' },
        { id: 'filtro-unidade-alunos', key: 'unidade' },
        { id: 'filtro-turma-alunos', key: 'turma' },
        { id: 'filtro-genero-alunos', key: 'genero' },
        { id: 'filtro-status-programa-alunos', key: 'status_programa' },
        { id: 'filtro-data-inicial-alunos', key: 'data_inicial' },
        { id: 'filtro-data-final-alunos', key: 'data_final' }
    ];

    campos.forEach(campo => {
        const elemento = document.getElementById(campo.id);
        if (elemento && elemento.value.trim()) {
            filtros[campo.key] = elemento.value.trim();
        }
    });

    console.log('🔍 Filtros ativos:', filtros);
    return filtros;
}

function atualizarTabelaAlunos() {
    const tbody = document.getElementById('tabela-alunos-body');
    if (!tbody) return;

    // Calcular paginação
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const alunosPagina = alunosDataFiltered.slice(startIndex, endIndex);

    // Limpar tabela
    tbody.innerHTML = '';

    if (alunosPagina.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="sem-dados">
                    <i class="fas fa-users"></i>
                    <p>Nenhum aluno encontrado com os filtros aplicados</p>
                </td>
            </tr>
        `;
        return;
    }

    // Preencher tabela
    alunosPagina.forEach(aluno => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="aluno-foto">
                    ${aluno.foto ? 
                        `<img src="${aluno.foto}" alt="Foto de ${aluno.nome}" class="foto-miniatura">` :
                        `<div class="foto-placeholder"><i class="fas fa-user"></i></div>`
                    }
                </div>
            </td>
            <td>
                <div class="aluno-info">
                    <strong>${aluno.nome}</strong>
                    <small>${aluno.escola || 'Escola não informada'}</small>
                </div>
            </td>
            <td>
                <span class="matricula-badge">${aluno.numero_matricula}</span>
            </td>
            <td>
                <span class="idade-badge">${aluno.idade || 'N/A'} anos</span>
            </td>
            <td>
                <div class="turma-info">
                    <strong>${aluno.nome_turma || 'Não matriculado'}</strong>
                    <small>${aluno.unidade_nome || ''}</small>
                </div>
            </td>
            <td>
                <span class="status-badge ${aluno.status}">${aluno.status.toUpperCase()}</span>
            </td>
            <td>
                <div class="responsaveis-info">
                    ${aluno.responsaveis ? 
                        aluno.responsaveis.split(', ').slice(0, 2).join('<br>') :
                        'Não informado'
                    }
                    ${aluno.responsaveis && aluno.responsaveis.split(', ').length > 2 ? 
                        `<small>+${aluno.responsaveis.split(', ').length - 2} mais</small>` : 
                        ''
                    }
                </div>
            </td>
            <td>
                <div class="acoes-aluno">
                    <button class="btn btn-sm btn-primary btn-ver-detalhes-aluno" 
                            data-aluno-id="${aluno.id}" 
                            title="Ver detalhes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline btn-editar-aluno" 
                            data-aluno-id="${aluno.id}" 
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Atualizar paginação
    atualizarPaginacao();
}

function atualizarPaginacao() {
    totalPages = Math.ceil(alunosDataFiltered.length / itemsPerPage);
    
    // Atualizar informações
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, alunosDataFiltered.length);
    
    const infoPaginacao = document.getElementById('info-paginacao');
    if (infoPaginacao) {
        infoPaginacao.textContent = `Mostrando ${startIndex}-${endIndex} de ${alunosDataFiltered.length} resultados`;
    }

    // Atualizar controles
    const btnAnterior = document.querySelector('.btn-pagina-anterior');
    const btnProxima = document.querySelector('.btn-pagina-proxima');
    
    if (btnAnterior) btnAnterior.disabled = currentPage <= 1;
    if (btnProxima) btnProxima.disabled = currentPage >= totalPages;

    // Atualizar números das páginas
    const paginasContainer = document.getElementById('paginas-numeradas');
    if (paginasContainer) {
        paginasContainer.innerHTML = '';
        
        const maxPaginas = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPaginas / 2));
        let endPage = Math.min(totalPages, startPage + maxPaginas - 1);
        
        if (endPage - startPage + 1 < maxPaginas) {
            startPage = Math.max(1, endPage - maxPaginas + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const btn = document.createElement('button');
            btn.className = `btn btn-outline btn-sm pagina-alunos ${i === currentPage ? 'active' : ''}`;
            btn.dataset.pagina = i;
            btn.textContent = i;
            paginasContainer.appendChild(btn);
        }
    }
}

function irParaPagina(pagina) {
    currentPage = pagina;
    atualizarTabelaAlunos();
}

function atualizarEstatisticas() {
    // Calcular estatísticas dos dados filtrados
    const stats = {
        total: alunosDataFiltered.length,
        ativos: alunosDataFiltered.filter(a => a.status === 'ativo').length,
        pendentes: alunosDataFiltered.filter(a => a.status === 'pendente').length,
        masculino: alunosDataFiltered.filter(a => a.genero === 'masculino').length,
        feminino: alunosDataFiltered.filter(a => a.genero === 'feminino').length
    };

    // ✅ Atualizar elementos com verificação
    const elementos = {
        'total-ativos': stats.ativos,
        'total-pendentes': stats.pendentes,
        'total-masculino': stats.masculino,
        'total-feminino': stats.feminino
    };
    
    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    });
}

function atualizarContador() {
    const contador = document.getElementById('contador-alunos');
    if (contador) {
        contador.textContent = `${alunosDataFiltered.length} alunos encontrados`;
    }
}

async function filtrarAlunos() {
    await carregarAlunos();
    currentPage = 1; // Voltar para primeira página após filtrar
}

function limparFiltrosAlunos() {
    // Limpar todos os campos de filtro
    const campos = [
        'filtro-nome-aluno', 'filtro-cpf-aluno', 'filtro-matricula-aluno', 
        'filtro-escola-aluno', 'filtro-status-alunos', 'filtro-unidade-alunos', 
        'filtro-turma-alunos', 'filtro-genero-alunos', 'filtro-status-programa-alunos',
        'filtro-data-inicial-alunos', 'filtro-data-final-alunos'
    ];

    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.value = '';
        }
    });

    // Recarregar dados
    filtrarAlunos();
}

function toggleFiltrosAlunos() {
    const filtros = document.getElementById('filtros-alunos');
    if (filtros) {
        filtros.style.display = filtros.style.display === 'none' ? 'block' : 'none';
    }
}

// ===== FUNÇÃO CORRIGIDA: abrirDetalhesAluno =====
async function abrirDetalhesAluno(alunoId) {
    try {
        if (typeof showLoading === 'function') showLoading();

        const response = await fetch(`api/alunos_operations.php?action=detalhes&id=${alunoId}`);
        
        // ✅ Ler resposta como texto primeiro
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON dos detalhes:', parseError);
            throw new Error('Resposta inválida do servidor');
        }

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar detalhes do aluno');
        }

        const aluno = data.data;
        
        // Preencher modal de detalhes
        const conteudo = document.getElementById('conteudo-detalhes-aluno');
        conteudo.innerHTML = gerarHTMLDetalhesAluno(aluno);

        // Mostrar modal
        document.getElementById('modal-detalhes-aluno').style.display = 'flex';

    } catch (error) {
        console.error('Erro ao carregar detalhes do aluno:', error);
        if (typeof showNotification === 'function') {
            showNotification('Erro ao carregar detalhes do aluno: ' + error.message, 'error');
        } else {
            alert('Erro ao carregar detalhes do aluno: ' + error.message);
        }
    } finally {
        if (typeof hideLoading === 'function') hideLoading();
    }
}

function gerarHTMLDetalhesAluno(aluno) {
    return `
        <div class="detalhes-aluno">
            <!-- Cabeçalho do Aluno -->
            <div class="aluno-header">
                <div class="aluno-foto-grande">
                    ${aluno.foto ? 
                        `<img src="${aluno.foto}" alt="Foto de ${aluno.nome}">` :
                        `<div class="foto-placeholder-grande"><i class="fas fa-user-circle"></i></div>`
                    }
                </div>
                <div class="aluno-info-principal">
                    <h2>${aluno.nome}</h2>
                    <div class="badges-aluno">
                        <span class="badge status ${aluno.status}">${aluno.status.toUpperCase()}</span>
                        ${aluno.status_programa ? `<span class="badge programa">${aluno.status_programa.toUpperCase()}</span>` : ''}
                        <span class="badge genero">${aluno.genero ? aluno.genero.toUpperCase() : 'N/A'}</span>
                    </div>
                    <div class="info-basica">
                        <p><strong>Matrícula:</strong> ${aluno.numero_matricula}</p>
                        <p><strong>Idade:</strong> ${aluno.idade || 'N/A'} anos</p>
                        <p><strong>Data de Nascimento:</strong> ${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informada'}</p>
                    </div>
                </div>
            </div>

            <!-- Abas de Informações -->
            <div class="tabs-detalhes">
                <button class="tab-detalhes active" data-tab="pessoais">
                    <i class="fas fa-user"></i> Dados Pessoais
                </button>
                <button class="tab-detalhes" data-tab="educacionais">
                    <i class="fas fa-school"></i> Dados Educacionais
                </button>
                <button class="tab-detalhes" data-tab="saude">
                    <i class="fas fa-heartbeat"></i> Saúde
                </button>
                <button class="tab-detalhes" data-tab="responsaveis">
                    <i class="fas fa-users"></i> Responsáveis
                </button>
                <button class="tab-detalhes" data-tab="programa">
                    <i class="fas fa-fire"></i> Programa
                </button>
            </div>

            <!-- Conteúdo das Abas -->
            <div class="conteudo-tabs">
                <!-- Dados Pessoais -->
                <div id="tab-pessoais" class="tab-content active">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nome Completo:</label>
                            <span>${aluno.nome}</span>
                        </div>
                        <div class="info-item">
                            <label>CPF:</label>
                            <span>${aluno.cpf || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>RG:</label>
                            <span>${aluno.rg || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Gênero:</label>
                            <span>${aluno.genero || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Data de Nascimento:</label>
                            <span>${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informada'}</span>
                        </div>
                        <div class="info-item">
                            <label>Idade:</label>
                            <span>${aluno.idade || 'N/A'} anos</span>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <h4><i class="fas fa-map-marker-alt"></i> Endereço</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>CEP:</label>
                            <span>${aluno.cep || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Logradouro:</label>
                            <span>${aluno.logradouro || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Número:</label>
                            <span>${aluno.endereco_numero || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Complemento:</label>
                            <span>${aluno.complemento || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Bairro:</label>
                            <span>${aluno.bairro || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Cidade:</label>
                            <span>${aluno.cidade || 'Não informada'}</span>
                        </div>
                    </div>
                </div>

                <!-- Dados Educacionais -->
                <div id="tab-educacionais" class="tab-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Escola:</label>
                            <span>${aluno.escola || 'Não informada'}</span>
                        </div>
                        <div class="info-item">
                            <label>Série:</label>
                            <span>${aluno.serie || 'Não informada'}</span>
                        </div>
                        <div class="info-item">
                            <label>Telefone da Escola:</label>
                            <span>${aluno.telefone_escola || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Diretor:</label>
                            <span>${aluno.diretor_escola || 'Não informado'}</span>
                        </div>
                    </div>
                </div>

                <!-- Dados de Saúde -->
                <div id="tab-saude" class="tab-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Tipo Sanguíneo:</label>
                            <span>${aluno.tipo_sanguineo || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Criança Atípica:</label>
                            <span>${aluno.crianca_atipica === 'sim' ? 'Sim' : 'Não'}</span>
                        </div>
                        <div class="info-item">
                            <label>Tem Alergias/Condições:</label>
                            <span>${aluno.tem_alergias_condicoes === 'sim' ? 'Sim' : 'Não'}</span>
                        </div>
                        <div class="info-item">
                            <label>Medicação Contínua:</label>
                            <span>${aluno.medicacao_continua === 'sim' ? 'Sim' : 'Não'}</span>
                        </div>
                    </div>

                    ${aluno.detalhes_alergias_condicoes ? `
                        <h4><i class="fas fa-exclamation-triangle"></i> Detalhes de Alergias/Condições</h4>
                        <div class="info-texto">
                            <p>${aluno.detalhes_alergias_condicoes}</p>
                        </div>
                    ` : ''}

                    ${aluno.detalhes_medicacao ? `
                        <h4><i class="fas fa-pills"></i> Detalhes da Medicação</h4>
                        <div class="info-texto">
                            <p>${aluno.detalhes_medicacao}</p>
                        </div>
                    ` : ''}
                </div>

                <!-- Responsáveis -->
                <div id="tab-responsaveis" class="tab-content">
                    ${aluno.responsaveis && aluno.responsaveis.length > 0 ? 
                        aluno.responsaveis.map(resp => `
                            <div class="responsavel-card">
                                <h4><i class="fas fa-user"></i> ${resp.nome}</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label>Parentesco:</label>
                                        <span>${resp.parentesco}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>CPF:</label>
                                        <span>${resp.cpf}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>RG:</label>
                                        <span>${resp.rg}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Telefone:</label>
                                        <span>${resp.telefone}</span>
                                    </div>
                                    <div class="info-item">
                                        <label>Email:</label>
                                        <span>${resp.email}</span>
                                    </div>
                                    ${resp.profissao ? `
                                        <div class="info-item">
                                            <label>Profissão:</label>
                                            <span>${resp.profissao}</span>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('') 
                        : '<p class="sem-dados">Nenhum responsável cadastrado</p>'
                    }
                </div>

                <!-- Dados do Programa -->
                <div id="tab-programa" class="tab-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Unidade:</label>
                            <span>${aluno.unidade_nome || 'Não matriculado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Turma:</label>
                            <span>${aluno.nome_turma || 'Não matriculado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Status no Programa:</label>
                            <span>${aluno.status_programa || 'Não definido'}</span>
                        </div>
                        <div class="info-item">
                            <label>Data de Matrícula:</label>
                            <span>${aluno.data_matricula ? new Date(aluno.data_matricula).toLocaleDateString('pt-BR') : 'Não informada'}</span>
                        </div>
                    </div>

                    <!-- Uniformes -->
                    <h4><i class="fas fa-tshirt"></i> Uniformes</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Tamanho Camisa:</label>
                            <span>${aluno.tamanho_camisa || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Tamanho Calça:</label>
                            <span>${aluno.tamanho_calca || 'Não informado'}</span>
                        </div>
                        <div class="info-item">
                            <label>Tamanho Calçado:</label>
                            <span>${aluno.tamanho_calcado || 'Não informado'}</span>
                        </div>
                    </div>

                    ${aluno.unidade_nome ? `
                        <h4><i class="fas fa-building"></i> Informações da Unidade</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Endereço:</label>
                                <span>${aluno.endereco_unidade || 'Não informado'}</span>
                            </div>
                            <div class="info-item">
                                <label>Telefone:</label>
                                <span>${aluno.telefone_unidade || 'Não informado'}</span>
                            </div>
                            <div class="info-item">
                                <label>Coordenador:</label>
                                <span>${aluno.coordenador || 'Não informado'}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function fecharModalAlunos() {
    const modal = document.getElementById('modal-alunos');
    if (modal) {
        modal.style.display = 'none';
    }
}

function fecharModalDetalhesAluno() {
    const modal = document.getElementById('modal-detalhes-aluno');
    if (modal) {
        modal.style.display = 'none';
    }
}

function editarAluno(alunoId) {
    // Redirecionar para a página de edição ou abrir modal de edição
    // Esta funcionalidade pode ser implementada posteriormente
    if (typeof showNotification === 'function') {
        showNotification('Funcionalidade de edição será implementada em breve', 'info');
    } else {
        alert('Funcionalidade de edição será implementada em breve');
    }
}

// ===== FUNÇÃO CORRIGIDA: exportarAlunos =====
async function exportarAlunos() {
    try {
        if (typeof showLoading === 'function') showLoading();

        const filtros = obterFiltrosAtivos();
        const params = new URLSearchParams(filtros);
        params.append('action', 'exportar');

        const response = await fetch(`api/alunos_operations.php?${params}`);
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error('Resposta inválida do servidor');
        }

        if (!data.success) {
            throw new Error(data.error || 'Erro ao exportar dados');
        }

        // Gerar CSV
        const csv = gerarCSVAlunos(data.data);
        downloadCSV(csv, 'alunos.csv');

        if (typeof showNotification === 'function') {
            showNotification('Dados exportados com sucesso!', 'success');
        } else {
            alert('Dados exportados com sucesso!');
        }

    } catch (error) {
        console.error('Erro ao exportar alunos:', error);
        if (typeof showNotification === 'function') {
            showNotification('Erro ao exportar dados: ' + error.message, 'error');
        } else {
            alert('Erro ao exportar dados: ' + error.message);
        }
    } finally {
        if (typeof hideLoading === 'function') hideLoading();
    }
}

function gerarCSVAlunos(alunos) {
    const headers = [
        'Nome', 'Matrícula', 'CPF', 'Data Nascimento', 'Idade', 'Gênero',
        'Escola', 'Série', 'Unidade', 'Turma', 'Status', 'Status Programa',
        'Tipo Sanguíneo', 'Telefone Escola', 'Responsáveis', 'Endereço'
    ];

    const rows = alunos.map(aluno => [
        aluno.nome || '',
        aluno.numero_matricula || '',
        aluno.cpf || '',
        aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : '',
        aluno.idade || '',
        aluno.genero || '',
        aluno.escola || '',
        aluno.serie || '',
        aluno.unidade_nome || '',
        aluno.nome_turma || '',
        aluno.status || '',
        aluno.status_programa || '',
        aluno.tipo_sanguineo || '',
        aluno.telefone_escola || '',
        aluno.responsaveis || '',
        aluno.endereco_completo || ''
    ]);

    return [headers, ...rows].map(row => 
        row.map(cell => `"${cell}"`).join(',')
    ).join('\n');
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Event listeners para as abas de detalhes
document.addEventListener('click', function(e) {
    if (e.target.matches('.tab-detalhes')) {
        const tab = e.target.dataset.tab;
        
        // Remover classe active de todas as abas
        document.querySelectorAll('.tab-detalhes').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Ativar aba clicada
        e.target.classList.add('active');
        const tabContent = document.getElementById(`tab-${tab}`);
        if (tabContent) tabContent.classList.add('active');
    }
});

// ===== FUNÇÃO CORRIGIDA: carregarUnidadesSelect =====
async function carregarUnidadesSelect(selectId) {
    try {
        console.log('🔄 Carregando unidades para select:', selectId);
        
        const response = await fetch('api/get_data.php?type=unidades');
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON das unidades:', parseError);
            throw new Error('Resposta inválida do servidor para unidades');
        }
        
        const select = document.getElementById(selectId);
        if (select && data.success) {
            select.innerHTML = '<option value="">Todas as unidades</option>';
            data.data.forEach(unidade => {
                select.innerHTML += `<option value="${unidade.id}">${unidade.nome}</option>`;
            });
            console.log('✅ Unidades carregadas:', data.data.length);
        } else if (!data.success) {
            console.error('❌ Erro ao carregar unidades:', data.error);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar unidades:', error);
    }
}

// ===== FUNÇÃO CORRIGIDA: carregarTurmasSelect =====
async function carregarTurmasSelect(selectId) {
    try {
        console.log('🔄 Carregando turmas para select:', selectId);
        
        const response = await fetch('api/get_data.php?type=turmas');
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON das turmas:', parseError);
            throw new Error('Resposta inválida do servidor para turmas');
        }
        
        const select = document.getElementById(selectId);
        if (select && data.success) {
            select.innerHTML = '<option value="">Todas as turmas</option>';
            data.data.forEach(turma => {
                select.innerHTML += `<option value="${turma.id}">${turma.nome_turma} - ${turma.unidade_nome || 'Sem unidade'}</option>`;
            });
            console.log('✅ Turmas carregadas:', data.data.length);
        } else if (!data.success) {
            console.error('❌ Erro ao carregar turmas:', data.error);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar turmas:', error);
    }
}

// ===== TESTE SIMPLES PARA DEBUG =====

// Função de teste simples
async function testeSimpleAPI() {
    console.log('🧪 Iniciando teste simples da API...');
    
    try {
        // Teste 1: Verificar se a API responde
        console.log('1. Testando acesso básico à API...');
        const response = await fetch('api/alunos_operations.php?action=listar');
        
        console.log('Response status:', response.status);
        console.log('Response OK:', response.ok);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        // Teste 2: Ler resposta como texto primeiro
        const textResponse = await response.text();
        console.log('2. Resposta como texto:', textResponse.substring(0, 500));
        
        // Teste 3: Tentar fazer parse do JSON
        let jsonData;
        try {
            jsonData = JSON.parse(textResponse);
            console.log('3. JSON parseado com sucesso:', jsonData);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            console.log('Texto que falhou no parse:', textResponse.substring(0, 500));
            return;
        }
        
        // Teste 4: Verificar estrutura da resposta
        if (jsonData.success) {
            console.log('✅ API retornou sucesso!');
            console.log('Total de alunos:', jsonData.total);
            console.log('Primeiros 2 alunos:', jsonData.data.slice(0, 2));
        } else {
            console.log('❌ API retornou erro:', jsonData.error);
        }
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
    }
}

// Função para testar API de dados básicos
async function testeAPIBasica() {
    console.log('🧪 Testando API de dados básicos...');
    
    const tipos = ['unidades', 'turmas', 'professores'];
    
    for (const tipo of tipos) {
        try {
            console.log(`Testando ${tipo}...`);
            const response = await fetch(`api/get_data.php?type=${tipo}`);
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.log(`❌ ${tipo}: Erro de parse - ${responseText.substring(0, 200)}`);
                continue;
            }
            
            if (data.success) {
                console.log(`✅ ${tipo}: ${data.data.length} registros`);
            } else {
                console.log(`❌ ${tipo}: ${data.error}`);
            }
        } catch (error) {
            console.log(`❌ Erro em ${tipo}:`, error.message);
        }
    }
}

// Função para substituir temporariamente a função carregarAlunos
function carregarAlunosSimples() {
    console.log('🔄 Função simplificada de carregar alunos');
    
    testeSimpleAPI().then(() => {
        console.log('Teste concluído. Verifique o console para detalhes.');
    });
}

// Botão de teste no console
window.testeAlunosAPI = testeSimpleAPI;
window.testeAPIBasica = testeAPIBasica;
window.carregarAlunosSimples = carregarAlunosSimples;

// Log de inicialização
console.log('🔧 Funções de teste carregadas:');
console.log('- testeAlunosAPI() - Testa a API principal');
console.log('- testeAPIBasica() - Testa APIs de dados básicos');
console.log('- carregarAlunosSimples() - Versão simplificada do carregamento');

// Auto-executar teste quando abrir o modal (para debug)
document.addEventListener('click', function(e) {
    if (e.target.matches('#ver-aluno-btn')) {
        console.log('🎯 Modal de alunos sendo aberto - executando teste automático em 2 segundos...');
        setTimeout(() => {
            testeSimpleAPI();
        }, 2000);
    }
});

// ===== VERIFICAÇÕES FINAIS E LOGS =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Verificando funções necessárias:');
    console.log('- showLoading:', typeof showLoading);
    console.log('- hideLoading:', typeof hideLoading);
    console.log('- showNotification:', typeof showNotification);
    console.log('- alunosData:', typeof alunosData);
    
    // Verificar se variáveis globais existem
    if (typeof window.usuarioId !== 'undefined') {
        console.log('- usuarioId:', window.usuarioId);
    }
    if (typeof window.usuarioNome !== 'undefined') {
        console.log('- usuarioNome:', window.usuarioNome);
    }
});