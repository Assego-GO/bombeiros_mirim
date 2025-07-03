// js/admin_ocorrencias.js - Adaptado para APIs existentes

// Função principal para abrir o modal (chamada pelo onclick)
// SUBSTITUA apenas esta função no seu admin_ocorrencias.js:

function abrirModalOcorrencias() {
    console.log('🚀 Abrindo modal de ocorrências...');
    
    // Mostrar o modal
    document.getElementById('modalOcorrenciasAdmin').style.display = 'block';
    
    // FORÇAR que os filtros sejam visíveis
    setTimeout(() => {
        // Mostrar o container de filtros
        const filterContainer = document.querySelector('.filter-container') || 
                               document.getElementById('filter-container');
        
        if (filterContainer) {
            filterContainer.style.display = 'block';
            filterContainer.style.visibility = 'visible';
            filterContainer.style.opacity = '1';
            console.log('✅ Filter container forçado a ser visível');
        }
        
        // Tentar clicar no botão toggle se existir e os filtros ainda estiverem ocultos
        const toggleButton = document.getElementById('toggle-filter');
        if (toggleButton) {
            const container = document.querySelector('.filter-container');
            if (container && window.getComputedStyle(container).display === 'none') {
                console.log('🔘 Clicando no botão toggle-filter...');
                toggleButton.click();
            }
        }
        
        // Carregar dados dos filtros APÓS garantir que estão visíveis
        carregarDadosFiltros();
        
    }, 200); // Aguarda 200ms para garantir que o modal está totalmente carregado
    
    // Carregar as ocorrências
    carregarOcorrenciasAdmin();
}

// Função para carregar as ocorrências COM FILTROS
function carregarOcorrenciasAdmin() {
    const listaElement = document.getElementById('lista-ocorrencias-admin');
    listaElement.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Carregando ocorrências...</p></div>';
    
    // Coletar dados dos filtros
    const filtros = coletarFiltros();
    
    // Construir URL com parâmetros de filtro
    let url = 'api/listar_ocorrencias_admin.php';
    const params = new URLSearchParams();
    
    // Adicionar filtros à URL se existirem
    Object.keys(filtros).forEach(key => {
        if (filtros[key] && filtros[key].trim() !== '') {
            params.append(key, filtros[key]);
        }
    });
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    console.log('🔍 Carregando ocorrências com URL:', url);
    console.log('📋 Filtros aplicados:', filtros);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                exibirOcorrenciasAdmin(data.ocorrencias, data.estatisticas);
            } else {
                listaElement.innerHTML = '<div class="alert alert-info">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar ocorrências:', error);
            listaElement.innerHTML = '<div class="alert alert-error">Erro ao carregar ocorrências. Verifique se as APIs estão funcionando.</div>';
        });
}

// Função para coletar dados dos filtros
function coletarFiltros() {
    const filtros = {};
    
    // Tentar coletar de diferentes possíveis IDs
    const professoresSelect = document.getElementById('filtro-professor') || 
                            document.querySelector('select[name="professor_id"]') ||
                            document.querySelector('#filtros-ocorrencias select[name="professor_id"]');
    
    const unidadeSelect = document.getElementById('filtro-unidade') || 
                         document.querySelector('select[name="unidade_id"]') ||
                         document.querySelector('#filtros-ocorrencias select[name="unidade_id"]');
    
    const turmaSelect = document.getElementById('filtro-turma') || 
                       document.querySelector('select[name="turma_id"]') ||
                       document.querySelector('#filtros-ocorrencias select[name="turma_id"]');
    
    const statusSelect = document.getElementById('filtro-status-feedback') || 
                        document.querySelector('select[name="status_feedback"]') ||
                        document.querySelector('#filtros-ocorrencias select[name="status_feedback"]');
    
    const dataInicioInput = document.getElementById('filtro-data-inicio') || 
                           document.querySelector('input[name="data_inicio"]') ||
                           document.querySelector('#filtros-ocorrencias input[name="data_inicio"]');
    
    const dataFimInput = document.getElementById('filtro-data-fim') || 
                        document.querySelector('input[name="data_fim"]') ||
                        document.querySelector('#filtros-ocorrencias input[name="data_fim"]');
    
    // Coletar valores
    if (professoresSelect) filtros.professor_id = professoresSelect.value;
    if (unidadeSelect) filtros.unidade_id = unidadeSelect.value;
    if (turmaSelect) filtros.turma_id = turmaSelect.value;
    if (statusSelect) filtros.status_feedback = statusSelect.value;
    if (dataInicioInput) filtros.data_inicio = dataInicioInput.value;
    if (dataFimInput) filtros.data_fim = dataFimInput.value;
    
    return filtros;
}

