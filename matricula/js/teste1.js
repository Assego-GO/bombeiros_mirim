document.addEventListener("DOMContentLoaded", () => {
  carregarMatriculas();
  inicializarFiltros();
  setupActionButtons(); 
  adicionarEstilosModais(); 

  // Botão para gerar carteirinhas
  const gerarCarteirinhaBtn = document.getElementById('gerar-carterinha-btn');
  if (gerarCarteirinhaBtn) {
    gerarCarteirinhaBtn.addEventListener('click', function (e) {
      // Prevenir comportamento padrão para permitir uso do dropdown
      e.preventDefault();
      e.stopPropagation();

      // Se não tem um dropdown, usa o comportamento padrão
      if (!document.getElementById('gerar-carterinha-btn-dropdown')) {
        gerarCarteirinha();
      }
    });
  }

  // Botão para selecionar todos os alunos
  const selectAllCheckbox = document.getElementById('select-all');
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function () {
      const checkboxes = document.querySelectorAll('#matriculas-body input[type="checkbox"]');
      checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
      });
    });
  }

  // Botão toggle para mostrar/ocultar filtros
  const toggleFiltro = document.getElementById("toggle-filter");
  if (toggleFiltro) {
    toggleFiltro.addEventListener("click", () => {
      const filterContainer = document.getElementById("filter-container");
      if (filterContainer) {
        filterContainer.style.display = filterContainer.style.display === "none" ? "block" : "none";
      }
    });
  }

  // Formulário de filtro
  const formFiltro = document.getElementById("filter-form");
  if (formFiltro) {
    formFiltro.addEventListener("submit", function (e) {
      e.preventDefault();
      aplicarFiltros();
    });
  }

  // Botão limpar filtros
  const limparFiltros = document.getElementById("limpar-filtros");
  if (limparFiltros) {
    limparFiltros.addEventListener("click", function () {
      document.getElementById("filter-form").reset();
      carregarMatriculas(); // Recarrega dados sem filtros
    });
  }

  // Botão gerar PDF
  const gerarPDF = document.getElementById("gerar-pdf");
  if (gerarPDF) {
    gerarPDF.addEventListener("click", function () {
      gerarRelatorioPDF();
    });
  }

  // ==== EVENTOS DE MODAIS ====

  // Botão Nova Turma - comportamento será substituído pelo menu dropdown
  const botaoNovaTurma = document.getElementById("nova-turma-btn");
  if (botaoNovaTurma) {
    // O evento será gerenciado pelo dropdown agora
  }

  // Botão Nova Unidade - comportamento será substituído pelo menu dropdown
  const botaoNovaUnidade = document.querySelector("#nova-unidade-btn");
  if (botaoNovaUnidade) {
    // O evento será gerenciado pelo dropdown agora
  }

  const botaoNovoProfessor = document.querySelector("#novo-professor-btn");
  if (botaoNovoProfessor) {
    // O evento será gerenciado pelo dropdown agora
  }

  // ==== EVENTOS DE FORMULÁRIOS ====

  // Formulário Nova Turma
  const formNovaTurma = document.getElementById("nova-turma-form");
  if (formNovaTurma) {
    formNovaTurma.addEventListener("submit", function (e) {
      e.preventDefault();
      showLoading();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      dados.status = document.getElementById("status-active")?.checked ? 1 : 0;

      fetch("api/nova_turma.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dados),
      })
        .then(res => res.json())
        .then(resp => {
          hideLoading();
          if (resp.status === "sucesso") {
            alert("Turma criada com sucesso!");
            document.getElementById("nova-turma-modal").style.display = "none";
            this.reset();
            carregarMatriculas();
          } else {
            alert("Erro ao criar turma: " + resp.mensagem);
          }
        })
        .catch(err => {
          hideLoading();
          console.error("Erro ao salvar turma:", err);
          alert("Erro inesperado ao salvar turma.");
        });
    });
  }

  // Formulário Nova Unidade
  const formNovaUnidade = document.getElementById("nova-unidade-form");
  if (formNovaUnidade) {
    formNovaUnidade.addEventListener("submit", function (e) {
      e.preventDefault();
      showLoading();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      // Debug: verificar se a cidade está sendo capturada
      console.log('Dados enviados:', dados);
      console.log('Cidade capturada:', dados.cidade);

      fetch("api/nova_unidade.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dados),
      })
        .then(res => {
          if (!res.ok) {
            return res.text().then(text => {
              console.error("Resposta não-JSON:", text);
              throw new Error(`Erro HTTP ${res.status}: ${text}`);
            });
          }
          return res.json();
        })
        .then(resp => {
          hideLoading();
          if (resp.status === "sucesso") {
            alert(resp.mensagem || "Unidade criada com sucesso!");
            document.getElementById("nova-unidade-modal").style.display = "none";
            this.reset();
            // Atualize a lista de unidades se necessário
            if (typeof carregarUnidades === 'function') {
              carregarUnidades();
            }
            if (typeof carregarUnidadesEditar === 'function') {
              carregarUnidadesEditar();
            }
          } else {
            alert("Erro ao criar unidade: " + resp.mensagem);
          }
        })
        .catch(err => {
          hideLoading();
          console.error("Erro ao salvar unidade:", err);
          alert("Erro inesperado ao salvar unidade: " + err.message);
        });
    });
  }

  // Formulário Novo Professor
  const formNovoProfessor = document.getElementById("novo-professor-form");
  if (formNovoProfessor) {
    formNovoProfessor.addEventListener("submit", function (e) {
      e.preventDefault();
      showLoading();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      fetch("api/novo_professor.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dados),
      })
        .then(res => {
          if (!res.ok) {
            return res.text().then(text => {
              console.error("Resposta não-JSON:", text);
              throw new Error(`Erro HTTP ${res.status}: ${text}`);
            });
          }
          return res.json();
        })
        .then(resp => {
          hideLoading();
          if (resp.status === "sucesso") {
            alert(resp.mensagem || "Professor cadastrado com sucesso!");
            document.getElementById("novo-professor-modal").style.display = "none";
            this.reset();
            // Atualizar listas de professores
            if (typeof carregarProfessores === 'function') {
              carregarProfessores();
            }
          } else {
            alert("Erro ao cadastrar professor: " + resp.mensagem);
          }
        })
        .catch(err => {
          hideLoading();
          console.error("Erro ao salvar professor:", err);
          alert("Erro inesperado ao cadastrar professor: " + err.message);
        });
    });
  }

  // Formulário Editar Matrícula
  const formEditarMatricula = document.getElementById("edit-matricula-form");
  if (formEditarMatricula) {
    formEditarMatricula.addEventListener("submit", function (e) {
      e.preventDefault();
      showLoading();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      fetch("api/editar_matricula.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(dados),
      })
        .then(res => {
          // Add error checking for the response
          if (!res.ok) {
            return res.text().then(text => {
              console.error("Resposta não-JSON:", text);
              throw new Error(`Erro HTTP ${res.status}: ${text}`);
            });
          }
          return res.json();
        })
        .then(resp => {
          hideLoading();
          if (resp.status === "sucesso") {
            alert("Matrícula atualizada com sucesso!");
            document.getElementById("edit-matricula-modal").style.display = "none";
            carregarMatriculas();
          } else {
            alert("Erro ao atualizar matrícula: " + resp.mensagem);
          }
        })
        .catch(err => {
          hideLoading();
          console.error("Erro ao editar matrícula:", err);
          alert("Erro inesperado ao editar matrícula: " + err.message);
        });
    });
  }

  // Fecha modais quando clicar fora deles
  document.querySelectorAll('.modal-backdrop').forEach(modal => {
    modal.addEventListener('click', function (e) {
      if (e.target === this) {
        this.style.display = 'none';
      }
    });
  });

  // Fechar todos os menus dropdown quando clicar fora deles
  document.addEventListener('click', function (e) {
    closeAllDropdowns(e);
  });
});


