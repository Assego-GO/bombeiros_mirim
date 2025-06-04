// Módulos adicionais para o sistema SuperAção
// Requer o arquivo components.js carregado antes deste

// Módulo de conteúdo para integrações
const IntegracaoModule = {
    // Renderiza a tab de API
    renderAPI: function() {
        // Status da API
        const apiStatus = Components.statusBadge('Ativo', 'active');
        
        // Chaves de API
        const apiKeys = [
            ['API-KEY-12345', 'Web', '15/06/2024', '120/dia', Components.statusBadge('Ativo', 'active')],
            ['API-KEY-67890', 'Mobile', '10/09/2024', '500/dia', Components.statusBadge('Ativo', 'active')],
            ['API-KEY-ABCDE', 'Parceiro X', '05/03/2024', '50/dia', Components.statusBadge('Inativo', 'inactive')]
        ];
        
        const apiKeyActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Regenerar', icon: '🔄' },
            { title: 'Excluir', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de API</h3>
                    <div class="form-help">Configure as chaves de API e permissões para integração com outros sistemas.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Status da API</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                        <div>${apiStatus}</div>
                        <button class="btn btn-outline btn-sm">Alterar Status</button>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">URL Base da API</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" class="form-input" value="https://api.superacao.org.br/v1" readonly style="flex: 1;">
                        <button class="btn btn-outline btn-sm">Copiar</button>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Chaves de API</label>
                    ${Components.dataTable(
                        ['Chave', 'Descrição', 'Validade', 'Limite', 'Status', 'Ações'],
                        apiKeys,
                        apiKeyActions
                    )}
                    <button class="btn btn-primary" style="margin-top: 15px;">+ Nova Chave de API</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de Webhooks
    renderWebhooks: function() {
        // Lista de webhooks
        const webhooks = [
            ['Matrículas', 'https://parceiro.com.br/webhook/matriculas', 'Criação, Atualização', Components.statusBadge('Ativo', 'active')],
            ['Pagamentos', 'https://parceiro.com.br/webhook/pagamentos', 'Confirmação', Components.statusBadge('Ativo', 'active')],
            ['Usuários', 'https://parceiro.com.br/webhook/usuarios', 'Criação', Components.statusBadge('Inativo', 'inactive')]
        ];
        
        const webhookActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Testar', icon: '🔄' },
            { title: 'Excluir', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Webhooks</h3>
                    <div class="form-help">Configure webhooks para notificar sistemas externos sobre eventos no SuperAção.</div>
                </div>
                
                <div class="form-row">
                    ${Components.alert('info', 'Webhooks permitem que sistemas externos sejam notificados em tempo real quando eventos ocorrem no SuperAção, como novas matrículas, atualizações de cadastro, etc.', 'ℹ️')}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Webhooks Configurados</label>
                    ${Components.dataTable(
                        ['Descrição', 'URL', 'Eventos', 'Status', 'Ações'],
                        webhooks,
                        webhookActions
                    )}
                    <button class="btn btn-primary" style="margin-top: 15px;">+ Novo Webhook</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de integrações externas
    renderExternalIntegrations: function() {
        // Lista de integrações
        const integrations = [
            ['Google Workspace', Components.statusBadge('Conectado', 'active'), '25/08/2024'],
            ['Microsoft 365', Components.statusBadge('Desconectado', 'inactive'), '--'],
            ['Mercado Pago', Components.statusBadge('Conectado', 'active'), '10/05/2024'],
            ['Correios', Components.statusBadge('Conectado', 'active'), '17/07/2024']
        ];
        
        const integrationActions = [
            { title: 'Configurar', icon: '⚙️' },
            { title: 'Reconectar', icon: '🔄' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Integrações Externas</h3>
                    <div class="form-help">Conecte o SuperAção com serviços e sistemas externos.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Integrações Disponíveis</label>
                    ${Components.dataTable(
                        ['Serviço', 'Status', 'Conectado em', 'Ações'],
                        integrations,
                        integrationActions
                    )}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Adicionar Nova Integração</label>
                    <div style="margin-top: 10px;">
                        <select class="form-select" style="margin-bottom: 10px;">
                            <option value="">Selecione um serviço...</option>
                            <option value="microsoft">Microsoft 365</option>
                            <option value="dropbox">Dropbox</option>
                            <option value="mailchimp">Mailchimp</option>
                            <option value="zapier">Zapier</option>
                            <option value="ifood">iFood</option>
                        </select>
                        <button class="btn btn-primary">Conectar</button>
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'api', label: 'API', content: this.renderAPI(), active: true },
            { id: 'webhooks', label: 'Webhooks', content: this.renderWebhooks(), active: false },
            { id: 'external', label: 'Integrações Externas', content: this.renderExternalIntegrations(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section" id="integracao">
                <h2 class="settings-title">Integrações</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Módulo para Usuários e Permissões
const UsuariosModule = {
    // Renderiza a tab de lista de usuários
    renderUsersList: function() {
        const users = [
            ['Carlos Oliveira', 'carlos@superacao.org.br', 'Administrador', Components.statusBadge('Ativo', 'active'), '28/09/2024 10:32'],
            ['Ricardo Santos', 'ricardo@superacao.org.br', 'Coordenador', Components.statusBadge('Ativo', 'active'), '28/09/2024 16:20'],
            ['Patrícia Gomes', 'patricia@superacao.org.br', 'Professor', Components.statusBadge('Ativo', 'active'), '28/09/2024 15:45'],
            ['Fernando Souza', 'fernando@superacao.org.br', 'Secretária', Components.statusBadge('Ativo', 'active'), '28/09/2024 14:10'],
            ['Mariana Lima', 'mariana@superacao.org.br', 'Financeiro', Components.statusBadge('Inativo', 'inactive'), '15/09/2024 09:25']
        ];
        
        const userActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Permissões', icon: '🔐' },
            { title: 'Excluir', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Usuários do Sistema</h3>
                    <div class="form-help">Gerencie usuários e seus acessos ao sistema.</div>
                </div>
                
                <div class="form-row">
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 15px;">
                        <button class="btn btn-primary">+ Novo Usuário</button>
                        <button class="btn btn-outline">Importar Usuários</button>
                    </div>
                </div>
                
                <div class="form-row">
                    ${Components.dataTable(
                        ['Nome', 'E-mail', 'Perfil', 'Status', 'Último Acesso', 'Ações'],
                        users,
                        userActions
                    )}
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de perfis e permissões
    renderPermissions: function() {
        const profiles = [
            ['Administrador', '5 usuários', 'Acesso total ao sistema'],
            ['Coordenador', '12 usuários', 'Acesso a matrículas, alunos, turmas e relatórios'],
            ['Professor', '28 usuários', 'Acesso a turmas, frequência e notas'],
            ['Secretária', '8 usuários', 'Acesso a matrículas e alunos'],
            ['Financeiro', '3 usuários', 'Acesso a pagamentos e relatórios financeiros']
        ];
        
        const profileActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Duplicar', icon: '🔄' },
            { title: 'Excluir', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Perfis e Permissões</h3>
                    <div class="form-help">Configure perfis de acesso e permissões para usuários do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Perfis de Acesso</label>
                    ${Components.dataTable(
                        ['Nome do Perfil', 'Quantidade de Usuários', 'Descrição', 'Ações'],
                        profiles,
                        profileActions
                    )}
                    <button class="btn btn-primary" style="margin-top: 15px;">+ Novo Perfil</button>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Política de Senhas</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Exigir senhas fortes', true)}
                        ${Components.toggleSwitch('Expiração de senha a cada 90 dias', true)}
                        ${Components.toggleSwitch('Bloquear após 5 tentativas inválidas', true)}
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de autenticação
    renderAuthentication: function() {
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Autenticação</h3>
                    <div class="form-help">Configure métodos de autenticação e segurança para acesso ao sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Métodos de Autenticação</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Login com e-mail e senha', true)}
                        ${Components.toggleSwitch('Autenticação de dois fatores (2FA)', true)}
                        ${Components.toggleSwitch('Autenticação com Google', false)}
                        ${Components.toggleSwitch('Autenticação com Microsoft', false)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Sessão</label>
                    <div style="margin-top: 10px;">
                        <div class="form-grid">
                            <div>
                                <div class="form-help">Tempo máximo de inatividade</div>
                                <select class="form-select">
                                    <option>15 minutos</option>
                                    <option selected>30 minutos</option>
                                    <option>1 hora</option>
                                    <option>2 horas</option>
                                    <option>4 horas</option>
                                </select>
                            </div>
                            <div>
                                <div class="form-help">Duração máxima da sessão</div>
                                <select class="form-select">
                                    <option>4 horas</option>
                                    <option selected>8 horas</option>
                                    <option>24 horas</option>
                                    <option>7 dias</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Segurança</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Bloquear múltiplas sessões simultâneas', false)}
                        ${Components.toggleSwitch('Registrar histórico de login', true)}
                        ${Components.toggleSwitch('Enviar notificação de novo login', true)}
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'usuarios-lista', label: 'Lista de Usuários', content: this.renderUsersList(), active: true },
            { id: 'perfis', label: 'Perfis e Permissões', content: this.renderPermissions(), active: false },
            { id: 'autenticacao', label: 'Autenticação', content: this.renderAuthentication(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section" id="usuarios">
                <h2 class="settings-title">Usuários e Permissões</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Módulo para Aparência
const AparenciaModule = {
    // Renderiza a tab de tema
    renderTheme: function() {
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Tema</h3>
                    <div class="form-help">Personalize as cores e o visual do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Cor Primária</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" value="#1a5276" style="width: 50px; height: 40px;">
                        <input type="text" class="form-input" value="#1a5276" style="width: 120px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Cor Secundária</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" value="#f39c12" style="width: 50px; height: 40px;">
                        <input type="text" class="form-input" value="#f39c12" style="width: 120px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Cor de Destaque</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" value="#2ecc71" style="width: 50px; height: 40px;">
                        <input type="text" class="form-input" value="#2ecc71" style="width: 120px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Tema do Sistema</label>
                    <div style="display: flex; gap: 20px; margin-top: 15px;">
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 80px; background-color: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></div>
                            <div class="checkbox-group">
                                <input type="radio" name="theme" id="theme-light" checked>
                                <label for="theme-light">Claro</label>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 80px; background-color: #2c3e50; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></div>
                            <div class="checkbox-group">
                                <input type="radio" name="theme" id="theme-dark">
                                <label for="theme-dark">Escuro</label>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 80px; background: linear-gradient(to right, white 50%, #2c3e50 50%); border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></div>
                            <div class="checkbox-group">
                                <input type="radio" name="theme" id="theme-auto">
                                <label for="theme-auto">Automático</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Estilos Adicionais</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Cantos arredondados', true)}
                        ${Components.toggleSwitch('Sombras nos elementos', true)}
                        ${Components.toggleSwitch('Efeitos de transição', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <button class="btn btn-primary">Aplicar Tema</button>
                    <button class="btn btn-outline" style="margin-left: 10px;">Restaurar Padrão</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de marca
    renderBranding: function() {
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Personalização de Marca</h3>
                    <div class="form-help">Configure os elementos de marca e identidade visual do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Logo do Sistema</label>
                    <div style="display: flex; gap: 20px; align-items: start; margin-top: 15px;">
                        <div style="text-align: center;">
                            <img src="/api/placeholder/100/100" alt="Logo" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            <div style="margin-top: 10px;">
                                <button class="btn btn-outline btn-sm">Trocar Logo</button>
                            </div>
                        </div>
                        <div>
                            <div class="form-help" style="margin-bottom: 5px;">Recomendações:</div>
                            <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: var(--gray);">
                                <li>Dimensões recomendadas: 200x200 pixels</li>
                                <li>Formatos aceitos: PNG, JPG, SVG</li>
                                <li>Tamanho máximo: 2MB</li>
                                <li>Preferível com fundo transparente</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Favicon</label>
                    <div style="display: flex; gap: 20px; align-items: start; margin-top: 15px;">
                        <div style="text-align: center;">
                            <img src="/api/placeholder/32/32" alt="Favicon" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            <div style="margin-top: 10px;">
                                <button class="btn btn-outline btn-sm">Trocar Favicon</button>
                            </div>
                        </div>
                        <div>
                            <div class="form-help" style="margin-bottom: 5px;">Recomendações:</div>
                            <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: var(--gray);">
                                <li>Dimensões: 32x32 pixels</li>
                                <li>Formato: ICO, PNG</li>
                                <li>Tamanho máximo: 100KB</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Nome do Sistema</label>
                    <input type="text" class="form-input" value="SuperAção">
                </div>
                
                <div class="form-row">
                    <label class="form-label">Slogan</label>
                    <input type="text" class="form-input" value="Transformando vidas através da educação">
                </div>
                
                <div class="form-row">
                    <button class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de layout
    renderLayout: function() {
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Layout</h3>
                    <div class="form-help">Personalize o layout e a disposição dos elementos na interface.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Layout do Menu</label>
                    <div style="display: flex; gap: 20px; margin-top: 15px;">
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 80px; background-color: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; display: flex;">
                                <div style="width: 30px; background-color: #eee;"></div>
                                <div style="flex: 1;"></div>
                            </div>
                            <div class="checkbox-group">
                                <input type="radio" name="layout" id="layout-sidebar" checked>
                                <label for="layout-sidebar">Barra Lateral</label>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 80px; background-color: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; display: flex; flex-direction: column;">
                                <div style="height: 30px; background-color: #eee;"></div>
                                <div style="flex: 1;"></div>
                            </div>
                            <div class="checkbox-group">
                                <input type="radio" name="layout" id="layout-top">
                                <label for="layout-top">Menu Superior</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Densidade da Interface</label>
                    <div style="display: flex; gap: 20px; margin-top: 15px;">
                        <div class="checkbox-group">
                            <input type="radio" name="density" id="density-compact">
                            <label for="density-compact">Compacta</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="radio" name="density" id="density-normal" checked>
                            <label for="density-normal">Normal</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="radio" name="density" id="density-comfortable">
                            <label for="density-comfortable">Confortável</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Elementos na Página Inicial</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Mostrar estatísticas rápidas', true)}
                        ${Components.toggleSwitch('Mostrar gráficos', true)}
                        ${Components.toggleSwitch('Mostrar calendário', true)}
                        ${Components.toggleSwitch('Mostrar atividades recentes', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <button class="btn btn-primary">Aplicar Layout</button>
                    <button class="btn btn-outline" style="margin-left: 10px;">Visualizar</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'tema', label: 'Tema e Cores', content: this.renderTheme(), active: true },
            { id: 'marca', label: 'Logo e Marca', content: this.renderBranding(), active: false },
            { id: 'layout', label: 'Layout', content: this.renderLayout(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section" id="aparencia">
                <h2 class="settings-title">Aparência</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Adicione estes módulos ao App.modules
// Isso deve ser feito no arquivo principal após a importação dos módulos
/*
App.modules.integracao = IntegracaoModule;
App.modules.usuarios = UsuariosModule;
App.modules.aparencia = AparenciaModule;
*/