// SUBSTITUA a função carregarDadosFiltros() no seu admin_ocorrencias.js por esta:

// SUBSTITUA a função carregarDadosFiltros() no seu admin_ocorrencias.js por esta versão corrigida:

function carregarDadosFiltros() {
    console.log('📦 Carregando dados para os filtros...');
    
    // Carregar professores (já funciona)
    fetch('api/listar_professor.php')
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                const select = document.getElementById('filtro-professor');
                if (select) {
                    select.innerHTML = '<option value="">Todos os professores</option>';
                    data.forEach(professor => {
                        const option = document.createElement('option');
                        option.value = professor.id;
                        option.textContent = professor.nome;
                        select.appendChild(option);
                    });
                    console.log('✅ Professores carregados:', data.length);
                } else {
                    console.error('❌ Select filtro-professor não encontrado!');
                }
            } else {
                console.error('❌ Resposta inválida da API de professores:', data);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar professores:', error);
        });
    
    // Carregar unidades - verificando diferentes formatos de resposta
    fetch('api/listar_unidades.php')
        .then(response => {
            console.log('🔍 Status da resposta unidades:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📋 Resposta completa da API unidades:', data);
            
            let unidades = [];
            
            // Verificar diferentes formatos de resposta
            if (data.status === 'sucesso' && Array.isArray(data.data)) {
                unidades = data.data;
                console.log('✅ Formato: data.data (sucesso)');
            } else if (data.success && Array.isArray(data.unidades)) {
                unidades = data.unidades;
                console.log('✅ Formato: data.unidades (success)');
            } else if (Array.isArray(data)) {
                unidades = data;
                console.log('✅ Formato: array direto');
            } else {
                console.error('❌ Formato de resposta não reconhecido para unidades:', data);
                return;
            }
            
            const select = document.getElementById('filtro-unidade');
            if (select) {
                select.innerHTML = '<option value="">Todas as unidades</option>';
                
                if (unidades.length > 0) {
                    // Verificar estrutura do primeiro item
                    const primeiroItem = unidades[0];
                    console.log('🔍 Estrutura do primeiro item de unidade:', primeiroItem);
                    
                    unidades.forEach((unidade, index) => {
                        const option = document.createElement('option');
                        // Tentar diferentes campos para ID e nome
                        option.value = unidade.id || unidade.unidade_id || unidade.codigo;
                        option.textContent = unidade.nome || unidade.nome_unidade || unidade.descricao;
                        
                        if (option.value && option.textContent) {
                            select.appendChild(option);
                        } else {
                            console.warn(`⚠️ Item ${index} de unidade inválido:`, unidade);
                        }
                    });
                    
                    console.log('✅ Unidades carregadas:', unidades.length);
                } else {
                    console.warn('⚠️ Array de unidades vazio');
                }
            } else {
                console.error('❌ Select filtro-unidade não encontrado!');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar unidades:', error);
        });
    
    // Carregar turmas - verificando diferentes formatos de resposta
    fetch('api/listar_turma.php')
        .then(response => {
            console.log('🔍 Status da resposta turmas:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📋 Resposta completa da API turmas:', data);
            
            let turmas = [];
            
            // Verificar diferentes formatos de resposta
            if (data.status === 'sucesso' && Array.isArray(data.data)) {
                turmas = data.data;
                console.log('✅ Formato: data.data (sucesso)');
            } else if (data.success && Array.isArray(data.turmas)) {
                turmas = data.turmas;
                console.log('✅ Formato: data.turmas (success)');
            } else if (Array.isArray(data)) {
                turmas = data;
                console.log('✅ Formato: array direto');
            } else {
                console.error('❌ Formato de resposta não reconhecido para turmas:', data);
                return;
            }
            
            const select = document.getElementById('filtro-turma');
            if (select) {
                select.innerHTML = '<option value="">Todas as turmas</option>';
                
                if (turmas.length > 0) {
                    // Verificar estrutura do primeiro item
                    const primeiroItem = turmas[0];
                    console.log('🔍 Estrutura do primeiro item de turma:', primeiroItem);
                    
                    turmas.forEach((turma, index) => {
                        const option = document.createElement('option');
                        // Tentar diferentes campos para ID e nome
                        option.value = turma.id || turma.turma_id || turma.codigo;
                        option.textContent = turma.nome_turma || turma.nome || turma.descricao;
                        
                        if (option.value && option.textContent) {
                            select.appendChild(option);
                        } else {
                            console.warn(`⚠️ Item ${index} de turma inválido:`, turma);
                        }
                    });
                    
                    console.log('✅ Turmas carregadas:', turmas.length);
                } else {
                    console.warn('⚠️ Array de turmas vazio');
                }
            } else {
                console.error('❌ Select filtro-turma não encontrado!');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar turmas:', error);
        });
}

// Versão melhorada da função preencherSelectFiltro
function preencherSelectFiltro(selectId, dados, valorField, textoField, opcaoPadrao) {
    console.log(`🔧 Tentando preencher select: ${selectId}`);
    
    // Tentar encontrar o select de várias formas
    let select = document.getElementById(selectId);
    
    if (!select) {
        // Tentar outras formas de encontrar
        const alternativeId = selectId.replace('filtro-', '');
        select = document.querySelector(`select[name="${alternativeId}_id"]`) ||
                document.querySelector(`select[name="${alternativeId}"]`) ||
                document.querySelector(`#${alternativeId}`) ||
                document.querySelector(`#filtros-ocorrencias select[name="${alternativeId}_id"]`);
    }
    
    if (!select) {
        console.error(`❌ Select não encontrado: ${selectId}`);
        console.log('🔍 Tentando listar todos os selects disponíveis:');
        const todosSelects = document.querySelectorAll('select');
        todosSelects.forEach((s, index) => {
            console.log(`  ${index + 1}. ID: "${s.id}", Name: "${s.name}", Classes: "${s.className}"`);
        });
        return;
    }
    
    console.log(`✅ Select encontrado: ${selectId}`);
    
    // Verificar se dados é um array válido
    if (!Array.isArray(dados)) {
        console.error(`❌ Dados não são array para ${selectId}:`, dados);
        return;
    }
    
    if (dados.length === 0) {
        console.warn(`⚠️ Array vazio para ${selectId}`);
        return;
    }
    
    // Limpar opções existentes
    select.innerHTML = `<option value="">${opcaoPadrao}</option>`;
    
    // Verificar se o primeiro item tem os campos necessários
    const primeiroItem = dados[0];
    if (!primeiroItem.hasOwnProperty(valorField) || !primeiroItem.hasOwnProperty(textoField)) {
        console.error(`❌ Campos obrigatórios não encontrados em ${selectId}:`, {
            procurado: { valorField, textoField },
            encontrado: Object.keys(primeiroItem),
            primeiroItem: primeiroItem
        });
        return;
    }
    
    // Adicionar novas opções
    let adicionados = 0;
    dados.forEach((item, index) => {
        if (item[valorField] && item[textoField]) {
            const option = document.createElement('option');
            option.value = item[valorField];
            option.textContent = item[textoField];
            select.appendChild(option);
            adicionados++;
        } else {
            console.warn(`⚠️ Item ${index} inválido em ${selectId}:`, item);
        }
    });
    
    console.log(`✅ Select ${selectId} preenchido com ${adicionados} de ${dados.length} itens`);
}
// Função para exibir as ocorrências
function exibirOcorrenciasAdmin(ocorrencias, estatisticas) {
    let html = '';
    
    // Estatísticas
    if (estatisticas) {
        html += '<div class="estatisticas-ocorrencias">';
        html += '  <div class="stats-row">';
        html += '    <div class="stat-item">';
        html += '      <span class="stat-number">' + estatisticas.total + '</span>';
        html += '      <span class="stat-label">Total</span>';
        html += '    </div>';
        html += '    <div class="stat-item">';
        html += '      <span class="stat-number">' + estatisticas.com_feedback + '</span>';
        html += '      <span class="stat-label">Com Feedback</span>';
        html += '    </div>';
        html += '    <div class="stat-item">';
        html += '      <span class="stat-number">' + estatisticas.sem_feedback + '</span>';
        html += '      <span class="stat-label">Pendentes</span>';
        html += '    </div>';
        html += '    <div class="stat-item">';
        html += '      <span class="stat-number">' + estatisticas.percentual_feedback + '%</span>';
        html += '      <span class="stat-label">% Respondidas</span>';
        html += '    </div>';
        html += '  </div>';
        html += '</div>';
    }
    
    if (ocorrencias.length === 0) {
        html += '<div class="alert alert-info">Nenhuma ocorrência encontrada.</div>';
    } else {
        html += '<div class="ocorrencias-admin-lista">';
        
        ocorrencias.forEach(function(ocorrencia) {
            const dataFormatada = formatarData(ocorrencia.data_ocorrencia);
            const statusFeedback = ocorrencia.status_feedback === 'com_feedback' ? 'com-feedback' : 'sem-feedback';
            const badgeFeedback = ocorrencia.status_feedback === 'com_feedback' ? 
                '<span class="badge badge-success">Com Feedback</span>' : 
                '<span class="badge badge-warning">Pendente</span>';
            
            html += '<div class="ocorrencia-admin-item ' + statusFeedback + '">';
            html += '  <div class="ocorrencia-admin-header">';
            html += '    <div class="ocorrencia-info">';
            html += '      <h4>Ocorrência #' + ocorrencia.id + ' ' + badgeFeedback + '</h4>';
            html += '      <div class="ocorrencia-meta">';
            html += '        <span><i class="fas fa-calendar"></i> ' + dataFormatada + '</span>';
            html += '        <span><i class="fas fa-user"></i> ' + ocorrencia.nome_aluno + ' (' + ocorrencia.numero_matricula + ')</span>';
            html += '        <span><i class="fas fa-chalkboard"></i> ' + ocorrencia.nome_turma + '</span>';
            html += '        <span><i class="fas fa-building"></i> ' + ocorrencia.nome_unidade + '</span>';
            html += '        <span><i class="fas fa-user-tie"></i> Prof. ' + ocorrencia.nome_professor + '</span>';
            html += '      </div>';
            html += '    </div>';
            html += '  </div>';
            
            html += '  <div class="ocorrencia-admin-preview">';
            html += '    <p><strong>Descrição:</strong> ' + truncarTexto(ocorrencia.descricao, 150) + '</p>';
            if (ocorrencia.feedback_admin) {
                html += '    <div class="feedback-preview">';
                html += '      <p><strong>Feedback:</strong> ' + truncarTexto(ocorrencia.feedback_admin, 100) + '</p>';
                html += '      <small>Por ' + ocorrencia.feedback_admin_nome + ' em ' + formatarDataHora(ocorrencia.feedback_data) + '</small>';
                html += '    </div>';
            }
            html += '  </div>';
            
            html += '  <div class="ocorrencia-admin-actions">';
            html += '    <button class="btn btn-small" onclick="verDetalhesOcorrenciaAdmin(' + ocorrencia.id + ')">';
            html += '      <i class="fas fa-eye"></i> Ver Detalhes';
            html += '    </button>';
            
            if (ocorrencia.status_feedback === 'sem_feedback') {
                html += '    <button class="btn btn-small btn-primary" onclick="abrirModalFeedback(' + ocorrencia.id + ', \'adicionar\')">';
                html += '      <i class="fas fa-plus"></i> Adicionar Feedback';
                html += '    </button>';
            } else {
                html += '    <button class="btn btn-small btn-secondary" onclick="abrirModalFeedback(' + ocorrencia.id + ', \'editar\')">';
                html += '      <i class="fas fa-edit"></i> Editar Feedback';
                html += '    </button>';
                html += '    <button class="btn btn-small btn-danger" onclick="removerFeedback(' + ocorrencia.id + ')">';
                html += '      <i class="fas fa-trash"></i> Remover';
                html += '    </button>';
            }
            
            html += '  </div>';
            html += '</div>';
        });
        
        html += '</div>';
    }
    
    document.getElementById('lista-ocorrencias-admin').innerHTML = html;
}

// Função para aplicar filtros
function aplicarFiltros() {
    console.log('🔍 Aplicando filtros...');
    carregarOcorrenciasAdmin();
}

// Função para limpar filtros
function limparFiltros() {
    console.log('🧹 Limpando filtros...');
    
    // Resetar o formulário se existir
    const formFiltros = document.getElementById('filtros-ocorrencias');
    if (formFiltros) {
        formFiltros.reset();
    } else {
        // Limpar campos individualmente
        const campos = [
            'filtro-professor', 'filtro-unidade', 'filtro-turma', 
            'filtro-status-feedback', 'filtro-data-inicio', 'filtro-data-fim'
        ];
        
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = '';
            }
        });
    }
    
    // Recarregar sem filtros
    carregarOcorrenciasAdmin();
}

