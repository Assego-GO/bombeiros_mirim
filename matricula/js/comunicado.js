// ===============================================
// SISTEMA DE COMUNICADOS - VERSÃO COMPLETA COM UTF-8 PARA BRASIL
// ===============================================

// Classe principal do sistema de comunicados
class SistemaComunicados {
    constructor() {
        this.comunicadoEditando = null;
        this.todosComunicados = [];
        
        // 🇧🇷 CONFIGURAÇÕES PARA BRASIL
        this.configurarLocaleBrasil();
        this.init();
    }

    // 🇧🇷 Configuração completa para Brasil
    configurarLocaleBrasil() {
        // Configurar timezone brasileiro
        this.timezone = 'America/Sao_Paulo';
        
        // Configurar formatadores brasileiros
        this.formatadorData = new Intl.DateTimeFormat('pt-BR', {
            timeZone: this.timezone,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        this.formatadorHora = new Intl.DateTimeFormat('pt-BR', {
            timeZone: this.timezone,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        this.formatadorDataHora = new Intl.DateTimeFormat('pt-BR', {
            timeZone: this.timezone,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        this.formatadorNumero = new Intl.NumberFormat('pt-BR');
        
        console.log('🇧🇷 Configurações brasileiras aplicadas no sistema de comunicados');
    }

    init() {
        console.log('🚀 Sistema de comunicados carregado com UTF-8');
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
            });
        } else {
            this.setupEventListeners();
        }
    }

    setupEventListeners() {
        // Evento para abrir modal de comunicados
        const btnComunicado = document.getElementById('comunicado-btn');
        if (btnComunicado) {
            btnComunicado.addEventListener('click', () => {
                console.log('📢 Botão comunicado clicado');
                this.abrirModalComunicados();
            });
        } else {
            console.error('❌ Botão comunicado não encontrado');
        }
        
        // Event listeners para fechar modais e outras ações
        this.setupModalEvents();
    }

    setupModalEvents() {
        // Fechar modal principal
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('fechar-modal-comunicado')) {
                this.fecharModalComunicados();
            }
            
            if (e.target.classList.contains('fechar-modal-visualizar')) {
                this.fecharModalVisualizacao();
            }
            
            // Trocar abas
            if (e.target.classList.contains('tab-comunicado')) {
                const aba = e.target.dataset.tab;
                this.mostrarAba(aba);
            }
            
            // Botões de ação
            if (e.target.classList.contains('btn-editar-comunicado')) {
                const id = parseInt(e.target.dataset.id);
                this.editarComunicado(id);
            }
            
            if (e.target.classList.contains('btn-excluir-comunicado')) {
                const id = parseInt(e.target.dataset.id);
                this.excluirComunicado(id);
            }
            
            if (e.target.classList.contains('btn-visualizar-comunicado')) {
                const id = parseInt(e.target.dataset.id);
                this.visualizarComunicado(id);
            }
        });
        
        // Formulário de comunicado
        const form = document.getElementById('form-comunicado');
        if (form) {
            form.addEventListener('submit', (e) => this.salvarComunicado(e));
        }
        
        // Botão cancelar
        const btnCancelar = document.getElementById('btn-cancelar-comunicado');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => this.cancelarEdicao());
        }
        
        // Fechar modal com ESC
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.fecharModalComunicados();
                this.fecharModalVisualizacao();
            }
        });

        // Fechar modal ao clicar fora
        document.addEventListener('click', (event) => {
            const modal = document.getElementById('modal-comunicados');
            if (event.target === modal) {
                this.fecharModalComunicados();
            }
            
            const modalVisualizacao = document.getElementById('modal-visualizar-comunicado');
            if (event.target === modalVisualizacao) {
                this.fecharModalVisualizacao();
            }
        });
    }

    // 🌐 FUNÇÃO DE REQUISIÇÃO COM UTF-8
    async fetchComUTF8(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json; charset=UTF-8',
                'Accept': 'application/json; charset=UTF-8',
                'Accept-Charset': 'UTF-8',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, defaultOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Garantir que a resposta seja interpretada como UTF-8
            const text = await response.text();
            
            try {
                return JSON.parse(text);
            } catch (parseError) {
                console.error('❌ Erro ao fazer parse JSON:', parseError);
                console.log('📄 Resposta recebida:', text);
                throw new Error('Resposta não é um JSON válido');
            }
        } catch (error) {
            console.error('❌ Erro na requisição:', error);
            throw error;
        }
    }

    // 🛡️ FUNÇÃO PARA SANITIZAR STRINGS UTF-8
    sanitizarTexto(texto) {
        if (!texto) return '';
        
        // Converter para string e normalizar caracteres Unicode
        const textoLimpo = String(texto).normalize('NFC');
        
        // Escapar HTML para prevenir XSS mas manter caracteres especiais
        const div = document.createElement('div');
        div.textContent = textoLimpo;
        return div.innerHTML;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = this.sanitizarTexto(text);
        return div.innerHTML;
    }

    // 🇧🇷 FUNÇÕES DE FORMATAÇÃO BRASILEIRAS
    formatarData(dateString) {
        if (!dateString) return '-';
        
        try {
            const date = new Date(dateString);
            return this.formatadorDataHora.format(date);
        } catch (error) {
            console.warn('Erro ao formatar data:', dateString, error);
            return new Date(dateString).toLocaleString('pt-BR', {
                timeZone: this.timezone,
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    formatarDataSimples(dateString) {
        if (!dateString) return '-';
        
        try {
            const date = new Date(dateString);
            return this.formatadorData.format(date);
        } catch (error) {
            console.warn('Erro ao formatar data simples:', dateString, error);
            return new Date(dateString).toLocaleDateString('pt-BR', {
                timeZone: this.timezone
            });
        }
    }

    formatarNumero(numero) {
        try {
            return this.formatadorNumero.format(numero);
        } catch (error) {
            return String(numero);
        }
    }

    truncarTexto(texto, limite) {
        if (!texto) return '';
        const textoSanitizado = this.sanitizarTexto(texto);
        if (textoSanitizado.length <= limite) return textoSanitizado;
        return textoSanitizado.substring(0, limite) + '...';
    }

    // ===============================================
    // FUNÇÕES PRINCIPAIS
    // ===============================================

    abrirModalComunicados() {
        console.log('🔄 Abrindo modal de comunicados');
        const modal = document.getElementById('modal-comunicados');
        if (modal) {
            modal.style.display = 'flex';
            this.mostrarAba('criar');
            this.limparFormulario();
            console.log('✅ Modal aberto com sucesso');
        } else {
            console.error('❌ Modal não encontrado');
        }
    }

    fecharModalComunicados() {
        const modal = document.getElementById('modal-comunicados');
        if (modal) {
            modal.style.display = 'none';
            this.limparFormulario();
            this.comunicadoEditando = null;
            console.log('✅ Modal fechado');
        }
    }

    mostrarAba(aba) {
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
                this.carregarComunicados();
            }
            console.log('✅ Aba ativa:', aba);
        } else {
            console.error('❌ Aba não encontrada:', aba);
        }
    }

    async salvarComunicado(event) {
        event.preventDefault();
        console.log('💾 Salvando comunicado');
        
        const formData = new FormData(event.target);
        const dados = {
            titulo: this.sanitizarTexto(formData.get('titulo')),
            conteudo: this.sanitizarTexto(formData.get('conteudo')),
            status: 'ativo'
        };
        
        // Se estiver editando, adicionar ID
        if (this.comunicadoEditando) {
            dados.id = this.comunicadoEditando;
        }
        
        // Validar dados
        if (!dados.titulo.trim() || !dados.conteudo.trim()) {
            this.showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
            return;
        }
        
        try {
            this.mostrarLoading(true);
            
            const url = this.comunicadoEditando ? 'api/comunicados.php?action=editar' : 'api/comunicados.php?action=criar';
            const resultado = await this.fetchComUTF8(url, {
                method: 'POST',
                body: JSON.stringify(dados)
            });
            
            if (resultado.success) {
                const mensagem = this.comunicadoEditando ? 
                    '✅ Comunicado atualizado com sucesso!' : 
                    '✅ Comunicado criado com sucesso!';
                this.showNotification(mensagem, 'success');
                this.limparFormulario();
                this.cancelarEdicao();
                this.mostrarAba('listar');
            } else {
                this.showNotification('❌ Erro: ' + (resultado.message || 'Erro desconhecido'), 'error');
            }
            
        } catch (error) {
            console.error('Erro ao salvar comunicado:', error);
            this.showNotification('❌ Erro ao salvar comunicado. Tente novamente.', 'error');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async carregarComunicados() {
        try {
            this.mostrarLoading(true);
            console.log('📦 Carregando comunicados do banco...');
            
            const resultado = await this.fetchComUTF8('api/comunicados.php?action=listar');
            
            if (resultado.success) {
                this.todosComunicados = resultado.data || [];
                this.renderizarComunicados();
                this.atualizarEstatisticas();
                console.log('✅ Comunicados carregados:', this.todosComunicados.length);
            } else {
                console.error('Erro ao carregar comunicados:', resultado.message);
                this.showNotification('❌ Erro ao carregar comunicados: ' + resultado.message, 'error');
                this.renderizarComunicados([]);
            }
            
        } catch (error) {
            console.error('Erro ao carregar comunicados:', error);
            this.showNotification('❌ Erro ao carregar comunicados. Verifique sua conexão.', 'error');
            this.renderizarComunicados([]);
        } finally {
            this.mostrarLoading(false);
        }
    }

    renderizarComunicados() {
        const container = document.getElementById('lista-comunicados');
        
        if (!this.todosComunicados || this.todosComunicados.length === 0) {
            container.innerHTML = `
                <div class="sem-comunicados">
                    <i class="fas fa-bullhorn"></i>
                    <p>Nenhum comunicado encontrado</p>
                    <button class="btn btn-primary" onclick="sistemaComunicados.mostrarAba('criar')">
                        <i class="fas fa-plus"></i> Criar Primeiro Comunicado
                    </button>
                </div>
            `;
            return;
        }
        
        const html = this.todosComunicados
            .sort((a, b) => new Date(b.data_criacao) - new Date(a.data_criacao))
            .map(comunicado => `
                <div class="comunicado-item" data-id="${comunicado.id}">
                    <div class="comunicado-header">
                        <h3>${this.escapeHtml(comunicado.titulo)}</h3>
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
                        ${this.truncarTexto(comunicado.conteudo, 100)}
                    </div>
                    <div class="comunicado-footer">
                        <span class="autor">Por: ${this.escapeHtml(comunicado.autor_nome)}</span>
                        <span class="data">${this.formatarData(comunicado.data_criacao)}</span>
                        ${comunicado.data_atualizacao !== comunicado.data_criacao ? 
                            `<span class="editado">Editado: ${this.formatarData(comunicado.data_atualizacao)}</span>` : ''}
                    </div>
                </div>
            `).join('');
        
        container.innerHTML = html;
    }

    editarComunicado(id) {
        console.log('✏️ Editando comunicado:', id);
        const comunicado = this.todosComunicados.find(c => c.id == id);
        if (!comunicado) {
            this.showNotification('Comunicado não encontrado.', 'error');
            return;
        }
        
        this.comunicadoEditando = id;
        
        // Preencher formulário com dados sanitizados
        document.getElementById('titulo-comunicado').value = this.sanitizarTexto(comunicado.titulo);
        document.getElementById('conteudo-comunicado').value = this.sanitizarTexto(comunicado.conteudo);
        
        // Trocar para aba de criar/editar
        this.mostrarAba('criar');
        
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

    visualizarComunicado(id) {
        console.log('👁️ Visualizando comunicado:', id);
        const comunicado = this.todosComunicados.find(c => c.id == id);
        if (!comunicado) {
            this.showNotification('Comunicado não encontrado.', 'error');
            return;
        }
        
        const modal = document.getElementById('modal-visualizar-comunicado');
        if (modal) {
            document.getElementById('visualizar-titulo').textContent = this.sanitizarTexto(comunicado.titulo);
            
            // Processar conteúdo mantendo quebras de linha e sanitizando
            const conteudoProcessado = this.sanitizarTexto(comunicado.conteudo)
                .replace(/\n/g, '<br>')
                .replace(/\r\n/g, '<br>');
            document.getElementById('visualizar-conteudo').innerHTML = conteudoProcessado;
            
            document.getElementById('visualizar-autor').textContent = this.sanitizarTexto(comunicado.autor_nome);
            document.getElementById('visualizar-data').textContent = this.formatarData(comunicado.data_criacao);
            
            modal.style.display = 'flex';
        }
    }

    fecharModalVisualizacao() {
        const modal = document.getElementById('modal-visualizar-comunicado');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async excluirComunicado(id) {
        console.log('🗑️ Excluindo comunicado:', id);
        if (!confirm('Tem certeza que deseja excluir este comunicado?')) {
            return;
        }
        
        try {
            this.mostrarLoading(true);
            
            const resultado = await this.fetchComUTF8('api/comunicados.php?action=excluir', {
                method: 'POST',
                body: JSON.stringify({ id: id })
            });
            
            if (resultado.success) {
                this.showNotification('✅ Comunicado excluído com sucesso!', 'success');
                this.carregarComunicados(); // Recarregar lista
            } else {
                this.showNotification('❌ Erro: ' + (resultado.message || 'Erro desconhecido'), 'error');
            }
            
        } catch (error) {
            console.error('Erro ao excluir comunicado:', error);
            this.showNotification('❌ Erro ao excluir comunicado. Tente novamente.', 'error');
        } finally {
            this.mostrarLoading(false);
        }
    }

    cancelarEdicao() {
        this.comunicadoEditando = null;
        this.limparFormulario();
        
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

    limparFormulario() {
        const form = document.getElementById('form-comunicado');
        if (form) {
            form.reset();
        }
    }

    atualizarEstatisticas() {
        const totalElement = document.getElementById('total-comunicados');
        if (totalElement) {
            const total = this.todosComunicados.length;
            totalElement.textContent = `${this.formatarNumero(total)} comunicado${total !== 1 ? 's' : ''}`;
        }
    }

    // ===============================================
    // FUNÇÕES AUXILIARES
    // ===============================================

    mostrarLoading(show) {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.style.display = show ? 'flex' : 'none';
        }
    }

    showNotification(message, type = 'info') {
        // Remover notificações existentes
        document.querySelectorAll('.notification').forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            info: '#17a2b8',
            warning: '#ffc107'
        };

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            z-index: 10001;
            max-width: 400px;
            background: ${colors[type] || colors.info};
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            animation: slideInNotification 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        `;

        const icon = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle',
            warning: 'fas fa-exclamation-triangle'
        };

        // Usar sanitizarTexto para a mensagem
        notification.innerHTML = `
            <i class="${icon[type] || icon.info}"></i>
            <span>${this.sanitizarTexto(message)}</span>
        `;
        
        document.body.appendChild(notification);

        // Mostrar notificação
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remover após 4 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutNotification 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
}

// ===============================================
// COMPATIBILIDADE COM CÓDIGO LEGADO
// ===============================================

// Variáveis globais para compatibilidade
let comunicadoEditando = null;
let todosComunicados = [];

// Funções globais para compatibilidade
function abrirModalComunicados() {
    return sistemaComunicados.abrirModalComunicados();
}

function fecharModalComunicados() {
    return sistemaComunicados.fecharModalComunicados();
}

function mostrarAba(aba) {
    return sistemaComunicados.mostrarAba(aba);
}

function editarComunicado(id) {
    return sistemaComunicados.editarComunicado(id);
}

function visualizarComunicado(id) {
    return sistemaComunicados.visualizarComunicado(id);
}

function excluirComunicado(id) {
    return sistemaComunicados.excluirComunicado(id);
}

function fecharModalVisualizacao() {
    return sistemaComunicados.fecharModalVisualizacao();
}

function cancelarEdicao() {
    return sistemaComunicados.cancelarEdicao();
}

function limparFormulario() {
    return sistemaComunicados.limparFormulario();
}

function carregarComunicados() {
    return sistemaComunicados.carregarComunicados();
}

// Funções auxiliares globais
function escapeHtml(text) {
    return sistemaComunicados.escapeHtml(text);
}

function truncarTexto(texto, limite) {
    return sistemaComunicados.truncarTexto(texto, limite);
}

function formatarData(dateString) {
    return sistemaComunicados.formatarData(dateString);
}

function mostrarLoading(show) {
    return sistemaComunicados.mostrarLoading(show);
}

function showNotification(message, type = 'info') {
    return sistemaComunicados.showNotification(message, type);
}

// ===============================================
// INICIALIZAÇÃO
// ===============================================

// Criar instância global
let sistemaComunicados;

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    sistemaComunicados = new SistemaComunicados();
    
    // Atualizar variáveis globais para compatibilidade
    comunicadoEditando = sistemaComunicados.comunicadoEditando;
    todosComunicados = sistemaComunicados.todosComunicados;
    
    console.log('🎯 Sistema de comunicados inicializado com UTF-8 completo para Brasil');
});

// CSS para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInNotification {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutNotification {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification.show {
        animation: slideInNotification 0.3s ease !important;
    }
`;
document.head.appendChild(style);