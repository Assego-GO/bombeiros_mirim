// ===============================================
// SISTEMA DE COMUNICADOS - BANCO DE DADOS
// ===============================================

// Variáveis globais
let comunicadoEditando = null;
let todosComunicados = [];

// Inicializar eventos quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema de comunicados carregado');
    
    // Evento para abrir modal de comunicados
    const btnComunicado = document.getElementById('comunicado-btn');
    if (btnComunicado) {
        btnComunicado.addEventListener('click', function() {
            console.log('📢 Botão comunicado clicado');
            abrirModalComunicados();
        });
    } else {
        console.error('❌ Botão comunicado não encontrado');
    }
    
    // Event listeners para fechar modais
    setupEventListeners();
});

function setupEventListeners() {
    // Fechar modal principal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('fechar-modal-comunicado')) {
            fecharModalComunicados();
        }
        
        if (e.target.classList.contains('fechar-modal-visualizar')) {
            fecharModalVisualizacao();
        }
        
        // Trocar abas
        if (e.target.classList.contains('tab-comunicado')) {
            const aba = e.target.dataset.tab;
            mostrarAba(aba);
        }
        
        // Botões de ação
        if (e.target.classList.contains('btn-editar-comunicado')) {
            const id = parseInt(e.target.dataset.id);
            editarComunicado(id);
        }
        
        if (e.target.classList.contains('btn-excluir-comunicado')) {
            const id = parseInt(e.target.dataset.id);
            excluirComunicado(id);
        }
        
        if (e.target.classList.contains('btn-visualizar-comunicado')) {
            const id = parseInt(e.target.dataset.id);
            visualizarComunicado(id);
        }
    });
    
    // Formulário de comunicado
    const form = document.getElementById('form-comunicado');
    if (form) {
        form.addEventListener('submit', salvarComunicado);
    }
    
    // Botão cancelar
    const btnCancelar = document.getElementById('btn-cancelar-comunicado');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', cancelarEdicao);
    }
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            fecharModalComunicados();
            fecharModalVisualizacao();
        }
    });
}

// ===============================================
// FUNÇÕES PRINCIPAIS
// ===============================================

function abrirModalComunicados() {
    console.log('🔄 Abrindo modal de comunicados');
    const modal = document.getElementById('modal-comunicados');
    if (modal) {
        modal.style.display = 'flex';
        mostrarAba('criar');
        limparFormulario();
        console.log('✅ Modal aberto com sucesso');
    } else {
        console.error('❌ Modal não encontrado');
    }
}

function fecharModalComunicados() {
    const modal = document.getElementById('modal-comunicados');
    if (modal) {
        modal.style.display = 'none';
        limparFormulario();
        comunicadoEditando = null;
        console.log('✅ Modal fechado');
    }
}

function mostrarAba(aba) {
    console.log('🔄 Mostrando aba:', aba);
    
    // Remover classe ativo de todas as abas
    document.querySelectorAll('.tab-comunicado').forEach(tab => {
        tab.classList.remove('ativo');
    });
    
    // Ocultar todo o conteúdo
    document.querySelectorAll('.tab-content-comunicado').forEach(content => {
        content.style.display = 'none';
    });
    
    // Ativar aba selecionada
    const tabAtiva = document.querySelector(`[data-tab="${aba}"]`);
    const contentAtivo = document.getElementById(`tab-${aba}`);
    
    if (tabAtiva && contentAtivo) {
        tabAtiva.classList.add('ativo');
        contentAtivo.style.display = 'block';
        
        if (aba === 'listar') {
            carregarComunicados();
        }
        console.log('✅ Aba ativa:', aba);
    } else {
        console.error('❌ Aba não encontrada:', aba);
    }
}

