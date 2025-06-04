// JS corrigido com todas as funções completas e funcionais

document.addEventListener("DOMContentLoaded", () => {
  carregarMatriculas();
  // Inicializar filtros
  inicializarFiltros();

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
    formFiltro.addEventListener("submit", function(e) {
      e.preventDefault();
      aplicarFiltros();
    });
  }
  
  // Botão limpar filtros
  const limparFiltros = document.getElementById("limpar-filtros");
  if (limparFiltros) {
    limparFiltros.addEventListener("click", function() {
      document.getElementById("filter-form").reset();
      carregarMatriculas(); // Recarrega dados sem filtros
    });
  }
  
  // Botão gerar PDF
  const gerarPDF = document.getElementById("gerar-pdf");
  if (gerarPDF) {
    gerarPDF.addEventListener("click", function() {
      gerarRelatorioPDF();
    });
  }

  const botaoNovaTurma = document.getElementById("nova-turma-btn");
  if (botaoNovaTurma) {
    botaoNovaTurma.addEventListener("click", () => {
      document.getElementById("nova-turma-modal").style.display = "flex";
      carregarUnidades();
      carregarProfessores();
    });
  }

  const botaoNovaUnidade = document.querySelector("#nova-unidade-btn");
  if (botaoNovaUnidade) {
    botaoNovaUnidade.addEventListener("click", () => {
      document.getElementById("nova-unidade-modal").style.display = "flex";
    });
  }

  // Botão Novo Professor
  const botaoNovoProfessor = document.querySelector("#novo-professor-btn");
  if (botaoNovoProfessor) {
    botaoNovoProfessor.addEventListener("click", () => {
      document.getElementById("novo-professor-modal").style.display = "flex";
    });
  }

  const formNovaTurma = document.getElementById("nova-turma-form");
  if (formNovaTurma) {
    formNovaTurma.addEventListener("submit", function (e) {
      e.preventDefault();
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
          console.error("Erro ao salvar turma:", err);
          alert("Erro inesperado ao salvar turma.");
        });
    });
  }

  const formNovaUnidade = document.getElementById("nova-unidade-form");
  if (formNovaUnidade) {
    formNovaUnidade.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      //console.log("Enviando dados de nova unidade:", dados);

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
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      //console.log("Enviando dados de novo professor:", dados);

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
          console.error("Erro ao salvar professor:", err);
          alert("Erro inesperado ao cadastrar professor: " + err.message);
        });
    });
  }

  const formEditarMatricula = document.getElementById("edit-matricula-form");
  if (formEditarMatricula) {
    formEditarMatricula.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const dados = {};
      formData.forEach((valor, chave) => {
        dados[chave] = valor;
      });

      // Add some console logging to debug
      //console.log("Dados enviados:", dados);

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
          if (resp.status === "sucesso") {
            alert("Matrícula atualizada com sucesso!");
            document.getElementById("edit-matricula-modal").style.display = "none";
            carregarMatriculas();
          } else {
            alert("Erro ao atualizar matrícula: " + resp.mensagem);
          }
        })
        .catch(err => {
          console.error("Erro ao editar matrícula:", err);
          alert("Erro inesperado ao editar matrícula: " + err.message);
        });
    });
  }
}); // Fechamento correto do DOMContentLoaded

// Inicializar selects de filtros
function inicializarFiltros() {
  // Carregar unidades para o filtro
  fetch("api/listar_unidades.php")
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById("filtro-unidade");
      if (select) {
        data.forEach(unidade => {
          const opt = document.createElement("option");
          opt.value = unidade.id;
          opt.textContent = unidade.nome;
          select.appendChild(opt);
        });
      }
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
    });
}

// Aplicar filtros
function aplicarFiltros() {
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
          <td><input type="checkbox"></td>
          <td>${m.aluno_nome}</td>
          <td>${m.responsaveis}</td>
          <td>-</td>
          <td>${m.unidade}</td>
          <td>${m.turma}</td>
          <td>${m.data_matricula}</td>
          <td>-</td>
          <td>${m.status}</td>
          <td>
              <button class="editar-btn" data-id="${m.aluno_id}">✏️</button>
              <button class="visualizar-btn" data-id="${m.aluno_id}">🔍</button>
              <button class="excluir-btn" data-id="${m.aluno_id}">🗑️</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
      
      adicionarEventosAosBotoes();
    })
    .catch(err => {
      console.error("Erro ao filtrar matrículas:", err);
      alert("Erro ao aplicar filtros.");
    });
}