// Função para ver detalhes da ocorrência
function verDetalhesOcorrenciaAdmin(ocorrenciaId) {
    document.getElementById('detalhes-ocorrencia-admin').innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Carregando detalhes...</p></div>';
    
    fetch('api/buscar_ocorrencia_admin.php?id=' + ocorrenciaId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                exibirDetalhesOcorrenciaAdmin(data.ocorrencia);
                document.getElementById('modalOcorrenciasAdmin').style.display = 'none';
                document.getElementById('detalhesOcorrenciaAdminModal').style.display = 'block';
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar detalhes da ocorrência.');
        });
}

// Função para exibir detalhes completos
function exibirDetalhesOcorrenciaAdmin(ocorrencia) {
    const dataFormatada = formatarData(ocorrencia.data_ocorrencia);
    const dataCriacaoFormatada = formatarDataHora(ocorrencia.data_criacao);
    
    let html = '<div class="detalhes-ocorrencia-admin">';
    html += '  <div class="detalhes-header-admin">';
    html += '    <h3>Ocorrência #' + ocorrencia.id + '</h3>';
    if (ocorrencia.feedback_admin) {
        html += '    <span class="badge badge-success">Com Feedback</span>';
    } else {
        html += '    <span class="badge badge-warning">Pendente Feedback</span>';
    }
    html += '  </div>';
    
    // Informações da ocorrência
    html += '  <div class="detalhes-section-admin">';
    html += '    <h4>Informações da Ocorrência</h4>';
    html += '    <div class="info-grid-admin">';
    html += '      <div><strong>Data:</strong> ' + dataFormatada + '</div>';
    html += '      <div><strong>Aluno:</strong> ' + ocorrencia.nome_aluno + '</div>';
    html += '      <div><strong>Matrícula:</strong> ' + ocorrencia.numero_matricula + '</div>';
    html += '      <div><strong>Turma:</strong> ' + ocorrencia.nome_turma + '</div>';
    html += '      <div><strong>Unidade:</strong> ' + ocorrencia.nome_unidade + '</div>';
    html += '      <div><strong>Professor:</strong> ' + ocorrencia.nome_professor + '</div>';
    html += '      <div><strong>Registrado em:</strong> ' + dataCriacaoFormatada + '</div>';
    html += '    </div>';
    html += '  </div>';
    
    // Descrição
    html += '  <div class="detalhes-section-admin">';
    html += '    <h4>Descrição da Ocorrência</h4>';
    html += '    <p>' + ocorrencia.descricao.replace(/\n/g, '<br>') + '</p>';
    html += '  </div>';
    
    // Ações tomadas
    if (ocorrencia.acoes_tomadas) {
        html += '  <div class="detalhes-section-admin">';
        html += '    <h4>Ações Tomadas</h4>';
        html += '    <p>' + ocorrencia.acoes_tomadas.replace(/\n/g, '<br>') + '</p>';
        html += '  </div>';
    }
    
    // Reunião com responsáveis
    html += '  <div class="detalhes-section-admin">';
    html += '    <h4>Reunião com Responsáveis</h4>';
    if (ocorrencia.houve_reuniao_responsaveis == 1) {
        html += '    <p><span class="status-success">✓ Sim, houve reunião</span></p>';
        if (ocorrencia.detalhes_reuniao) {
            html += '    <div class="reuniao-detalhes">';
            html += '      <strong>Detalhes:</strong><br>';
            html += '      ' + ocorrencia.detalhes_reuniao.replace(/\n/g, '<br>');
            html += '    </div>';
        }
    } else {
        html += '    <p><span class="status-neutral">✗ Não houve reunião</span></p>';
    }
    html += '  </div>';
    
    // Feedback da administração
    html += '  <div class="detalhes-section-admin">';
    html += '    <h4><i class="fas fa-comment-dots"></i> Feedback da Administração</h4>';
    if (ocorrencia.feedback_admin) {
        html += '    <div class="feedback-admin-display">';
        html += '      <p>' + ocorrencia.feedback_admin.replace(/\n/g, '<br>') + '</p>';
        html += '      <div class="feedback-meta-admin">';
        html += '        <small>Por ' + ocorrencia.feedback_admin_nome + ' em ' + formatarDataHora(ocorrencia.feedback_data) + '</small>';
        html += '      </div>';
        html += '    </div>';
    } else {
        html += '    <div class="no-feedback-admin">';
        html += '      <p><i class="fas fa-info-circle"></i> Nenhum feedback adicionado ainda.</p>';
        html += '    </div>';
    }
    html += '  </div>';
    
    // Ações
    html += '  <div class="detalhes-actions-admin">';
    if (ocorrencia.feedback_admin) {
        html += '    <button class="btn btn-secondary" onclick="abrirModalFeedback(' + ocorrencia.id + ', \'editar\')">';
        html += '      <i class="fas fa-edit"></i> Editar Feedback';
        html += '    </button>';
        html += '    <button class="btn btn-danger" onclick="removerFeedback(' + ocorrencia.id + ')">';
        html += '      <i class="fas fa-trash"></i> Remover Feedback';
        html += '    </button>';
    } else {
        html += '    <button class="btn btn-primary" onclick="abrirModalFeedback(' + ocorrencia.id + ', \'adicionar\')">';
        html += '      <i class="fas fa-plus"></i> Adicionar Feedback';
        html += '    </button>';
    }
    html += '    <button class="btn btn-outline" onclick="voltarParaLista()">';
    html += '      <i class="fas fa-arrow-left"></i> Voltar à Lista';
    html += '    </button>';
    html += '  </div>';
    
    html += '</div>';
    
    document.getElementById('detalhes-ocorrencia-admin').innerHTML = html;
}