// aqui estamos preservando os modais de novas undiades 
function removerModais() {
  document.querySelectorAll('.modal-backdrop:not(#edit-matricula-modal):not(#view-details-modal):not(#nova-turma-modal):not(#nova-unidade-modal):not(#novo-professor-modal)').forEach(m => m.remove());
}

// Adicionar estilos para modais mais amplos
function adicionarEstilosModais() {
  // Cria um elemento de estilo
  const style = document.createElement('style');
  style.textContent = `
      /* Estilos para modais maiores e responsivos */
      .modal-backdrop {
          z-index: 1050;
      }
      
      .modal {
          max-height: 90vh;
          overflow-y: auto;
      }
      
      /* Ajuste para a tabela em telas pequenas */
      @media (max-width: 768px) {
          .modal {
              width: 95% !important;
              max-width: 95% !important;
          }
          
          .table-container {
              overflow-x: auto;
          }
      }
      
      /* Melhoria para os botões de ação */
      .action-btn {
          margin: 0 5px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
      }
      
      /* Dropdown melhorado */
      .dropdown-menu-action {
          min-width: 180px;
      }
  `;

  // Adiciona ao head do documento
  document.head.appendChild(style);
}

function showLoading() {
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'flex';
  }
}

// Função para esconder o loading overlay
function hideLoading() {
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'none';
  }
}

// Função para formatar a exibição do status com classe CSS
function formatarStatus(status) {
  // Verificar se o status é null ou undefined
  if (status === null || status === undefined) {
    return '<span class="status-badge">-</span>';
  }

  let classe = '';
  // Converter para string e depois para lowercase para evitar erro
  const statusLower = String(status).toLowerCase();

  switch (statusLower) {
    case 'ativo':
      classe = 'status-ativo';
      break;
    case 'inativo':
      classe = 'status-inativo';
      break;
    case 'pendente':
      classe = 'status-pendente';
      break;
    default:
      classe = '';
  }

  return `<span class="status-badge ${classe}">${status}</span>`;
}

// Função para formatar a data
function formatarData(dataString) {
  if (!dataString) return '-';

  try {
    const data = new Date(dataString);
    if (isNaN(data.getTime())) return dataString;

    return data.toLocaleDateString('pt-BR');
  } catch (e) {
    console.error('Erro ao formatar data:', e);
    return dataString; // Retorna a string original se houver erro
  }
}

// ==== CONFIGURAÇÃO DE BOTÕES DROPDOWN ====

// Configurar botões com dropdown
function setupActionButtons() {
  // Configuração para botão de Turma
  setupDropdownButton('nova-turma-btn', [
    {
      text: 'Cadastrar',
      icon: 'fas fa-plus',
      action: function () {
        document.getElementById('nova-turma-modal').style.display = 'flex';
        carregarUnidades();
        carregarProfessores();
      }
    },
    {
      text: 'Listar',
      icon: 'fas fa-list',
      action: function () {
        listarTurmas();
      }
    }
  ]);

  // Configuração para botão de Unidade
  setupDropdownButton('nova-unidade-btn', [
    {
      text: 'Cadastrar',
      icon: 'fas fa-plus',
      action: function () {
        document.getElementById('nova-unidade-modal').style.display = 'flex';
      }
    },
    {
      text: 'Listar',
      icon: 'fas fa-list',
      action: function () {
        listarUnidades();
      }
    }
  ]);

  // Configuração para botão de Professor
  setupDropdownButton('novo-professor-btn', [
    {
      text: 'Cadastrar',
      icon: 'fas fa-plus',
      action: function () {
        document.getElementById('novo-professor-modal').style.display = 'flex';
      }
    },
    {
      text: 'Listar',
      icon: 'fas fa-list',
      action: function () {
        listarProfessores();
      }
    }
  ]);

  // Configuração para botão de Carteirinha
  setupDropdownButton('gerar-carterinha-btn', [
    {
      text: 'Gerar',
      icon: 'fas fa-plus',
      action: function () {
        gerarCarteirinha();
      }
    }
  ]);
}

// Configura um botão para ter menu dropdown
function setupDropdownButton(buttonId, menuItems) {
  const btn = document.getElementById(buttonId);
  if (!btn) return;

  // Preservar HTML original do botão
  const originalHTML = btn.innerHTML;

  // Extrair apenas o ícone e o texto principal (remover "Nova"/"Novo")
  const iconMatch = originalHTML.match(/<i class="([^"]+)"><\/i>/);
  const textMatch = originalHTML.match(/<\/i>\s*([^<]+)/);

  if (iconMatch && textMatch) {
    const iconClass = iconMatch[1];
    const fullText = textMatch[1].trim();
    // Remove "Nova" ou "Novo" do início do texto
    const simpleText = fullText.replace(/^(Nova|Novo)\s+/, '');

    // Atualizar o texto do botão
    btn.innerHTML = `<i class="${iconClass}"></i> ${simpleText}`;

    // Transformar o botão em um dropdown
    const wrapper = document.createElement('div');
    wrapper.className = 'dropdown-container';
    btn.parentNode.insertBefore(wrapper, btn);
    wrapper.appendChild(btn);

    // Criar menu dropdown
    const dropdownMenu = document.createElement('div');
    dropdownMenu.className = 'dropdown-menu-action';
    dropdownMenu.id = `${buttonId}-dropdown`;

    // Adicionar itens ao menu
    menuItems.forEach(item => {
      const menuItem = document.createElement('div');
      menuItem.className = 'dropdown-item-action';
      menuItem.innerHTML = `<i class="${item.icon}"></i> ${item.text}`;
      menuItem.addEventListener('click', function (e) {
        e.stopPropagation();
        closeAllDropdowns();
        item.action();
      });
      dropdownMenu.appendChild(menuItem);
    });

    wrapper.appendChild(dropdownMenu);

    // Adicionar evento ao botão
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      toggleDropdown(buttonId);
    });
  }
}

