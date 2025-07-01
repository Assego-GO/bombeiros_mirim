// Seu código existente com as correções de scroll aplicadas

// Variáveis para o modal de comunicados
const comunicadosModal = document.getElementById('comunicadosModal');
const closeComunicadosModal = document.getElementById('closeComunicadosModal');
const cardComunicados = document.getElementById('card-comunicados');

// Event listeners para comunicados
if (cardComunicados) {
    cardComunicados.addEventListener('click', function() {
        abrirComunicados();
    });
}

// CORRIGIDO: Event listener para fechar modal
if (closeComunicadosModal) {
    closeComunicadosModal.addEventListener('click', function() {
        comunicadosModal.style.display = 'none';
        document.body.style.overflow = ''; // NOVO: Libera scroll da página
    });
}

// CORRIGIDO: Fechar modal ao clicar fora dele
window.addEventListener('click', function(event) {
    if (event.target === comunicadosModal) {
        comunicadosModal.style.display = 'none';
        document.body.style.overflow = ''; // NOVO: Libera scroll da página
    }
});

// NOVO: Bloquear scroll da página quando estiver dentro do modal
if (comunicadosModal) {
    comunicadosModal.addEventListener('wheel', function(event) {
        event.stopPropagation(); // Impede que o scroll "vaze" para a página
    }, { passive: false });
}

// NOVO: Garantir que só o container de comunicados role
document.addEventListener('wheel', function(event) {
    if (comunicadosModal && comunicadosModal.style.display === 'block') {
        const dentroDoContainer = event.target.closest('.comunicados-container');
        if (!dentroDoContainer) {
            event.preventDefault(); // Bloqueia scroll se não estiver no container
        }
    }
}, { passive: false });

// Carregar contador de notificações quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    carregarContadorNotificacoes();
    
    // Atualizar contador a cada 30 segundos
    setInterval(carregarContadorNotificacoes, 30000);
});

// Função para carregar apenas o contador de notificações
function carregarContadorNotificacoes() {
    fetch('api/buscar_comunicados.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                atualizarBadgeNotificacao(data.nao_lidos);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar contador de notificações:', error);
        });
}

// Função para atualizar o badge de notificação - VERSÃO CORRIGIDA
function atualizarBadgeNotificacao(count) {
    const cardComunicados = document.getElementById('card-comunicados');
    let badge = cardComunicados.querySelector('.notification-badge');
    
    // Se não existe o badge, criar
    if (!badge) {
        badge = document.createElement('div');
        badge.className = 'notification-badge hidden';
        cardComunicados.appendChild(badge);
    }
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count.toString();
        badge.classList.remove('hidden');
        
        // Adicionar classe para números grandes
        if (count > 9) {
            badge.classList.add('large');
        } else {
            badge.classList.remove('large');
        }
    } else {
        badge.classList.add('hidden');
        badge.classList.remove('large');
    }
}

// CORRIGIDO: Função para abrir modal de comunicados
function abrirComunicados() {
    comunicadosModal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // NOVO: Bloqueia scroll da página
    carregarComunicados();
}

