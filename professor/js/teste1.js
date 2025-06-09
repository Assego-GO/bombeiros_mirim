document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM carregado - inicializando módulo de avaliações');

    // Verificar se os elementos necessários existem antes de continuar
    const cardAvaliacoes = document.querySelector('.dashboard-card:nth-child(2)');
    
    if (!cardAvaliacoes) {
        console.warn('Card de avaliações não encontrado. Módulo não será inicializado.');
        return;
    }

    // Verificar se o modal já existe para evitar duplicação
    let avaliacoesModal = document.getElementById('avaliacoesModal');
    
    if (!avaliacoesModal) {
        avaliacoesModal = document.createElement('div');
        avaliacoesModal.id = 'avaliacoesModal';
        avaliacoesModal.className = 'modal';

        avaliacoesModal.innerHTML = `
            <div class="modal-content">
                <span class="close" id="closeAvaliacoesModal">&times;</span>
                <h2>Avaliações de Alunos</h2>

                <div id="turmas-avaliacoes" style="margin-top:20px;">
                    <h3>Selecione uma Turma</h3>
                    <div id="turmas-lista-container"></div>
                </div>

                <div id="alunos-avaliacoes" style="margin-top:20px; display:none;">
                    <h3>Alunos da Turma</h3>
                    <div id="alunos-lista-container"></div>
                </div>
            </div>
        `;

        document.body.appendChild(avaliacoesModal);
    }

    // Abrir modal
    cardAvaliacoes.addEventListener('click', () => {
        avaliacoesModal.style.display = 'block';
        carregarTurmas();
    });

    // Fechar modal
    const closeAvaliacoesModal = document.getElementById('closeAvaliacoesModal');
    if (closeAvaliacoesModal) {
        closeAvaliacoesModal.addEventListener('click', () => {
            avaliacoesModal.style.display = 'none';
        });
    }

    // Fechar modal ao clicar fora
    window.addEventListener('click', function(event) {
        if (event.target === avaliacoesModal) {
            avaliacoesModal.style.display = 'none';
        }
    });

    // CORRIGIDO: Função para obter o caminho correto da foto do aluno
    function getStudentPhotoPath(photoFilename) {
        if (!photoFilename) {
            return `uploads/fotos/default.png`;
        }
        
        // Se for um caminho completo, extraia apenas o nome do arquivo
        const filename = photoFilename.split('/').pop();
        return `uploads/fotos/${filename}`;
    }

    function carregarTurmas() {
        const container = document.getElementById('turmas-lista-container');
        if (!container) {
            console.error('Container turmas-lista-container não encontrado');
            return;
        }
        
        container.innerHTML = 'Carregando turmas...';

        // Primeiro, tentar a API principal
        fetch('api/turma/listar_turmas_professor.php')
            .then(res => {
                if (!res.ok) {
                    // Se não funcionar, tentar caminho alternativo
                    console.log('Tentando caminho alternativo para turmas...');
                    return fetch('api/listar_turmas_professor.php');
                }
                return res;
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! Status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.success && data.turmas && data.turmas.length > 0) {
                    container.innerHTML = '';

                    data.turmas.forEach(turma => {
                        const div = document.createElement('div');
                        div.classList.add('turma-item');
                        div.innerHTML = `
                            <strong>${turma.nome_turma}</strong> - ${turma.nome_unidade}
                            <button class="btn-ver-alunos" data-id="${turma.id}">Ver Alunos</button>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div class="alert alert-info">Nenhuma turma encontrada.</div>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar turmas:', error);
                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar turmas. Verifique sua conexão.</div>';
            });
    }

    // Delegação de clique para botões de turma
    document.addEventListener('click', function (e) {
        // Verificar se é um botão "Ver Alunos" dentro do modal de avaliações
        if (e.target.classList.contains('btn-ver-alunos') && 
            e.target.closest('#avaliacoesModal')) {
            
            const turmaId = e.target.getAttribute('data-id');
            console.log('Clique no botão Ver Alunos - turma ID:', turmaId);
            carregarAlunos(turmaId);
        }
    });

    // CORRIGIDO: Função para carregar alunos
    function carregarAlunos(turmaId) {
        const modalContent = document.querySelector('#avaliacoesModal .modal-content');
        
        if (!modalContent) {
            console.error('Modal content não encontrado');
            return;
        }
        
        // Adicionar uma seção para os alunos
        let alunosSection = modalContent.querySelector('.alunos-section');
        
        // Se não existir, criar
        if (!alunosSection) {
            alunosSection = document.createElement('div');
            alunosSection.className = 'alunos-section';
            alunosSection.style.marginTop = '20px';
            modalContent.appendChild(alunosSection);
        }
        
        // Mostrar loading
        alunosSection.innerHTML = '<h3>Alunos da Turma</h3><p>Carregando...</p>';
        
        // Lista de URLs para tentar em ordem de prioridade
        const urls = [
            `api/aluno/listar_alunos_turma.php?turma_id=${turmaId}`,
            `api/listar_alunos_turma.php?turma_id=${turmaId}`,
            `alunos_turma.php?turma_id=${turmaId}`
        ];
        
        // Função para tentar cada URL sequencialmente
        function tentarCarregarAlunos(urlIndex = 0) {
            if (urlIndex >= urls.length) {
                alunosSection.innerHTML = `
                    <h3>Alunos da Turma</h3>
                    <div class="alert alert-danger">
                        Erro: Não foi possível carregar a lista de alunos.<br>
                        Verifique se os arquivos da API existem no servidor.
                    </div>
                `;
                return;
            }
            
            const currentUrl = urls[urlIndex];
            console.log(`Tentando URL ${urlIndex + 1}/${urls.length}: ${currentUrl}`);
            
            fetch(currentUrl)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    return res.json();
                })
                .then(data => {
                    console.log("Data received:", data);
                    
                    if (data.success && data.alunos && data.alunos.length > 0) {
                        let html = '<h3>Alunos da Turma</h3>';
                        
                        data.alunos.forEach(aluno => {
                            // Usar a função para obter o caminho da foto
                            const fotoPath = getStudentPhotoPath(aluno.foto);
                            const matricula = aluno.numero_matricula || aluno.matricula || aluno.id;
                            
                            html += `
                                <div class="aluno-item">
                                    <div class="aluno-foto">
                                        <img src="${fotoPath}" 
                                             alt="${aluno.nome}" 
                                             onerror="this.onerror=null; this.src='uploads/fotos/default.png';">
                                    </div>
                                    <div class="aluno-info">
                                        <div class="aluno-nome">${aluno.nome}</div>
                                        <div class="aluno-dados">
                                            Matrícula: <span class="text-danger">${matricula}</span>
                                        </div>
                                    </div>
                                    <div class="aluno-acoes">
                                        <a href="avaliar_aluno.php?aluno_id=${aluno.id}&turma_id=${turmaId}" 
                                           class="btn btn-sm btn-danger">
                                            <i class="fas fa-clipboard-check"></i> Avaliar
                                        </a>
                                        ${aluno.total_avaliacoes > 0 ? `
                                            <a href="avaliacoes_aluno.php?aluno_id=${aluno.id}&turma_id=${turmaId}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-clipboard-list"></i> Ver Avaliações (${aluno.total_avaliacoes})
                                            </a>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        alunosSection.innerHTML = html;
                    } else if (data.success && (!data.alunos || data.alunos.length === 0)) {
                        alunosSection.innerHTML = '<h3>Alunos da Turma</h3><div class="alert alert-info">Nenhum aluno encontrado nesta turma.</div>';
                    } else {
                        alunosSection.innerHTML = `<h3>Alunos da Turma</h3><div class="alert alert-warning">${data.message || 'Erro desconhecido ao carregar alunos.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error(`Erro na URL ${currentUrl}:`, error);
                    // Tentar a próxima URL
                    tentarCarregarAlunos(urlIndex + 1);
                });
        }
        
        // Iniciar tentativas
        tentarCarregarAlunos();
    }
    
    // Adicionar estilos CSS para o modal e alunos (só se não existirem)
    if (!document.getElementById('avaliacoes-styles')) {
        const style = document.createElement('style');
        style.id = 'avaliacoes-styles';
        style.textContent = `
            #avaliacoesModal .modal-content {
                max-width: 800px;
                width: 90%;
            }
            
            .alunos-section {
                max-height: 70vh;
                overflow-y: auto;
                padding: 10px;
            }
            
            .aluno-item {
                display: flex;
                align-items: center;
                padding: 15px;
                border-bottom: 1px solid #eee;
                margin-bottom: 10px;
                background-color: #f9f9f9;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .aluno-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            }
            
            .aluno-foto {
                width: 60px;
                height: 60px;
                overflow: hidden;
                margin-right: 15px;
                border-radius: 50%;
                background-color: #e0e0e0;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #ddd;
                flex-shrink: 0;
            }
            
            .aluno-foto img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .aluno-info {
                flex: 1;
                min-width: 0;
            }
            
            .aluno-nome {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 5px;
                word-wrap: break-word;
            }
            
            .aluno-dados {
                font-size: 14px;
                color: #666;
            }
            
            .text-danger {
                color: #ff3b30;
                font-weight: bold;
            }
            
            .aluno-acoes {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            
            .btn-danger {
                background: linear-gradient(135deg, #ff3b30, #ff6259);
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                transition: all 0.3s ease;
                white-space: nowrap;
            }
            
            .btn-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(255, 59, 48, 0.3);
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white;
                border: none;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                font-size: 0.85rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                transition: all 0.3s ease;
                white-space: nowrap;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            }
            
            .btn i {
                margin-right: 5px;
            }
            
            .btn-ver-alunos {
                background: linear-gradient(135deg, #0d2d56, #1a4a7a);
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 0.9rem;
                font-weight: 500;
                margin-left: 15px;
                transition: all 0.3s ease;
            }
            
            .btn-ver-alunos:hover {
                background: linear-gradient(135deg, #1a4a7a, #0d2d56);
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(13, 45, 86, 0.3);
            }
            
            .alert {
                padding: 12px 16px;
                border-radius: 4px;
                margin: 10px 0;
                font-weight: 500;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            
            .alert-info {
                background-color: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
            
            .alert-warning {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
            }
            
            @media (max-width: 768px) {
                .aluno-item {
                    flex-direction: column;
                    align-items: flex-start;
                    text-align: left;
                }
                
                .aluno-foto {
                    margin-bottom: 10px;
                    margin-right: 0;
                }
                
                .aluno-acoes {
                    margin-top: 10px;
                    width: 100%;
                    justify-content: center;
                }
                
                .aluno-acoes .btn {
                    flex: 1;
                    min-width: 120px;
                    max-width: 200px;
                }
            }
            
            @media (max-width: 480px) {
                .aluno-acoes {
                    flex-direction: column;
                }
                
                .aluno-acoes .btn {
                    width: 100%;
                    max-width: none;
                    margin-bottom: 5px;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    console.log("Módulo de avaliações inicializado com sucesso!");
});