// Alternar visibilidade do menu dropdown
function toggleDropdown(buttonId) {
  // Fechar todos os outros menus primeiro
  closeAllDropdowns();

  // Mostrar/esconder este dropdown
  const dropdownId = `${buttonId}-dropdown`;
  const dropdown = document.getElementById(dropdownId);

  if (dropdown) {
    dropdown.classList.toggle('show');
  }
}

// Fechar todos os dropdowns
function closeAllDropdowns(e) {
  const dropdowns = document.querySelectorAll('.dropdown-menu-action');

  dropdowns.forEach(dropdown => {
    // Se clicou dentro do dropdown ou no botão que o controla, não fecha
    if (e) {
      const buttonId = dropdown.id.replace('-dropdown', '');
      const button = document.getElementById(buttonId);

      if ((button && button.contains(e.target)) || dropdown.contains(e.target)) {
        return;
      }
    }

    dropdown.classList.remove('show');
  });
}

function listarUnidades() {
  removerModais();
  
  showLoading();
  fetch("api/listar_unidades.php")
    .then(res => {
      if (!res.ok) {
        throw new Error(`Erro HTTP: ${res.status}`);
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      const unidades = response.data || [];

      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
              <div class="modal" style="max-width: 85%; width: 1000px;">
                  <div class="modal-header">
                      <span><i class="fas fa-building"></i> Lista de Unidades</span>
                      <button onclick="this.closest('.modal-backdrop').remove()">×</button>
                  </div>
                  <div class="modal-body" style="padding: 0;">
                      <div class="table-container" style="margin: 0; box-shadow: none; border-radius: 0;">
                          <table style="min-width: 100%;">
                              <thead>
                                  <tr>
                                      <th style="width: 20%;">Nome da Unidade</th>
                                      <th style="width: 25%;">Endereço</th>
                                      <th style="width: 12%;">Telefone</th>
                                      <th style="width: 18%;">Comandante</th>
                                      <th style="width: 15%;">Cidade</th>
                                      <th style="width: 10%; text-align: center;">Ações</th>
                                  </tr>
                              </thead>
                              <tbody>
          `;

      if (unidades.length === 0) {
        html += `
                  <tr>
                      <td colspan="6" style="text-align: center;">Nenhuma unidade encontrada</td>
                  </tr>
              `;
      } else {
        unidades.forEach(unidade => {
          // Verificação segura para campos que podem não existir
          const nome = unidade.nome || '-';
          const endereco = unidade.endereco || '-';
          const telefone = unidade.telefone || '-';
          const coordenador = unidade.coordenador || '-';
          const cidade = unidade.cidade || '-';
          
          html += `
                      <tr>
                          <td>${nome}</td>
                          <td>${endereco}</td>
                          <td>${telefone}</td>
                          <td>${coordenador}</td>
                          <td>${cidade}</td>
                          <td style="text-align: center; white-space: nowrap;">
                              <button class="action-btn editar-btn" title="Editar" onclick="editarUnidade(${unidade.id})">
                                  <i class="fas fa-edit"></i>
                              </button>
                              <button class="action-btn excluir-btn" title="Excluir" onclick="confirmarExclusaoUnidade(${unidade.id}, '${nome.replace(/'/g, "\\'")}')">
                                  <i class="fas fa-trash-alt"></i>
                              </button>
                          </td>
                      </tr>
                  `;
        });
      }

      html += `
                              </tbody>
                          </table>
                      </div>
                  </div>
                  
              </div>
          `;

      modal.innerHTML = html;
      document.body.appendChild(modal);


      modal.addEventListener('click', function (e) {
        if (e.target === this) {
          this.remove();
        }
      });
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao listar unidades:", err);
      alert("Erro ao carregar lista de unidades: " + err.message);
    });
}

function confirmarExclusaoUnidade(id, nome) {
  if (!id) {
    alert("ID da unidade não fornecido!");
    return;
  }

  if (confirm(`Tem certeza que deseja excluir a unidade "${nome}"?\nEsta ação não poderá ser desfeita.`)) {
    excluirUnidade(id);
  }
}


// Função para excluir unidade
function excluirUnidade(id) {
  if (!id) {
    alert("ID da unidade não fornecido!");
    return;
  }

  showLoading();

  fetch(`api/excluir_unidade.php?id=${id}`)
    .then(res => {
      if (!res.ok) {
        return res.json().then(errorData => {
          throw new Error(errorData.mensagem || `Erro HTTP: ${res.status}`);
        });
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      alert(response.mensagem || "Unidade excluída com sucesso!");

      removerModais();
      listarUnidades();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao excluir unidade:", err);
      alert("Erro ao excluir unidade: " + err.message);
    });
}

// Função para carregar cidades do IBGE
function carregarCidadesIBGE(datalistId) {
  fetch("https://servicodados.ibge.gov.br/api/v1/localidades/estados/52/municipios")
    .then(response => response.json())
    .then(data => {
      const datalist = document.getElementById(datalistId);
      if (datalist) {
        datalist.innerHTML = ''; // Limpar opções existentes
        data.forEach(cidade => {
          const option = document.createElement("option");
          option.value = cidade.nome;
          datalist.appendChild(option);
        });
      }
    })
    .catch(err => {
      console.error("Erro ao carregar cidades:", err);
    });
}

// Função para editar unidade
function editarUnidade(id) {
  removerModais();
  
  showLoading();

  fetch(`api/buscar_unidade.php?id=${id}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`Erro HTTP: ${res.status}`);
      }
      return res.json();
    })
    .then(response => {
      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      const unidade = response.data;

      // Criar modal de edição maior
      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
              <div class="modal" style="width: 600px; max-width: 90%;">
                  <div class="modal-header">
                      <span><i class="fas fa-edit"></i> Editar Unidade</span>
                      <button onclick="this.closest('.modal-backdrop').remove()">×</button>
                  </div>
                  <div class="modal-body">
                      <form id="editar-unidade-form">
                          <input type="hidden" name="id" value="${unidade.id}">
                          
                          <div class="form-group">
                              <label for="edit-nome">Nome da Unidade</label>
                              <input type="text" id="edit-nome" name="nome" value="${unidade.nome || ''}" required>
                          </div>
                          
                          <div class="form-group">
                              <label for="edit-endereco">Endereço</label>
                              <input type="text" id="edit-endereco" name="endereco" value="${unidade.endereco || ''}">
                          </div>
                          
                          <div class="form-group">
                              <label for="edit-telefone">Telefone</label>
                              <input type="text" id="edit-telefone" name="telefone" value="${unidade.telefone || ''}">
                          </div>
                          
                          <div class="form-group">
                              <label for="edit-coordenador">Comandante da Unidade</label>
                              <input type="text" id="edit-coordenador" name="coordenador" value="${unidade.coordenador || ''}">
                          </div>

                          <div class="form-group">
                              <label>Cidade</label>
                              <input list="lista-cidades-edit" id="edit-cidade" name="cidade" value="${unidade.cidade || ''}" placeholder="Digite a cidade">
                              <datalist id="lista-cidades-edit"></datalist>
                          </div>
                          
                          <div class="modal-footer">
                              <button type="button" class="btn btn-outline" onclick="this.closest('.modal-backdrop').remove()">
                                  Cancelar
                              </button>
                              <button type="submit" class="btn btn-primary">
                                  <i class="fas fa-save"></i> Salvar Alterações
                              </button>
                          </div>
                      </form>
                  </div>
              </div>
          `;

      modal.innerHTML = html;
      document.body.appendChild(modal);

      // Carregar cidades do IBGE para o modal de edição
      carregarCidadesIBGE('lista-cidades-edit');

      // Configurar o formulário
      document.getElementById('editar-unidade-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const dados = {};
        formData.forEach((value, key) => {
          dados[key] = value;
        });

        // Debug: verificar se a cidade está sendo capturada
        console.log('Dados de edição enviados:', dados);
        console.log('Cidade na edição:', dados.cidade);

        salvarEdicaoUnidade(dados, modal);
      });

      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao carregar unidade para edição:", err);
      alert("Erro ao carregar dados da unidade: " + err.message);
    });
}

// Função para salvar edição de unidade
function salvarEdicaoUnidade(dados, modal) {
  if (!dados.id) {
    alert("ID da unidade não fornecido!");
    return;
  }

  showLoading();

  fetch('api/editar_unidade.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(dados)
  })
    .then(res => {
      if (!res.ok) {
        return res.json().then(errorData => {
          throw new Error(errorData.mensagem || `Erro HTTP: ${res.status}`);
        });
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      alert(response.mensagem);

      // Fechar o modal
      if (modal) {
        modal.remove();
      }

      removerModais();
      
      // Recarregar a lista de unidades para refletir as alterações
      listarUnidades();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao salvar edição de unidade:", err);
      alert("Erro ao salvar alterações: " + err.message);
    });
}

// ==== FUNCIONALIDADE DE GERENCIAMENTO DE TURMAS ====

// Função para listar turmas (com modal amplo)
function listarTurmas() {
  removerModais();
  
  showLoading();
  fetch("api/listar_turma.php")
    .then(res => res.json())
    .then(turmas => {
      hideLoading();

      // Criar uma modal para mostrar a lista - ampla
      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
              <div class="modal" style="max-width: 80%; width: 900px;">
                  <div class="modal-header">
                      <span><i class="fas fa-chalkboard"></i> Lista de Turmas</span>
                      <button onclick="this.closest('.modal-backdrop').remove()">×</button>
                  </div>
                  <div class="modal-body" style="padding: 0;">
                      <div class="table-container" style="margin: 0; box-shadow: none; border-radius: 0;">
                          <table style="min-width: 100%;">
                              <thead>
                                  <tr>
                                      <th style="width: 20%;">Nome da Turma</th>
                                      <th style="width: 20%;">Unidade</th>
                                      <th style="width: 20%;">Professor</th>
                                      <th style="width: 10%;">Capacidade</th>
                                      <th style="width: 10%;">Horário</th>
                                      <th style="width: 10%;">Status</th>
                                      <th style="width: 10%; text-align: center;">Ações</th>
                                  </tr>
                              </thead>
                              <tbody>
          `;

      if (turmas.length === 0) {
        html += `
                  <tr>
                      <td colspan="7" style="text-align: center;">Nenhuma turma encontrada</td>
                  </tr>
              `;
      } else {
        turmas.forEach(turma => {
          html += `
                      <tr>
                          <td>${turma.nome_turma || '-'}</td>
                          <td>${turma.unidade_nome || '-'}</td>
                          <td>${turma.professor_nome || '-'}</td>
                          <td>${turma.capacidade || '0'} / ${turma.matriculados || '0'}</td>
                          <td>${turma.horario_inicio || '-'} - ${turma.horario_fim || '-'}</td>
                          <td>${formatarStatus(turma.status || 'ATIVO')}</td>
                          <td style="text-align: center; white-space: nowrap;">
                              <button class="action-btn editar-btn" title="Editar" onclick="editarTurma(${turma.id})">
                                  <i class="fas fa-edit"></i>
                              </button>
                              <button class="action-btn excluir-btn" title="Excluir" onclick="confirmarExclusaoTurma(${turma.id}, '${turma.nome_turma?.replace(/'/g, "\\'")}')">
                                  <i class="fas fa-trash-alt"></i>
                              </button>
                          </td>
                      </tr>
                  `;
        });
      }

      html += `
                              </tbody>
                          </table>
                      </div>
                  </div>
                 
                  </div>
              </div>
          `;

      modal.innerHTML = html;
      document.body.appendChild(modal);

      // Fechar modal ao clicar fora
      modal.addEventListener('click', function (e) {
        if (e.target === this) {
          this.remove();
        }
      });
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao listar turmas:", err);
      alert("Erro ao carregar lista de turmas.");
    });
}

