// Adicionar CSS para turmas lotadas
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .turma-lotada {
            color: #ff0000;
            font-weight: bold;
        }
        
        select option:disabled {
            color: #ff0000;
            background-color: #ffeeee;
        }
        
        .alerta-vagas {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .alerta-erro {
            background-color: #ffeeee;
            color: #cc0000;
            border: 1px solid #ffcccc;
        }
        
        .alerta-info {
            background-color: #e7f3fe;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
    `);
    
    // Sistema de carregamento de unidades e turmas
    document.addEventListener('DOMContentLoaded', function() {
        const unidadeSelect = document.getElementById('unidade');
        const turmaSelect = document.getElementById('turma');
        const matriculaForm = document.getElementById('matricula-form');
        
        if (!unidadeSelect || !turmaSelect) return;
        
        // Área para exibir alertas relacionados a vagas
        const alertaContainer = document.createElement('div');
        alertaContainer.className = 'alerta-vagas';
        turmaSelect.parentNode.insertBefore(alertaContainer, turmaSelect.nextSibling);
        
        // Função para criar um alerta
        function criarAlerta(tipo, mensagem) {
            return `<div class="alerta-${tipo}">${mensagem}</div>`;
        }
        
        // Função para criar uma barra de progresso
        function criarProgressBar(porcentagem, cor) {
            return `
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${porcentagem}%; background-color: ${cor};"></div>
                </div>
            `;
        }
        
        // Função para carregar unidades do banco de dados
        function carregarUnidades() {
            fetch('/formulario/get_unidades.php')
                .then(response => {
                    if (!response.ok) throw new Error('Erro na resposta da rede');
                    return response.json();
                })
                .then(data => {
                    // Limpar e adicionar opção padrão
                    unidadeSelect.innerHTML = '<option value="">Selecione uma unidade</option>';
                    
                    // Adicionar opções de unidades
                    data.forEach(unidade => {
                        const option = document.createElement('option');
                        option.value = unidade.id;
                        option.textContent = unidade.nome;
                        unidadeSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Erro ao carregar unidades:', error);
                    alertaContainer.innerHTML = criarAlerta(
                        'erro', 
                        'Erro ao carregar unidades. Por favor, tente novamente mais tarde.'
                    );
                });
        }
        
        // Função para carregar turmas com base na unidade selecionada
        function carregarTurmas(unidadeId) {
            // Limpar e adicionar opção padrão
            turmaSelect.innerHTML = '<option value="">Carregando turmas...</option>';
            alertaContainer.innerHTML = '';
            
            if (!unidadeId) {
                turmaSelect.innerHTML = '<option value="">Selecione uma unidade primeiro</option>';
                return;
            }
            
            fetch(`/formulario/get_turmas.php?unidade_id=${unidadeId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na resposta da rede');
                    return response.json();
                })
                .then(data => {
                    turmaSelect.innerHTML = '<option value="">Selecione uma turma</option>';
                    
                    if (data.length === 0) {
                        turmaSelect.innerHTML = '<option value="">Nenhuma turma disponível</option>';
                        alertaContainer.innerHTML = criarAlerta(
                            'info', 
                            'Não há turmas disponíveis para esta unidade.'
                        );
                        return;
                    }
                    
                    // Verificar se há turmas com vagas
                    let temTurmasComVagas = false;
                    
                    data.forEach(turma => {
                        const option = document.createElement('option');
                        option.value = turma.id;
                        
                        // Calcular vagas disponíveis
                        const vagasDisponiveis = turma.capacidade - turma.matriculados;
                        const isLotada = vagasDisponiveis <= 0;
                        
                        if (isLotada) {
                            option.textContent = `${turma.nome_turma} (LOTADA)`;
                            option.disabled = true;
                            option.classList.add('turma-lotada');
                        } else {
                            option.textContent = `${turma.nome_turma} (${vagasDisponiveis} vagas)`;
                            temTurmasComVagas = true;
                        }
                        
                        turmaSelect.appendChild(option);
                    });
                    
                    // Mostrar alerta se todas as turmas estiverem lotadas
                    if (!temTurmasComVagas) {
                        alertaContainer.innerHTML = criarAlerta(
                            'erro', 
                            'Todas as turmas desta unidade estão lotadas.'
                        );
                    } else {
                        // Exibir barra de progresso para cada turma
                        let progressHTML = `<div style="margin-top: 10px;">`;
                        
                        data.forEach(turma => {
                            const ocupacaoPercentual = Math.min(100, Math.round((turma.matriculados / turma.capacidade) * 100));
                            let corBarra = '#2ecc71'; // var(--accent)
                            
                            if (ocupacaoPercentual > 90) corBarra = '#ff0000';
                            else if (ocupacaoPercentual > 75) corBarra = '#f39c12';
                            
                            progressHTML += `
                                <div style="margin-bottom: 6px;">
                                    <small>${turma.nome_turma}: ${turma.matriculados}/${turma.capacidade} (${ocupacaoPercentual}%)</small>
                                    ${criarProgressBar(ocupacaoPercentual, corBarra)}
                                </div>
                            `;
                        });
                        
                        progressHTML += `</div>`;
                        alertaContainer.innerHTML = progressHTML;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar turmas:', error);
                    turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                    alertaContainer.innerHTML = criarAlerta(
                        'erro', 
                        'Erro ao carregar turmas. Por favor, tente novamente mais tarde.'
                    );
                });
        }
        
        // Event listener para mudança na seleção de unidade
        unidadeSelect.addEventListener('change', function() {
            carregarTurmas(this.value);
        });
        
        // Validação do formulário para evitar matrícula em turmas lotadas
        if (matriculaForm) {
            matriculaForm.addEventListener('submit', function(e) {
                if (!turmaSelect.value) {
                    e.preventDefault();
                    alertaContainer.innerHTML = criarAlerta(
                        'erro', 
                        'Por favor, selecione uma turma válida antes de continuar.'
                    );
                    return false;
                }
                
                const turmaOption = turmaSelect.options[turmaSelect.selectedIndex];
                
                if (turmaOption.disabled) {
                    e.preventDefault();
                    alertaContainer.innerHTML = criarAlerta(
                        'erro', 
                        'Esta turma está lotada. Por favor, selecione outra turma com vagas disponíveis.'
                    );
                    return false;
                }
                
                return true;
            });
        }
        
        // Carregar unidades quando a página carregar
        carregarUnidades();
    });