// Função para abrir modal de feedback
function abrirModalFeedback(ocorrenciaId, acao) {
    document.getElementById('feedback-ocorrencia-id').value = ocorrenciaId;
    document.getElementById('feedback-action').value = acao === 'editar' ? 'editar_feedback' : 'adicionar_feedback';
    document.getElementById('modal-feedback-title').textContent = acao === 'editar' ? 'Editar Feedback' : 'Adicionar Feedback';
    
    if (acao === 'editar') {
        // Buscar feedback atual
        carregarFeedbackAtual(ocorrenciaId);
    } else {
        document.getElementById('feedback-texto').value = '';
    }
    
    document.getElementById('feedbackModal').style.display = 'block';
}

// Função para carregar feedback atual para edição
function carregarFeedbackAtual(ocorrenciaId) {
    fetch('api/buscar_ocorrencia_admin.php?id=' + ocorrenciaId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ocorrencia.feedback_admin) {
                document.getElementById('feedback-texto').value = data.ocorrencia.feedback_admin;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar feedback:', error);
        });
}

// Função para salvar feedback
function salvarFeedback() {
    const form = document.getElementById('form-feedback');
    const formData = new FormData(form);
    
    fetch('api/gerenciar_feedback_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Feedback salvo com sucesso!');
            document.getElementById('feedbackModal').style.display = 'none';
            
            // Recarregar dados
            if (document.getElementById('detalhesOcorrenciaAdminModal').style.display === 'block') {
                const ocorrenciaId = document.getElementById('feedback-ocorrencia-id').value;
                verDetalhesOcorrenciaAdmin(ocorrenciaId);
            } else {
                carregarOcorrenciasAdmin();
            }
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar feedback. Tente novamente.');
    });
}