function confirmarExclusaoTurma(id, nome) {
  if (!id) {
    alert("ID da turma não fornecido!");
    return;
  }
  
  if (confirm(`Tem certeza que deseja excluir a turma "${nome}"?\nEsta ação não poderá ser desfeita.`)) {
    excluirTurma(id);
  }
}


function listarProfessores() {
  removerModais();
  
  showLoading();
  fetch("api/listar_professor.php")
    .then(res => res.json())
    .then(professores => {
      hideLoading();

     
      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
          <div class="modal" style="max-width: 80%; width: 900px;">
              <div class="modal-header">
                  <span><i class="fas fa-user-tie"></i> Lista de Professores</span>
                  <button onclick="this.closest('.modal-backdrop').remove()">×</button>
              </div>
              <div class="modal-body" style="padding: 0;">
                  <div class="table-container" style="margin: 0; box-shadow: none; border-radius: 0;">
                      <table style="min-width: 100%;">
                          <thead>
                              <tr>
                                  <th style="width: 40%;">Nome</th>
                                  <th style="width: 30%;">Email</th>
                                  <th style="width: 20%;">Telefone</th>
                                  <th style="width: 10%; text-align: center;">Ações</th>
                              </tr>
                          </thead>
                          <tbody>
      `;

      if (professores.length === 0) {
        html += `
              <tr>
                  <td colspan="4" style="text-align: center;">Nenhum professor encontrado</td>
              </tr>
          `;
      } else {
        professores.forEach(prof => {
          html += `
                  <tr>
                      <td>${prof.nome || '-'}</td>
                      <td>${prof.email || '-'}</td>
                      <td>${prof.telefone || '-'}</td>
                      <td style="text-align: center; white-space: nowrap;">
                          <button class="action-btn editar-btn" title="Editar" onclick="editarProfessor(${prof.id})">
                              <i class="fas fa-edit"></i>
                          </button>
                          <button class="action-btn excluir-btn" title="Excluir" onclick="confirmarExclusaoProfessor(${prof.id}, '${prof.nome?.replace(/'/g, "\\'")}')">
                              <i class="fas fa-trash-alt"></i>
                          </button>
                      </td>
                  </tr>
              `;
        });
      }

      html += `
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      `;

      modal.innerHTML = html;
      document.body.appendChild(modal);

      modal.addEventListener('click', function (e) {
        if (e.target === this) {
          this.remove();
        }
      });
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao listar professores:", err);
      alert("Erro ao carregar lista de professores.");
    });
}

function confirmarExclusaoProfessor(id, nome) {
  if (!id) {
    alert("ID do professor não fornecido!");
    return;
  }
  
  if (confirm(`Tem certeza que deseja excluir o professor "${nome}"?\nEsta ação não poderá ser desfeita.`)) {
    excluirProfessor(id);
  }
}




// Função para gerar carteirinha
function gerarCarteirinha() {
  // Obter alunos selecionados
  const checkboxes = document.querySelectorAll('#matriculas-body input[type="checkbox"]:checked');

  if (checkboxes.length === 0) {
    alert('Por favor, selecione pelo menos um aluno para gerar a carteirinha.');
    return;
  }

  // Coletar IDs dos alunos selecionados
  const alunosIds = Array.from(checkboxes).map(checkbox => checkbox.value);

  // Mostrar overlay de carregamento
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'flex';
  }

  // Criar formulário para envio via POST
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/matricula/api/gerar_carteirinha.php'; // Caminho absoluto para evitar problemas
  form.style.display = 'none';
  form.target = '_blank'; // Abre em nova aba

  // Adicionar campo de IDs dos alunos
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'alunos_ids';
  input.value = alunosIds.join(',');

  form.appendChild(input);
  document.body.appendChild(form);

  // Enviar para download
  form.submit();

  // Esconder overlay após um pequeno delay
  setTimeout(() => {
    if (loadingOverlay) {
      loadingOverlay.style.display = 'none';
    }
  }, 2000);

  // Limpar formulário
  setTimeout(() => {
    document.body.removeChild(form);
  }, 1000);
}

// ==== FUNÇÕES DE FILTROS E INICIALIZAÇÃO ====

// Inicializar selects de filtros
function inicializarFiltros() {
  // Carregar unidades para o filtro
  fetch("api/listar_unidades.php")
    .then(res => res.json())
    .then(response => {
      const select = document.getElementById("filtro-unidade");
      const unidades = response.data || [];
      if (select) {
        unidades.forEach(unidade => {
          const opt = document.createElement("option");
          opt.value = unidade.id;
          opt.textContent = unidade.nome;
          select.appendChild(opt);
        });
      }
    })
    .catch(err => {
      console.error("Erro ao carregar unidades para filtro:", err);
    });

  // Carregar turmas para o filtro
  fetch("api/listar_turma.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("filtro-turma");
      if (select) {
        data.forEach(turma => {
          const opt = document.createElement("option");
          opt.value = turma.id;
          opt.textContent = turma.nome_turma || turma.nome;
          select.appendChild(opt);
        });
      }
    })
    .catch(err => {
      console.error("Erro ao carregar turmas para filtro:", err);
    });
}

// Aplicar filtros
function aplicarFiltros() {
  showLoading();
  const form = document.getElementById("filter-form");
  const formData = new FormData(form);
  const params = new URLSearchParams();

  formData.forEach((valor, chave) => {
    if (valor) {
      params.append(chave, valor);
    }
  });

  fetch(`api/filtrar_matriculas.php?${params.toString()}`)
    .then(res => res.json())
    .then(matriculas => {
      const tbody = document.getElementById("matriculas-body");
      tbody.innerHTML = "";

      // Atualizar contador de resultados
      document.getElementById("total-results").textContent = matriculas.length;

      matriculas.forEach(m => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td><input type="checkbox" value="${m.aluno_id}"></td>
          <td>${m.aluno_nome}</td>
          <td>${m.responsaveis || '-'}</td>
          <td></td>
          <td>${m.unidade}</td>
          <td>${m.turma}</td>
          <td>${formatarData(m.data_matricula)}</td>
          <td></td>
          <td>${formatarStatus(m.status)}</td>
          <td>
              <button class="action-btn editar-btn" title="Editar" data-id="${m.aluno_id}">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn visualizar-btn" title="Visualizar" data-id="${m.aluno_id}">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn excluir-btn" title="Excluir" data-id="${m.aluno_id}">
                <i class="fas fa-trash-alt"></i>
              </button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      adicionarEventosAosBotoes();
      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao filtrar matrículas:", err);
      alert("Erro ao aplicar filtros.");
    });
}

// Gerar relatório em PDF
function gerarRelatorioPDF() {
  const form = document.getElementById("filter-form");
  const formData = new FormData(form);
  const params = new URLSearchParams();

  formData.forEach((valor, chave) => {
    if (valor) {
      params.append(chave, valor);
    }
  });

  // Gerar nome do arquivo
  const dataAtual = new Date().toISOString().slice(0, 10);
  const nomeArquivo = `relatorio_matriculas_${dataAtual}.pdf`;

  // Redirecionar para o endpoint de PDF com os filtros
  window.open(`api/gerar_pdf_matriculas.php?${params.toString()}&filename=${nomeArquivo}`, '_blank');
}

// ==== CARREGAMENTO DE DADOS ====

// Carregar dados para os selects
function carregarTurmas() {
  fetch("api/listar_turma.php")
    .then(res => res.json())
    .then(data => {
      const selectNova = document.getElementById("turma");
      const selectEditar = document.getElementById("turma-editar");

      if (selectNova) {
        selectNova.innerHTML = '<option value="">Selecione</option>';
        data.forEach(t => {
          const opt = document.createElement("option");
          opt.value = t.id;
          opt.textContent = t.nome_turma || t.nome;
          selectNova.appendChild(opt);
        });
      }

      if (selectEditar) {
        selectEditar.innerHTML = '<option value="">Selecione</option>';
        data.forEach(t => {
          const opt = document.createElement("option");
          opt.value = t.id;
          opt.textContent = t.nome_turma || t.nome;
          selectEditar.appendChild(opt);
        });
      }
    })
    .catch(err => {
      console.error("Erro ao carregar turmas:", err);
    });
}

function carregarTurmasEditar() {
  fetch("api/listar_turma.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("turma-editar");
      if (!select) return;

      select.innerHTML = '<option value="">Selecione</option>';
      data.forEach(turma => {
        const opt = document.createElement("option");
        opt.value = turma.id;
        opt.textContent = turma.nome_turma || turma.nome || `Turma ${turma.id}`;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("Erro ao carregar turmas para edição:", err);
    });
}

function carregarUnidades() {
  fetch("api/listar_unidades.php")
    .then(res => res.json())
    .then(response => {
      const select = document.getElementById("unidade");
      const unidades = response.data || [];
      if (!select) return;
      select.innerHTML = '<option value="">Selecione</option>';
      unidades.forEach(unidade => {
        const opt = document.createElement("option");
        opt.value = unidade.id;
        opt.textContent = unidade.nome;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("Erro ao carregar unidades:", err);
    });
}

function carregarUnidadesEditar() {
  fetch("api/listar_unidades.php")
    .then(res => res.json())
    .then(response => {
      const select = document.getElementById("unidade-editar");
      const unidades = response.data || [];
      if (!select) return;
      select.innerHTML = '<option value="">Selecione</option>';
      unidades.forEach(unidade => {
        const opt = document.createElement("option");
        opt.value = unidade.id;
        opt.textContent = unidade.nome;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("Erro ao carregar unidades para edição:", err);
    });
}

function carregarProfessores() {
  fetch("api/listar_professores.php")
    .then(res => res.json())
    .then(data => {
      const select = document.querySelector("[name='professor_responsavel']");
      if (!select) return;
      select.innerHTML = '<option value="">Selecione</option>';
      data.forEach(prof => {
        const opt = document.createElement("option");
        opt.value = prof.id;
        opt.textContent = prof.nome;
        select.appendChild(opt);
      });
    })
    .catch(err => {
      console.error("Erro ao carregar professores:", err);
    });
}

// Carregar matrículas
function carregarMatriculas() {
  showLoading();
  fetch("api/listar_matriculas.php")
    .then(res => res.json())
    .then(matriculas => {
      const tbody = document.getElementById("matriculas-body");
      tbody.innerHTML = "";

      // Atualizar contador de resultados caso exista
      const totalResults = document.getElementById("total-results");
      if (totalResults) {
        totalResults.textContent = matriculas.length;
      }

      matriculas.forEach(m => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td><input type="checkbox" value="${m.aluno_id}"></td>
          <td>${m.aluno_nome}</td>
          <td>${m.responsaveis || '-'}</td>
          <td></td>
          <td>${m.unidade}</td>
          <td>${m.turma}</td>
          <td>${formatarData(m.data_matricula)}</td>
          <td></td>
          <td>${formatarStatus(m.status)}</td>
          <td>
              <button class="action-btn editar-btn" title="Editar" data-id="${m.aluno_id}">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn visualizar-btn" title="Visualizar" data-id="${m.aluno_id}">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn excluir-btn" title="Excluir" data-id="${m.aluno_id}">
                <i class="fas fa-trash-alt"></i>
              </button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      adicionarEventosAosBotoes();
      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao carregar matrículas:", err);
      alert("Erro ao carregar matrículas.");
    });
}

// ==== FUNÇÕES PARA MANIPULAÇÃO DE DADOS ====

// Adicionar eventos aos botões
function adicionarEventosAosBotoes() {
  document.querySelectorAll(".editar-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      editarMatricula(btn.dataset.id);
    });
  });

  document.querySelectorAll(".visualizar-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      visualizarMatricula(btn.dataset.id);
    });
  });

  document.querySelectorAll(".excluir-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      if (confirm("Deseja excluir esta matrícula?")) {
        excluirMatricula(btn.dataset.id);
      }
    });
  });
}

// Funções para editar e excluir (placeholders para implementação futura)
function editarTurma(id) {
  removerModais();
  
  showLoading();

  // Carregar unidades e professores para os selects
  Promise.all([
    fetch("api/listar_unidades.php").then(res => res.json()),
    fetch("api/listar_professores.php").then(res => res.json()),
    fetch(`api/buscar_turma.php?id=${id}`).then(res => res.json())
  ])
    .then(([unidadesResp, professoresResp, turmaResp]) => {
      hideLoading();
      
      if (turmaResp.status === 'erro') {
        throw new Error(turmaResp.mensagem);
      }

      const turma = turmaResp.data;
      const unidades = unidadesResp.data || [];
      const professores = professoresResp || [];

      // Criar modal de edição 
      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
          <div class="modal" style="width: 700px; max-width: 90%;">
              <div class="modal-header">
                  <span><i class="fas fa-edit"></i> Editar Turma</span>
                  <button onclick="this.closest('.modal-backdrop').remove()">×</button>
              </div>
              <div class="modal-body">
                  <form id="editar-turma-form">
                      <input type="hidden" name="id" value="${turma.id}">
                      
                      <div class="form-group">
                          <label for="edit-nome-turma">Nome da Turma</label>
                          <input type="text" id="edit-nome-turma" name="nome_turma" value="${turma.nome_turma || ''}" required>
                      </div>
                      
                      <div class="form-row">
                          <div class="form-group col-md-6">
                              <label for="edit-unidade">Unidade</label>
                              <select id="edit-unidade" name="id_unidade" required>
                                  <option value="">Selecione</option>
                                  ${unidades.map(u => `<option value="${u.id}" ${turma.id_unidade == u.id ? 'selected' : ''}>${u.nome}</option>`).join('')}
                              </select>
                          </div>
                          
                          <div class="form-group col-md-6">
                              <label for="edit-professor">Professor</label>
                              <select id="edit-professor" name="id_professor">
                                  <option value="">Selecione</option>
                                  ${professores.map(p => `<option value="${p.id}" ${turma.id_professor == p.id ? 'selected' : ''}>${p.nome}</option>`).join('')}
                              </select>
                          </div>
                      </div>
                      
                      <div class="form-row">
                          <div class="form-group col-md-6">
                              <label for="edit-capacidade">Capacidade</label>
                              <input type="number" id="edit-capacidade" name="capacidade" value="${turma.capacidade || '0'}" min="0">
                          </div>
                          
                          <div class="form-group col-md-6">
                              <label for="edit-status">Status</label>
                              <select id="edit-status" name="status">
                                  <option value="Em Andamento" ${turma.status === 'Em Andamento' ? 'selected' : ''}>Ativa</option>
                                  
                                  <option value="Cancelada" ${turma.status === 'Cancelada' ? 'selected' : ''}>Cancelada</option>
                              </select>
                          </div>
                      </div>
                      
                      <div class="form-group">
                          <label for="edit-dias-aula">Dias de Aula</label>
                          <input type="text" id="edit-dias-aula" name="dias_aula" value="${turma.dias_aula || ''}" placeholder="Ex: Seg, Qua, Sex">
                      </div>
                      
                      <div class="form-row">
                          <div class="form-group col-md-6">
                              <label for="edit-horario-inicio">Horário de Início</label>
                              <input type="time" id="edit-horario-inicio" name="horario_inicio" value="${turma.horario_inicio || ''}">
                          </div>
                          
                          <div class="form-group col-md-6">
                              <label for="edit-horario-fim">Horário de Término</label>
                              <input type="time" id="edit-horario-fim" name="horario_fim" value="${turma.horario_fim || ''}">
                          </div>
                      </div>
                      
                      <div class="modal-footer">
                          <button type="button" class="btn btn-outline" onclick="this.closest('.modal-backdrop').remove()">
                              Cancelar
                          </button>
                          <button type="submit" class="btn btn-primary">
                              <i class="fas fa-save"></i> Salvar Alterações
                          </button>
                      </div>
                  </form>
              </div>
          </div>
      `;

      modal.innerHTML = html;
      document.body.appendChild(modal);

      // Configurar o formulário
      document.getElementById('editar-turma-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const dados = {};
        formData.forEach((value, key) => {
          dados[key] = value;
        });

        salvarEdicaoTurma(dados, modal);
      });
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao carregar dados para edição de turma:", err);
      alert("Erro ao carregar dados: " + err.message);
    });
}

