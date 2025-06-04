
// Módulo de componentes reutilizáveis
const Components = {
    // Cria um toggle switch
    toggleSwitch: function(label, checked = false) {
        return `
            <div class="toggle-row">
                <span class="toggle-label">${label}</span>
                <label class="toggle-switch">
                    <input type="checkbox" ${checked ? 'checked' : ''}>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        `;
    },
    
    // Cria uma tabela de dados
    dataTable: function(headers, rows, actions = []) {
        let tableHeaders = headers.map(header => `<th>${header}</th>`).join('');
        
        let tableRows = rows.map(row => {
            let cells = row.map(cell => `<td>${cell}</td>`).join('');
            
            // Adicionar coluna de ações se houver
            if (actions.length > 0) {
                let actionButtons = actions.map(action => 
                    `<button class="action-button ${action.danger ? 'danger' : ''}" title="${action.title}">${action.icon}</button>`
                ).join('');
                
                cells += `<td>${actionButtons}</td>`;
            }
            
            return `<tr>${cells}</tr>`;
        }).join('');
        
        return `
            <div class="table-container">
                <table>
                    <thead>
                        <tr>${tableHeaders}</tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </div>
        `;
    },
    
    // Cria uma barra de progresso
    progressBar: function(percentage, color = 'var(--accent)') {
        return `
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${percentage}%; background-color: ${color};"></div>
            </div>
        `;
    },
    
    // Cria um grupo de checkboxes
    checkboxGroup: function(options) {
        return options.map(option => `
            <div class="checkbox-group">
                <input type="checkbox" id="${option.id}" ${option.checked ? 'checked' : ''}>
                <label for="${option.id}">${option.label}</label>
            </div>
        `).join('');
    },
    
    // Cria um alerta
    alert: function(type, message, icon) {
        return `
            <div class="alert alert-${type}">
                <span>${icon}</span>
                <div>${message}</div>
            </div>
        `;
    },
    
    // Cria um conjunto de tabs
    tabs: function(tabsData) {
        const tabsHtml = tabsData.map(tab => 
            `<div class="tab ${tab.active ? 'active' : ''}" data-tab="${tab.id}">${tab.label}</div>`
        ).join('');
        
        const contentHtml = tabsData.map(tab => 
            `<div class="tab-content ${tab.active ? 'active' : ''}" id="${tab.id}">${tab.content}</div>`
        ).join('');
        
        return {
            tabs: `<div class="tabs">${tabsHtml}</div>`,
            content: contentHtml
        };
    },
    
    // Cria um formulário com grid
    formGrid: function(rows) {
        return `
            <div class="form-grid">
                ${rows.map(row => `
                    <div>
                        <div class="form-help">${row.label}</div>
                        ${row.content}
                    </div>
                `).join('')}
            </div>
        `;
    },
    
    // Cria um status badge
    statusBadge: function(label, type) {
        return `<span class="status ${type}">${label}</span>`;
    }
};

