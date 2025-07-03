/**
 * SISTEMA DE RANKING DOS ALUNOS
 * Gerencia o modal de ranking, consultas ao banco e exibição dos dados
 */

class SistemaRanking {
    constructor() {
        this.rankingAtual = [];
        this.turmasSelecionadas = [];
        this.periodoAtual = 'atual';
        this.init();
    }

    init() {
        this.bindEvents();
        this.carregarTurmas();
        console.log('🏆 Sistema de Ranking inicializado');
    }

    bindEvents() {
        // Evento do botão principal
        document.getElementById('ranking-btn')?.addEventListener('click', () => {
            this.abrirModal();
        });

        // Eventos de fechar modal
        document.querySelectorAll('.fechar-modal-ranking').forEach(btn => {
            btn.addEventListener('click', () => this.fecharModal());
        });

        document.querySelectorAll('.fechar-modal-detalhes-aluno').forEach(btn => {
            btn.addEventListener('click', () => this.fecharModalDetalhes());
        });

        // Eventos das abas
        document.querySelectorAll('.tab-ranking').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.trocarAba(e.target.dataset.tab);
            });
        });

        // Fechar modal clicando fora
        document.getElementById('modal-ranking')?.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.fecharModal();
            }
        });

        document.getElementById('modal-detalhes-aluno-ranking')?.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.fecharModalDetalhes();
            }
        });
    }

    async abrirModal() {
        try {
            document.getElementById('modal-ranking').style.display = 'flex';
            await this.carregarRanking();
        } catch (error) {
            console.error('❌ Erro ao abrir modal de ranking:', error);
            this.exibirNotificacao('Erro ao carregar ranking', 'error');
        }
    }

    fecharModal() {
        document.getElementById('modal-ranking').style.display = 'none';
    }

    fecharModalDetalhes() {
        document.getElementById('modal-detalhes-aluno-ranking').style.display = 'none';
    }

    trocarAba(aba) {
        // Remover classe ativo de todas as abas
        document.querySelectorAll('.tab-ranking').forEach(tab => {
            tab.classList.remove('ativo');
        });

        // Esconder todo conteúdo
        document.querySelectorAll('.tab-content-ranking').forEach(content => {
            content.style.display = 'none';
        });

        // Ativar aba selecionada
        document.querySelector(`[data-tab="${aba}"]`).classList.add('ativo');
        document.getElementById(`tab-${aba}`).style.display = 'block';

        // Carregar conteúdo específico da aba
        switch (aba) {
            case 'geral':
                this.exibirRankingGeral();
                break;
            case 'premiados':
                this.exibirRankingPremiados();
                break;
            case 'estatisticas':
                this.carregarEstatisticas();
                break;
            case 'relatorios':
                // Já está carregado
                break;
        }
    }

    async carregarTurmas() {
        try {
            const response = await fetch('api/listar_turmas.php');
            const data = await response.json();

            if (data.success) {
                const select = document.getElementById('filtro-turma-ranking');
                select.innerHTML = '<option value="">Todas as Turmas</option>';

                data.turmas.forEach(turma => {
                    const option = document.createElement('option');
                    option.value = turma.id;
                    option.textContent = `${turma.nome} - ${turma.unidade_nome}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('❌ Erro ao carregar turmas:', error);
        }
    }

    async carregarRanking() {
        try {
            this.exibirLoading('ranking-geral-container');
            
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const periodo = document.getElementById('filtro-periodo-ranking').value;

            const params = new URLSearchParams();
            if (turmaId) params.append('turma_id', turmaId);
            if (periodo && periodo !== 'atual') params.append('periodo', periodo);

            const response = await fetch(`api/ranking_alunos.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.rankingAtual = data.ranking;
                this.exibirRankingGeral();
                this.atualizarEstatisticas(data.estatisticas || {});
                
                // Atualizar contador
                document.getElementById('total-alunos-ranking').textContent = 
                    `${this.rankingAtual.length} alunos avaliados`;
            } else {
                throw new Error(data.message || 'Erro ao carregar ranking');
            }

        } catch (error) {
            console.error('❌ Erro ao carregar ranking:', error);
            this.exibirErro('ranking-geral-container', 'Erro ao carregar dados do ranking');
        }
    }

    exibirRankingGeral() {
        const container = document.getElementById('ranking-geral-container');
        
        if (!this.rankingAtual || this.rankingAtual.length === 0) {
            container.innerHTML = this.getHTMLVazio('Nenhum dados de ranking encontrado');
            return;
        }

        // Agrupar por turma
        const rankingPorTurma = this.agruparPorTurma(this.rankingAtual);
        
        let html = '';
        
        Object.keys(rankingPorTurma).forEach(turmaId => {
            const turmaData = rankingPorTurma[turmaId];
            const alunos = turmaData.alunos;
            
            html += `
                <div class="turma-ranking">
                    <div class="turma-header">
                        <h4>
                            <i class="fas fa-chalkboard"></i>
                            ${alunos[0].turma_nome || `Turma ${turmaId}`}
                        </h4>
                        <span class="turma-stats">${alunos.length} alunos</span>
                    </div>
                    <div class="alunos-ranking">
                        ${alunos.map(aluno => this.getHTMLAluno(aluno)).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        this.bindEventosAlunos();
    }

    exibirRankingPremiados() {
        const container = document.getElementById('ranking-premiados-container');
        
        // Filtrar apenas os premiados (top 3 de cada turma)
        const premiados = this.rankingAtual.filter(aluno => 
            aluno.premiado === 'SIM' || aluno.posicao <= 3
        );

        if (premiados.length === 0) {
            container.innerHTML = this.getHTMLVazio('Nenhum aluno premiado encontrado');
            return;
        }

        // Agrupar por turma
        const premiadosPorTurma = this.agruparPorTurma(premiados);
        
        let html = '';
        
        Object.keys(premiadosPorTurma).forEach(turmaId => {
            const turmaData = premiadosPorTurma[turmaId];
            const alunos = turmaData.alunos;
            
            html += `
                <div class="turma-ranking">
                    <div class="turma-header">
                        <h4>
                            <i class="fas fa-trophy"></i>
                            ${alunos[0].turma_nome || `Turma ${turmaId}`} - Premiados
                        </h4>
                        <span class="turma-stats">${alunos.length} premiados</span>
                    </div>
                    <div class="alunos-ranking">
                        ${alunos.map(aluno => this.getHTMLAlunoPremiado(aluno)).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        this.bindEventosAlunos();
        
        // Atualizar contador
        document.getElementById('total-premiados').textContent = `${premiados.length} alunos premiados`;
    }

    async carregarEstatisticas() {
        try {
            const container = document.getElementById('estatisticas-container');
            this.exibirLoading('estatisticas-container');

            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const params = new URLSearchParams();
            if (turmaId) params.append('turma_id', turmaId);

            const response = await fetch(`api/ranking_estatisticas.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.exibirEstatisticas(data.estatisticas);
            } else {
                throw new Error(data.message || 'Erro ao carregar estatísticas');
            }

        } catch (error) {
            console.error('❌ Erro ao carregar estatísticas:', error);
            this.exibirErro('estatisticas-container', 'Erro ao carregar estatísticas');
        }
    }

    exibirEstatisticas(estatisticas) {
        const container = document.getElementById('estatisticas-container');
        
        if (!estatisticas || estatisticas.length === 0) {
            container.innerHTML = this.getHTMLVazio('Nenhuma estatística disponível');
            return;
        }

        let html = '';
        
        estatisticas.forEach(turma => {
            html += `
                <div class="estatistica-turma">
                    <h4>
                        <i class="fas fa-chart-bar"></i>
                        ${turma.turma_nome || `Turma ${turma.turma_id}`}
                    </h4>
                    
                    <div class="estatistica-item">
                        <span class="estatistica-label">Total de Alunos:</span>
                        <span class="estatistica-valor">${turma.total_alunos}</span>
                    </div>
                    
                    <div class="estatistica-item">
                        <span class="estatistica-label">Média da Turma:</span>
                        <span class="estatistica-valor">${parseFloat(turma.media_turma || 0).toFixed(1)}</span>
                    </div>
                    
                    <div class="estatistica-item">
                        <span class="estatistica-label">Maior Nota:</span>
                        <span class="estatistica-valor">${parseFloat(turma.maior_nota || 0).toFixed(1)}</span>
                    </div>
                    
                    <div class="estatistica-item">
                        <span class="estatistica-label">Menor Nota:</span>
                        <span class="estatistica-valor">${parseFloat(turma.menor_nota || 0).toFixed(1)}</span>
                    </div>
                    
                    <div class="estatistica-item">
                        <span class="estatistica-label">Desvio Padrão:</span>
                        <span class="estatistica-valor">${parseFloat(turma.desvio_padrao || 0).toFixed(1)}</span>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    getHTMLAluno(aluno) {
        const posicaoClass = this.getPosicaoClass(aluno.posicao);
        const premioTexto = this.getPremioTexto(aluno.posicao);
        
        return `
            <div class="aluno-ranking-item" onclick="sistemaRanking.abrirDetalhesAluno(${aluno.aluno_id})">
                <div class="posicao-ranking ${posicaoClass}">
                    ${aluno.posicao}
                </div>
                
                <div class="aluno-info">
                    <div class="aluno-nome">${aluno.aluno_nome || 'Nome não informado'}</div>
                    <div class="aluno-detalhes">
                        <span><i class="fas fa-chart-line"></i> Física: ${parseFloat(aluno.nota_fisica || 0).toFixed(1)}</span>
                        <span><i class="fas fa-tasks"></i> Atividades: ${parseFloat(aluno.nota_atividades || 0).toFixed(1)}</span>
                        <span><i class="fas fa-calendar-check"></i> Presença: ${parseFloat(aluno.taxa_presenca || 0).toFixed(0)}%</span>
                    </div>
                </div>
                
                <div class="nota-final">
                    <div class="nota-valor">${parseFloat(aluno.media_final || 0).toFixed(1)}</div>
                    <div class="nota-label">Média</div>
                </div>
                
                <div class="premio-badge ${this.getPremioClass(aluno.posicao)}">
                    ${premioTexto}
                </div>
            </div>
        `;
    }

    getHTMLAlunoPremiado(aluno) {
        const posicaoClass = this.getPosicaoClass(aluno.posicao);
        const premioTexto = this.getPremioTexto(aluno.posicao);
        
        return `
            <div class="aluno-ranking-item ${posicaoClass}" onclick="sistemaRanking.abrirDetalhesAluno(${aluno.aluno_id})">
                <div class="posicao-ranking ${posicaoClass}">
                    ${aluno.posicao === 1 ? '🥇' : aluno.posicao === 2 ? '🥈' : '🥉'}
                </div>
                
                <div class="aluno-info">
                    <div class="aluno-nome">${aluno.aluno_nome || 'Nome não informado'}</div>
                    <div class="aluno-detalhes">
                        <span><i class="fas fa-trophy"></i> ${premioTexto}</span>
                        <span><i class="fas fa-star"></i> Média: ${parseFloat(aluno.media_final || 0).toFixed(1)}</span>
                    </div>
                </div>
                
                <div class="nota-final">
                    <div class="nota-valor">${parseFloat(aluno.media_final || 0).toFixed(1)}</div>
                    <div class="nota-label">Pontos</div>
                </div>
                
                <div class="premio-badge ${this.getPremioClass(aluno.posicao)}">
                    ${premioTexto}
                </div>
            </div>
        `;
    }

    abrirDetalhesAluno(alunoId) {
        const aluno = this.rankingAtual.find(a => a.aluno_id == alunoId);
        if (!aluno) {
            this.exibirNotificacao('Aluno não encontrado', 'error');
            return;
        }

        // Preencher dados do modal
        document.getElementById('detalhe-nome-aluno').textContent = aluno.aluno_nome || 'Nome não informado';
        document.getElementById('detalhe-turma-aluno').textContent = aluno.turma_nome || 'Turma não informada';
        document.getElementById('detalhe-posicao-aluno').textContent = `${aluno.posicao}º lugar`;
        
        const premioTexto = this.getPremioTexto(aluno.posicao);
        const premioBadge = document.getElementById('detalhe-premio-aluno');
        premioBadge.textContent = premioTexto;
        premioBadge.className = `premio-badge ${this.getPremioClass(aluno.posicao)}`;

        // Preencher notas com barras de progresso
        this.preencherBarra('barra-fisica', 'nota-fisica-valor', aluno.nota_fisica, 10);
        this.preencherBarra('barra-comportamento-aval', 'nota-comportamento-aval-valor', aluno.nota_comportamento, 10);
        this.preencherBarra('barra-atividades', 'nota-atividades-valor', aluno.nota_atividades, 10);
        this.preencherBarra('barra-comportamento-ativ', 'nota-comportamento-ativ-valor', aluno.nota_comportamento_atividades, 10);
        this.preencherBarra('barra-presenca', 'nota-presenca-valor', aluno.taxa_presenca, 100, '%');

        // Média final
        document.getElementById('media-final-valor').textContent = parseFloat(aluno.media_final || 0).toFixed(1);

        // Informações adicionais
        document.getElementById('total-avaliacoes-aluno').textContent = aluno.total_avaliacoes || 0;
        document.getElementById('total-atividades-aluno').textContent = aluno.total_atividades || 0;
        document.getElementById('data-calculo-aluno').textContent = this.formatarData(aluno.data_calculo);

        // Abrir modal
        document.getElementById('modal-detalhes-aluno-ranking').style.display = 'flex';
    }

    preencherBarra(barraId, valorId, valor, maximo, sufixo = '') {
        const barra = document.getElementById(barraId);
        const valorElement = document.getElementById(valorId);
        
        const valorNum = parseFloat(valor || 0);
        const porcentagem = (valorNum / maximo) * 100;
        
        setTimeout(() => {
            barra.style.width = `${Math.min(porcentagem, 100)}%`;
        }, 100);
        
        valorElement.textContent = valorNum.toFixed(sufixo === '%' ? 0 : 1) + sufixo;
    }

    getPosicaoClass(posicao) {
        switch (parseInt(posicao)) {
            case 1: return 'primeiro';
            case 2: return 'segundo';
            case 3: return 'terceiro';
            default: return 'normal';
        }
    }

    getPremioClass(posicao) {
        switch (parseInt(posicao)) {
            case 1: return 'ouro';
            case 2: return 'prata';
            case 3: return 'bronze';
            default: return 'participante';
        }
    }

    getPremioTexto(posicao) {
        switch (parseInt(posicao)) {
            case 1: return '1º LUGAR - OURO';
            case 2: return '2º LUGAR - PRATA';
            case 3: return '3º LUGAR - BRONZE';
            default: return 'PARTICIPANTE';
        }
    }

    agruparPorTurma(alunos) {
        const grupos = {};
        
        alunos.forEach(aluno => {
            const turmaId = aluno.turma_id;
            if (!grupos[turmaId]) {
                grupos[turmaId] = {
                    id: turmaId,
                    nome: aluno.turma_nome,
                    alunos: []
                };
            }
            grupos[turmaId].alunos.push(aluno);
        });

        // Ordenar alunos dentro de cada turma por posição
        Object.keys(grupos).forEach(turmaId => {
            grupos[turmaId].alunos.sort((a, b) => a.posicao - b.posicao);
        });

        return grupos;
    }

    bindEventosAlunos() {
        // Os eventos onClick já estão nos HTMLs
    }

    async calcularNovoRanking() {
        try {
            this.exibirNotificacao('Calculando novo ranking...', 'info');
            
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const periodo = document.getElementById('filtro-periodo-ranking').value;

            const dados = {
                turma_id: turmaId || null,
                periodo: periodo === 'atual' ? null : periodo
            };

            const response = await fetch('api/calcular_ranking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            const data = await response.json();

            if (data.success) {
                this.exibirNotificacao('Ranking calculado com sucesso!', 'success');
                await this.carregarRanking();
            } else {
                throw new Error(data.message || 'Erro ao calcular ranking');
            }

        } catch (error) {
            console.error('❌ Erro ao calcular ranking:', error);
            this.exibirNotificacao('Erro ao calcular ranking', 'error');
        }
    }

    // Relatórios específicos
    async gerarRelatorioFisico() {
        try {
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const params = new URLSearchParams({ tipo: 'fisico' });
            if (turmaId) params.append('turma_id', turmaId);

            const response = await fetch(`api/ranking_relatorio.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.exibirRelatorio('Melhor Desempenho Físico', data.dados);
            }
        } catch (error) {
            console.error('❌ Erro ao gerar relatório físico:', error);
        }
    }

    async gerarRelatorioComportamento() {
        try {
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const params = new URLSearchParams({ tipo: 'comportamento' });
            if (turmaId) params.append('turma_id', turmaId);

            const response = await fetch(`api/ranking_relatorio.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.exibirRelatorio('Melhor Comportamento', data.dados);
            }
        } catch (error) {
            console.error('❌ Erro ao gerar relatório de comportamento:', error);
        }
    }

    async gerarRelatorioPresenca() {
        try {
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const params = new URLSearchParams({ tipo: 'presenca' });
            if (turmaId) params.append('turma_id', turmaId);

            const response = await fetch(`api/ranking_relatorio.php?${params}`);
            const data = await response.json();

            if (data.success) {
                this.exibirRelatorio('Melhor Presença', data.dados);
            }
        } catch (error) {
            console.error('❌ Erro ao gerar relatório de presença:', error);
        }
    }

    exibirRelatorio(titulo, dados) {
        // Implementar exibição do relatório específico
        console.log(`📊 Relatório: ${titulo}`, dados);
        this.exibirNotificacao(`Relatório ${titulo} carregado!`, 'success');
    }

    // Funções de exportação
    async exportarRankingPDF() {
        try {
            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const periodo = document.getElementById('filtro-periodo-ranking').value;

            const params = new URLSearchParams();
            if (turmaId) params.append('turma_id', turmaId);
            if (periodo && periodo !== 'atual') params.append('periodo', periodo);

            const url = `api/exportar_ranking_pdf.php?${params}`;
            window.open(url, '_blank');
            
            this.exibirNotificacao('PDF do ranking gerado!', 'success');
        } catch (error) {
            console.error('❌ Erro ao exportar PDF:', error);
            this.exibirNotificacao('Erro ao gerar PDF', 'error');
        }
    }

    async gerarCertificados() {
        try {
            const premiados = this.rankingAtual.filter(aluno => 
                aluno.premiado === 'SIM' || aluno.posicao <= 3
            );

            if (premiados.length === 0) {
                this.exibirNotificacao('Nenhum aluno premiado para gerar certificados', 'warning');
                return;
            }

            const turmaId = document.getElementById('filtro-turma-ranking').value;
            const params = new URLSearchParams({ apenas_premiados: 1 });
            if (turmaId) params.append('turma_id', turmaId);

            const url = `api/gerar_certificados_ranking.php?${params}`;
            window.open(url, '_blank');
            
            this.exibirNotificacao('Certificados gerados!', 'success');
        } catch (error) {
            console.error('❌ Erro ao gerar certificados:', error);
            this.exibirNotificacao('Erro ao gerar certificados', 'error');
        }
    }

    async gerarCertificadoIndividual() {
        try {
            const alunoId = this.alunoAtualDetalhes?.aluno_id;
            if (!alunoId) return;

            const url = `api/gerar_certificado_individual.php?aluno_id=${alunoId}`;
            window.open(url, '_blank');
            
            this.exibirNotificacao('Certificado individual gerado!', 'success');
        } catch (error) {
            console.error('❌ Erro ao gerar certificado:', error);
            this.exibirNotificacao('Erro ao gerar certificado', 'error');
        }
    }

    // Funções auxiliares
    atualizarEstatisticas(estatisticas) {
        // Implementar se necessário
    }

    formatarData(data) {
        if (!data) return '-';
        
        try {
            const date = new Date(data);
            return date.toLocaleDateString('pt-BR');
        } catch {
            return data;
        }
    }

    exibirLoading(containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="ranking-loading">
                <i class="fas fa-spinner"></i>
                Carregando dados do ranking...
            </div>
        `;
    }

    exibirErro(containerId, mensagem) {
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="sem-ranking">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Erro</h3>
                <p>${mensagem}</p>
            </div>
        `;
    }

    getHTMLVazio(mensagem) {
        return `
            <div class="sem-ranking">
                <i class="fas fa-trophy"></i>
                <h3>Nenhum dado encontrado</h3>
                <p>${mensagem}</p>
            </div>
        `;
    }

    exibirNotificacao(mensagem, tipo = 'info') {
        if (typeof window.exibirNotificacao === 'function') {
            window.exibirNotificacao(mensagem, tipo);
        } else {
            console.log(`${tipo.toUpperCase()}: ${mensagem}`);
        }
    }
}

// Funções globais para os eventos onclick
window.atualizarRanking = function() {
    sistemaRanking.carregarRanking();
};

window.calcularNovoRanking = function() {
    sistemaRanking.calcularNovoRanking();
};

window.gerarRelatorioFisico = function() {
    sistemaRanking.gerarRelatorioFisico();
};

window.gerarRelatorioComportamento = function() {
    sistemaRanking.gerarRelatorioComportamento();
};

window.gerarRelatorioPresenca = function() {
    sistemaRanking.gerarRelatorioPresenca();
};

window.gerarRelatorioCompleto = function() {
    sistemaRanking.exportarRankingPDF();
};

window.exportarRankingPDF = function() {
    sistemaRanking.exportarRankingPDF();
};

window.gerarCertificados = function() {
    sistemaRanking.gerarCertificados();
};

window.gerarCertificadoIndividual = function() {
    sistemaRanking.gerarCertificadoIndividual();
};

// Inicializar sistema quando DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.sistemaRanking === 'undefined') {
        window.sistemaRanking = new SistemaRanking();
        console.log('🏆 Sistema de Ranking inicializado globalmente');
    }
});