function salvarEdicaoTurma(dados, modal) {
  if (!dados.id) {
    alert("ID da turma não fornecido!");
    return;
  }

  showLoading();
  
  // Validar e converter tipos de dados para evitar problemas
  if (dados.capacidade) {
    dados.capacidade = parseInt(dados.capacidade);
  }
  if (dados.id_unidade) {
    dados.id_unidade = parseInt(dados.id_unidade);
  }
  if (dados.id_professor) {
    dados.id_professor = parseInt(dados.id_professor);
  }
  
  // Usar XMLHttpRequest em vez de fetch para mais controle e diagnóstico
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'api/editar_turma.php', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) { // Requisição completa
      hideLoading();
      
      if (xhr.status === 200) {
        try {
          // Tentar processar como JSON
          const response = JSON.parse(xhr.responseText);
          
          if (response.status === 'sucesso') {
            alert(response.mensagem || "Turma atualizada com sucesso!");
            
            // Fechar o modal
            if (modal) {
              modal.remove();
            }
            
            removerModais();
            
            // Recarregar a lista de turmas
            listarTurmas();
          } else {
            alert("Erro: " + (response.mensagem || "Erro desconhecido"));
          }
        } catch (jsonError) {
          console.error("Erro ao analisar JSON:", jsonError);
          alert("Erro do servidor. Resposta não é JSON válido: " + xhr.responseText.substring(0, 100) + "...");
        }
      } else {
      
        alert(`Erro do servidor: ${xhr.status} ${xhr.statusText}`);
      }
    }
  };
  
  xhr.onerror = function() {
    hideLoading();
    console.error("Erro de rede ao salvar turma");
    alert("Erro de conexão. Verifique sua rede e tente novamente.");
  };
  
  xhr.timeout = 15000;
  xhr.ontimeout = function() {
    hideLoading();
    console.error("Timeout ao salvar turma");
    alert("A requisição demorou muito. Tente novamente.");
  };
  
  try {
    xhr.send(JSON.stringify(dados));
  } catch (e) {
    hideLoading();
    console.error("Erro ao enviar dados:", e);
    alert("Erro ao enviar dados: " + e.message);
  }
}