// Módulo de conteúdo para backup
const BackupModule = {
    // Renderiza a tab de backup automático
    renderAutoBackup: function() {
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Backup Automático</h3>
                    <div class="form-help">Configure backups automáticos para garantir a segurança dos dados do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Status do Backup Automático</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Ativar backup automático', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Frequência de Backup</label>
                    <select class="form-select">
                        <option>Diário</option>
                        <option>Semanal (Domingo)</option>
                        <option>Semanal (Segunda-feira)</option>
                        <option>Quinzenal</option>
                        <option>Mensal</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Horário do Backup</label>
                    <input type="time" class="form-input" value="02:00">
                    <div class="form-help">Recomendamos agendar o backup para horários de baixo uso do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Retenção de Backups</label>
                    <div class="form-grid">
                        <div>
                            <div class="form-help">Backups diários - manter por</div>
                            <select class="form-select">
                                <option>7 dias</option>
                                <option>14 dias</option>
                                <option>30 dias</option>
                            </select>
                        </div>
                        <div>
                            <div class="form-help">Backups semanais - manter por</div>
                            <select class="form-select">
                                <option>4 semanas</option>
                                <option>8 semanas</option>
                                <option>12 semanas</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Locais de Armazenamento</label>
                    <div style="margin-top: 10px;">
                        ${Components.checkboxGroup([
                            { id: 'backup-local', label: 'Armazenamento local (servidor)', checked: true },
                            { id: 'backup-gdrive', label: 'Google Drive', checked: true },
                            { id: 'backup-s3', label: 'Amazon S3', checked: false },
                            { id: 'backup-ftp', label: 'FTP/SFTP', checked: false }
                        ])}
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de backup manual
    renderManualBackup: function() {
        const manualBackupHistory = [
            ['28/09/2024 14:30', 'Pré-atualização do sistema', 'Completo', '1.2 GB', 'Carlos Oliveira', Components.statusBadge('Concluído', 'active')],
            ['15/09/2024 09:15', 'Backup semestral', 'Completo', '1.1 GB', 'Ricardo Santos', Components.statusBadge('Concluído', 'active')],
            ['01/08/2024 16:48', 'Antes da migração', 'Apenas BD', '350 MB', 'Carlos Oliveira', Components.statusBadge('Concluído', 'active')]
        ];
        
        const actions = [
            { title: 'Fazer Download', icon: '⬇️' },
            { title: 'Restaurar', icon: '🔄' },
            { title: 'Excluir', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Backup Manual</h3>
                    <div class="form-help">Realize backups manuais do sistema quando necessário.</div>
                </div>
                
                <div class="form-row">
                    ${Components.alert('info', 'Gerar um backup manual não afeta a programação de backups automáticos. Os backups manuais são armazenados separadamente e não são excluídos pela política de retenção automática.', 'ℹ️')}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Opções de Backup</label>
                    <div style="margin-top: 10px;">
                        ${Components.checkboxGroup([
                            { id: 'manual-banco', label: 'Banco de dados', checked: true },
                            { id: 'manual-arquivos', label: 'Arquivos enviados (fotos, documentos, etc.)', checked: true },
                            { id: 'manual-configs', label: 'Configurações do sistema', checked: true },
                            { id: 'manual-logs', label: 'Logs do sistema', checked: false }
                        ])}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Destino do Backup</label>
                    <select class="form-select">
                        <option>Todos os destinos configurados</option>
                        <option>Apenas armazenamento local</option>
                        <option>Apenas Google Drive</option>
                        <option>Download direto (navegador)</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Descrição do Backup</label>
                    <input type="text" class="form-input" placeholder="Ex: Backup antes da atualização v2.5">
                    <div class="form-help">Opcional. Ajuda a identificar o propósito deste backup específico.</div>
                </div>
                
                <div class="form-row">
                    <button class="btn btn-primary">🔄 Iniciar Backup Manual</button>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Histórico de Backups Manuais</label>
                    ${Components.dataTable(
                        ['Data e Hora', 'Descrição', 'Conteúdo', 'Tamanho', 'Realizado Por', 'Status', 'Ações'],
                        manualBackupHistory,
                        actions
                    )}
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de restauração
    renderRestoration: function() {
        // Lista de backups disponíveis
        const backupsList = [
            ['<input type="radio" name="backup-select">', '29/09/2024 02:00', 'Automático', 'Completo', '1.3 GB', 'Local, Google Drive'],
            ['<input type="radio" name="backup-select">', '28/09/2024 14:30', 'Manual', 'Completo', '1.2 GB', 'Local, Google Drive'],
            ['<input type="radio" name="backup-select">', '28/09/2024 02:00', 'Automático', 'Completo', '1.2 GB', 'Local, Google Drive'],
            ['<input type="radio" name="backup-select">', '27/09/2024 02:00', 'Automático', 'Completo', '1.2 GB', 'Local, Google Drive']
        ];
        
        const detailsAction = [
            { title: 'Detalhes', icon: '🔍' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Restauração de Backup</h3>
                    <div class="form-help">Restaure o sistema a partir de um backup previamente criado.</div>
                </div>
                
                <div class="form-row">
                    ${Components.alert('warning', '<strong>Atenção!</strong> A restauração de backup substituirá todos os dados atuais pelos dados do backup selecionado. Esta ação não pode ser desfeita.', '⚠️')}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Selecione um Backup para Restaurar</label>
                    ${Components.dataTable(
                        ['Seleção', 'Data e Hora', 'Tipo', 'Conteúdo', 'Tamanho', 'Local', 'Ações'],
                        backupsList,
                        detailsAction
                    )}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Restaurar a partir de Arquivo</label>
                    <input type="file" class="form-input">
                    <div class="form-help">Faça upload de um arquivo de backup anteriormente baixado.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Opções de Restauração</label>
                    <div style="margin-top: 10px;">
                        ${Components.checkboxGroup([
                            { id: 'rest-banco', label: 'Banco de dados', checked: true },
                            { id: 'rest-arquivos', label: 'Arquivos enviados (fotos, documentos, etc.)', checked: true },
                            { id: 'rest-configs', label: 'Configurações do sistema', checked: true },
                            { id: 'rest-logs', label: 'Logs do sistema', checked: false }
                        ])}
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="checkbox-group">
                        <input type="checkbox" id="backup-pre-restauracao">
                        <label for="backup-pre-restauracao">Realizar backup do estado atual antes da restauração</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Confirmação de Segurança</label>
                    <input type="text" class="form-input" placeholder="Digite 'CONFIRMAR RESTAURAÇÃO' para prosseguir">
                </div>
                
                <div class="form-row">
                    <button class="btn btn-danger">Restaurar Sistema</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de limpeza de dados
    renderDataCleaning: function() {
        const cleanupItems = [
            ['Cache do Sistema', '--', '620 MB', '28/09/2024'],
            ['Arquivos Temporários', '157 arquivos', '450 MB', '25/09/2024'],
            ['Logs Antigos', '--', '780 MB', '10/09/2024'],
            ['Backups Antigos', '8 backups', '2.4 GB', '05/09/2024']
        ];
        
        const cleanupActions = [
            { title: 'Limpar', icon: '🧹' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Limpeza e Retenção de Dados</h3>
                    <div class="form-help">Configure políticas de limpeza e retenção de dados no sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Uso Atual de Armazenamento</label>
                    <div style="background-color: #f5f5f5; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div>Espaço Total:</div>
                                <div><strong>50 GB</strong></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <div>Espaço Utilizado:</div>
                                <div><strong>32.7 GB</strong> (65.4%)</div>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <div>Espaço Livre:</div>
                                <div><strong>17.3 GB</strong> (34.6%)</div>
                            </div>
                        </div>
                        
                        ${Components.progressBar(65.4)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Políticas de Retenção</label>
                    <div style="margin-top: 10px;">
                        ${Components.formGrid([
                            { 
                                label: 'Logs do Sistema', 
                                content: `
                                    <select class="form-select">
                                        <option>1 mês</option>
                                        <option>3 meses</option>
                                        <option selected>6 meses</option>
                                        <option>1 ano</option>
                                        <option>Indefinidamente</option>
                                    </select>
                                `
                            },
                            { 
                                label: 'Logs de Acesso', 
                                content: `
                                    <select class="form-select">
                                        <option>1 mês</option>
                                        <option>3 meses</option>
                                        <option selected>6 meses</option>
                                        <option>1 ano</option>
                                        <option>Indefinidamente</option>
                                    </select>
                                `
                            }
                        ])}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Arquivos Temporários</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Limpar automaticamente arquivos temporários', true)}
                        ${Components.toggleSwitch('Remover anexos de matrículas canceladas após 30 dias', true)}
                        ${Components.toggleSwitch('Remover fotos antigas quando atualizadas', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Limpeza Manual</label>
                    ${Components.dataTable(
                        ['Tipo de Dados', 'Quantidade', 'Tamanho', 'Última Limpeza', 'Ações'],
                        cleanupItems,
                        cleanupActions
                    )}
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'backup-auto', label: 'Backup Automático', content: this.renderAutoBackup(), active: true },
            { id: 'backup-manual', label: 'Backup Manual', content: this.renderManualBackup(), active: false },
            { id: 'restauracao', label: 'Restauração', content: this.renderRestoration(), active: false },
            { id: 'limpeza', label: 'Limpeza de Dados', content: this.renderDataCleaning(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section active" id="backup">
                <h2 class="settings-title">Backup e Dados</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Módulo de conteúdo para logs
const LogsModule = {
    renderSystemLogs: function() {
        const systemLogsData = [
            ['29/09/2024 10:45:23', 'Carlos Oliveira', 'Edição', 'Configurações', 'Alteração nas configurações de backup', '192.168.1.10'],
            ['29/09/2024 10:32:15', 'Carlos Oliveira', 'Login', 'Sistema', 'Login bem-sucedido', '192.168.1.10'],
            ['28/09/2024 16:20:45', 'Ricardo Santos', 'Criação', 'Matrículas', 'Nova matrícula: João Pedro Silva', '192.168.1.25'],
            ['28/09/2024 15:18:32', 'Patrícia Gomes', 'Edição', 'Alunos', 'Atualização de dados: Maria Fernanda', '192.168.1.30'],
            ['28/09/2024 14:55:10', 'Fernando Souza', 'Exportação', 'Relatórios', 'Exportação de relatório de frequência', '192.168.1.40']
        ];
        
        const logActions = [
            { title: 'Ver Detalhes', icon: '🔍' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Atividades de Usuários</h3>
                    <div class="form-help">Visualize e filtre o histórico de atividades realizadas pelos usuários do sistema.</div>
                </div>
                
                <div class="form-row">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Usuário</div>
                            <select class="form-select">
                                <option>Todos os usuários</option>
                                <option>Carlos Oliveira</option>
                                <option>Ricardo Santos</option>
                                <option>Patrícia Gomes</option>
                                <option>Fernando Souza</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Tipo de Ação</div>
                            <select class="form-select">
                                <option>Todas as ações</option>
                                <option>Login/Logout</option>
                                <option>Criação</option>
                                <option>Edição</option>
                                <option>Exclusão</option>
                                <option>Exportação</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Módulo</div>
                            <select class="form-select">
                                <option>Todos os módulos</option>
                                <option>Matrículas</option>
                                <option>Alunos</option>
                                <option>Turmas</option>
                                <option>Usuários</option>
                                <option>Configurações</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Período</div>
                            <select class="form-select">
                                <option>Últimas 24 horas</option>
                                <option>Últimos 7 dias</option>
                                <option>Últimos 30 dias</option>
                                <option>Personalizado</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 15px;">
                        <button class="btn btn-outline">Limpar Filtros</button>
                        <button class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </div>
                
                <div class="form-row">
                    ${Components.dataTable(
                        ['Data e Hora', 'Usuário', 'Ação', 'Módulo', 'Descrição', 'IP', 'Detalhes'],
                        userActivitiesData,
                        activityActions
                    )}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div>
                            <button class="btn btn-outline">⬇️ Exportar Logs</button>
                        </div>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <button class="btn btn-outline btn-sm">«</button>
                            <button class="btn btn-outline btn-sm" style="background-color: var(--primary); color: white;">1</button>
                            <button class="btn btn-outline btn-sm">2</button>
                            <button class="btn btn-outline btn-sm">3</button>
                            <button class="btn btn-outline btn-sm">»</button>
                            <span style="margin-left: 10px; color: var(--gray); font-size: 14px;">Mostrando 1-5 de 1245 registros</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Log</label>
                    <div style="margin-top: 10px;">
                        ${Components.formGrid([
                            { 
                                label: 'Nível Mínimo de Log', 
                                content: `
                                    <select class="form-select">
                                        <option>Debug</option>
                                        <option selected>Info</option>
                                        <option>Warning</option>
                                        <option>Error</option>
                                        <option>Critical</option>
                                    </select>
                                `
                            },
                            { 
                                label: 'Retenção de Logs', 
                                content: `
                                    <select class="form-select">
                                        <option>30 dias</option>
                                        <option>60 dias</option>
                                        <option selected>90 dias</option>
                                        <option>180 dias</option>
                                        <option>365 dias</option>
                                    </select>
                                `
                            }
                        ])}
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de segurança e auditoria
    renderSecurityAudit: function() {
        const securityEvents = [
            ['29/09/2024 09:45:28', 'Acesso a Dados Sensíveis', 'Carlos Oliveira', '192.168.1.10', 'Visualização de informações financeiras de 3 alunos', Components.statusBadge('Média', 'warning')],
            ['28/09/2024 17:22:45', 'Exportação em Massa', 'Fernando Souza', '192.168.1.40', 'Exportação de dados de 230 alunos', Components.statusBadge('Média', 'warning')],
            ['28/09/2024 15:30:12', 'Alteração de Permissões', 'Carlos Oliveira', '192.168.1.10', 'Modificação nas permissões do usuário Ricardo Santos', Components.statusBadge('Média', 'warning')],
            ['27/09/2024 10:15:30', 'Falha de Autenticação', 'Unknown', '203.0.113.45', '5 tentativas falhas de login para conta admin', Components.statusBadge('Alta', 'error')]
        ];
        
        const securityActions = [
            { title: 'Ver Detalhes', icon: '🔍' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Segurança e Auditoria</h3>
                    <div class="form-help">Configure logs de segurança e auditoria para monitorar o acesso a dados sensíveis.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Auditoria</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Ativar logs de auditoria', true)}
                        ${Components.toggleSwitch('Registrar acesso a dados sensíveis', true)}
                        ${Components.toggleSwitch('Registrar alterações em permissões', true)}
                        ${Components.toggleSwitch('Registrar falhas de autenticação', true)}
                        ${Components.toggleSwitch('Registrar exportação de dados', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Campos Sensíveis para Auditoria</label>
                    <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px;">
                        <span class="badge">CPF</span>
                        <span class="badge">RG</span>
                        <span class="badge">Endereço</span>
                        <span class="badge">Contatos</span>
                        <span class="badge">Informações financeiras</span>
                        <span class="badge">Dados de saúde</span>
                        <span class="badge">Informações escolares</span>
                    </div>
                    <input type="text" class="form-input" placeholder="Digite e pressione Enter para adicionar...">
                </div>
                
                <div class="form-row">
                    <label class="form-label">Eventos de Segurança Recentes</label>
                    ${Components.dataTable(
                        ['Data e Hora', 'Tipo', 'Usuário', 'IP', 'Descrição', 'Severidade', 'Detalhes'],
                        securityEvents,
                        securityActions
                    )}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Retenção de Logs de Auditoria</label>
                    <select class="form-select">
                        <option>90 dias</option>
                        <option selected>180 dias</option>
                        <option>1 ano</option>
                        <option>2 anos</option>
                        <option>5 anos</option>
                    </select>
                    <div class="form-help">Período de retenção para logs de auditoria de segurança.</div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de alertas
    renderAlerts: function() {
        const alertConfigs = [
            ['Falha de login repetida', 'Mais de 5 tentativas falhas em 10 minutos', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard', Components.statusBadge('Ativo', 'active')],
            ['Acesso a dados sensíveis', 'Acesso em massa a dados de alunos', Components.statusBadge('Média', 'warning'), 'E-mail, Dashboard', Components.statusBadge('Ativo', 'active')],
            ['Falha de backup', 'Backup automático falhou', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard, SMS', Components.statusBadge('Ativo', 'active')],
            ['Erro crítico do sistema', 'Erro que afeta funcionalidades principais', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard, SMS', Components.statusBadge('Ativo', 'active')]
        ];
        
        const alertActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Desativar', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Alertas</h3>
                    <div class="form-help">Configure alertas automáticos para eventos importantes do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Canais de Notificação</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('E-mail', true)}
                        ${Components.toggleSwitch('Notificação no Dashboard', true)}
                        ${Components.toggleSwitch('SMS', false)}
                        ${Components.toggleSwitch('Webhook', false)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">E-mails para Alertas</label>
                    <input type="text" class="form-input" value="admin@superacao.org.br, seguranca@superacao.org.br">
                    <div class="form-help">Separe múltiplos e-mails com vírgulas.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Alertas</label>
                    ${Components.dataTable(
                        ['Tipo de Alerta', 'Descrição', 'Severidade', 'Canais', 'Status', 'Ações'],
                        alertConfigs,
                        alertActions
                    )}
                    <button class="btn btn-outline" style="margin-top: 10px;">➕ Novo Alerta</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'atividades', label: 'Atividades de Usuários', content: this.renderUserActivities(), active: true },
            { id: 'sistema', label: 'Logs do Sistema', content: this.renderSystemLogs(), active: false },
            { id: 'seguranca', label: 'Segurança e Auditoria', content: this.renderSecurityAudit(), active: false },
            { id: 'alertas', label: 'Configurações de Alertas', content: this.renderAlerts(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section" id="logs">
                <h2 class="settings-title">Logs e Atividades</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Gerenciador de aplicação
const App = {
    modules: {
        backup: BackupModule,
        logs: LogsModule
    },
    
    // Inicializa a aplicação
    init: function() {
        this.renderNavigation();
        this.activateSection('backup'); // Seção padrão
        this.setupEventListeners();
    },
    
    // Configura event listeners
    setupEventListeners: function() {
        // Adicionar event listeners para os itens de navegação
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const sectionId = e.currentTarget.getAttribute('data-section');
                this.activateSection(sectionId);
            });
        });
        
        // Event listener para o botão salvar
        document.getElementById('save-settings').addEventListener('click', () => {
            this.saveSettings();
        });
        
        // Adicionar event listeners para as tabs (delegação de eventos)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('tab') || e.target.parentElement.classList.contains('tab')) {
                const tab = e.target.classList.contains('tab') ? e.target : e.target.parentElement;
                const tabId = tab.getAttribute('data-tab');
                this.activateTab(tab, tabId);
            }
        });
    },
    
    // Ativa uma tab específica
    activateTab: function(tabElement, tabId) {
        // Encontrar o contêiner de tabs pai
        const tabsContainer = tabElement.parentElement;
        const tabContentContainer = tabsContainer.parentElement;
        
        // Desativar todas as tabs no mesmo contêiner
        tabsContainer.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Desativar todos os conteúdos de tab
        tabContentContainer.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Ativar a tab clicada
        tabElement.classList.add('active');
        
        // Ativar o conteúdo correspondente
        const tabContent = tabContentContainer.querySelector(`#${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }
    },
    
    // Ativa uma seção específica
    activateSection: function(sectionId) {
        // Remover classe active de todas as seções e itens de navegação
        document.querySelectorAll('.settings-section').forEach(section => {
            section.classList.remove('active');
        });
        
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Ativar o item de navegação correspondente
        const navItem = document.querySelector(`.settings-nav-item[data-section="${sectionId}"]`);
        if (navItem) {
            navItem.classList.add('active');
        }
        
        // Renderizar e ativar a seção
        this.renderSection(sectionId);
    },
    
    // Renderiza uma seção específica
    renderSection: function(sectionId) {
        const module = this.modules[sectionId];
        
        // Se o módulo existe, renderize seu conteúdo
        if (module) {
            const contentContainer = document.querySelector('.settings-content');
            contentContainer.innerHTML = module.render();
            
            // Ativar a primeira tab se existir
            const firstTab = document.querySelector('.settings-content .tab');
            if (firstTab) {
                const tabId = firstTab.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            }
        } else {
            console.warn(`Módulo '${sectionId}' não encontrado.`);
        }
    },
    
    // Salva as configurações
    saveSettings: function() {
        // Simulação de salvamento
        console.log('Salvando configurações...');
        
        // Mostrar feedback ao usuário
        alert('Configurações salvas com sucesso!');
    },
    
    // Renderiza a navegação lateral
    renderNavigation: function() {
        const navItems = [
            { id: 'geral', icon: '⚙️', label: 'Configurações Gerais' },
            { id: 'aparencia', icon: '🎨', label: 'Aparência' },
            { id: 'unidades', icon: '🏢', label: 'Unidades' },
            { id: 'turmas', icon: '👥', label: 'Turmas' },
            { id: 'usuarios', icon: '👤', label: 'Usuários e Permissões' },
            { id: 'formularios', icon: '📋', label: 'Personalização de Formulários' },
            { id: 'comunicacao', icon: '📱', label: 'Comunicação' },
            { id: 'carteirinhas', icon: '🪪', label: 'Carteirinhas' },
            { id: 'integracao', icon: '🔌', label: 'Integrações' },
            { id: 'campanha', icon: '🗳️', label: 'Configurações de Campanha' },
            { id: 'backup', icon: '💾', label: 'Backup e Dados', active: true },
            { id: 'logs', icon: '📊', label: 'Logs e Atividades' }
        ];
        
        const navHtml = navItems.map(item => `
            <div class="settings-nav-item ${item.active ? 'active' : ''}" data-section="${item.id}">
                <i>${item.icon}</i>
                <span>${item.label}</span>
            </div>
        `).join('');
        
        document.querySelector('.settings-nav').innerHTML = navHtml;
    }
};

// Iniciar a aplicação quando o DOM estiver pronto

document.addEventListener('DOMContentLoaded', function() {
    App.init();
});

    
    // Renderiza a tab de logs do sistema
    renderSystemLogs: function() {
        const systemLogsData = [
            ['29/09/2024 10:55:12', '<span class="level-indicator level-info"></span> Info', 'Backup', 'Backup configuration updated by user Carlos Oliveira', 'BackupController.php', '145'],
            ['29/09/2024 10:32:15', '<span class="level-indicator level-info"></span> Info', 'Authentication', 'User Carlos Oliveira logged in successfully', 'AuthController.php', '78'],
            ['29/09/2024 02:00:03', '<span class="level-indicator level-info"></span> Info', 'Backup', 'Automated backup started', 'BackupService.php', '210'],
            ['29/09/2024 02:05:48', '<span class="level-indicator level-info"></span> Info', 'Backup', 'Automated backup completed successfully', 'BackupService.php', '342'],
            ['28/09/2024 18:32:45', '<span class="level-indicator level-warning"></span> Warning', 'Database', 'Slow query detected (>1s): SELECT * FROM matriculas WHERE...', 'MatriculaRepository.php', '156']
        ];
        
        const logActions = [
            { title: 'Ver Detalhes', icon: '🔍' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Logs do Sistema</h3>
                    <div class="form-help">Visualize os logs técnicos do sistema para diagnóstico e resolução de problemas.</div>
                </div>
                
                <div class="form-row">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Nível</div>
                            <select class="form-select">
                                <option>Todos os níveis</option>
                                <option>Info</option>
                                <option>Warning</option>
                                <option>Error</option>
                                <option>Debug</option>
                                <option>Critical</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Componente</div>
                            <select class="form-select">
                                <option>Todos os componentes</option>
                                <option>Database</option>
                                <option>Authentication</option>
                                <option>API</option>
                                <option>FileSystem</option>
                                <option>Cache</option>
                                <option>Email</option>
                                <option>Integration</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Período</div>
                            <select class="form-select">
                                <option>Últimas 24 horas</option>
                                <option>Últimos 7 dias</option>
                                <option>Últimos 30 dias</option>
                                <option>Personalizado</option>
                            </select>
                        </div>
                        <div style="flex: 1; min-width: 150px;">
                            <div class="form-help">Pesquisar</div>
                            <input type="text" class="form-input" placeholder="Termo de busca...">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 15px;">
                        <button class="btn btn-outline">Limpar Filtros</button>
                        <button class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </div>
                
                <div class="form-row">
                    ${Components.dataTable(
                        ['Data e Hora', 'Nível', 'Componente', 'Mensagem', 'Arquivo', 'Linha', 'Detalhes'],
                        systemLogsData,
                        logActions
                    )}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div>
                            <button class="btn btn-outline">⬇️ Exportar Logs</button>
                        </div>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <button class="btn btn-outline btn-sm">«</button>
                            <button class="btn btn-outline btn-sm" style="background-color: var(--primary); color: white;">1</button>
                            <button class="btn btn-outline btn-sm">2</button>
                            <button class="btn btn-outline btn-sm">3</button>
                            <button class="btn btn-outline btn-sm">»</button>
                            <span style="margin-left: 10px; color: var(--gray); font-size: 14px;">Mostrando 1-5 de 1245 registros</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Log</label>
                    <div style="margin-top: 10px;">
                        ${Components.formGrid([
                            { 
                                label: 'Nível Mínimo de Log', 
                                content: `
                                    <select class="form-select">
                                        <option>Debug</option>
                                        <option selected>Info</option>
                                        <option>Warning</option>
                                        <option>Error</option>
                                        <option>Critical</option>
                                    </select>
                                `
                            },
                            { 
                                label: 'Retenção de Logs', 
                                content: `
                                    <select class="form-select">
                                        <option>30 dias</option>
                                        <option>60 dias</option>
                                        <option selected>90 dias</option>
                                        <option>180 dias</option>
                                        <option>365 dias</option>
                                    </select>
                                `
                            }
                        ])}
                    </div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de segurança e auditoria
    renderSecurityAudit: function() {
        const securityEvents = [
            ['29/09/2024 09:45:28', 'Acesso a Dados Sensíveis', 'Carlos Oliveira', '192.168.1.10', 'Visualização de informações financeiras de 3 alunos', Components.statusBadge('Média', 'warning')],
            ['28/09/2024 17:22:45', 'Exportação em Massa', 'Fernando Souza', '192.168.1.40', 'Exportação de dados de 230 alunos', Components.statusBadge('Média', 'warning')],
            ['28/09/2024 15:30:12', 'Alteração de Permissões', 'Carlos Oliveira', '192.168.1.10', 'Modificação nas permissões do usuário Ricardo Santos', Components.statusBadge('Média', 'warning')],
            ['27/09/2024 10:15:30', 'Falha de Autenticação', 'Unknown', '203.0.113.45', '5 tentativas falhas de login para conta admin', Components.statusBadge('Alta', 'error')]
        ];
        
        const securityActions = [
            { title: 'Ver Detalhes', icon: '🔍' }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Segurança e Auditoria</h3>
                    <div class="form-help">Configure logs de segurança e auditoria para monitorar o acesso a dados sensíveis.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Auditoria</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('Ativar logs de auditoria', true)}
                        ${Components.toggleSwitch('Registrar acesso a dados sensíveis', true)}
                        ${Components.toggleSwitch('Registrar alterações em permissões', true)}
                        ${Components.toggleSwitch('Registrar falhas de autenticação', true)}
                        ${Components.toggleSwitch('Registrar exportação de dados', true)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Campos Sensíveis para Auditoria</label>
                    <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px;">
                        <span class="badge">CPF</span>
                        <span class="badge">RG</span>
                        <span class="badge">Endereço</span>
                        <span class="badge">Contatos</span>
                        <span class="badge">Informações financeiras</span>
                        <span class="badge">Dados de saúde</span>
                        <span class="badge">Informações escolares</span>
                    </div>
                    <input type="text" class="form-input" placeholder="Digite e pressione Enter para adicionar...">
                </div>
                
                <div class="form-row">
                    <label class="form-label">Eventos de Segurança Recentes</label>
                    ${Components.dataTable(
                        ['Data e Hora', 'Tipo', 'Usuário', 'IP', 'Descrição', 'Severidade', 'Detalhes'],
                        securityEvents,
                        securityActions
                    )}
                </div>
                
                <div class="form-row">
                    <label class="form-label">Retenção de Logs de Auditoria</label>
                    <select class="form-select">
                        <option>90 dias</option>
                        <option selected>180 dias</option>
                        <option>1 ano</option>
                        <option>2 anos</option>
                        <option>5 anos</option>
                    </select>
                    <div class="form-help">Período de retenção para logs de auditoria de segurança.</div>
                </div>
            </div>
        `;
    },
    
    // Renderiza a tab de alertas
    renderAlerts: function() {
        const alertConfigs = [
            ['Falha de login repetida', 'Mais de 5 tentativas falhas em 10 minutos', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard', Components.statusBadge('Ativo', 'active')],
            ['Acesso a dados sensíveis', 'Acesso em massa a dados de alunos', Components.statusBadge('Média', 'warning'), 'E-mail, Dashboard', Components.statusBadge('Ativo', 'active')],
            ['Falha de backup', 'Backup automático falhou', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard, SMS', Components.statusBadge('Ativo', 'active')],
            ['Erro crítico do sistema', 'Erro que afeta funcionalidades principais', Components.statusBadge('Alta', 'error'), 'E-mail, Dashboard, SMS', Components.statusBadge('Ativo', 'active')]
        ];
        
        const alertActions = [
            { title: 'Editar', icon: '✏️' },
            { title: 'Desativar', icon: '❌', danger: true }
        ];
        
        return `
            <div class="form-row">
                <div style="margin-bottom: 15px;">
                    <h3>Configurações de Alertas</h3>
                    <div class="form-help">Configure alertas automáticos para eventos importantes do sistema.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Canais de Notificação</label>
                    <div style="margin-top: 10px;">
                        ${Components.toggleSwitch('E-mail', true)}
                        ${Components.toggleSwitch('Notificação no Dashboard', true)}
                        ${Components.toggleSwitch('SMS', false)}
                        ${Components.toggleSwitch('Webhook', false)}
                    </div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">E-mails para Alertas</label>
                    <input type="text" class="form-input" value="admin@superacao.org.br, seguranca@superacao.org.br">
                    <div class="form-help">Separe múltiplos e-mails com vírgulas.</div>
                </div>
                
                <div class="form-row">
                    <label class="form-label">Configurações de Alertas</label>
                    ${Components.dataTable(
                        ['Tipo de Alerta', 'Descrição', 'Severidade', 'Canais', 'Status', 'Ações'],
                        alertConfigs,
                        alertActions
                    )}
                    <button class="btn btn-outline" style="margin-top: 10px;">➕ Novo Alerta</button>
                </div>
            </div>
        `;
    },
    
    // Renderiza o módulo completo
    render: function() {
        const tabsContent = [
            { id: 'atividades', label: 'Atividades de Usuários', content: this.renderUserActivities(), active: true },
            { id: 'sistema', label: 'Logs do Sistema', content: this.renderSystemLogs(), active: false },
            { id: 'seguranca', label: 'Segurança e Auditoria', content: this.renderSecurityAudit(), active: false },
            { id: 'alertas', label: 'Configurações de Alertas', content: this.renderAlerts(), active: false }
        ];
        
        const tabsComponent = Components.tabs(tabsContent);
        
        return `
            <div class="settings-section" id="logs">
                <h2 class="settings-title">Logs e Atividades</h2>
                ${tabsComponent.tabs}
                ${tabsComponent.content}
            </div>
        `;
    }
};

// Gerenciador de aplicação
const App = {
    // Armazena referências a todos os módulos
    modules: {
        backup: BackupModule,
        logs: LogsModule
        // Adicione outros módulos aqui
    },
    
    // Inicializa a aplicação
    init: function() {
        this.renderNavigation();
        this.activateSection('backup'); // Seção padrão
        this.setupEventListeners();
    },
    
    // Configura event listeners
    setupEventListeners: function() {
        // Adicionar event listeners para os itens de navegação
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const sectionId = e.currentTarget.getAttribute('data-section');
                this.activateSection(sectionId);
            });
        });
        
        // Event listener para o botão salvar
        document.getElementById('save-settings').addEventListener('click', () => {
            this.saveSettings();
        });
        
        // Adicionar event listeners para as tabs (delegação de eventos)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('tab') || e.target.parentElement.classList.contains('tab')) {
                const tab = e.target.classList.contains('tab') ? e.target : e.target.parentElement;
                const tabId = tab.getAttribute('data-tab');
                this.activateTab(tab, tabId);
            }
        });
    },
    
    // Ativa uma tab específica
    activateTab: function(tabElement, tabId) {
        // Encontrar o contêiner de tabs pai
        const tabsContainer = tabElement.parentElement;
        const tabContentContainer = tabsContainer.parentElement;
        
        // Desativar todas as tabs no mesmo contêiner
        tabsContainer.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Desativar todos os conteúdos de tab
        tabContentContainer.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Ativar a tab clicada
        tabElement.classList.add('active');
        
        // Ativar o conteúdo correspondente
        const tabContent = tabContentContainer.querySelector(`#${tabId}`);
        if (tabContent) {
            tabContent.classList.add('active');
        }
    },
    
    // Ativa uma seção específica
    activateSection: function(sectionId) {
        // Remover classe active de todas as seções e itens de navegação
        document.querySelectorAll('.settings-section').forEach(section => {
            section.classList.remove('active');
        });
        
        document.querySelectorAll('.settings-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Ativar o item de navegação correspondente
        const navItem = document.querySelector(`.settings-nav-item[data-section="${sectionId}"]`);
        if (navItem) {
            navItem.classList.add('active');
        }
        
        // Renderizar e ativar a seção
        this.renderSection(sectionId);
    },
    
    // Renderiza uma seção específica
    renderSection: function(sectionId) {
        const module = this.modules[sectionId];
        
        // Se o módulo existe, renderize seu conteúdo
        if (module) {
            const contentContainer = document.querySelector('.settings-content');
            contentContainer.innerHTML = module.render();
            
            // Ativar a primeira tab se existir
            const firstTab = document.querySelector('.settings-content .tab');
            if (firstTab) {
                const tabId = firstTab.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            }
        } else {
            console.warn(`Módulo '${sectionId}' não encontrado.`);
        }
    },
    
    // Salva as configurações
    saveSettings: function() {
        // Simulação de salvamento
        console.log('Salvando configurações...');
        
        // Mostrar feedback ao usuário
        alert('Configurações salvas com sucesso!');
    },
    
    // Renderiza a navegação lateral
    renderNavigation: function() {
        const navItems = [
            { id: 'geral', icon: '⚙️', label: 'Configurações Gerais' },
            { id: 'aparencia', icon: '🎨', label: 'Aparência' },
            { id: 'unidades', icon: '🏢', label: 'Unidades' },
            { id: 'turmas', icon: '👥', label: 'Turmas' },
            { id: 'usuarios', icon: '👤', label: 'Usuários e Permissões' },
            { id: 'formularios', icon: '📋', label: 'Personalização de Formulários' },
            { id: 'comunicacao', icon: '📱', label: 'Comunicação' },
            { id: 'carteirinhas', icon: '🪪', label: 'Carteirinhas' },
            { id: 'integracao', icon: '🔌', label: 'Integrações' },
            { id: 'campanha', icon: '🗳️', label: 'Configurações de Campanha' },
            { id: 'backup', icon: '💾', label: 'Backup e Dados', active: true },
            { id: 'logs', icon: '📊', label: 'Logs e Atividades' }
        ];
        
        const navHtml = navItems.map(item => `
            <div class="settings-nav-item ${item.active ? 'active' : ''}" data-section="${item.id}">
                <i>${item.icon}</i>
                <span>${item.label}</span>
            </div>
        `).join('');
        
        document.querySelector('.settings-nav').innerHTML = navHtml;
    }
};

// Iniciar a aplicação quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    App.init();
});