// Função para remover feedback
function removerFeedback(ocorrenciaId) {
    if (confirm('Tem certeza que deseja remover o feedback desta ocorrência?')) {
        const formData = new FormData();
        formData.append('action', 'remover_feedback');
        formData.append('ocorrencia_id', ocorrenciaId);
        
        fetch('api/gerenciar_feedback_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Feedback removido com sucesso!');
                
                // Recarregar dados
                if (document.getElementById('detalhesOcorrenciaAdminModal').style.display === 'block') {
                    verDetalhesOcorrenciaAdmin(ocorrenciaId);
                } else {
                    carregarOcorrenciasAdmin();
                }
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao remover feedback. Tente novamente.');
        });
    }
}

// Função para voltar à lista
function voltarParaLista() {
    document.getElementById('detalhesOcorrenciaAdminModal').style.display = 'none';
    document.getElementById('modalOcorrenciasAdmin').style.display = 'block';
}

// Funções utilitárias
function formatarData(data) {
    const d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

function formatarDataHora(dataHora) {
    const d = new Date(dataHora);
    return d.toLocaleDateString('pt-BR') + ' às ' + d.toLocaleTimeString('pt-BR');
}

function truncarTexto(texto, limite) {
    if (texto.length <= limite) return texto;
    return texto.substr(0, limite) + '...';
}

// Eventos para fechar modais e formulários
document.addEventListener('DOMContentLoaded', function() {
    // Fechar modais
    const closeOcorrenciasBtn = document.getElementById('closeOcorrenciasAdminModal');
    if (closeOcorrenciasBtn) {
        closeOcorrenciasBtn.addEventListener('click', function() {
            document.getElementById('modalOcorrenciasAdmin').style.display = 'none';
        });
    }

    const closeDetalhesBtn = document.getElementById('closeDetalhesOcorrenciaAdminModal');
    if (closeDetalhesBtn) {
        closeDetalhesBtn.addEventListener('click', function() {
            document.getElementById('detalhesOcorrenciaAdminModal').style.display = 'none';
        });
    }

    const closeFeedbackBtn = document.getElementById('closeFeedbackModal');
    if (closeFeedbackBtn) {
        closeFeedbackBtn.addEventListener('click', function() {
            document.getElementById('feedbackModal').style.display = 'none';
        });
    }

    // Submit do formulário de feedback
    const formFeedback = document.getElementById('form-feedback');
    if (formFeedback) {
        formFeedback.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarFeedback();
        });
    }

    // Botão cancelar feedback
    const btnCancelarFeedback = document.getElementById('btn-cancelar-feedback');
    if (btnCancelarFeedback) {
        btnCancelarFeedback.addEventListener('click', function() {
            document.getElementById('feedbackModal').style.display = 'none';
        });
    }

    // Filtros
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
    if (btnAplicarFiltros) {
        btnAplicarFiltros.addEventListener('click', aplicarFiltros);
        console.log('✅ Event listener adicionado ao botão aplicar filtros');
    } else {
        console.log('⚠️ Botão aplicar filtros não encontrado');
    }

    const btnLimparFiltros = document.getElementById('btn-limpar-filtros');
    if (btnLimparFiltros) {
        btnLimparFiltros.addEventListener('click', limparFiltros);
        console.log('✅ Event listener adicionado ao botão limpar filtros');
    } else {
        console.log('⚠️ Botão limpar filtros não encontrado');
    }
    
    // Debug: Listar todos os elementos de filtro encontrados
    setTimeout(() => {
        console.log('🔍 Debug - Elementos de filtro encontrados:');
        const elementos = [
            'filtro-professor', 'filtro-unidade', 'filtro-turma', 
            'filtro-status-feedback', 'filtro-data-inicio', 'filtro-data-fim',
            'btn-aplicar-filtros', 'btn-limpar-filtros'
        ];
        
        elementos.forEach(id => {
            const elemento = document.getElementById(id);
            console.log(id + ':', elemento ? '✅ Encontrado' : '❌ Não encontrado');
        });
    }, 1000);
});