async function salvarComunicado(event) {
    event.preventDefault();
    console.log('💾 Salvando comunicado');
    
    const formData = new FormData(event.target);
    const dados = {
        titulo: formData.get('titulo'),
        conteudo: formData.get('conteudo'),
        status: 'ativo'
    };
    
    // Se estiver editando, adicionar ID
    if (comunicadoEditando) {
        dados.id = comunicadoEditando;
    }
    
    // Validar dados
    if (!dados.titulo.trim() || !dados.conteudo.trim()) {
        showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
    }
    
    try {
        mostrarLoading(true);
        
        const url = comunicadoEditando ? 'api/comunicados.php?action=editar' : 'api/comunicados.php?action=criar';
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dados)
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            const mensagem = comunicadoEditando ? 'Comunicado atualizado com sucesso!' : 'Comunicado criado com sucesso!';
            showNotification(mensagem, 'success');
            limparFormulario();
            cancelarEdicao();
            mostrarAba('listar');
        } else {
            showNotification('Erro: ' + (resultado.message || 'Erro desconhecido'), 'error');
        }
        
    } catch (error) {
        console.error('Erro ao salvar comunicado:', error);
        showNotification('Erro ao salvar comunicado. Tente novamente.', 'error');
    } finally {
        mostrarLoading(false);
    }
}

async function carregarComunicados() {
    try {
        mostrarLoading(true);
        console.log('📦 Carregando comunicados do banco...');
        
        const response = await fetch('api/comunicados.php?action=listar');
        const resultado = await response.json();
        
        if (resultado.success) {
            todosComunicados = resultado.data || [];
            renderizarComunicados();
            atualizarEstatisticas();
            console.log('✅ Comunicados carregados:', todosComunicados.length);
        } else {
            console.error('Erro ao carregar comunicados:', resultado.message);
            showNotification('Erro ao carregar comunicados: ' + resultado.message, 'error');
            renderizarComunicados([]);
        }
        
    } catch (error) {
        console.error('Erro ao carregar comunicados:', error);
        showNotification('Erro ao carregar comunicados. Verifique sua conexão.', 'error');
        renderizarComunicados([]);
    } finally {
        mostrarLoading(false);
    }
}

function renderizarComunicados() {
    const container = document.getElementById('lista-comunicados');
    
    if (!todosComunicados || todosComunicados.length === 0) {
        container.innerHTML = `
            <div class="sem-comunicados">
                <i class="fas fa-bullhorn"></i>
                <p>Nenhum comunicado encontrado</p>
                <button class="btn btn-primary" onclick="mostrarAba('criar')">
                    <i class="fas fa-plus"></i> Criar Primeiro Comunicado
                </button>
            </div>
        `;
        return;
    }
    
    const html = todosComunicados
        .sort((a, b) => new Date(b.data_criacao) - new Date(a.data_criacao))
        .map(comunicado => `
            <div class="comunicado-item" data-id="${comunicado.id}">
                <div class="comunicado-header">
                    <h3>${escapeHtml(comunicado.titulo)}</h3>
                    <div class="comunicado-acoes">
                        <button class="btn btn-sm btn-outline btn-visualizar-comunicado" data-id="${comunicado.id}" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary btn-editar-comunicado" data-id="${comunicado.id}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-excluir-comunicado" data-id="${comunicado.id}" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="comunicado-preview">
                    ${truncarTexto(comunicado.conteudo, 100)}
                </div>
                <div class="comunicado-footer">
                    <span class="autor">Por: ${escapeHtml(comunicado.autor_nome)}</span>
                    <span class="data">${formatarData(comunicado.data_criacao)}</span>
                    ${comunicado.data_atualizacao !== comunicado.data_criacao ? 
                        `<span class="editado">Editado: ${formatarData(comunicado.data_atualizacao)}</span>` : ''}
                </div>
            </div>
        `).join('');
    
    container.innerHTML = html;
}

