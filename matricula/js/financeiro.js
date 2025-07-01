// financeiro.js - VERSÃO FINAL CORRIGIDA - Módulo Financeiro para Controle de Materiais

class ModuloFinanceiro {
  constructor() {
    this.materiais = {
      uniforme: ['calça', 'camiseta', 'calção', 'calçado', 'maiô', 'sunga', 'boné', 'jaqueta'],
      material_didatico: ['livro', 'apostila', 'caderno', 'lápis', 'borracha', 'régua', 'estojo'],
      equipamento: ['mochila', 'squeeze', 'prancheta', 'megafone', 'apito', 'cone']
    };
    
    // Definir caminho da API
    this.apiPath = '../matricula/api/financeiro_api.php';
    
    this.init();
  }

  init() {
    console.log('🚀 Inicializando Módulo Financeiro...');
    
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.setupEventListeners();
      });
    } else {
      this.setupEventListeners();
    }
  }

  setupEventListeners() {
    console.log('🔧 Configurando event listeners...');

    // Evento para abrir o modal
    const btnModal = document.getElementById('modulo-financeiro-btn');
    if (btnModal) {
      btnModal.addEventListener('click', () => {
        console.log('📊 Abrindo módulo financeiro...');
        this.openModal();
      });
      console.log('✅ Botão encontrado e configurado');
    } else {
      console.warn('⚠️ Botão não encontrado');
    }

    // Configurar eventos do modal após um pequeno delay
    setTimeout(() => {
      this.setupModalEvents();
    }, 100);
  }

  setupModalEvents() {
    const modal = document.getElementById('modulo-financeiro-modal');
    if (!modal) {
      console.warn('⚠️ Modal não encontrado ainda...');
      return;
    }

    // Eventos das abas - usando delegação de eventos
    modal.addEventListener('click', (e) => {
      const tabBtn = e.target.closest('.tab-btn');
      if (tabBtn) {
        e.preventDefault();
        const tab = tabBtn.dataset.tab;
        if (tab) {
          this.switchTab(tab);
        }
      }
    });

    // Eventos dos formulários
    const entradaForm = modal.querySelector('#entrada-material-form');
    if (entradaForm) {
      entradaForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.registrarEntrada(e.target);
      });
    }

    const saidaForm = modal.querySelector('#saida-material-form');
    if (saidaForm) {
      saidaForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.registrarSaida(e.target);
      });
    }

    // Eventos de mudança de campos
    this.setupFieldEvents(modal);
    this.setupButtonEvents(modal);

    console.log('✅ Eventos do modal configurados com delegação');
  }

  setupFieldEvents(modal) {
    // Tipo de material - Entrada
    const tipoEntrada = modal.querySelector('#tipo-material-entrada');
    if (tipoEntrada) {
      tipoEntrada.addEventListener('change', (e) => {
        this.updateItensSelect('entrada', e.target.value);
      });
    }

    // Tipo de material - Saída  
    const tipoSaida = modal.querySelector('#tipo-material-saida');
    if (tipoSaida) {
      tipoSaida.addEventListener('change', (e) => {
        this.updateItensSelect('saida', e.target.value);
      });
    }

    // Item - Entrada
    const itemEntrada = modal.querySelector('#item-entrada');
    if (itemEntrada) {
      itemEntrada.addEventListener('change', (e) => {
        this.toggleTamanhoField('entrada', e.target.value);
      });
    }

    // Item - Saída
    const itemSaida = modal.querySelector('#item-saida');
    if (itemSaida) {
      itemSaida.addEventListener('change', (e) => {
        this.toggleTamanhoField('saida', e.target.value);
      });
    }

    // Aluno - Saída
    const alunoSaida = modal.querySelector('#aluno-saida');
    if (alunoSaida) {
      alunoSaida.addEventListener('change', (e) => {
        this.loadTurmaByAluno(e.target.value);
      });
    }
    
    console.log('✅ Eventos de campos configurados');
  }

  setupButtonEvents(modal) {
    // Botão testar carregamento
    const btnTeste = modal.querySelector('#atualizar-estoque');
    if (btnTeste) {
      btnTeste.addEventListener('click', () => {
        this.testApi();
      });
    }

    // Botão imprimir romaneio
    const btnImprimir = modal.querySelector('#imprimir-romaneio');
    if (btnImprimir) {
      btnImprimir.addEventListener('click', () => {
        this.imprimirRomaneio();
      });
    }
  }

  openModal() {
    let modal = document.getElementById('modulo-financeiro-modal');
    
    if (!modal) {
      console.log('🏗️ Criando modal dinamicamente...');
      this.createModalDynamically();
      modal = document.getElementById('modulo-financeiro-modal');
    }
    
    if (modal) {
      modal.style.display = 'block';
      
      // Sempre reconfigurar eventos para evitar conflitos
      if (!modal.dataset.eventsConfigured) {
        console.log('🔧 Configurando eventos do modal...');
        this.setupModalEvents();
        modal.dataset.eventsConfigured = 'true';
      }
      
      // Garantir que a aba "entrada" seja a ativa inicialmente
      this.switchTab('entrada');
      
      // Carregar dados iniciais necessários
      this.loadInitialData();
      
      console.log('✅ Modal aberto e configurado');
    } else {
      console.error('❌ Erro ao criar/encontrar modal');
    }
  }

  createModalDynamically() {
    const modalHTML = `
    <div id="modulo-financeiro-modal" class="modal-backdrop" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
      <div class="modal" style="position: relative; background: white; margin: 50px auto; width: 90%; max-width: 1200px; border-radius: 8px; overflow: hidden;">
        
        <div class="modal-header" style="background: #e74c3c; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 1.2rem; font-weight: bold; color: #ffffff;">
            <i class="fas fa-calculator"></i> Módulo Financeiro - Controle de Materiais
          </span>
          <button onclick="document.getElementById('modulo-financeiro-modal').style.display='none'" 
                  style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <div class="modal-body" style="padding: 30px;">
          
          <!-- Abas do Módulo -->
          <div class="financeiro-tabs" style="display: flex; border-bottom: 2px solid #e0e0e0; margin-bottom: 20px;">
            <button class="tab-btn active" data-tab="entrada" 
                    style="padding: 12px 20px; border: none; background: none; cursor: pointer; border-bottom: 3px solid #007bff; font-weight: 500; color: #007bff;">
              <i class="fas fa-plus-circle"></i> Entrada
            </button>
            <button class="tab-btn" data-tab="saida" 
                    style="padding: 12px 20px; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 500; color: #666;">
              <i class="fas fa-minus-circle"></i> Saída
            </button>
    

          </div>

          <!-- Aba Entrada (Ativa por padrão) -->
          <div id="entrada-tab" class="tab-pane active" style="display: block;">
            <h3 style="margin-bottom: 20px;">Entrada de Materiais</h3>
            
            <form id="entrada-material-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tipo de Material *</label>
                <select id="tipo-material-entrada" name="tipo_material" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o tipo</option>
                  <option value="uniforme">Uniforme</option>
                  <option value="material_didatico">Material Didático</option>
                  <option value="equipamento">Equipamento</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Item *</label>
                <select id="item-entrada" name="item" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione primeiro o tipo</option>
                </select>
              </div>
              
              <div id="tamanho-grupo-entrada" style="display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tamanho</label>
                <select id="tamanho-entrada" name="tamanho" 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o tamanho</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Quantidade *</label>
                <input type="number" id="quantidade-entrada" name="quantidade" min="1" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Valor Unitário (R$)</label>
                <input type="number" id="valor-unitario-entrada" name="valor_unitario" step="0.01" min="0" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Fornecedor</label>
                <input type="text" id="fornecedor-entrada" name="fornecedor" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
              </div>
              
              <div style="grid-column: 1 / -1;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Observações</label>
                <textarea name="observacoes" rows="3" 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
              </div>
              
              <div style="grid-column: 1 / -1; margin-top: 20px;">
                <button type="submit" style="padding: 12px 30px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                  <i class="fas fa-save"></i> Registrar Entrada
                </button>
              </div>
            </form>
          </div>

          <!-- Aba Saída -->
          <div id="saida-tab" class="tab-pane" style="display: none;">
            <h3 style="margin-bottom: 20px;">Saída de Materiais</h3>
            
            <form id="saida-material-form" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tipo de Material *</label>
                <select id="tipo-material-saida" name="tipo_material" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o tipo</option>
                  <option value="uniforme">Uniforme</option>
                  <option value="material_didatico">Material Didático</option>
                  <option value="equipamento">Equipamento</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Item *</label>
                <select id="item-saida" name="item" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione primeiro o tipo</option>
                </select>
              </div>
              
              <div id="tamanho-grupo-saida" style="display: none;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tamanho</label>
                <select id="tamanho-saida" name="tamanho" 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o tamanho</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Aluno *</label>
                <select id="aluno-saida" name="aluno_id" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Carregando alunos...</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Turma *</label>
                <select id="turma-saida" name="turma_id" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o aluno primeiro</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Quantidade *</label>
                <input type="number" name="quantidade" min="1" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
              </div>
              
              <div style="grid-column: 1 / -1;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Motivo da Saída *</label>
                <select name="motivo" required 
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                  <option value="">Selecione o motivo</option>
                  <option value="entrega_uniforme">Entrega de Uniforme</option>
                  <option value="material_didatico">Material Didático</option>
                  <option value="equipamento_atividade">Equipamento para Atividade</option>
                  <option value="reposicao">Reposição</option>
                  <option value="outros">Outros</option>
                </select>
              </div>
              
              <div style="grid-column: 1 / -1;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Observações</label>
                <textarea name="observacoes" rows="3" 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
              </div>
              
              <div style="grid-column: 1 / -1; margin-top: 20px;">
                <button type="submit" style="padding: 12px 30px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                  <i class="fas fa-save"></i> Registrar Saída
                </button>
              </div>
            </form>
          </div>

          <!-- Aba Estoque -->
          <div id="estoque-tab" class="tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
              <h3 style="margin: 0;">Controle de Estoque</h3>
              <button id="atualizar-estoque" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-sync"></i> Testar API
              </button>
            </div>
            
            <div id="status-conexao" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
              <h4>Status da Conexão:</h4>
              <div>Clique em "Testar API" para verificar a conexão</div>
            </div>
            
            <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
              <table id="tabela-estoque" style="width: 100%; border-collapse: collapse;">
                <thead>
                  <tr style="background: #343a40; color: white;">
                    <th style="padding: 10px; text-align: left;">Tipo</th>
                    <th style="padding: 10px; text-align: left;">Item</th>
                    <th style="padding: 10px; text-align: left;">Tamanho</th>
                    <th style="padding: 10px; text-align: left;">Quantidade</th>
                    <th style="padding: 10px; text-align: left;">Valor Unit.</th>
                    <th style="padding: 10px; text-align: left;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="6" style="padding: 20px; text-align: center;">Nenhum dado carregado</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Aba Romaneio -->
          <div id="romaneio-tab" class="tab-pane" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
              <h3 style="margin: 0;">Romaneio de Uniformes</h3>
              <button id="imprimir-romaneio" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Imprimir
              </button>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
              <p>Funcionalidade de romaneio em desenvolvimento...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
    .tab-btn:hover { 
      background: #f8f9fa !important; 
      color: #007bff !important;
    }
    .tab-btn.active { 
      border-bottom-color: #007bff !important; 
      background: #f8f9fa !important; 
      color: #007bff !important;
    }
    .tab-pane { 
      display: none; 
    }
    .tab-pane.active { 
      display: block !important; 
    }
    </style>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    console.log('✅ Modal criado dinamicamente com estrutura corrigida');
  }

  switchTab(tabName) {
    console.log(`🔀 Alternando para aba: ${tabName}`);
    
    // Remove classe ativa de todos os botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.remove('active');
      btn.style.borderBottomColor = 'transparent';
      btn.style.background = 'none';
      btn.style.color = '#666';
    });
    
    // Oculta todas as abas
    document.querySelectorAll('.tab-pane').forEach(pane => {
      pane.classList.remove('active');
      pane.style.display = 'none';
    });

    // Ativa o botão da aba selecionada
    const tabBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (tabBtn) {
      tabBtn.classList.add('active');
      tabBtn.style.borderBottomColor = '#007bff';
      tabBtn.style.background = '#f8f9fa';
      tabBtn.style.color = '#007bff';
    }
    
    // Mostra a aba selecionada
    const tabPane = document.getElementById(`${tabName}-tab`);
    if (tabPane) {
      tabPane.classList.add('active');
      tabPane.style.display = 'block';
    }

    // Carregar dados específicos para cada aba
    switch (tabName) {
      case 'estoque':
        // Só carrega estoque se não foi carregado ainda ou se precisa atualizar
        this.loadEstoque();
        break;
      case 'saida':
        // Carrega alunos para o select da aba de saída
        this.loadAlunos();
        break;
      case 'entrada':
        // Reseta os selects da entrada se necessário
        this.resetEntradaForm();
        break;
      case 'romaneio':
        // Carrega dados do romaneio se necessário
        this.loadTurmasRomaneio();
        break;
    }
  }

  resetEntradaForm() {
    const form = document.getElementById('entrada-material-form');
    if (form) {
      // Reseta selects dependentes
      const itemSelect = document.getElementById('item-entrada');
      if (itemSelect) {
        itemSelect.innerHTML = '<option value="">Selecione primeiro o tipo</option>';
      }
      
      // Oculta campo de tamanho
      const tamanhoGrupo = document.getElementById('tamanho-grupo-entrada');
      if (tamanhoGrupo) {
        tamanhoGrupo.style.display = 'none';
      }
    }
  }

  loadTurmasRomaneio() {
    console.log('📋 Carregando dados do romaneio...');
  }

  updateItensSelect(tipo, tipoMaterial) {
    const select = document.getElementById(`item-${tipo}`);
    if (!select) return;

    select.innerHTML = '<option value="">Selecione o item</option>';

    if (tipoMaterial && this.materiais[tipoMaterial]) {
      this.materiais[tipoMaterial].forEach(item => {
        const option = document.createElement('option');
        option.value = item;
        option.textContent = item.charAt(0).toUpperCase() + item.slice(1);
        select.appendChild(option);
      });
    }
  }

  toggleTamanhoField(tipo, item) {
    const tamanhoGrupo = document.getElementById(`tamanho-grupo-${tipo}`);
    if (!tamanhoGrupo) return;

    const isUniforme = ['calça', 'camiseta', 'calção', 'calçado', 'maiô', 'sunga', 'boné', 'jaqueta'].includes(item);
    
    if (isUniforme) {
      tamanhoGrupo.style.display = 'block';
      this.updateTamanhosDisponiveis(tipo, item);
    } else {
      tamanhoGrupo.style.display = 'none';
    }
  }

  updateTamanhosDisponiveis(tipo, item) {
    const select = document.getElementById(`tamanho-${tipo}`);
    if (!select) return;
    
    const tamanhos = {
      'calçado': ['34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44'],
      'calça': ['PP', 'P', 'M', 'G', 'GG', 'XG'],
      'camiseta': ['PP', 'P', 'M', 'G', 'GG', 'XG'],
      'calção': ['PP', 'P', 'M', 'G', 'GG', 'XG'],
      'maiô': ['PP', 'P', 'M', 'G', 'GG'],
      'sunga': ['PP', 'P', 'M', 'G', 'GG'],
      'boné': ['Único'],
      'jaqueta': ['PP', 'P', 'M', 'G', 'GG', 'XG']
    };

    select.innerHTML = '<option value="">Selecione o tamanho</option>';
    
    if (tamanhos[item]) {
      tamanhos[item].forEach(tamanho => {
        const option = document.createElement('option');
        option.value = tamanho;
        option.textContent = tamanho;
        select.appendChild(option);
      });
    }
  }

  async testApi() {
    console.log('🧪 Testando conexão com API...');
    this.updateStatus('Testando conexão...', 'info');
    
    try {
      const response = await fetch(`${this.apiPath}?action=test`);
      console.log('📡 Response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const result = await response.json();
      console.log('📊 Resultado do teste:', result);
      
      if (result.success) {
        this.updateStatus('✅ Conexão estabelecida com sucesso!', 'success');
        this.showNotification('API funcionando corretamente!', 'success');
        
        // Carregar estoque após teste bem-sucedido
        setTimeout(() => {
          this.loadEstoque();
        }, 1000);
      } else {
        throw new Error(result.error || 'Teste falhou');
      }
    } catch (error) {
      console.error('❌ Erro no teste da API:', error);
      this.updateStatus(`❌ Erro: ${error.message}`, 'error');
      this.showNotification('Erro na conexão: ' + error.message, 'error');
    }
  }

  async loadEstoque() {
    try {
      console.log('📦 Carregando estoque...');
      this.updateStatus('Carregando estoque...', 'info');
      
      const response = await fetch(`${this.apiPath}?action=estoque`);
      const result = await response.json();

      if (result.success) {
        this.updateEstoqueTable(result.data);
        this.updateStatus(`✅ ${result.data.length} itens carregados`, 'success');
        this.showNotification(`${result.data.length} itens de estoque carregados!`, 'success');
      } else {
        throw new Error(result.error || 'Erro ao carregar estoque');
      }
    } catch (error) {
      console.error('❌ Erro ao carregar estoque:', error);
      this.updateStatus(`❌ Erro: ${error.message}`, 'error');
      this.showNotification('Erro ao carregar estoque: ' + error.message, 'error');
    }
  }

  updateEstoqueTable(data) {
    const tbody = document.querySelector('#tabela-estoque tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center;">Nenhum item no estoque</td></tr>';
      return;
    }

    data.forEach(item => {
      const row = document.createElement('tr');
      const status = this.getStatusEstoque(item.quantidade, item.quantidade_minima || 5);
      
      row.innerHTML = `
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">${this.formatarTipo(item.tipo_material)}</td>
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">${this.formatarItem(item.item)}</td>
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">${item.tamanho || '-'}</td>
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">${item.quantidade}</td>
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">R$ ${parseFloat(item.valor_unitario).toFixed(2)}</td>
        <td style="padding: 10px; border-bottom: 1px solid #ddd;">
          <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; ${status.style}">
            ${status.text}
          </span>
        </td>
      `;
      tbody.appendChild(row);
    });
  }

  async loadAlunos() {
    try {
      console.log('👥 Carregando alunos...');
      const response = await fetch(`${this.apiPath}?action=alunos`);
      const result = await response.json();

      if (result.success) {
        const select = document.getElementById('aluno-saida');
        if (select) {
          select.innerHTML = '<option value="">Selecione o aluno</option>';

          result.data.forEach(aluno => {
            const option = document.createElement('option');
            option.value = aluno.id;
            option.textContent = aluno.nome;
            option.dataset.turmaId = aluno.turma_id;
            option.dataset.turmaNome = aluno.turma_nome;
            select.appendChild(option);
          });
          
          this.showNotification(`${result.data.length} alunos carregados!`, 'success');
        }
      } else {
        throw new Error(result.error || 'Erro ao carregar alunos');
      }
    } catch (error) {
      console.error('❌ Erro ao carregar alunos:', error);
      this.showNotification('Erro ao carregar alunos: ' + error.message, 'error');
    }
  }

  loadTurmaByAluno(alunoId) {
    const alunoSelect = document.getElementById('aluno-saida');
    const turmaSelect = document.getElementById('turma-saida');
    
    if (!alunoSelect || !turmaSelect) return;
    
    turmaSelect.innerHTML = '<option value="">Selecione o aluno primeiro</option>';
    
    if (alunoId) {
      const selectedOption = alunoSelect.options[alunoSelect.selectedIndex];
      if (selectedOption) {
        const turmaId = selectedOption.dataset.turmaId;
        const turmaNome = selectedOption.dataset.turmaNome;
        
        if (turmaId && turmaNome) {
          turmaSelect.innerHTML = `<option value="${turmaId}">${turmaNome}</option>`;
          turmaSelect.value = turmaId;
        }
      }
    }
  }

  async registrarEntrada(form) {
    try {
      console.log('📥 Registrando entrada...');
      const formData = new FormData(form);
      const entrada = {
        tipo_material: formData.get('tipo_material'),
        item: formData.get('item'),
        tamanho: formData.get('tamanho') || null,
        quantidade: parseInt(formData.get('quantidade')),
        valor_unitario: parseFloat(formData.get('valor_unitario')) || 0,
        fornecedor: formData.get('fornecedor'),
        observacoes: formData.get('observacoes')
      };

      const response = await fetch(`${this.apiPath}?action=entrada`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(entrada)
      });

      const result = await response.json();

      if (result.success) {
        this.showNotification('Entrada registrada com sucesso!', 'success');
        form.reset();
        
        // Ocultar campo tamanho
        const tamanhoGrupo = document.getElementById('tamanho-grupo-entrada');
        if (tamanhoGrupo) tamanhoGrupo.style.display = 'none';
        
        // Resetar selects dependentes
        const itemSelect = document.getElementById('item-entrada');
        if (itemSelect) {
          itemSelect.innerHTML = '<option value="">Selecione primeiro o tipo</option>';
        }
        
        // Atualizar estoque se estivermos na aba de estoque
        const estoqueTab = document.getElementById('estoque-tab');
        if (estoqueTab && estoqueTab.style.display === 'block') {
          this.loadEstoque();
        }
      } else {
        throw new Error(result.error || 'Erro desconhecido');
      }
    } catch (error) {
      console.error('❌ Erro ao registrar entrada:', error);
      this.showNotification('Erro ao registrar entrada: ' + error.message, 'error');
    }
  }

  async registrarSaida(form) {
    try {
      console.log('📤 Registrando saída...');
      const formData = new FormData(form);
      const saida = {
        tipo_material: formData.get('tipo_material'),
        item: formData.get('item'),
        tamanho: formData.get('tamanho') || null,
        quantidade: parseInt(formData.get('quantidade')),
        aluno_id: formData.get('aluno_id'),
        turma_id: formData.get('turma_id'),
        motivo: formData.get('motivo'),
        observacoes: formData.get('observacoes')
      };

      // Validação básica
      if (!saida.tipo_material || !saida.item || !saida.quantidade || !saida.aluno_id || !saida.turma_id || !saida.motivo) {
        this.showNotification('Por favor, preencha todos os campos obrigatórios', 'warning');
        return;
      }

      const response = await fetch(`${this.apiPath}?action=saida`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(saida) // CORRIGIDO: era 'entrada' antes
      });

      const result = await response.json();

      if (result.success) {
        this.showNotification('Saída registrada com sucesso!', 'success');
        form.reset();
        
        // Ocultar campo tamanho
        const tamanhoGrupo = document.getElementById('tamanho-grupo-saida');
        if (tamanhoGrupo) tamanhoGrupo.style.display = 'none';
        
        // Resetar selects dependentes
        const itemSelect = document.getElementById('item-saida');
        if (itemSelect) {
          itemSelect.innerHTML = '<option value="">Selecione primeiro o tipo</option>';
        }
        
        const turmaSelect = document.getElementById('turma-saida');
        if (turmaSelect) {
          turmaSelect.innerHTML = '<option value="">Selecione o aluno primeiro</option>';
        }
        
        // Atualizar estoque se estivermos na aba de estoque
        const estoqueTab = document.getElementById('estoque-tab');
        if (estoqueTab && estoqueTab.style.display === 'block') {
          this.loadEstoque();
        }
      } else {
        throw new Error(result.error || 'Erro desconhecido');
      }
    } catch (error) {
      console.error('❌ Erro ao registrar saída:', error);
      this.showNotification('Erro ao registrar saída: ' + error.message, 'error');
    }
  }

  getStatusEstoque(quantidade, minima = 5) {
    if (quantidade === 0) {
      return { 
        text: 'Esgotado', 
        style: 'background: #f8d7da; color: #721c24;' 
      };
    } else if (quantidade <= minima) {
      return { 
        text: 'Baixo', 
        style: 'background: #fff3cd; color: #856404;' 
      };
    } else {
      return { 
        text: 'Disponível', 
        style: 'background: #d4edda; color: #155724;' 
      };
    }
  }

  updateStatus(message, type) {
    const statusDiv = document.getElementById('status-conexao');
    if (statusDiv) {
      const colors = {
        success: '#28a745',
        error: '#dc3545', 
        info: '#17a2b8'
      };
      
      statusDiv.innerHTML = `
        <div style="color: ${colors[type] || colors.info}; font-weight: bold;">
          ${message}
        </div>
      `;
    }
  }

  loadInitialData() {
    console.log('🚀 Carregando dados iniciais...');
    
    // Carregar alunos em background para quando precisar
    setTimeout(() => {
      this.loadAlunos();
    }, 500);
  }

  closeModal() {
    const modal = document.getElementById('modulo-financeiro-modal');
    if (modal) {
      modal.style.display = 'none';
      
      // Resetar formulários
      const forms = modal.querySelectorAll('form');
      forms.forEach(form => form.reset());
      
      // Ocultar campos condicionais
      const tamanhoGrupos = modal.querySelectorAll('[id*="tamanho-grupo"]');
      tamanhoGrupos.forEach(grupo => {
        grupo.style.display = 'none';
      });
      
      console.log('🚪 Modal fechado e resetado');
    }
  }

  formatarTipo(tipo) {
    const tipos = {
      'uniforme': 'Uniforme',
      'material_didatico': 'Material Didático',
      'equipamento': 'Equipamento'
    };
    return tipos[tipo] || tipo;
  }

  formatarItem(item) {
    return item.charAt(0).toUpperCase() + item.slice(1);
  }

  imprimirRomaneio() {
    this.showNotification('Função de impressão em desenvolvimento', 'info');
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
      border-radius: 8px;
      color: white;
      font-weight: 500;
      z-index: 10000;
      max-width: 400px;
      background: ${colors[type] || colors.info};
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      animation: slideIn 0.3s ease;
    `;

    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => notification.remove(), 300);
    }, 4000);

    // Adicionar CSS das animações
    if (!document.getElementById('notification-styles')) {
      const styles = document.createElement('style');
      styles.id = 'notification-styles';
      styles.textContent = `
        @keyframes slideIn {
          from { transform: translateX(100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
          from { transform: translateX(0); opacity: 1; }
          to { transform: translateX(100%); opacity: 0; }
        }
      `;
      document.head.appendChild(styles);
    }
  }
}

// Inicializar
console.log('🚀 Inicializando Módulo Financeiro Final...');
window.moduloFinanceiro = new ModuloFinanceiro();
window.ModuloFinanceiro = ModuloFinanceiro;