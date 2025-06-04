document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM carregado - inicializando módulo de avaliações');

    const cardAvaliacoes = document.querySelector('.dashboard-card:nth-child(2)');

    let avaliacoesModal = document.createElement('div');
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

    // Abrir modal
    cardAvaliacoes.addEventListener('click', () => {
        avaliacoesModal.style.display = 'block';
        carregarTurmas();
    });

    // Fechar modal
    document.getElementById('closeAvaliacoesModal').addEventListener('click', () => {
        avaliacoesModal.style.display = 'none';
    });

    // Fechar modal ao clicar fora
    window.addEventListener('click', function(event) {
        if (event.target === avaliacoesModal) {
            avaliacoesModal.style.display = 'none';
        }
    });

    // Função para obter o caminho correto da foto do aluno
    function getStudentPhotoPath(photoFilename) {
        // Definir a URL base correta
        const serverUrl = window.location.protocol + '//' + window.location.host;
        const correctBaseUrl = serverUrl + '';

        if (!photoFilename) {
            return `${correctBaseUrl}/uploads/fotos/default.png`;
        }
        
        // Se for um caminho completo, extraia apenas o nome do arquivo
        const filename = photoFilename.split('/').pop();
        return `${correctBaseUrl}/uploads/fotos/${filename}`;
    }

    function carregarTurmas() {
        const container = document.getElementById('turmas-lista-container');
        container.innerHTML = 'Carregando turmas...';

        fetch('api/turma/listar_turmas_professor.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.turmas.length > 0) {
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
                    container.innerHTML = 'Nenhuma turma encontrada.';
                }
            });
    }

    // Delegação de clique para botões de turma
    document.getElementById('turmas-lista-container').addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-ver-alunos')) {
            const turmaId = e.target.getAttribute('data-id');
            console.log('Clique no botão Ver Alunos - turma ID:', turmaId);
            carregarAlunos(turmaId);
        }
    });

    function carregarAlunos(turmaId) {
        const modalContent = document.querySelector('#avaliacoesModal .modal-content');
        
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
        
        // Verificar se temos a API alunos_turma.php ou listar_alunos_turma.php
        // Definir a URL base correta
        const serverUrl = window.location.protocol + '//' + window.location.host;
        const correctBaseUrl = serverUrl + '';
        
        // Tentar primeiro com a API professor/api/alunos_turma.php
        const fetchUrl = `${correctBaseUrl}/professor/api/alunos_turma.php?turma_id=${turmaId}`;
        
        // Buscar dados
        fetch(fetchUrl)
            .then(res => {
                if (!res.ok && res.status === 404) {
                    // Se a primeira API não funcionar, tente a segunda
                    return fetch(`api/aluno/listar_alunos_turma.php?turma_id=${turmaId}`);
                }
                return res;
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.alunos && data.alunos.length > 0) {
                    let html = '<h3>Alunos da Turma</h3>';
                    
                    data.alunos.forEach(aluno => {
                        // Usar a função para obter o caminho da foto
                        const fotoPath = getStudentPhotoPath(aluno.foto);
                        const matricula = aluno.numero_matricula || aluno.matricula || aluno.id;
                        
                        html += `
                            <div class="aluno-item">
                                <div class="aluno-foto">
                                    <img src="${fotoPath}" alt="${aluno.nome}" onerror="this.onerror=null; this.src='${correctBaseUrl}/uploads/fotos/default.png';">
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
                                </div>
                            </div>
                        `;
                    });
                    
                    alunosSection.innerHTML = html;
                } else {
                    alunosSection.innerHTML = '<h3>Alunos da Turma</h3><p>Nenhum aluno encontrado.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alunosSection.innerHTML = `<h3>Alunos da Turma</h3><p>Erro: ${error.message}</p>`;
            });
    }
    
    // Adicionar estilos CSS para o modal e alunos
    const style = document.createElement('style');
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
        }
        
        .aluno-foto img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .aluno-info {
            flex: 1;
        }
        
        .aluno-nome {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
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
            gap: 5px;
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
        }
        
        .btn-danger i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .aluno-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .aluno-foto {
                margin-bottom: 10px;
            }
            
            .aluno-acoes {
                margin-top: 10px;
                width: 100%;
            }
        }
    `;
    
    document.head.appendChild(style);
});