function editarComunicado(id) {
    console.log('✏️ Editando comunicado:', id);
    const comunicado = todosComunicados.find(c => c.id == id);
    if (!comunicado) {
        showNotification('Comunicado não encontrado.', 'error');
        return;
    }
    
    comunicadoEditando = id;
    
    // Preencher formulário
    document.getElementById('titulo-comunicado').value = comunicado.titulo;
    document.getElementById('conteudo-comunicado').value = comunicado.conteudo;
    
    // Trocar para aba de criar/editar
    mostrarAba('criar');
    
    // Mudar texto do botão
    const btnSalvar = document.querySelector('#form-comunicado button[type="submit"]');
    if (btnSalvar) {
        btnSalvar.innerHTML = '<i class="fas fa-save"></i> Atualizar Comunicado';
    }
    
    // Mostrar botão cancelar
    const btnCancelar = document.getElementById('btn-cancelar-comunicado');
    if (btnCancelar) {
        btnCancelar.style.display = 'inline-block';
    }
}

function visualizarComunicado(id) {
    console.log('👁️ Visualizando comunicado:', id);
    const comunicado = todosComunicados.find(c => c.id == id);
    if (!comunicado) {
        showNotification('Comunicado não encontrado.', 'error');
        return;
    }
    
    const modal = document.getElementById('modal-visualizar-comunicado');
    if (modal) {
        document.getElementById('visualizar-titulo').textContent = comunicado.titulo;
        document.getElementById('visualizar-conteudo').innerHTML = comunicado.conteudo.replace(/\n/g, '<br>');
        document.getElementById('visualizar-autor').textContent = comunicado.autor_nome;
        document.getElementById('visualizar-data').textContent = formatarData(comunicado.data_criacao);
        
        modal.style.display = 'flex';
    }
}

function fecharModalVisualizacao() {
    const modal = document.getElementById('modal-visualizar-comunicado');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function excluirComunicado(id) {
    console.log('🗑️ Excluindo comunicado:', id);
    if (!confirm('Tem certeza que deseja excluir este comunicado?')) {
        return;
    }
    
    try {
        mostrarLoading(true);
        
        const response = await fetch('api/comunicados.php?action=excluir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            showNotification('Comunicado excluído com sucesso!', 'success');
            carregarComunicados(); // Recarregar lista
        } else {
            showNotification('Erro: ' + (resultado.message || 'Erro desconhecido'), 'error');
        }
        
    } catch (error) {
        console.error('Erro ao excluir comunicado:', error);
        showNotification('Erro ao excluir comunicado. Tente novamente.', 'error');
    } finally {
        mostrarLoading(false);
    }
}

function cancelarEdicao() {
    comunicadoEditando = null;
    limparFormulario();
    
    // Restaurar texto do botão
    const btnSalvar = document.querySelector('#form-comunicado button[type="submit"]');
    if (btnSalvar) {
        btnSalvar.innerHTML = '<i class="fas fa-save"></i> Criar Comunicado';
    }
    
    // Ocultar botão cancelar
    const btnCancelar = document.getElementById('btn-cancelar-comunicado');
    if (btnCancelar) {
        btnCancelar.style.display = 'none';
    }
}

function limparFormulario() {
    const form = document.getElementById('form-comunicado');
    if (form) {
        form.reset();
    }
}

function atualizarEstatisticas() {
    const totalElement = document.getElementById('total-comunicados');
    if (totalElement) {
        const total = todosComunicados.length;
        totalElement.textContent = `${total} comunicado${total !== 1 ? 's' : ''}`;
    }
}

// ===============================================
// FUNÇÕES AUXILIARES
// ===============================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncarTexto(texto, limite) {
    if (!texto) return '';
    if (texto.length <= limite) return texto;
    return texto.substring(0, limite) + '...';
}

function formatarData(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'Data inválida';
    }
}

function mostrarLoading(show) {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = show ? 'flex' : 'none';
    }
}

function showNotification(message, type = 'info') {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Adicionar ao body
    document.body.appendChild(notification);
    
    // Mostrar notificação
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(event) {
    const modal = document.getElementById('modal-comunicados');
    if (event.target === modal) {
        fecharModalComunicados();
    }
    
    const modalVisualizacao = document.getElementById('modal-visualizar-comunicado');
    if (event.target === modalVisualizacao) {
        fecharModalVisualizacao();
    }
});

console.log('🎯 Sistema de comunicados inicializado com banco de dados');