function excluirTurma(id) {
   if (!id) {
    alert("ID da Truma não fornecido!");
    return;
  }

  showLoading();

  fetch(`api/excluir_turma.php?id=${id}`)
    .then(res => {
      if (!res.ok) {
        return res.json().then(errorData => {
          throw new Error(errorData.mensagem || `Erro HTTP: ${res.status}`);
        });
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      alert(response.mensagem || "Turma excluída com sucesso!");

      listarTurmas();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao excluir Turma:", err);
      alert("Erro ao excluir Turma: " + err.message);
    });
}

function editarMatricula(aluno_id) {
  // Não remover os modais específicos, apenas esconder outros modais
  removerModais();
  
  showLoading();
  carregarUnidadesEditar();
  carregarTurmasEditar();

  fetch(`api/buscar_matricula.php?id=${aluno_id}`)
    .then(res => res.json())
    .then(data => {
      const modal = document.getElementById("edit-matricula-modal");
      modal.style.display = "flex";

      document.querySelector("#editar-id").value = data.matricula_id;
      document.querySelector("[name='aluno_nome']").value = data.aluno_nome;

      setTimeout(() => {
        document.getElementById("turma-editar").value = data.turma_id;
        document.getElementById("unidade-editar").value = data.unidade_id;

        if (document.getElementById("turma-editar").value === '') {
          const turmaLabel = document.createElement('p');
          turmaLabel.textContent = `Turma atual: ${data.nome_turma || data.turma_id}`;
          document.getElementById("turma-editar").parentNode.appendChild(turmaLabel);
        }

        if (document.getElementById("unidade-editar").value === '') {
          const unidadeLabel = document.createElement('p');
          unidadeLabel.textContent = `Unidade atual: ${data.unidade_nome || data.unidade_id}`;
          document.getElementById("unidade-editar").parentNode.appendChild(unidadeLabel);
        }
      }, 500); 

      document.querySelector("[name='status']").value = data.status;

     // Preencher o status no programa
      const statusProgramaField = document.querySelector("[name='status-programa']");
      if (statusProgramaField) {
          statusProgramaField.value = data.status_programa || 'novato';
      }

      let dataMatricula = data.data_matricula || '';
      if (dataMatricula.includes(' ')) {
        dataMatricula = dataMatricula.split(' ')[0];
      }
      document.querySelector("[name='data_matricula']").value = dataMatricula;

      const container = document.getElementById("responsaveis-editar");
      container.innerHTML = "";
      if (data.responsaveis && Array.isArray(data.responsaveis)) {
        data.responsaveis.forEach((resp, index) => {
          container.innerHTML += `
            <div class="responsavel-item">
              <div class="responsavel-nome"><i class="fas fa-user"></i> <strong>${resp.nome}</strong></div>
              <div class="responsavel-contato">
                <div><i class="fas fa-phone"></i> ${resp.telefone || 'Não informado'}</div>
                <div><i class="fas fa-envelope"></i> ${resp.email || 'Não informado'}</div>
              </div>
            </div>
          `;
        });
      }
      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao buscar matrícula:", err);
      alert("Erro ao carregar dados para edição.");
    });
}

function visualizarMatricula(id) {
  // Não remover os modais específicos, apenas esconder outros modais
  removerModais();
  
  showLoading();
  fetch(`api/buscar_matricula.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      const modal = document.getElementById("view-details-modal");
      const content = document.getElementById("detalhes-matricula");

      let turmaNome = '';
      if (data.nome_turma) turmaNome = data.nome_turma;
      else if (data.turma_nome) turmaNome = data.turma_nome;
      else if (data.turma && typeof data.turma === 'string' && data.turma !== data.turma_id) turmaNome = data.turma;
      else turmaNome = `Turma ID: ${data.turma_id || data.turma || 'Não definida'}`;

      let unidadeNome = '';
      if (data.unidade_nome) unidadeNome = data.unidade_nome;
      else if (data.unidade && typeof data.unidade === 'string' && !isNumeric(data.unidade)) unidadeNome = data.unidade;
      else unidadeNome = `Unidade ID: ${data.unidade_id || data.unidade || 'Não definida'}`;

      let statusFormatado = formatarStatus(data.status);
      let dataFormatada = formatarData(data.data_matricula);

      let html = `
        <div class="summary-card">
          <div class="summary-info">
            <div class="summary-name">${data.aluno_nome}</div>
            <div class="summary-details">
              <div class="detail-item">
                <span class="detail-label">Unidade</span>
                <span class="detail-value">${unidadeNome}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Turma</span>
                <span class="detail-value">${turmaNome}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Data da Matrícula</span>
                <span class="detail-value">${dataFormatada}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">${statusFormatado}</span>
              </div>
            </div>
          </div>
        </div>
      `;

      if (data.responsaveis && Array.isArray(data.responsaveis)) {
        html += '<div class="summary-section"><h3>Responsáveis</h3><ul class="responsaveis-list">';
        data.responsaveis.forEach((r, i) => {
          html += `
            <li class="responsavel-item">
              <div class="responsavel-nome"><i class="fas fa-user"></i> <strong>${r.nome}</strong></div>
              <div class="responsavel-contato">
                <div><i class="fas fa-phone"></i> ${r.telefone || 'Não informado'}</div>
                <div><i class="fas fa-envelope"></i> ${r.email || 'Não informado'}</div>
              </div>
            </li>
          `;
        });
        html += '</ul></div>';
      }

      content.innerHTML = html;
      modal.style.display = "flex";
      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao visualizar matrícula:", err);
      alert("Erro ao visualizar matrícula.");
    });
}

function isNumeric(value) {
  return !isNaN(parseFloat(value)) && isFinite(value);
}

function excluirMatricula(id) {
  if (!confirm("Tem certeza que deseja excluir esta matrícula?")) {
    return;
  }

  showLoading();
  fetch(`api/excluir_matricula.php?id=${id}`)
    .then(res => res.json())
    .then(resp => {
      hideLoading();
      if (resp.status === "sucesso") {
        alert("Matrícula excluída com sucesso!");
        carregarMatriculas();
      } else {
        alert("Erro ao excluir matrícula: " + (resp.mensagem || ""));
      }
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao excluir matrícula:", err);
      alert("Erro inesperado ao excluir matrícula.");
    });
}

function editarProfessor(id) {
  removerModais();
  
  showLoading();

  fetch(`api/buscar_professor.php?id=${id}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`Erro HTTP: ${res.status}`);
      }
      return res.json();
    })
    .then(response => {
      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      const professor = response.data;

      const modal = document.createElement('div');
      modal.className = 'modal-backdrop';
      modal.style.display = 'flex';

      let html = `
          <div class="modal" style="width: 600px; max-width: 90%;">
              <div class="modal-header">
                  <span><i class="fas fa-edit"></i> Editar Professor</span>
                  <button onclick="this.closest('.modal-backdrop').remove()">×</button>
              </div>
              <div class="modal-body">
                  <form id="editar-professor-form">
                      <input type="hidden" name="id" value="${professor.id}">
                      
                      <div class="form-group">
                          <label for="edit-nome-professor">Nome do Professor</label>
                          <input type="text" id="edit-nome-professor" name="nome" value="${professor.nome || ''}" required>
                      </div>
                      
                      <div class="form-group">
                          <label for="edit-email-professor">Email</label>
                          <input type="email" id="edit-email-professor" name="email" value="${professor.email || ''}">
                      </div>

                      <div class="form-group">
                          <label for="edit-senha-professor">Senha (deixe em branco para manter a atual)</label>
                          <input type="password" id="edit-senha-professor" name="senha" placeholder="Nova senha (opcional)">
                      </div>
                      
                      <div class="form-group">
                          <label for="edit-telefone-professor">Telefone</label>
                          <input type="text" id="edit-telefone-professor" name="telefone" value="${professor.telefone || ''}">
                      </div>
                      
                      <div class="modal-footer">
                          <button type="button" class="btn btn-outline" onclick="this.closest('.modal-backdrop').remove()">
                              Cancelar
                          </button>
                          <button type="submit" class="btn btn-primary">
                              <i class="fas fa-save"></i> Salvar Alterações
                          </button>
                      </div>
                  </form>
              </div>
          </div>
      `;

      modal.innerHTML = html;
      document.body.appendChild(modal);

      document.getElementById('editar-professor-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const dados = {};
        formData.forEach((value, key) => {
          
          if (key !== 'senha' || value.trim() !== '') {
            dados[key] = value;
          }
        });

        salvarEdicaoProfessor(dados, modal);
      });

      hideLoading();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao carregar professor para edição:", err);
      alert("Erro ao carregar dados do professor: " + err.message);
    });
} 


