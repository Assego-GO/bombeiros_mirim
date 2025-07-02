// controle.js - Módulo de Controle de Materiais - VERSÃO COMPLETA COM UTF-8 E FUSO HORÁRIO BRASILEIRO CORRETO

class ModuloControle {
  constructor() {
    this.apiPath = '../matricula/api/financeiro_api.php';
    this.dados = {
      saidas: [],
      entradas: [],
      estoque: [],
      alunos: [],
      turmas: []
    };
    this.filtros = {
      tipo: '',
      data_inicial: '',
      data_final: ''
    };
    
    // 🇧🇷 CONFIGURAÇÕES PARA BRASIL COM FUSO HORÁRIO CORRETO
    this.configurarLocaleBrasil();
    this.init();
  }

  // 🇧🇷 Configuração completa para Brasil com correção de fuso horário
  configurarLocaleBrasil() {
    // Configurar timezone brasileiro - América/São Paulo (GMT-3)
    this.timezone = 'America/Sao_Paulo';
    
    // Offset do Brasil em relação ao UTC (em horas)
    this.offsetBrasil = -3; // Brasil está GMT-3
    
    // Configurar formatadores brasileiros com timezone correto
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
      second: '2-digit',
      hour12: false
    });
    
    this.formatadorDataHora = new Intl.DateTimeFormat('pt-BR', {
      timeZone: this.timezone,
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    });
    
    this.formatadorMoeda = new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
    
    console.log('🇧🇷 Configurações brasileiras aplicadas com fuso horário correto');
    console.log(`⏰ Timezone: ${this.timezone} (GMT${this.offsetBrasil})`);
    console.log(`⏰ Hora atual do Brasil: ${this.getHoraBrasil()}`);
  }

  // 🕒 FUNÇÃO PARA OBTER DATA/HORA ATUAL DO BRASIL
  getHoraBrasil() {
    const agora = new Date();
    return new Intl.DateTimeFormat('pt-BR', {
      timeZone: this.timezone,
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    }).format(agora);
  }

  // 🕒 FUNÇÃO PARA CONVERTER UTC PARA HORÁRIO BRASILEIRO
  converterUTCParaBrasil(utcDateString) {
    if (!utcDateString) return null;
    
    try {
      // Criar data a partir da string UTC
      let data = new Date(utcDateString);
      
      // Se a data não tem informação de timezone, assumir que é UTC
      if (!utcDateString.includes('Z') && !utcDateString.includes('+') && !utcDateString.includes('-')) {
        // Adicionar 'Z' para indicar UTC se não tiver timezone
        data = new Date(utcDateString + (utcDateString.includes('T') ? 'Z' : ''));
      }
      
      // Verificar se a data é válida
      if (isNaN(data.getTime())) {
        console.warn('Data inválida:', utcDateString);
        return new Date();
      }
      
      return data;
    } catch (error) {
      console.error('Erro ao converter data UTC para Brasil:', error);
      return new Date();
    }
  }

  // 🕒 FUNÇÃO PARA CRIAR DATA NO FUSO HORÁRIO BRASILEIRO
  criarDataBrasil(ano, mes, dia, hora = 0, minuto = 0, segundo = 0) {
    // Criar data no timezone brasileiro
    const data = new Date();
    data.setFullYear(ano);
    data.setMonth(mes - 1); // Mês é zero-based
    data.setDate(dia);
    data.setHours(hora, minuto, segundo, 0);
    
    // Ajustar para o timezone brasileiro
    const offsetLocal = data.getTimezoneOffset() * 60 * 1000; // offset local em ms
    const offsetBrasil = this.offsetBrasil * 60 * 60 * 1000; // offset brasil em ms
    
    return new Date(data.getTime() + offsetLocal + offsetBrasil);
  }

  init() {
    console.log('🎯 Inicializando Módulo de Controle...');
    
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.setupEventListeners();
      });
    } else {
      this.setupEventListeners();
    }
  }

  setupEventListeners() {
    console.log('🔧 Configurando event listeners do controle...');

    // Evento para abrir o modal de controle
    const btnControle = document.getElementById('saida-btn');
    if (btnControle) {
      btnControle.addEventListener('click', () => {
        console.log('📊 Abrindo controle de materiais...');
        this.openModal();
      });
      console.log('✅ Botão Controle de Materiais configurado');
    } else {
      console.warn('⚠️ Botão Controle de Materiais não encontrado');
    }

    // Configurar eventos do modal após um pequeno delay
    setTimeout(() => {
      this.setupModalEvents();
    }, 100);
  }

  setupModalEvents() {
    const modal = document.getElementById('controle-materiais-modal');
    if (!modal) {
      console.warn('⚠️ Modal de controle não encontrado ainda...');
      return;
    }

    // Eventos das abas
    modal.addEventListener('click', (e) => {
      const tabBtn = e.target.closest('.controle-tab-btn');
      if (tabBtn) {
        e.preventDefault();
        const tab = tabBtn.dataset.tab;
        if (tab) {
          this.switchTab(tab);
        }
      }
    });

    // Evento do formulário de filtros
    const filterForm = modal.querySelector('#controle-filter-form');
    if (filterForm) {
      filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.aplicarFiltros(e.target);
      });
    }

    // Evento do botão limpar filtros
    const btnLimpar = modal.querySelector('#limpar-filtros-controle');
    if (btnLimpar) {
      btnLimpar.addEventListener('click', () => {
        this.limparFiltros();
      });
    }

    // Evento do botão atualizar
    const btnAtualizar = modal.querySelector('#atualizar-dados-controle');
    if (btnAtualizar) {
      btnAtualizar.addEventListener('click', () => {
        this.carregarDados();
      });
    }

    // Evento do botão maximizar/minimizar
    const btnMaximizar = modal.querySelector('#maximizar-modal');
    if (btnMaximizar) {
      btnMaximizar.addEventListener('click', () => {
        this.toggleMaximizeModal();
      });
    }

    // Evento ESC para fechar modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.style.display === 'block') {
        this.closeModal();
      }
    });

    console.log('✅ Eventos do modal de controle configurados');
  }

  openModal() {
    let modal = document.getElementById('controle-materiais-modal');
    
    if (!modal) {
      console.log('🏗️ Criando modal de controle dinamicamente...');
      this.createModalDynamically();
      modal = document.getElementById('controle-materiais-modal');
    }
    
    if (modal) {
      modal.style.display = 'block';
      document.body.style.overflow = 'hidden'; // Previne scroll da página
      
      // Configurar eventos se necessário
      if (!modal.dataset.eventsConfigured) {
        console.log('🔧 Configurando eventos do modal de controle...');
        this.setupModalEvents();
        modal.dataset.eventsConfigured = 'true';
      }
      
      // Garantir que a aba "saidas" seja a ativa inicialmente
      this.switchTab('saidas');
      
      // Carregar dados iniciais
      this.carregarDados();
      
      console.log('✅ Modal de controle aberto e configurado');
      console.log(`⏰ Horário de abertura: ${this.getHoraBrasil()}`);
    } else {
      console.error('❌ Erro ao criar/encontrar modal de controle');
    }
  }

  closeModal() {
    const modal = document.getElementById('controle-materiais-modal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = ''; // Restaura scroll da página
    }
  }

  toggleMaximizeModal() {
    const modal = document.querySelector('#controle-materiais-modal .modal');
    const btnMaximizar = document.getElementById('maximizar-modal');
    
    if (modal.classList.contains('maximized')) {
      // Restaurar tamanho normal
      modal.classList.remove('maximized');
      modal.style.width = '98%';
      modal.style.height = '95vh';
      modal.style.margin = '5px auto';
      btnMaximizar.innerHTML = '<i class="fas fa-expand"></i>';
      btnMaximizar.title = 'Maximizar';
    } else {
      // Maximizar
      modal.classList.add('maximized');
      modal.style.width = '100%';
      modal.style.height = '100vh';
      modal.style.margin = '0';
      btnMaximizar.innerHTML = '<i class="fas fa-compress"></i>';
      btnMaximizar.title = 'Restaurar';
    }
  }

  // 🌐 FUNÇÕES DE REQUISIÇÃO COM UTF-8
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
      
      // Verificar se a resposta está OK
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

  // 🇧🇷 FUNÇÕES DE FORMATAÇÃO BRASILEIRAS COM FUSO HORÁRIO CORRETO
  formatarData(datetime) {
    if (!datetime) return '-';
    
    try {
      const data = this.converterUTCParaBrasil(datetime);
      return this.formatadorData.format(data);
    } catch (error) {
      console.warn('Erro ao formatar data:', datetime, error);
      // Fallback para formatação manual
      const data = new Date(datetime);
      return data.toLocaleDateString('pt-BR', { timeZone: this.timezone });
    }
  }

  formatarHora(datetime) {
    if (!datetime) return '-';
    
    try {
      const data = this.converterUTCParaBrasil(datetime);
      return this.formatadorHora.format(data);
    } catch (error) {
      console.warn('Erro ao formatar hora:', datetime, error);
      // Fallback para formatação manual
      const data = new Date(datetime);
      return data.toLocaleTimeString('pt-BR', { timeZone: this.timezone, hour12: false });
    }
  }

  formatarDataHora(datetime) {
    if (!datetime) return '-';
    
    try {
      const data = this.converterUTCParaBrasil(datetime);
      return this.formatadorDataHora.format(data);
    } catch (error) {
      console.warn('Erro ao formatar data/hora:', datetime, error);
      // Fallback para formatação manual
      const data = new Date(datetime);
      return data.toLocaleString('pt-BR', { timeZone: this.timezone, hour12: false });
    }
  }

  formatarMoeda(valor) {
    try {
      return this.formatadorMoeda.format(parseFloat(valor || 0));
    } catch (error) {
      console.warn('Erro ao formatar moeda:', valor, error);
      return `R$ ${parseFloat(valor || 0).toFixed(2)}`;
    }
  }

  formatarNumero(numero) {
    try {
      return new Intl.NumberFormat('pt-BR').format(numero);
    } catch (error) {
      return String(numero);
    }
  }

  formatarTipo(tipo) {
    const tipos = {
      'uniforme': 'Uniforme',
      'material_didatico': 'Material Didático',
      'equipamento': 'Equipamento'
    };
    return tipos[tipo] || this.sanitizarTexto(tipo);
  }

  formatarItem(item) {
    if (!item) return '';
    const itemFormatado = this.sanitizarTexto(item);
    return itemFormatado.charAt(0).toUpperCase() + itemFormatado.slice(1);
  }

  formatarMotivo(motivo) {
    if (!motivo) return '-';
    const motivoFormatado = this.sanitizarTexto(motivo);
    return motivoFormatado.replace('_', ' ').charAt(0).toUpperCase() + motivoFormatado.slice(1);
  }

  async carregarDados() {
    console.log('📊 Carregando dados do controle...');
    console.log(`⏰ Horário da requisição: ${this.getHoraBrasil()}`);
    this.showLoading();
    
    try {
      // Carregar TODOS os dados necessários com UTF-8
      const [saidasData, entradasData, estoqueData, alunosData, turmasData] = await Promise.all([
        this.buscarHistoricoCompleto(),
        this.buscarEntradas(), 
        this.buscarEstoque(),
        this.buscarAlunos(),
        this.buscarTurmas()
      ]);
      
      // Armazenar dados auxiliares
      this.dados.alunos = alunosData;
      this.dados.turmas = turmasData;
      
      // Processar dados de saídas com informações completas E correção de fuso horário
      this.dados.saidas = this.processarSaidasCompletas(saidasData.filter(item => item.tipo_operacao === 'saida'));
      this.dados.entradas = this.processarEntradasCompletas(saidasData.filter(item => item.tipo_operacao === 'entrada'));
      this.dados.estoque = estoqueData;
      
      console.log('📊 Dados carregados:', {
        saidas: this.dados.saidas.length,
        entradas: this.dados.entradas.length,
        estoque: this.dados.estoque.length,
        alunos: this.dados.alunos.length,
        turmas: this.dados.turmas.length
      });
      
      // Debug das datas carregadas
      if (this.dados.saidas.length > 0) {
        const ultimaSaida = this.dados.saidas[0];
        console.log(`⏰ Data da última saída (original): ${ultimaSaida.created_at_original}`);
        console.log(`⏰ Data da última saída (Brasil): ${this.formatarDataHora(ultimaSaida.created_at)}`);
      }
      
      this.atualizarEstatisticas();
      this.atualizarAbaAtiva(this.getAbaAtiva());
      
      this.showNotification('✅ Dados carregados com sucesso!', 'success');
      
    } catch (error) {
      console.error('❌ Erro ao carregar dados:', error);
      this.showNotification('❌ Erro ao carregar dados: ' + error.message, 'error');
      
      // Em caso de erro, usar dados mock
      this.usarDadosMock();
    } finally {
      this.hideLoading();
    }
  }

  async buscarHistoricoCompleto() {
    try {
      const result = await this.fetchComUTF8(`${this.apiPath}?action=historico`);
      
      if (result.success && result.data) {
        return result.data;
      } else {
        throw new Error(result.error || 'Erro ao buscar histórico');
      }
    } catch (error) {
      console.warn('Erro na API de histórico:', error);
      return [];
    }
  }

  async buscarAlunos() {
    try {
      const result = await this.fetchComUTF8(`${this.apiPath}?action=alunos`);
      
      if (result.success && result.data) {
        return result.data;
      }
    } catch (error) {
      console.warn('Erro ao buscar alunos:', error);
    }
    return [];
  }

  async buscarTurmas() {
    try {
      const result = await this.fetchComUTF8(`${this.apiPath}?action=turmas`);
      
      if (result.success && result.data) {
        return result.data;
      }
    } catch (error) {
      console.warn('Erro ao buscar turmas:', error);
    }
    return [];
  }

  async buscarEntradas() {
    try {
      const result = await this.fetchComUTF8(`${this.apiPath}?action=historico`);
      
      if (result.success) {
        return result.data.filter(item => item.tipo_operacao === 'entrada');
      }
    } catch (error) {
      console.warn('Erro ao buscar entradas:', error);
    }
    return [];
  }

  async buscarEstoque() {
    try {
      const result = await this.fetchComUTF8(`${this.apiPath}?action=estoque`);
      
      if (result.success) {
        return result.data;
      }
    } catch (error) {
      console.warn('Erro ao buscar estoque:', error);
    }
    return [];
  }

  processarSaidasCompletas(saidas) {
    return saidas.map(saida => {
      // Buscar informações do aluno
      const aluno = this.dados.alunos.find(a => a.id == saida.aluno_id);
      
      // Buscar informações da turma
      let turma = null;
      if (saida.turma_id) {
        turma = this.dados.turmas.find(t => t.id == saida.turma_id);
      } else if (aluno && aluno.turma_id) {
        turma = this.dados.turmas.find(t => t.id == aluno.turma_id);
      }
      
      // ARMAZENAR DATA ORIGINAL E CORRIGIR FUSO HORÁRIO
      const dataOriginal = saida.created_at;
      const dataCorrigida = this.converterUTCParaBrasil(dataOriginal);
      
      return {
        ...saida,
        created_at_original: dataOriginal, // Manter original para debug
        created_at: dataCorrigida.toISOString(), // Data corrigida para o Brasil
        aluno_nome: this.sanitizarTexto(aluno ? aluno.nome : (saida.aluno_nome || 'Aluno não encontrado')),
        turma_nome: this.sanitizarTexto(turma ? turma.nome : (aluno ? aluno.turma_nome : 'Turma não encontrada')),
        motivo: this.sanitizarTexto(saida.motivo || 'Entrega de material'),
        usuario_nome: this.sanitizarTexto(saida.usuario_nome || 'Sistema')
      };
    });
  }

  processarEntradasCompletas(entradas) {
    return entradas.map(entrada => {
      // ARMAZENAR DATA ORIGINAL E CORRIGIR FUSO HORÁRIO
      const dataOriginal = entrada.created_at;
      const dataCorrigida = this.converterUTCParaBrasil(dataOriginal);
      
      return {
        ...entrada,
        created_at_original: dataOriginal, // Manter original para debug
        created_at: dataCorrigida.toISOString(), // Data corrigida para o Brasil
        fornecedor: this.sanitizarTexto(entrada.fornecedor || ''),
        observacoes: this.sanitizarTexto(entrada.observacoes || '')
      };
    });
  }

  usarDadosMock() {
    console.log('🎭 Usando dados mock para demonstração');
    
    this.dados.alunos = [
      { id: 1, nome: 'LUÍS FILIPE E SILVA', turma_id: 1, turma_nome: 'Turma A - Matutino' },
      { id: 2, nome: 'Maria José dos Santos', turma_id: 1, turma_nome: 'Turma A - Matutino' },
      { id: 3, nome: 'João Carlos da Silva', turma_id: 2, turma_nome: 'Turma B - Vespertino' },
      { id: 4, nome: 'Ana Lúcia Pereira', turma_id: 2, turma_nome: 'Turma B - Vespertino' }
    ];
    
    this.dados.turmas = [
      { id: 1, nome: 'Turma A - Matutino', unidade_nome: 'Unidade Central - Goiânia' },
      { id: 2, nome: 'Turma B - Vespertino', unidade_nome: 'Unidade Norte - Anápolis' }
    ];
    
    // Criar data atual brasileira CORRETA
    const agora = new Date();
    const agoraBrasil = this.converterUTCParaBrasil(agora.toISOString());
    const agoraBrasilISO = agoraBrasil.toISOString();
    
    console.log(`⏰ Mock data criada - UTC: ${agora.toISOString()}`);
    console.log(`⏰ Mock data criada - Brasil: ${agoraBrasilISO}`);
    console.log(`⏰ Mock data formatada: ${this.formatarDataHora(agoraBrasilISO)}`);
    
    this.dados.saidas = [
      {
        id: 1,
        tipo_operacao: 'saida',
        tipo_material: 'uniforme',
        item: 'calça',
        tamanho: 'P',
        quantidade: 2,
        aluno_id: 1,
        turma_id: 1,
        aluno_nome: 'LUÍS FILIPE E SILVA',
        turma_nome: 'Turma A - Matutino',
        motivo: 'Entrega de uniforme novo',
        usuario_nome: 'Administrador',
        created_at: agoraBrasilISO,
        created_at_original: agora.toISOString()
      },
      {
        id: 2,
        tipo_operacao: 'saida',
        tipo_material: 'material_didatico',
        item: 'apostila',
        tamanho: 'A4',
        quantidade: 1,
        aluno_id: 2,
        turma_id: 1,
        aluno_nome: 'Maria José dos Santos',
        turma_nome: 'Turma A - Matutino',
        motivo: 'Material didático mensal',
        usuario_nome: 'Professor João',
        created_at: agoraBrasilISO,
        created_at_original: agora.toISOString()
      }
    ];
    
    this.dados.entradas = [];
    this.dados.estoque = [
      {
        id: 1,
        tipo_material: 'uniforme',
        item: 'camiseta',
        tamanho: 'M',
        quantidade: 15,
        valor_unitario: 25.00
      },
      {
        id: 2,
        tipo_material: 'uniforme',
        item: 'boné',
        tamanho: 'Único',
        quantidade: 8,
        valor_unitario: 18.50
      }
    ];
    
    this.atualizarEstatisticas();
    this.atualizarAbaAtiva(this.getAbaAtiva());
  }

  switchTab(tabName) {
    console.log(`🔀 Alternando para aba de controle: ${tabName}`);
    
    // Remove classe ativa de todos os botões
    document.querySelectorAll('.controle-tab-btn').forEach(btn => {
      btn.classList.remove('active');
      btn.style.borderBottomColor = 'transparent';
      btn.style.background = 'none';
      btn.style.color = '#666';
    });
    
    // Oculta todas as abas
    document.querySelectorAll('.controle-tab-pane').forEach(pane => {
      pane.classList.remove('active');
      pane.style.display = 'none';
    });

    // Ativa o botão da aba selecionada
    const tabBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (tabBtn) {
      tabBtn.classList.add('active');
      tabBtn.style.borderBottomColor = '#e74c3c';
      tabBtn.style.background = '#f8f9fa';
      tabBtn.style.color = '#e74c3c';
    }
    
    // Mostra a aba selecionada
    const tabPane = document.getElementById(`${tabName}-controle-tab`);
    if (tabPane) {
      tabPane.classList.add('active');
      tabPane.style.display = 'flex';
    }

    // Atualizar dados da aba específica
    this.atualizarAbaAtiva(tabName);
  }

  atualizarAbaAtiva(tabName) {
    switch (tabName) {
      case 'saidas':
        this.atualizarTabelaSaidas();
        break;
      case 'entradas':
        this.atualizarTabelaEntradas();
        break;
      case 'estoque':
        this.atualizarTabelaEstoque();
        break;
    }
  }

  aplicarFiltros(form) {
    const formData = new FormData(form);
    this.filtros = {
      tipo: formData.get('tipo'),
      data_inicial: formData.get('data_inicial'),
      data_final: formData.get('data_final')
    };
    
    console.log('🔍 Aplicando filtros:', this.filtros);
    this.atualizarAbaAtiva(this.getAbaAtiva());
  }

  limparFiltros() {
    this.filtros = { tipo: '', data_inicial: '', data_final: '' };
    
    // Limpar campos do formulário
    document.getElementById('filtro-tipo-controle').value = '';
    document.getElementById('data-inicial-controle').value = '';
    document.getElementById('data-final-controle').value = '';
    
    console.log('🧹 Filtros limpos');
    this.atualizarAbaAtiva(this.getAbaAtiva());
    this.showNotification('Filtros limpos!', 'info');
  }

  getAbaAtiva() {
    const activeTab = document.querySelector('.controle-tab-btn.active');
    return activeTab ? activeTab.dataset.tab : 'saidas';
  }

  atualizarEstatisticas() {
    document.getElementById('total-entradas').textContent = this.formatarNumero(this.dados.entradas.length);
    document.getElementById('total-saidas').textContent = this.formatarNumero(this.dados.saidas.length);
    document.getElementById('total-estoque').textContent = this.formatarNumero(this.dados.estoque.length);
  }

  atualizarTabelaSaidas() {
    const tbody = document.querySelector('#tabela-saidas-controle tbody');
    const contador = document.getElementById('contador-saidas');
    
    if (!tbody) return;
    
    let dadosFiltrados = this.aplicarFiltrosNoDados(this.dados.saidas);
    
    tbody.innerHTML = '';
    contador.textContent = `${this.formatarNumero(dadosFiltrados.length)} registros`;
    
    if (dadosFiltrados.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="padding: 50px; text-align: center; color: #666; font-size: 1.1rem;">Nenhuma saída encontrada</td></tr>';
      return;
    }
    
    dadosFiltrados.forEach((saida, index) => {
      const row = document.createElement('tr');
      
      // Debug da data para cada linha
      if (index === 0) {
        console.log('🔍 Primeira saída - Debug da data:');
        console.log('  - Data original (do banco):', saida.created_at_original);
        console.log('  - Data corrigida (ISO):', saida.created_at);
        console.log('  - Data formatada (Brasil):', this.formatarDataHora(saida.created_at));
        console.log('  - Hora formatada (Brasil):', this.formatarHora(saida.created_at));
      }
      
      row.innerHTML = `
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong>${this.formatarData(saida.created_at)}</strong><br>
          <small style="color: #666;">${this.formatarHora(saida.created_at)}</small>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <span class="badge" style="padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; ${this.getBadgeStyle(saida.tipo_material)}">
            ${this.formatarTipo(saida.tipo_material)}
          </span>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong>${this.formatarItem(saida.item)}</strong>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">${this.sanitizarTexto(saida.tamanho) || '-'}</td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong style="color: #e74c3c;">${this.formatarNumero(saida.quantidade)}</strong>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong>${saida.aluno_nome || 'N/A'}</strong>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          ${saida.turma_nome || 'N/A'}
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <small>${this.formatarMotivo(saida.motivo)}</small>
        </td>
      `;
      tbody.appendChild(row);
    });
  }

  atualizarTabelaEntradas() {
    const tbody = document.querySelector('#tabela-entradas-controle tbody');
    const contador = document.getElementById('contador-entradas');
    
    if (!tbody) return;
    
    let dadosFiltrados = this.aplicarFiltrosNoDados(this.dados.entradas);
    
    tbody.innerHTML = '';
    contador.textContent = `${this.formatarNumero(dadosFiltrados.length)} registros`;
    
    if (dadosFiltrados.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="padding: 50px; text-align: center; color: #666; font-size: 1.1rem;">Nenhuma entrada encontrada</td></tr>';
      return;
    }
    
    dadosFiltrados.forEach(entrada => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong>${this.formatarData(entrada.created_at)}</strong><br>
          <small style="color: #666;">${this.formatarHora(entrada.created_at)}</small>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <span class="badge" style="padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; ${this.getBadgeStyle(entrada.tipo_material)}">
            ${this.formatarTipo(entrada.tipo_material)}
          </span>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong>${this.formatarItem(entrada.item)}</strong>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">${this.sanitizarTexto(entrada.tamanho) || '-'}</td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">
          <strong style="color: #28a745;">${this.formatarNumero(entrada.quantidade)}</strong>
        </td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">${this.formatarMoeda(entrada.valor_unitario || 0)}</td>
        <td style="padding: 12px; border-bottom: 1px solid #e9ecef;">${this.sanitizarTexto(entrada.fornecedor) || '-'}</td>
      `;
      tbody.appendChild(row);
    });
  }

  atualizarTabelaEstoque() {
    const tbody = document.querySelector('#tabela-estoque-controle tbody');
    const contador = document.getElementById('contador-estoque');
    
    if (!tbody) return;
    
    let dadosFiltrados = this.aplicarFiltrosNoDados(this.dados.estoque);
    
    tbody.innerHTML = '';
    contador.textContent = `${this.formatarNumero(dadosFiltrados.length)} itens`;
    
    if (dadosFiltrados.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="padding: 30px; text-align: center; color: #666; font-size: 1rem;">Nenhum item no estoque</td></tr>';
      return;
    }
    
    dadosFiltrados.forEach((item, index) => {
      const row = document.createElement('tr');
      const status = this.getStatusEstoque(item.quantidade || item.quantidade_atual, item.quantidade_minima || 5);
      const valorUnitario = parseFloat(item.valor_unitario || 0);
      const quantidade = parseInt(item.quantidade || item.quantidade_atual || 0);
      const valorTotal = valorUnitario * quantidade;
      
      // Alternar cores das linhas para melhor legibilidade
      const backgroundColor = index % 2 === 0 ? '#ffffff' : '#f8f9fa';
      
      row.style.backgroundColor = backgroundColor;
      row.innerHTML = `
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.75rem;">
          <span class="badge" style="padding: 3px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; ${this.getBadgeStyle(item.tipo_material)}">
            ${this.formatarTipo(item.tipo_material)}
          </span>
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.8rem;">
          <strong>${this.formatarItem(item.item)}</strong>
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.75rem; text-align: center;">
          ${this.sanitizarTexto(item.tamanho) || '-'}
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.8rem; text-align: center;">
          <strong style="color: ${quantidade > 10 ? '#28a745' : quantidade > 5 ? '#ffc107' : '#dc3545'};">
            ${this.formatarNumero(quantidade)}
          </strong>
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.75rem; text-align: right;">
          ${this.formatarMoeda(valorUnitario)}
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.75rem; text-align: center;">
          <span style="padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: bold; ${status.style}">
            ${status.text}
          </span>
        </td>
        <td style="padding: 6px 4px; border-bottom: 1px solid #e9ecef; font-size: 0.75rem; text-align: right; font-weight: bold; color: #2c3e50;">
          ${this.formatarMoeda(valorTotal)}
        </td>
      `;
      tbody.appendChild(row);
    });
    
    // Adicionar linha de totais se houver itens
    if (dadosFiltrados.length > 0) {
      const totalItems = dadosFiltrados.reduce((sum, item) => sum + parseInt(item.quantidade || item.quantidade_atual || 0), 0);
      const totalValue = dadosFiltrados.reduce((sum, item) => {
        const valor = parseFloat(item.valor_unitario || 0);
        const qtd = parseInt(item.quantidade || item.quantidade_atual || 0);
        return sum + (valor * qtd);
      }, 0);
      
      const totalRow = document.createElement('tr');
      totalRow.style.backgroundColor = '#e9ecef';
      totalRow.style.fontWeight = 'bold';
      totalRow.innerHTML = `
        <td colspan="3" style="padding: 8px 4px; border-top: 2px solid #dee2e6; font-weight: bold; color: #495057; font-size: 0.8rem;">
          <i class="fas fa-calculator"></i> TOTAIS
        </td>
        <td style="padding: 8px 4px; border-top: 2px solid #dee2e6; text-align: center; font-weight: bold; color: #e74c3c; font-size: 0.8rem;">
          ${this.formatarNumero(totalItems)}
        </td>
        <td style="padding: 8px 4px; border-top: 2px solid #dee2e6; font-size: 0.75rem;">-</td>
        <td style="padding: 8px 4px; border-top: 2px solid #dee2e6; font-size: 0.75rem;">-</td>
        <td style="padding: 8px 4px; border-top: 2px solid #dee2e6; text-align: right; font-weight: bold; color: #28a745; font-size: 0.85rem;">
          ${this.formatarMoeda(totalValue)}
        </td>
      `;
      tbody.appendChild(totalRow);
    }
  }

  aplicarFiltrosNoDados(dados) {
    return dados.filter(item => {
      // Filtro por tipo
      if (this.filtros.tipo && item.tipo_material !== this.filtros.tipo) {
        return false;
      }
      
      // Filtro por data (se aplicável)
      if (item.created_at && (this.filtros.data_inicial || this.filtros.data_final)) {
        const dataItem = new Date(item.created_at);
        
        if (this.filtros.data_inicial) {
          const dataInicial = new Date(this.filtros.data_inicial);
          // Ajustar para timezone brasileiro
          dataInicial.setHours(0, 0, 0, 0);
          if (dataItem < dataInicial) return false;
        }
        
        if (this.filtros.data_final) {
          const dataFinal = new Date(this.filtros.data_final);
          // Ajustar para timezone brasileiro e incluir o dia inteiro
          dataFinal.setHours(23, 59, 59, 999);
          if (dataItem > dataFinal) return false;
        }
      }
      
      return true;
    });
  }

  getBadgeStyle(tipo) {
    const styles = {
      'uniforme': 'background: linear-gradient(135deg, #e3f2fd, #bbdefb); color: #1565c0; border: 1px solid #2196f3;',
      'material_didatico': 'background: linear-gradient(135deg, #f3e5f5, #e1bee7); color: #7b1fa2; border: 1px solid #9c27b0;',
      'equipamento': 'background: linear-gradient(135deg, #e8f5e8, #c8e6c9); color: #2e7d32; border: 1px solid #4caf50;'
    };
    return styles[tipo] || 'background: #f8f9fa; color: #666; border: 1px solid #dee2e6;';
  }

  getStatusEstoque(quantidade, minima = 5) {
    if (quantidade === 0) {
      return { 
        text: 'Esgotado', 
        style: 'background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; border: 1px solid #dc3545;' 
      };
    } else if (quantidade <= minima) {
      return { 
        text: 'Baixo', 
        style: 'background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; border: 1px solid #ffc107;' 
      };
    } else {
      return { 
        text: 'Disponível', 
        style: 'background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; border: 1px solid #28a745;' 
      };
    }
  }

  showLoading() {
    const tabelas = ['#tabela-saidas-controle tbody', '#tabela-entradas-controle tbody', '#tabela-estoque-controle tbody'];
    tabelas.forEach(selector => {
      const tbody = document.querySelector(selector);
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="8" style="padding: 50px; text-align: center; color: #666; font-size: 1.1rem;"><i class="fas fa-spinner fa-spin"></i> Carregando dados...</td></tr>';
      }
    });
  }

  hideLoading() {
    // O loading será removido quando os dados forem atualizados
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
      animation: slideIn 0.3s ease;
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

    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => notification.remove(), 300);
    }, 4000);
  }

  createModalDynamically() {
    const modalHTML = `
    <div id="controle-materiais-modal" class="modal-backdrop" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000;">
      <div class="modal" style="position: relative; background: white; margin: 5px auto; width: 98%; max-width: none; border-radius: 8px; overflow: hidden; height: 95vh; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
        
        <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: #ffffff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
          <span style="font-size: 1.3rem; font-weight: bold; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-sign-out-alt"></i> Controle de Materiais
            <small style="font-size: 0.8rem; opacity: 0.8; margin-left: 10px;">🇧🇷 Horário de Brasília</small>
          </span>
          <div style="display: flex; gap: 10px; align-items: center;">
            <span style="font-size: 0.85rem; background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 15px;">
              <i class="fas fa-clock"></i> ${this.getHoraBrasil()}
            </span>
            <button id="maximizar-modal" title="Maximizar" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 1rem; display: flex; align-items: center;">
              <i class="fas fa-expand"></i>
            </button>
            <button onclick="document.getElementById('controle-materiais-modal').style.display='none'; document.body.style.overflow='';" 
                    style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 1.2rem;">×</button>
          </div>
        </div>
        <div class="modal-body" style="padding: 8px; flex: 1; overflow: hidden; display: flex; flex-direction: column; background: #f8f9fa; height: calc(100vh - 60px);">
          
          <!-- Cards de Estatísticas - Ultra Compactos -->
          <div class="stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; margin-bottom: 8px; flex-shrink: 0;">
            <div class="stat-card" style="background: white; padding: 8px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); text-align: center; border-left: 3px solid #e74c3c; transition: transform 0.2s;">
              <h3 style="color: #666; font-size: 0.7rem; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px;">Total de Entradas</h3>
              <div class="number" id="total-entradas" style="font-size: 1.3rem; font-weight: bold; color: #e74c3c;">0</div>
            </div>
            <div class="stat-card" style="background: white; padding: 8px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); text-align: center; border-left: 3px solid #e74c3c; transition: transform 0.2s;">
              <h3 style="color: #666; font-size: 0.7rem; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px;">Total de Saídas</h3>
              <div class="number" id="total-saidas" style="font-size: 1.3rem; font-weight: bold; color: #e74c3c;">0</div>
            </div>
            <div class="stat-card" style="background: white; padding: 8px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); text-align: center; border-left: 3px solid #e74c3c; transition: transform 0.2s;">
              <h3 style="color: #666; font-size: 0.7rem; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px;">Itens em Estoque</h3>
              <div class="number" id="total-estoque" style="font-size: 1.3rem; font-weight: bold; color: #e74c3c;">0</div>
            </div>
          </div>

          <!-- Filtros - Ultra Compactos -->
          <div class="filtros-controle" style="background: white; padding: 8px; border-radius: 6px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); margin-bottom: 8px; flex-shrink: 0;">
            <h3 style="margin-bottom: 6px; color: #333; display: flex; align-items: center; gap: 4px; font-size: 0.85rem;">
              <i class="fas fa-filter" style="color: #e74c3c;"></i> Filtros de Pesquisa
            </h3>
            <form id="controle-filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; align-items: end;">
              <div>
                <label style="display: block; margin-bottom: 2px; font-weight: 600; color: #555; font-size: 0.75rem;">Tipo de Material</label>
                <select name="tipo" id="filtro-tipo-controle" style="width: 100%; padding: 4px; border: 1px solid #e1e5e9; border-radius: 4px; font-size: 0.8rem; background: white; transition: border-color 0.2s;">
                  <option value="">Todos os tipos</option>
                  <option value="uniforme">Uniforme</option>
                  <option value="material_didatico">Material Didático</option>
                  <option value="equipamento">Equipamento</option>
                </select>
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 2px; font-weight: 600; color: #555; font-size: 0.75rem;">Data Inicial</label>
                <input type="date" name="data_inicial" id="data-inicial-controle" 
                       style="width: 100%; padding: 4px; border: 1px solid #e1e5e9; border-radius: 4px; font-size: 0.8rem; transition: border-color 0.2s;">
              </div>
              
              <div>
                <label style="display: block; margin-bottom: 2px; font-weight: 600; color: #555; font-size: 0.75rem;">Data Final</label>
                <input type="date" name="data_final" id="data-final-controle" 
                       style="width: 100%; padding: 4px; border: 1px solid #e1e5e9; border-radius: 4px; font-size: 0.8rem; transition: border-color 0.2s;">
              </div>
              
              <div style="display: flex; gap: 6px;">
                <button type="submit" style="padding: 4px 8px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 3px; transition: background 0.2s; font-size: 0.75rem;">
                  <i class="fas fa-search"></i> Filtrar
                </button>
                <button type="button" id="limpar-filtros-controle" style="padding: 4px 8px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 3px; transition: background 0.2s; font-size: 0.75rem;">
                  <i class="fas fa-eraser"></i> Limpar
                </button>
              </div>
            </form>
          </div>

          <!-- Abas -->
          <div class="controle-tabs" style="display: flex; border-bottom: 3px solid #e0e0e0; margin-bottom: 20px; flex-shrink: 0; background: white; border-radius: 15px 15px 0 0; padding: 0 20px;">
            <button class="controle-tab-btn active" data-tab="saidas" 
                    style="padding: 15px 25px; border: none; background: none; cursor: pointer; border-bottom: 3px solid #e74c3c; font-weight: 600; color: #e74c3c; font-size: 1rem; transition: all 0.2s;">
              <i class="fas fa-sign-out-alt"></i> Saídas
            </button>
            <button class="controle-tab-btn" data-tab="entradas" 
                    style="padding: 15px 25px; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 600; color: #666; font-size: 1rem; transition: all 0.2s;">
              <i class="fas fa-sign-in-alt"></i> Entradas
            </button>
            <button class="controle-tab-btn" data-tab="estoque" 
                    style="padding: 15px 25px; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; font-weight: 600; color: #666; font-size: 1rem; transition: all 0.2s;">
              <i class="fas fa-boxes"></i> Estoque
            </button>
          </div>

          <!-- Conteúdo das Abas -->
          <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: hidden;">
          
            <!-- Aba Saídas -->
            <div id="saidas-controle-tab" class="controle-tab-pane active" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-shrink: 0;">
                <h3 style="margin: 0; color: #333; display: flex; align-items: center; gap: 10px; font-size: 1.2rem;">
                  <i class="fas fa-list" style="color: #e74c3c;"></i> Histórico de Saídas
                </h3>
                <div style="display: flex; gap: 12px; align-items: center;">
                  <button id="atualizar-dados-controle" style="padding: 10px 18px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: background 0.2s;">
                    <i class="fas fa-sync"></i> Atualizar
                  </button>
                  <span id="contador-saidas" style="padding: 10px 18px; background: white; border-radius: 8px; font-size: 0.95rem; color: #666; font-weight: 500; border: 2px solid #e9ecef;">
                    0 registros
                  </span>
                </div>
              </div>
              
              <div style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); overflow: hidden; flex: 1; display: flex; flex-direction: column;">
                <div style="flex: 1; overflow: auto;">
                  <table id="tabela-saidas-controle" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                      <tr style="background: linear-gradient(135deg, #343a40, #495057); color: white;">
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Data/Hora 🇧🇷</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Tipo</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Item</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Tamanho</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Qtd</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Aluno</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Turma</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Motivo</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><td colspan="8" style="padding: 50px; text-align: center; color: #666; font-size: 1.1rem;">Carregando dados...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Aba Entradas -->
            <div id="entradas-controle-tab" class="controle-tab-pane" style="display: none; flex-direction: column; height: 100%; overflow: hidden;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-shrink: 0;">
                <h3 style="margin: 0; color: #333; display: flex; align-items: center; gap: 10px; font-size: 1.2rem;">
                  <i class="fas fa-list" style="color: #e74c3c;"></i> Histórico de Entradas
                </h3>
                <span id="contador-entradas" style="padding: 10px 18px; background: white; border-radius: 8px; font-size: 0.95rem; color: #666; font-weight: 500; border: 2px solid #e9ecef;">
                  0 registros
                </span>
              </div>
              
              <div style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); overflow: hidden; flex: 1; display: flex; flex-direction: column;">
                <div style="flex: 1; overflow: auto;">
                  <table id="tabela-entradas-controle" style="width: 100%; border-collapse: collapse; min-width: 700px;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                      <tr style="background: linear-gradient(135deg, #343a40, #495057); color: white;">
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Data/Hora 🇧🇷</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Tipo</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Item</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Tamanho</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Qtd</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Valor Unit.</th>
                        <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 0.95rem;">Fornecedor</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><td colspan="7" style="padding: 50px; text-align: center; color: #666; font-size: 1.1rem;">Carregando dados...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Aba Estoque - OTIMIZADA PARA MOSTRAR MUITOS ITENS -->
            <div id="estoque-controle-tab" class="controle-tab-pane" style="display: none; flex-direction: column; height: 100%; overflow: hidden;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-shrink: 0;">
                <h3 style="margin: 0; color: #333; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
                  <i class="fas fa-boxes" style="color: #e74c3c;"></i> Estoque Atual
                </h3>
                <span id="contador-estoque" style="padding: 8px 15px; background: white; border-radius: 6px; font-size: 0.9rem; color: #666; font-weight: 500; border: 2px solid #e9ecef;">
                  0 itens
                </span>
              </div>
              
              <!-- TABELA OTIMIZADA PARA MÁXIMA VISIBILIDADE -->
              <div style="background: white; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); overflow: hidden; flex: 1; display: flex; flex-direction: column;">
                <div style="flex: 1; overflow: auto; max-height: calc(100vh - 300px);">
                  <table id="tabela-estoque-controle" style="width: 100%; border-collapse: collapse; min-width: 700px; font-size: 0.85rem;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                      <tr style="background: linear-gradient(135deg, #343a40, #495057); color: white;">
                        <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: 0.7rem; width: 12%;">Tipo</th>
                        <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: 0.7rem; width: 25%;">Item</th>
                        <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: 0.7rem; width: 10%;">Tamanho</th>
                        <th style="padding: 6px 4px; text-align: center; font-weight: 600; font-size: 0.7rem; width: 12%;">Quantidade</th>
                        <th style="padding: 6px 4px; text-align: right; font-weight: 600; font-size: 0.7rem; width: 15%;">Valor Unit.</th>
                        <th style="padding: 6px 4px; text-align: center; font-weight: 600; font-size: 0.7rem; width: 15%;">Status</th>
                        <th style="padding: 6px 4px; text-align: right; font-weight: 600; font-size: 0.7rem; width: 15%;">Valor Total</th>
                      </tr>
                    </thead>
                    <tbody style="height: 100%;">
                      <tr><td colspan="7" style="padding: 20px; text-align: center; color: #666; font-size: 0.9rem;">Carregando dados...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
    /* Hover effects para os cards */
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    /* Hover effects para botões de aba */
    .controle-tab-btn:hover { 
      background: #f8f9fa !important; 
      color: #e74c3c !important;
      transform: translateY(-2px);
    }
    
    .controle-tab-btn.active { 
      border-bottom-color: #e74c3c !important; 
      background: #f8f9fa !important; 
      color: #e74c3c !important;
    }
    
    /* Controle de exibição das abas */
    .controle-tab-pane { 
      display: none; 
    }
    .controle-tab-pane.active { 
      display: flex !important; 
    }
    
    /* Hover effects para linhas da tabela */
    #controle-materiais-modal table tr:hover {
      background: #f8f9fa !important;
      transform: scale(1.01);
      transition: all 0.2s ease;
    }
    
    /* Focus effects para inputs */
    #controle-materiais-modal .form-group input:focus,
    #controle-materiais-modal .form-group select:focus,
    #controle-materiais-modal input:focus,
    #controle-materiais-modal select:focus {
      outline: none;
      border-color: #e74c3c !important;
      box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
    
    /* Hover effects para botões */
    #controle-materiais-modal button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Estilo para modal maximizado */
    .modal.maximized {
      border-radius: 0 !important;
    }
    
    /* Scroll personalizado */
    #controle-materiais-modal ::-webkit-scrollbar {
      width: 8px;
    }
    
    #controle-materiais-modal ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 4px;
    }
    
    #controle-materiais-modal ::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 4px;
    }
    
    #controle-materiais-modal ::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
    
    /* Responsividade melhorada */
    @media (max-width: 768px) {
      #controle-materiais-modal .modal {
        width: 100% !important;
        height: 100vh !important;
        margin: 0 !important;
        border-radius: 0 !important;
      }
      
      .stats-cards {
        grid-template-columns: 1fr !important;
      }
      
      #controle-filter-form {
        grid-template-columns: 1fr !important;
      }
      
      .controle-tabs {
        flex-wrap: wrap;
      }
      
      .controle-tab-btn {
        flex: 1;
        min-width: 120px;
      }
      
      /* Tornar tabela responsiva em mobile */
      #tabela-estoque-controle {
        font-size: 0.7rem !important;
      }
      
      #tabela-estoque-controle th,
      #tabela-estoque-controle td {
        padding: 6px 4px !important;
      }
    }
    
    /* Otimizações especiais para tabela de estoque */
    #tabela-estoque-controle tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    
    #tabela-estoque-controle tr:hover {
      background-color: #e3f2fd !important;
      transform: none; /* Remove transform para não afetar layout */
    }
    
    /* Scroll mais suave para tabela */
    #tabela-estoque-controle tbody {
      scroll-behavior: smooth;
    }
    
    /* Animações para notificações */
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
    </style>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    console.log('✅ Modal de controle completo com UTF-8 e fuso horário brasileiro correto criado');
  }
}

// Inicializar
console.log('🎯 Inicializando Módulo de Controle Completo com UTF-8 e Fuso Horário Brasileiro...');
window.moduloControle = new ModuloControle();
window.ModuloControle = ModuloControle;