// Gerar relatório em PDF
// Gerar relatório em PDF
function gerarRelatorioPDF() {
  // Mostrar indicador de carregamento (se existir)
  const loadingOverlay = document.getElementById('loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.style.display = 'flex';
  }
  
  const form = document.getElementById("filter-form");
  const formData = new FormData(form);
  const params = new URLSearchParams();
  
  // Incluir todos os valores de filtros não vazios
  formData.forEach((valor, chave) => {
    if (valor) {
      params.append(chave, valor);
    }
  });
  
  // Verificar se há alunos selecionados (checkboxes)
  const alunosSelecionados = [];
  document.querySelectorAll('#matriculas-body input[type="checkbox"]:checked').forEach(checkbox => {
    if (checkbox.value) {
      alunosSelecionados.push(checkbox.value);
    }
  });
  
  // Se houver alunos selecionados, adicionar ao parâmetro
  if (alunosSelecionados.length > 0) {
    params.append('alunos_ids', alunosSelecionados.join(','));
  }
  
  // Gerar nome do arquivo
  const dataAtual = new Date().toISOString().slice(0, 10);
  const nomeArquivo = `relatorio_matriculas_${dataAtual}.pdf`;
  params.append('filename', nomeArquivo);
  
  // Criar URL completa
  const url = `api/gerar_pdf_matriculas.php?${params.toString()}`;
  
  // Abrir em nova aba
  const pdfWindow = window.open(url, '_blank');
  
  // Ocultar indicador de carregamento após um curto período
  // (não podemos detectar quando o PDF terminou de carregar em outra aba)
  setTimeout(() => {
    if (loadingOverlay) {
      loadingOverlay.style.display = 'none';
    }
    
    // Se a janela foi bloqueada pelo navegador
    if (!pdfWindow || pdfWindow.closed || typeof pdfWindow.closed === 'undefined') {
      alert('O relatório PDF foi bloqueado pelo navegador. Por favor, permita pop-ups para este site.');
    }
  }, 2000);
}

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
        // Certifique-se de que estamos usando o nome correto da propriedade
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
    .then(data => {
      const select = document.getElementById("unidade");
      if (!select) return;
      select.innerHTML = '<option value="">Selecione</option>';
      data.forEach(unidade => {
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
    .then(data => {
      const select = document.getElementById("unidade-editar");
      if (!select) return;
      select.innerHTML = '<option value="">Selecione</option>';
      data.forEach(unidade => {
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

// Função para exibir a lista completa de professores (opcional)
function listarTodosProfessores() {
  fetch("api/listar_professores.php")
    .then(res => res.json())
    .then(professores => {
      const tbody = document.getElementById("professores-body");
      if (!tbody) return;
      
      tbody.innerHTML = "";

      professores.forEach(prof => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${prof.nome}</td>
          <td>${prof.email || '-'}</td>
          <td>${prof.telefone || '-'}</td>
          <td>
            <button class="editar-prof-btn" data-id="${prof.id}">✏️</button>
            <button class="excluir-prof-btn" data-id="${prof.id}">🗑️</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Adicionar eventos aos botões
      adicionarEventosAosBotoesProfessor();
    })
    .catch(err => {
      console.error("Erro ao listar professores:", err);
    });
}

// Adicionar eventos aos botões da lista de professores (opcional)
function adicionarEventosAosBotoesProfessor() {
  document.querySelectorAll(".editar-prof-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      editarProfessor(btn.dataset.id);
    });
  });

  document.querySelectorAll(".excluir-prof-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      if (confirm("Deseja excluir este professor?")) {
        excluirProfessor(btn.dataset.id);
      }
    });
  });
}

function carregarMatriculas() {
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
          <td><input type="checkbox"></td>
          <td>${m.aluno_nome}</td>
          <td>${m.responsaveis}</td>
          <td>-</td>
          <td>${m.unidade}</td>
          <td>${m.turma}</td>
          <td>${m.data_matricula}</td>
          <td>-</td>
          <td>${m.status}</td>
          <td>
              <button class="editar-btn" data-id="${m.aluno_id}">✏️</button>
              <button class="visualizar-btn" data-id="${m.aluno_id}">🔍</button>
              <button class="excluir-btn" data-id="${m.aluno_id}">🗑️</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      adicionarEventosAosBotoes();
    })
    .catch(err => {
      console.error("Erro ao carregar matrículas:", err);
      alert("Erro ao carregar matrículas.");
    });
}

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