function salvarEdicaoProfessor(dados, modal) {
  if (!dados.id) {
    alert("ID do professor não fornecido!");
    return;
  }

  showLoading();

  fetch('api/editar_professor.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(dados)
  })
    .then(res => {
      if (!res.ok) {
        return res.json().then(errorData => {
          throw new Error(errorData.mensagem || `Erro HTTP: ${res.status}`);
        });
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      alert(response.mensagem || "Professor atualizado com sucesso!");

      if (modal) {
        modal.remove();
      }

      removerModais();
      
      listarProfessores();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao salvar edição de professor:", err);
      alert("Erro ao salvar alterações: " + err.message);
    });
}
function excluirProfessor(id) {
  if (!id) {
    alert("ID do professor não fornecido!");
    return;
  }

  showLoading();

  fetch(`api/excluir_professor.php?id=${id}`)
    .then(res => {
      if (!res.ok) {
        return res.json().then(errorData => {
          throw new Error(errorData.mensagem || `Esse Professor(a) pertence a uma turma. Primeiramente para excluí-lo remova-o da turma a qual ele pertence`);
        });
      }
      return res.json();
    })
    .then(response => {
      hideLoading();

      if (response.status === 'erro') {
        throw new Error(response.mensagem);
      }

      alert(response.mensagem || "Professor excluído com sucesso!");

      // Recarregar a lista de professores para refletir a exclusão
      listarProfessores();
    })
    .catch(err => {
      hideLoading();
      console.error("Erro ao excluir professor: Esse Professor(a) pertence a uma turma. Primeiramente para excluí-lo remova-o da turma a qual ele pertence");
      alert("Erro ao excluir professor: " + "Esse Professor(a) pertence a uma turma. Primeiramente para excluí-lo remova-o da turma a qual ele pertence");
    });
}