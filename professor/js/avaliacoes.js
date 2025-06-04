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
        
        // Buscar dados
        fetch(`api/aluno/listar_alunos_turma.php?turma_id=${turmaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.alunos.length > 0) {
                    let html = '<h3>Alunos da Turma</h3>';
                    
                    data.alunos.forEach(aluno => {
                        html += `
                            <div style="background: #f5f7fa; margin: 8px 0; padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: bold;">${aluno.nome}</div>
                                    <div style="font-size: 12px; color:rgb(255, 0, 0);">Matrícula: ${aluno.numero_matricula || aluno.id}</div>
                                </div>
                                <a href="avaliar_aluno.php?aluno_id=${aluno.id}&turma_id=${turmaId}" 
                                   style="background:rgb(86, 13, 13); color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none;">
                                    Avaliar
                                </a>
                            </div>
                        `;
                    });
                    
                    alunosSection.innerHTML = html;
                } else {
                    alunosSection.innerHTML = '<h3>Alunos da Turma</h3><p>Nenhum aluno encontrado.</p>';
                }
            })
            .catch(error => {
                alunosSection.innerHTML = `<h3>Alunos da Turma</h3><p>Erro: ${error.message}</p>`;
            });
    }
    
});