// Função para carregar comunicados
function carregarComunicados() {
    const comunicadosLista = document.getElementById('comunicados-lista');
    const comunicadosTurmaInfo = document.querySelector('.comunicados-turma-info');
    
    // Mostrar loading
    comunicadosLista.innerHTML = '<div class="loading-comunicados"><i class="fas fa-spinner fa-spin"></i> Carregando comunicados...</div>';
    
    fetch('api/buscar_comunicados.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar informações da turma
                if (comunicadosTurmaInfo) {
                    document.getElementById('comunicados-turma-nome').textContent = data.turma.nome_turma || 'Não informado';
                    document.getElementById('comunicados-unidade-nome').textContent = data.turma.nome_unidade || 'Não informado';
                }
                
                // Renderizar comunicados
                renderizarComunicados(data.comunicados);
                
                // Atualizar badge
                atualizarBadgeNotificacao(data.nao_lidos);
            } else {
                comunicadosLista.innerHTML = `
                    <div class="comunicados-erro">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Erro ao carregar comunicados: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar comunicados:', error);
            comunicadosLista.innerHTML = `
                <div class="comunicados-erro">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erro ao carregar comunicados. Tente novamente mais tarde.</p>
                </div>
            `;
        });
}

// Função para renderizar lista de comunicados
function renderizarComunicados(comunicados) {
    const comunicadosLista = document.getElementById('comunicados-lista');
    
    if (!comunicados || comunicados.length === 0) {
        comunicadosLista.innerHTML = `
            <div class="comunicados-vazio">
                <i class="fas fa-bullhorn"></i>
                <h3>Nenhum comunicado encontrado</h3>
                <p>Ainda não há comunicados disponíveis para sua turma.</p>
            </div>
        `;
        return;
    }
    
    // Verificar se há comunicados não lidos
    const temNaoLidos = comunicados.some(c => !c.lido);
    
    let html = '<div class="comunicados-container">';
    
    // Adicionar botão para marcar todos como lidos se houver não lidos
    if (temNaoLidos) {
        html += `
            <div class="comunicados-actions-top">
                <button onclick="marcarTodosComoLidos()" class="btn-marcar-todos">
                    <i class="fas fa-check-double"></i>
                    Marcar todos como lidos
                </button>
            </div>
        `;
    }
    
    comunicados.forEach(comunicado => {
        const statusLeitura = comunicado.lido ? 'lido' : 'nao-lido';
        const iconeStatus = comunicado.lido ? 'fa-envelope-open' : 'fa-envelope';
        
        html += `
            <div class="comunicado-item ${statusLeitura}" data-comunicado-id="${comunicado.id}">
                <div class="comunicado-header">
                    <h3 class="comunicado-titulo">
                        <i class="fas fa-bullhorn"></i>
                        ${escapeHtml(comunicado.titulo)}
                        <span class="status-leitura">
                            <i class="fas ${iconeStatus}"></i>
                        </span>
                    </h3>
                    <div class="comunicado-meta">
                        <span class="comunicado-autor">
                            <i class="fas fa-user"></i>
                            Por: ${escapeHtml(comunicado.autor_nome)}
                        </span>
                        <span class="comunicado-data">
                            <i class="fas fa-calendar"></i>
                            ${comunicado.data_criacao_formatada}
                        </span>
                        ${comunicado.lido && comunicado.data_leitura_formatada ? `
                            <span class="comunicado-lido-em">
                                <i class="fas fa-eye"></i>
                                Lido em: ${comunicado.data_leitura_formatada}
                            </span>
                        ` : ''}
                    </div>
                </div>
                
                <div class="comunicado-conteudo">
                    <div class="comunicado-preview">
                        ${escapeHtml(comunicado.conteudo_preview)}
                    </div>
                    <div class="comunicado-completo" style="display: none;">
                        ${escapeHtml(comunicado.conteudo).replace(/\n/g, '<br>')}
                    </div>
                </div>
                
                <div class="comunicado-actions">
                    <button class="btn-ver-mais" onclick="toggleComunicado(${comunicado.id})">
                        <i class="fas fa-chevron-down"></i>
                        Ver mais
                    </button>
                    ${!comunicado.lido ? `
                        <button class="btn-marcar-lido" onclick="marcarComoLido(${comunicado.id})">
                            <i class="fas fa-check"></i>
                            Marcar como lido
                        </button>
                    ` : ''}
                </div>
                
                ${comunicado.data_atualizacao_formatada ? `
                    <div class="comunicado-updated">
                        <small>
                            <i class="fas fa-edit"></i>
                            Atualizado em: ${comunicado.data_atualizacao_formatada}
                        </small>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    comunicadosLista.innerHTML = html;
}

// Função para expandir/contrair comunicado
function toggleComunicado(comunicadoId) {
    const comunicadoItem = document.querySelector(`[data-comunicado-id="${comunicadoId}"]`);
    const preview = comunicadoItem.querySelector('.comunicado-preview');
    const completo = comunicadoItem.querySelector('.comunicado-completo');
    const botao = comunicadoItem.querySelector('.btn-ver-mais');
    
    if (completo.style.display === 'none') {
        // Expandir
        preview.style.display = 'none';
        completo.style.display = 'block';
        botao.innerHTML = '<i class="fas fa-chevron-up"></i> Ver menos';
        comunicadoItem.classList.add('expandido');
        
        // Marcar como lido automaticamente quando expandir
        if (comunicadoItem.classList.contains('nao-lido')) {
            marcarComoLido(comunicadoId, false); // false = não recarregar a lista
        }
    } else {
        // Contrair
        preview.style.display = 'block';
        completo.style.display = 'none';
        botao.innerHTML = '<i class="fas fa-chevron-down"></i> Ver mais';
        comunicadoItem.classList.remove('expandido');
    }
}

// Função para marcar comunicado como lido
function marcarComoLido(comunicadoId, recarregar = true) {
    fetch('api/buscar_comunicados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'marcar_lido',
            comunicado_id: comunicadoId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (recarregar) {
                carregarComunicados(); // Recarregar lista
            } else {
                // Apenas atualizar visualmente o item específico
                const comunicadoItem = document.querySelector(`[data-comunicado-id="${comunicadoId}"]`);
                if (comunicadoItem) {
                    comunicadoItem.classList.remove('nao-lido');
                    comunicadoItem.classList.add('lido');
                    
                    const statusIcon = comunicadoItem.querySelector('.status-leitura i');
                    statusIcon.className = 'fas fa-envelope-open';
                    
                    const btnMarcar = comunicadoItem.querySelector('.btn-marcar-lido');
                    if (btnMarcar) {
                        btnMarcar.remove();
                    }
                }
                carregarContadorNotificacoes(); // Atualizar apenas o contador
            }
        }
    })
    .catch(error => {
        console.error('Erro ao marcar como lido:', error);
    });
}

// Função para marcar todos os comunicados como lidos
function marcarTodosComoLidos() {
    fetch('api/buscar_comunicados.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'marcar_todos_lidos'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarComunicados(); // Recarregar lista completa
        }
    })
    .catch(error => {
        console.error('Erro ao marcar todos como lidos:', error);
    });
}

// Função auxiliar para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}