function editarMatricula(aluno_id) {
  carregarUnidadesEditar();
  carregarTurmasEditar();

  fetch(`api/buscar_matricula.php?id=${aluno_id}`)
    .then(res => res.json())
    .then(data => {
      //console.log("Dados para edição:", data); // Log para depuração
      
      const modal = document.getElementById("edit-matricula-modal");
      modal.style.display = "flex";

      // Use o ID da matrícula, não o ID do aluno
      document.querySelector("#editar-id").value = data.matricula_id;
      
      document.querySelector("[name='aluno_nome']").value = data.aluno_nome;
      
      // Aguarde um momento para que os dropdowns sejam carregados antes de tentar definir valores
      setTimeout(() => {
        // Use os IDs para os selects
        document.getElementById("turma-editar").value = data.turma_id;
        document.getElementById("unidade-editar").value = data.unidade_id;
        
        // Se os selects não tiverem valores, vamos mostrar os nomes em um campo de texto somente leitura
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
      }, 500); // Aguarde 500ms para que os dropdowns sejam carregados
      
      document.querySelector("[name='status']").value = data.status;
      
      // Formata a data para o input
      let dataMatricula = data.data_matricula || '';
      if (dataMatricula.includes(' ')) {
        dataMatricula = dataMatricula.split(' ')[0];
      }
      document.querySelector("[name='data_matricula']").value = dataMatricula;

      // Exibe os responsáveis
      const container = document.getElementById("responsaveis-editar");
      container.innerHTML = "";
      if (data.responsaveis && Array.isArray(data.responsaveis)) {
        data.responsaveis.forEach((resp, index) => {
          container.innerHTML += `
            <div class="responsavel-item">
              <strong>${index + 1}:</strong> ${resp.nome} - ${resp.telefone} - ${resp.email}
            </div>
          `;
        });
      }
    })
    .catch(err => {
      console.error("Erro ao buscar matrícula:", err);
      alert("Erro ao carregar dados para edição.");
    });
}

function visualizarMatricula(id) {
  fetch(`api/buscar_matricula.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      // Adicionar log para verificar o formato exato dos dados recebidos
      //console.log("Dados para visualização:", data);
      
      const modal = document.getElementById("view-details-modal");
      const content = document.getElementById("detalhes-matricula");
      
      // Vamos fazer uma verificação completa dos campos disponíveis
      let turmaNome = '';
      if (data.nome_turma) turmaNome = data.nome_turma;
      else if (data.turma_nome) turmaNome = data.turma_nome;
      else if (data.turma && typeof data.turma === 'string' && data.turma !== data.turma_id) turmaNome = data.turma;
      else turmaNome = `Turma ID: ${data.turma_id || data.turma || 'Não definida'}`;

      let unidadeNome = '';
      if (data.unidade_nome) unidadeNome = data.unidade_nome;
      else if (data.unidade && typeof data.unidade === 'string' && !isNumeric(data.unidade)) unidadeNome = data.unidade;
      else unidadeNome = `Unidade ID: ${data.unidade_id || data.unidade || 'Não definida'}`;
      
      let html = `
        <div class="summary-card">
          <div class="summary-info">
            <div class="summary-name">${data.aluno_nome}</div>
            <div class="summary-details">
              <div class="detail-item">
                <span class="detail-label">Unidade:</span>
                <span class="detail-value">${unidadeNome}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Turma:</span>
                <span class="detail-value">${turmaNome}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Data da Matrícula:</span>
                <span class="detail-value">${data.data_matricula}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status:</span>
                <span class="detail-value">${data.status}</span>
              </div>
            </div>
          </div>
        </div>
      `;
      
      if (data.responsaveis && Array.isArray(data.responsaveis)) {
        html += '<div class="summary-section"><h3>Responsáveis:</h3><ul>';
        data.responsaveis.forEach((r, i) => {
          html += `<li><strong>${r.nome}</strong> - Tel: ${r.telefone} | Email: ${r.email}</li>`;
        });
        html += '</ul></div>';
      }
      
      content.innerHTML = html;
      modal.style.display = "flex";
    })
    .catch(err => {
      console.error("Erro ao visualizar matrícula:", err);
      alert("Erro ao visualizar matrícula.");
    });
}

// Função auxiliar para verificar se um valor é numérico
function isNumeric(value) {
  return !isNaN(parseFloat(value)) && isFinite(value);
}

function excluirMatricula(id) {
  fetch(`api/excluir_matricula.php?id=${id}`)
    .then(res => res.json())
    .then(resp => {
      if (resp.status === "sucesso") {
        alert("Matrícula excluída com sucesso!");
        carregarMatriculas();
      } else {
        alert("Erro ao excluir matrícula.");
      }
    })
    .catch(err => {
      console.error("Erro ao excluir matrícula:", err);
      alert("Erro inesperado.");
    });
}

// Função para editar professor (opcional)
function editarProfessor(id) {
  fetch(`api/buscar_professor.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      // Implementar lógica para edição de professor
      //console.log("Dados do professor:", data);
      // Abrir modal de edição e preencher campos
    })
    .catch(err => {
      console.error("Erro ao buscar dados do professor:", err);
    });
}

// Função para excluir professor (opcional)
function excluirProfessor(id) {
  fetch(`api/excluir_professor.php?id=${id}`)
    .then(res => res.json())
    .then(resp => {
      if (resp.status === "sucesso") {
        alert("Professor excluído com sucesso!");
        listarTodosProfessores(); // Atualizar lista
      } else {
        alert("Erro ao excluir professor: " + resp.mensagem);
      }
    })
    .catch(err => {
      console.error("Erro ao excluir professor:", err);
    });
}