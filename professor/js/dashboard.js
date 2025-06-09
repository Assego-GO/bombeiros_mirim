document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS loading...");
    
    // Modal Elements - com verificações de segurança
    const turmasModal = document.getElementById('turmasModal');
    const perfilModal = document.getElementById('perfilModal');
    const alunosModal = document.getElementById('alunosModal');
    const atividadesModal = document.getElementById('atividadesModal');
    const cadastroAtividadeModal = document.getElementById('cadastroAtividadeModal');
    const detalhesAtividadeModal = document.getElementById('detalhesAtividadeModal');
    
    // Open/Close buttons
    const cardTurmas = document.getElementById('card-turmas');
    const cardPerfil = document.getElementById('card-perfil');
    const cardAtividades = document.getElementById('card-atividades');
    const closeTurmasModal = document.getElementById('closeModal');
    const closePerfilModal = document.getElementById('closePerfilModal');
    const closeAlunosModal = document.getElementById('closeAlunosModal');
    const closeAtividadesModal = document.getElementById('closeAtividadesModal');
    const closeCadastroAtividadeModal = document.getElementById('closeCadastroAtividadeModal');
    const closeDetalhesAtividadeModal = document.getElementById('closeDetalhesAtividadeModal');
    
    // Profile sections
    const visualizarPerfil = document.getElementById('visualizar-perfil');
    const editarPerfil = document.getElementById('editar-perfil');
    const btnEditarPerfil = document.getElementById('btn-editar-perfil');
    const btnCancelarEdicao = document.getElementById('btn-cancelar-edicao');
    
    // Tipos de atividades válidos (baseado no ENUM do banco)
    const TIPOS_ATIVIDADES = [
        'Ed. Física', 'Salvamento', 'Informática', 'Primeiro Socorros',
        'Ordem Unida', 'Combate a Incêndio', 'Ética e Cidadania',
        'Higiene Pessoal', 'Meio Ambiente', 'Educação no Trânsito',
        'Temas Transversais', 'Combate uso de Drogas',
        'ECA e Direitos Humanos', 'Treinamento de Formatura'
    ];
    
    // Verificar se os elementos críticos existem
    console.log("Elementos encontrados:", {
        turmasModal: !!turmasModal,
        perfilModal: !!perfilModal,
        alunosModal: !!alunosModal,
        atividadesModal: !!atividadesModal,
        cadastroAtividadeModal: !!cadastroAtividadeModal,
        detalhesAtividadeModal: !!detalhesAtividadeModal,
        cardAtividades: !!cardAtividades
    });
    
    // Utility function to calculate age from birthdate
    function calcularIdade(dataNascimento) {
        const hoje = new Date();
        const nascimento = new Date(dataNascimento);
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mesAtual = hoje.getMonth();
        const mesNascimento = nascimento.getMonth();
        
        if (mesNascimento > mesAtual || 
            (mesNascimento === mesAtual && nascimento.getDate() > hoje.getDate())) {
            idade--;
        }
        
        return idade;
    }
    
    // Utility function to show messages
    function showMessage(elementId, message, type) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        } else {
            console.warn(`Elemento ${elementId} não encontrado para mostrar mensagem:`, message);
        }
    }
    
    // FUNÇÕES PARA ATIVIDADES - com verificações robustas
    function loadAtividades() {
        console.log("Carregando atividades...");
        
        const atividadesContainer = document.getElementById('atividades-lista-container');
        if (!atividadesContainer) {
            console.error("Container não encontrado: atividades-lista-container");
            return;
        }
        
        atividadesContainer.innerHTML = '<p>Carregando atividades...</p>';
        
        fetch('./api/atividades.php?action=listar')
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Atividades recebidas:", data);
                
                if (data.success) {
                    if (data.atividades && data.atividades.length > 0) {
                        let html = '';
                        data.atividades.forEach(atividade => {
                            html += `
                                <div class="atividade-item">
                                    <h3>${atividade.nome_atividade || 'Atividade sem nome'}</h3>
                                    
                                    <div class="atividade-info">
                                        <div class="atividade-field">
                                            <label>Turma:</label>
                                            <span>${atividade.nome_turma || 'N/A'} - ${atividade.unidade_nome || 'N/A'}</span>
                                        </div>
                                        <div class="atividade-field">
                                            <label>Data:</label>
                                            <span>${formatarData(atividade.data_atividade)}</span>
                                        </div>
                                        <div class="atividade-field">
                                            <label>Horário:</label>
                                            <span>${atividade.hora_inicio ? atividade.hora_inicio.substring(0,5) : 'N/A'} às ${atividade.hora_termino ? atividade.hora_termino.substring(0,5) : 'N/A'}</span>
                                        </div>
                                        <div class="atividade-field">
                                            <label>Local:</label>
                                            <span>${atividade.local_atividade || 'N/A'}</span>
                                        </div>
                                        <div class="atividade-field">
                                            <label>Instrutor:</label>
                                            <span>${atividade.instrutor_responsavel || 'N/A'}</span>
                                        </div>
                                        <div class="atividade-field">
                                            <label>Status:</label>
                                            <span class="status-${atividade.status || 'planejada'}">${formatarStatus(atividade.status)}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="atividade-actions">
                                        <button class="btn btn-sm btn-detalhes" data-atividade-id="${atividade.id}">
                                            <i class="fas fa-eye"></i> Detalhes
                                        </button>
                                        <button class="btn btn-sm btn-participacao" data-atividade-id="${atividade.id}">
                                            <i class="fas fa-users"></i> Participação (${atividade.total_participacoes || 0})
                                        </button>
                                        <button class="btn btn-sm btn-editar" data-atividade-id="${atividade.id}">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-excluir" data-atividade-id="${atividade.id}">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        atividadesContainer.innerHTML = html;
                    } else {
                        atividadesContainer.innerHTML = '<div class="alert alert-info">Não há atividades cadastradas ainda.</div>';
                    }
                } else {
                    atividadesContainer.innerHTML = `<div class="alert alert-danger">${data.message || 'Erro ao carregar atividades.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar atividades:', error);
                atividadesContainer.innerHTML = `<div class="alert alert-danger">Erro de conexão: ${error.message}</div>`;
            });
    }
    
    function loadTurmasSelect() {
        fetch('./api/atividades.php?action=turmas')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const turmaSelect = document.getElementById('turma_id');
                if (data.success && turmaSelect) {
                    turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
                    if (data.turmas && Array.isArray(data.turmas)) {
                        data.turmas.forEach(turma => {
                            turmaSelect.innerHTML += `<option value="${turma.id}">${turma.nome_turma} - ${turma.unidade_nome}</option>`;
                        });
                    }
                } else if (turmaSelect) {
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar turmas:', error);
                const turmaSelect = document.getElementById('turma_id');
                if (turmaSelect) {
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                }
            });
    }
    
    function populateAtividadeSelect(selectElement) {
        if (!selectElement) return;
        
        selectElement.innerHTML = '<option value="">Selecione o tipo de atividade</option>';
        TIPOS_ATIVIDADES.forEach(tipo => {
            selectElement.innerHTML += `<option value="${tipo}">${tipo}</option>`;
        });
    }
    
    function formatarData(dataString) {
        if (!dataString) return 'Data não informada';
        try {
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR');
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return 'Data inválida';
        }
    }
    
    function formatarStatus(status) {
        const statusMap = {
            'planejada': 'Planejada',
            'em_andamento': 'Em Andamento',
            'concluida': 'Concluída',
            'cancelada': 'Cancelada'
        };
        return statusMap[status] || 'Status não definido';
    }
    
    function mostrarFormularioAtividade(atividade = null) {
        if (!cadastroAtividadeModal) {
            console.error('Modal de cadastro de atividade não encontrado');
            alert('Erro: Modal de cadastro não encontrado. Verifique se os modais foram adicionados ao HTML.');
            return;
        }
        
        const form = document.getElementById('form-atividade');
        const title = document.getElementById('modalTitleCadastroAtividade');
        
        if (!form) {
            console.error('Formulário de atividade não encontrado');
            alert('Erro: Formulário não encontrado no DOM.');
            return;
        }
        
        if (atividade) {
            // Editando atividade existente
            if (title) title.textContent = 'Editar Atividade';
            
            const atividadeIdInput = document.getElementById('atividade_id');
            const actionInput = document.querySelector('input[name="action"]');
            
            if (atividadeIdInput) atividadeIdInput.value = atividade.id;
            if (actionInput) actionInput.value = 'editar';
            
            // Preencher campos com verificações
            const campos = {
                'nome_atividade': atividade.nome_atividade,
                'turma_id': atividade.turma_id,
                'data_atividade': atividade.data_atividade,
                'local_atividade': atividade.local_atividade,
                'hora_inicio': atividade.hora_inicio,
                'hora_termino': atividade.hora_termino,
                'instrutor_responsavel': atividade.instrutor_responsavel,
                'objetivo_atividade': atividade.objetivo_atividade,
                'conteudo_abordado': atividade.conteudo_abordado
            };
            
            Object.keys(campos).forEach(campo => {
                const elemento = document.getElementById(campo);
                if (elemento && campos[campo] !== undefined) {
                    elemento.value = campos[campo];
                }
            });
        } else {
            // Nova atividade
            if (title) title.textContent = 'Nova Atividade';
            form.reset();
            
            const atividadeIdInput = document.getElementById('atividade_id');
            const actionInput = document.querySelector('input[name="action"]');
            
            if (atividadeIdInput) atividadeIdInput.value = '';
            if (actionInput) actionInput.value = 'cadastrar';
        }
        
        // Carregar turmas e popular select de atividades
        loadTurmasSelect();
        const nomeAtividadeSelect = document.getElementById('nome_atividade');
        populateAtividadeSelect(nomeAtividadeSelect);
        
        cadastroAtividadeModal.style.display = 'block';
    }
    
    function mostrarDetalhesAtividade(atividade) {
        if (!detalhesAtividadeModal) {
            console.error('Modal de detalhes não encontrado');
            alert('Erro: Modal de detalhes não encontrado.');
            return;
        }
        
        const container = document.getElementById('detalhes-atividade-container');
        const title = document.getElementById('modalTitleDetalhesAtividade');
        
        if (!container) {
            console.error('Container de detalhes não encontrado');
            return;
        }
        
        if (title) {
            title.textContent = `Detalhes: ${atividade.nome_atividade || 'Atividade'}`;
        }
        
        let html = `
            <div class="detalhes-atividade">
                <h4>Informações da Atividade</h4>
                <div class="atividade-info">
                    <div class="atividade-field">
                        <label>Tipo:</label>
                        <span>${atividade.nome_atividade || 'N/A'}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Turma:</label>
                        <span>${atividade.nome_turma || 'N/A'} - ${atividade.unidade_nome || 'N/A'}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Data:</label>
                        <span>${formatarData(atividade.data_atividade)}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Horário:</label>
                        <span>${atividade.hora_inicio ? atividade.hora_inicio.substring(0,5) : 'N/A'} às ${atividade.hora_termino ? atividade.hora_termino.substring(0,5) : 'N/A'}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Local:</label>
                        <span>${atividade.local_atividade || 'N/A'}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Instrutor:</label>
                        <span>${atividade.instrutor_responsavel || 'N/A'}</span>
                    </div>
                    <div class="atividade-field">
                        <label>Status:</label>
                        <span class="status-${atividade.status || 'planejada'}">${formatarStatus(atividade.status)}</span>
                    </div>
                </div>
                
                <div class="atividade-field" style="margin-top: 15px;">
                    <label>Objetivo:</label>
                    <span style="white-space: pre-wrap;">${atividade.objetivo_atividade || 'Não informado'}</span>
                </div>
                
                <div class="atividade-field" style="margin-top: 15px;">
                    <label>Conteúdo Abordado:</label>
                    <span style="white-space: pre-wrap;">${atividade.conteudo_abordado || 'Não informado'}</span>
                </div>
            </div>
        `;
        
        if (atividade.participacoes && atividade.participacoes.length > 0) {
            html += `
                <div class="detalhes-atividade">
                    <h4>Participações dos Alunos</h4>
            `;
            
            atividade.participacoes.forEach(participacao => {
                html += `
                    <div class="participacao-item">
                        <h5>${participacao.aluno_nome || 'Aluno'}</h5>
                        <div class="participacao-dados">
                            <span><strong>Presença:</strong> ${mapearPresenca(participacao.presenca)}</span>
                            ${participacao.desempenho_nota ? `<span><strong>Nota:</strong> ${participacao.desempenho_nota}</span>` : ''}
                            ${participacao.desempenho_conceito ? `<span><strong>Conceito:</strong> ${participacao.desempenho_conceito}</span>` : ''}
                            ${participacao.comportamento ? `<span><strong>Comportamento:</strong> ${participacao.comportamento}</span>` : ''}
                        </div>
                        ${participacao.observacoes ? `<p style="margin-top: 10px;"><strong>Observações:</strong> ${participacao.observacoes}</p>` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
        }
        
        container.innerHTML = html;
        detalhesAtividadeModal.style.display = 'block';
    }
    
    function mapearPresenca(presenca) {
        const presencaMap = {
            'sim': 'Presente',
            'nao': 'Ausente',
            'falta_justificada': 'Falta Justificada'
        };
        return presencaMap[presenca] || presenca;
    }
    
    // CORRIGIDO: Load turma students function (mantém a função original)
    function loadAlunosTurma(turmaId) {
        console.log("Loading students for turma ID:", turmaId);
        
        const alunosContainer = document.getElementById('alunos-lista-container');
        if (!alunosContainer) {
            console.error("Container not found: alunos-lista-container");
            return;
        }
        
        alunosContainer.innerHTML = '<p>Carregando lista de alunos...</p>';
        
        // Get turma name for the modal title
        let turmaNome = '';
        const turmaItem = document.querySelector(`.turma-item[data-turma-id="${turmaId}"]`);
        if (turmaItem) {
            const h3Element = turmaItem.querySelector('h3');
            if (h3Element) {
                turmaNome = h3Element.textContent;
            }
        }
        
        // Update modal title
        const modalTitle = document.getElementById('modalTitleAlunos');
        if (modalTitle) {
            modalTitle.textContent = `Alunos da Turma: ${turmaNome}`;
        }
        
        // CORREÇÃO: Usar caminhos relativos simples
        const fetchUrl = `./alunos_turma.php?turma_id=${turmaId}`;
        console.log("Fetching URL:", fetchUrl);
        
        // Fetch students from this class
        fetch(fetchUrl)
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    // Se não funcionar, tente com ./api/alunos_turma.php
                    console.log("Tentando URL alternativa...");
                    return fetch(`./api/alunos_turma.php?turma_id=${turmaId}`);
                }
                return response;
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Data received:", data);
                
                if (data.success) {
                    if (data.alunos && data.alunos.length > 0) {
                        let html = '';
                        data.alunos.forEach(aluno => {
                            // CORREÇÃO: Fix the photo path - usar caminhos relativos
                            let fotoPath = '';
                            
                            if (aluno.foto) {
                                const filename = aluno.foto.split('/').pop();
                                fotoPath = `../uploads/fotos/${filename}`;
                                console.log('Caminho original:', aluno.foto);
                                console.log('Caminho corrigido:', fotoPath);
                            } else {
                                fotoPath = `../uploads/fotos/default.png`;
                            }                        
                            
                            html += `
                                <div class="aluno-item">
                                    <div class="aluno-foto">
                                        ${aluno.foto ? 
                                            `<img src="${fotoPath}" alt="${aluno.nome}" onerror="this.onerror=null; this.src='../uploads/fotos/default.png';">` : 
                                            `<i class="fas fa-user-graduate"></i>`}
                                    </div>
                                    <div class="aluno-info">
                                        <div class="aluno-nome">${aluno.nome}</div>
                                        <div class="aluno-dados">
                                            ${aluno.data_nascimento ? `Idade: ${calcularIdade(aluno.data_nascimento)} anos` : ''}
                                            ${aluno.escola ? ` | Escola: ${aluno.escola}` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="aluno-acoes">
                                        <a href="aluno_detalhe.php?id=${aluno.id}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Ver Detalhes
                                        </a>
                                        ${aluno.total_avaliacoes > 0 ? 
                                            `<a href="avaliacoes_aluno.php?aluno_id=${aluno.id}&turma_id=${turmaId}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-clipboard-list"></i> Ver Avaliações (${aluno.total_avaliacoes})
                                            </a>` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        alunosContainer.innerHTML = html;
                    } else {
                        alunosContainer.innerHTML = '<div class="alert alert-info">Não há alunos matriculados nesta turma.</div>';
                    }
                } else {
                    alunosContainer.innerHTML = `<div class="alert alert-danger">${data.message || 'Erro ao carregar alunos.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alunosContainer.innerHTML = `
                    <div class="alert alert-danger">
                        Erro de conexão: ${error.message}<br>
                        Tentou acessar: ${fetchUrl}<br>
                        Verifique se o arquivo alunos_turma.php existe no servidor.
                    </div>
                `;
            });
    }
    
    // Event Listeners para Atividades - com verificações
    const btnNovaAtividade = document.getElementById('btn-nova-atividade');
    const btnCancelarAtividade = document.getElementById('btn-cancelar-atividade');
    const formAtividade = document.getElementById('form-atividade');
    
    if (btnNovaAtividade) {
        btnNovaAtividade.addEventListener('click', function() {
            mostrarFormularioAtividade();
        });
    }
    
    if (btnCancelarAtividade) {
        btnCancelarAtividade.addEventListener('click', function() {
            if (cadastroAtividadeModal) {
                cadastroAtividadeModal.style.display = 'none';
            }
        });
    }
    
    if (formAtividade) {
        formAtividade.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validação adicional para horários
            const horaInicio = formData.get('hora_inicio');
            const horaTermino = formData.get('hora_termino');
            
            if (horaInicio && horaTermino && horaInicio >= horaTermino) {
                showMessage('mensagem-atividade', 'A hora de término deve ser posterior à hora de início.', 'danger');
                return;
            }
            
            fetch('./api/atividades.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showMessage('mensagem-atividade', data.message || 'Atividade salva com sucesso!', 'success');
                    setTimeout(() => {
                        if (cadastroAtividadeModal) {
                            cadastroAtividadeModal.style.display = 'none';
                        }
                        loadAtividades(); // Recarregar lista
                    }, 2000);
                } else {
                    showMessage('mensagem-atividade', data.message || 'Erro ao salvar atividade.', 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('mensagem-atividade', 'Erro de conexão. Tente novamente.', 'danger');
            });
        });
    }
    
    // Toggle modals - com verificações
    if (cardTurmas) {
        cardTurmas.addEventListener('click', function() {
            if (turmasModal) {
                turmasModal.style.display = 'block';
            } else {
                console.error('Modal de turmas não encontrado');
            }
        });
    }
    
    if (cardPerfil) {
        cardPerfil.addEventListener('click', function() {
            if (perfilModal) {
                perfilModal.style.display = 'block';
            } else {
                console.error('Modal de perfil não encontrado');
            }
        });
    }
    
    if (cardAtividades) {
        cardAtividades.addEventListener('click', function() {
            if (atividadesModal) {
                atividadesModal.style.display = 'block';
                loadAtividades();
            } else {
                console.error('Modal de atividades não encontrado');
                alert('Os modais de atividades ainda não foram adicionados ao HTML. Por favor, adicione-os primeiro.');
            }
        });
    } else {
        console.warn('Card de atividades não encontrado no DOM');
    }
    
    // Close modals - com verificações
    if (closeTurmasModal && turmasModal) {
        closeTurmasModal.addEventListener('click', function() {
            turmasModal.style.display = 'none';
        });
    }
    
    if (closePerfilModal && perfilModal) {
        closePerfilModal.addEventListener('click', function() {
            perfilModal.style.display = 'none';
        });
    }
    
    if (closeAlunosModal && alunosModal) {
        closeAlunosModal.addEventListener('click', function() {
            alunosModal.style.display = 'none';
        });
    }
    
    if (closeAtividadesModal && atividadesModal) {
        closeAtividadesModal.addEventListener('click', function() {
            atividadesModal.style.display = 'none';
        });
    }
    
    if (closeCadastroAtividadeModal && cadastroAtividadeModal) {
        closeCadastroAtividadeModal.addEventListener('click', function() {
            cadastroAtividadeModal.style.display = 'none';
        });
    }
    
    if (closeDetalhesAtividadeModal && detalhesAtividadeModal) {
        closeDetalhesAtividadeModal.addEventListener('click', function() {
            detalhesAtividadeModal.style.display = 'none';
        });
    }
    
    // Close all modals when clicking outside
    window.addEventListener('click', function(event) {
        if (turmasModal && event.target === turmasModal) {
            turmasModal.style.display = 'none';
        }
        if (perfilModal && event.target === perfilModal) {
            perfilModal.style.display = 'none';
        }
        if (alunosModal && event.target === alunosModal) {
            alunosModal.style.display = 'none';
        }
        if (atividadesModal && event.target === atividadesModal) {
            atividadesModal.style.display = 'none';
        }
        if (cadastroAtividadeModal && event.target === cadastroAtividadeModal) {
            cadastroAtividadeModal.style.display = 'none';
        }
        if (detalhesAtividadeModal && event.target === detalhesAtividadeModal) {
            detalhesAtividadeModal.style.display = 'none';
        }
    });
    
    // Event listeners globais para botões de atividades
    document.addEventListener('click', function(e) {
        // Verificar se é um botão de ação de atividade
        let target = e.target;
        let button = null;
        
        // Procurar o botão pai se clicou no ícone
        if (target.classList.contains('fas')) {
            target = target.parentElement;
        }
        
        if (target.classList.contains('btn-detalhes') || 
            target.classList.contains('btn-editar') || 
            target.classList.contains('btn-excluir') || 
            target.classList.contains('btn-participacao')) {
            button = target;
        }
        
        if (!button) return;
        
        const atividadeId = button.getAttribute('data-atividade-id');
        if (!atividadeId) {
            console.error('ID da atividade não encontrado');
            return;
        }
        
        // Botão "Detalhes"
        if (button.classList.contains('btn-detalhes')) {
            fetch(`./api/atividades.php?action=detalhes&id=${atividadeId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        mostrarDetalhesAtividade(data.atividade);
                    } else {
                        alert(data.message || 'Erro ao carregar detalhes');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro de conexão');
                });
        }
        
        // Botão "Editar"
        else if (button.classList.contains('btn-editar')) {
            fetch(`./api/atividades.php?action=detalhes&id=${atividadeId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        mostrarFormularioAtividade(data.atividade);
                    } else {
                        alert(data.message || 'Erro ao carregar atividade');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro de conexão');
                });
        }
        
        // Botão "Excluir"
        else if (button.classList.contains('btn-excluir')) {
            if (confirm('Tem certeza que deseja excluir esta atividade?')) {
                fetch(`./api/atividades.php?id=${atividadeId}`, {
                    method: 'DELETE'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Atividade excluída com sucesso!');
                        loadAtividades(); // Recarregar lista
                    } else {
                        alert(data.message || 'Erro ao excluir atividade');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro de conexão');
                });
            }
        }
        
        // Botão "Participação"
        else if (button.classList.contains('btn-participacao')) {
            window.location.href = `participacao_atividade.php?atividade_id=${atividadeId}`;
        }
    });

    // IMPORTANT: This is a special global event handler for all "Ver Alunos" buttons or links
    document.addEventListener('click', function(e) {
        // Look for elements with btn-ver-alunos class or their children
        let target = e.target;
        let verAlunosElement = null;
        
        // Check if the clicked element or any of its parents has the btn-ver-alunos class
        while (target != null && target !== document) {
            if (target.classList && target.classList.contains('btn-ver-alunos')) {
                verAlunosElement = target;
                break;
            }
            target = target.parentElement;
        }
        
        // If we found a "Ver Alunos" element
        if (verAlunosElement) {
            console.log("Ver Alunos element clicked:", verAlunosElement);
            e.preventDefault(); // Prevent default navigation
            e.stopPropagation(); // Stop event bubbling
            
            const turmaId = verAlunosElement.getAttribute('data-turma-id');
            console.log("Turma ID:", turmaId);
            
            if (turmaId) {
                // Show the modal
                if (alunosModal) {
                    alunosModal.style.display = 'block';
                    
                    // Load students using the loadAlunosTurma function
                    loadAlunosTurma(turmaId);
                } else {
                    console.error("Alunos modal not found in the DOM!");
                }
            } else {
                console.error("No turma_id attribute found on the clicked element");
            }
        }
    });
    
    // Rest of the profile and other functionality... (keeping the existing code)
    
    console.log("Dashboard JS initialized successfully!");
});