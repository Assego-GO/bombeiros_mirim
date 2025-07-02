// js/ocorrencias.js
$(document).ready(function() {
    
    // Abrir modal de ocorrências quando clicar no card
    $('#card-ocorrencias').click(function() {
        $('#ocorrenciasModal').show();
    });

    // Fechar modais
    $('#closeOcorrenciasModal').click(function() {
        $('#ocorrenciasModal').hide();
    });

    $('#closeCadastroOcorrenciaModal').click(function() {
        $('#cadastroOcorrenciaModal').hide();
        limparFormularioOcorrencia();
    });

    $('#closeListaOcorrenciasModal').click(function() {
        $('#listaOcorrenciasModal').hide();
    });

    $('#closeDetalhesOcorrenciaModal').click(function() {
        $('#detalhesOcorrenciaModal').hide();
    });

    // Botão Nova Ocorrência
    $('#btn-nova-ocorrencia').click(function() {
        $('#ocorrenciasModal').hide();
        $('#cadastroOcorrenciaModal').show();
        $('#modalTitleCadastroOcorrencia').text('Nova Ocorrência');
        $('#ocorrencia_id').val('');
        limparFormularioOcorrencia();
        
        // Definir data atual
        var hoje = new Date().toISOString().split('T')[0];
        $('#data_ocorrencia').val(hoje);
    });

    // Botão Listar Ocorrências
    $('#btn-listar-ocorrencias').click(function() {
        $('#ocorrenciasModal').hide();
        carregarListaOcorrencias();
        $('#listaOcorrenciasModal').show();
    });

    // Botão Cancelar
    $('#btn-cancelar-ocorrencia').click(function() {
        $('#cadastroOcorrenciaModal').hide();
        limparFormularioOcorrencia();
    });

    // Toggle do campo de detalhes da reunião
    $('#houve_reuniao').change(function() {
        if ($(this).is(':checked')) {
            $('#detalhes_reuniao_group').show();
            $('#detalhes_reuniao').attr('required', true);
        } else {
            $('#detalhes_reuniao_group').hide();
            $('#detalhes_reuniao').attr('required', false).val('');
        }
    });

    // Carregar alunos quando selecionar turma
    $('#turma_ocorrencia').change(function() {
        var turmaId = $(this).val();
        carregarAlunosPorTurma(turmaId, '#aluno_ocorrencia');
    });

 

    // Submit do formulário de ocorrência
    $('#form-ocorrencia').submit(function(e) {
        e.preventDefault();
        salvarOcorrencia();
    });

    // Funções
    function carregarAlunosPorTurma(turmaId, selectElement) {
        if (!turmaId) {
            $(selectElement).html('<option value="">Primeiro selecione uma turma</option>');
            return;
        }

        $(selectElement).html('<option value="">Carregando alunos...</option>');

        $.ajax({
            url: 'api/buscar_alunos_turma.php',
            type: 'GET',
            data: { turma_id: turmaId },
            dataType: 'json',
            success: function(response) {
                console.log('Resposta da API:', response); // Debug
                
                $(selectElement).empty();
                $(selectElement).append('<option value="">Selecione um aluno</option>');
                
                if (response.success && response.alunos && response.alunos.length > 0) {
                    $.each(response.alunos, function(index, aluno) {
                        var optionText = aluno.nome;
                        if (aluno.numero_matricula) {
                            optionText += ' (' + aluno.numero_matricula + ')';
                        }
                        $(selectElement).append('<option value="' + aluno.id + '">' + optionText + '</option>');
                    });
                    
                    // Mostrar mensagem de sucesso se houver
                    if (response.message) {
                        console.log('Info:', response.message);
                    }
                } else {
                    var mensagem = response.message || 'Nenhum aluno encontrado';
                    $(selectElement).append('<option value="">' + mensagem + '</option>');
                    console.log('Nenhum aluno encontrado:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', status, error);
                console.error('Response:', xhr.responseText);
                
                $(selectElement).html('<option value="">Erro ao carregar alunos</option>');
                mostrarMensagemOcorrencia('Erro ao carregar alunos da turma. Verifique o console para mais detalhes.', 'error');
            }
        });
    }

   

    function salvarOcorrencia() {
        var formData = new FormData($('#form-ocorrencia')[0]);
        
        // Converter checkbox para valor 1 ou 0
        if ($('#houve_reuniao').is(':checked')) {
            formData.set('houve_reuniao_responsaveis', '1');
        } else {
            formData.set('houve_reuniao_responsaveis', '0');
        }

        $.ajax({
            url: 'api/gerenciar_ocorrencias.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarMensagemOcorrencia('Ocorrência salva com sucesso!', 'success');
                    setTimeout(function() {
                        $('#cadastroOcorrenciaModal').hide();
                        limparFormularioOcorrencia();
                    }, 2000);
                } else {
                    mostrarMensagemOcorrencia(response.message, 'error');
                }
            },
            error: function() {
                mostrarMensagemOcorrencia('Erro ao salvar ocorrência. Tente novamente.', 'error');
            }
        });
    }

    function carregarListaOcorrencias() {
        $('#lista-ocorrencias-container').html('<p>Carregando ocorrências...</p>');

        $.ajax({
            url: 'api/listar_ocorrencias.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    exibirListaOcorrencias(response.ocorrencias);
                } else {
                    $('#lista-ocorrencias-container').html('<div class="alert alert-info">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#lista-ocorrencias-container').html('<div class="alert alert-error">Erro ao carregar ocorrências.</div>');
            }
        });
    }

    function exibirListaOcorrencias(ocorrencias) {
        var html = '';
        
        if (ocorrencias.length === 0) {
            html = '<div class="alert alert-info">Nenhuma ocorrência encontrada.</div>';
        } else {
            html += '<div class="ocorrencias-lista">';
            
            $.each(ocorrencias, function(index, ocorrencia) {
                var dataFormatada = formatarData(ocorrencia.data_ocorrencia);
                var statusClass = ocorrencia.status === 'ativa' ? 'status-ativa' : 'status-resolvida';
                
                html += '<div class="ocorrencia-item" data-ocorrencia-id="' + ocorrencia.id + '">';
                html += '  <div class="ocorrencia-header">';
                html += '    <h4>Ocorrência #' + ocorrencia.id + '</h4>';
                html += '    <span class="status ' + statusClass + '">' + ucfirst(ocorrencia.status) + '</span>';
                html += '  </div>';
                html += '  <div class="ocorrencia-info">';
                html += '    <p><strong>Data:</strong> ' + dataFormatada + '</p>';
                html += '    <p><strong>Aluno:</strong> ' + ocorrencia.nome_aluno + '</p>';
                html += '    <p><strong>Turma:</strong> ' + ocorrencia.nome_turma + '</p>';
                html += '    <p><strong>Descrição:</strong> ' + truncarTexto(ocorrencia.descricao, 100) + '</p>';
                html += '  </div>';
                html += '  <div class="ocorrencia-actions">';
                html += '    <button class="btn btn-small btn-ver-detalhes" data-ocorrencia-id="' + ocorrencia.id + '">';
                html += '      <i class="fas fa-eye"></i> Ver Detalhes';
                html += '    </button>';
                html += '    <button class="btn btn-small btn-editar-ocorrencia" data-ocorrencia-id="' + ocorrencia.id + '">';
                html += '      <i class="fas fa-edit"></i> Editar';
                html += '    </button>';
                html += '  </div>';
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        $('#lista-ocorrencias-container').html(html);
    }

    // Event delegation para botões da lista
    $(document).on('click', '.btn-ver-detalhes', function() {
        var ocorrenciaId = $(this).data('ocorrencia-id');
        verDetalhesOcorrencia(ocorrenciaId);
    });

    $(document).on('click', '.btn-editar-ocorrencia', function() {
        var ocorrenciaId = $(this).data('ocorrencia-id');
        editarOcorrencia(ocorrenciaId);
    });

    function verDetalhesOcorrencia(ocorrenciaId) {
        $('#detalhes-ocorrencia-container').html('<p>Carregando detalhes...</p>');
        $('#listaOcorrenciasModal').hide();
        $('#detalhesOcorrenciaModal').show();

        $.ajax({
            url: 'api/buscar_ocorrencia.php',
            type: 'GET',
            data: { id: ocorrenciaId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    exibirDetalhesOcorrencia(response.ocorrencia);
                } else {
                    $('#detalhes-ocorrencia-container').html('<div class="alert alert-error">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#detalhes-ocorrencia-container').html('<div class="alert alert-error">Erro ao carregar detalhes da ocorrência.</div>');
            }
        });
    }

// Atualização da função exibirDetalhesOcorrencia para incluir o feedback da administração
function exibirDetalhesOcorrencia(ocorrencia) {
    var dataFormatada = formatarData(ocorrencia.data_ocorrencia);
    var dataCriacaoFormatada = formatarDataHora(ocorrencia.data_criacao);
    var statusClass = ocorrencia.status === 'ativa' ? 'status-ativa' : 'status-resolvida';
    
    var html = '<div class="detalhes-ocorrencia">';
    html += '  <div class="detalhes-header">';
    html += '    <h3>Ocorrência #' + ocorrencia.id + '</h3>';
    html += '    <span class="status ' + statusClass + '">' + ucfirst(ocorrencia.status) + '</span>';
    html += '  </div>';
    
    html += '  <div class="detalhes-section">';
    html += '    <h4>Informações Básicas</h4>';
    html += '    <div class="info-grid">';
    html += '      <div class="info-item"><strong>Data da Ocorrência:</strong> ' + dataFormatada + '</div>';
    html += '      <div class="info-item"><strong>Aluno:</strong> ' + ocorrencia.nome_aluno + '</div>';
    html += '      <div class="info-item"><strong>Turma:</strong> ' + ocorrencia.nome_turma + '</div>';
    html += '      <div class="info-item"><strong>Registrado em:</strong> ' + dataCriacaoFormatada + '</div>';
    html += '    </div>';
    html += '  </div>';
    
    html += '  <div class="detalhes-section">';
    html += '    <h4>Descrição da Ocorrência</h4>';
    html += '    <p>' + ocorrencia.descricao.replace(/\n/g, '<br>') + '</p>';
    html += '  </div>';
    
    if (ocorrencia.acoes_tomadas) {
        html += '  <div class="detalhes-section">';
        html += '    <h4>Ações Tomadas</h4>';
        html += '    <p>' + ocorrencia.acoes_tomadas.replace(/\n/g, '<br>') + '</p>';
        html += '  </div>';
    }
    
    html += '  <div class="detalhes-section">';
    html += '    <h4>Reunião com Responsáveis</h4>';
    if (ocorrencia.houve_reuniao_responsaveis == 1) {
        html += '    <p><strong>Status:</strong> <span style="color: #28a745;">✓ Sim, houve reunião</span></p>';
        if (ocorrencia.detalhes_reuniao) {
            html += '    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">';
            html += '      <strong>Detalhes da reunião:</strong><br>';
            html += '      ' + ocorrencia.detalhes_reuniao.replace(/\n/g, '<br>');
            html += '    </div>';
        }
    } else {
        html += '    <p><strong>Status:</strong> <span style="color: #6c757d;">✗ Não houve reunião</span></p>';
    }
    html += '  </div>';
    
    // NOVA SEÇÃO: Feedback da Administração
    html += '  <div class="detalhes-section">';
    html += '    <h4><i class="fas fa-comment-dots"></i> Feedback da Administração</h4>';
    
    if (ocorrencia.feedback_admin && ocorrencia.feedback_admin.trim() !== '') {
        // Se há feedback, mostrar com destaque
        html += '    <div class="feedback-admin-container">';
        html += '      <div class="feedback-content">';
        html += '        <p>' + ocorrencia.feedback_admin.replace(/\n/g, '<br>') + '</p>';
        html += '      </div>';
        
        // Mostrar informações do feedback se disponíveis
        if (ocorrencia.feedback_data || ocorrencia.feedback_admin_nome) {
            html += '      <div class="feedback-meta">';
            if (ocorrencia.feedback_admin_nome) {
                html += '        <span class="feedback-autor"><i class="fas fa-user-shield"></i> ' + ocorrencia.feedback_admin_nome + '</span>';
            }
            if (ocorrencia.feedback_data) {
                var feedbackDataFormatada = formatarDataHora(ocorrencia.feedback_data);
                html += '        <span class="feedback-data"><i class="fas fa-clock"></i> ' + feedbackDataFormatada + '</span>';
            }
            html += '      </div>';
        }
        html += '    </div>';
    } else {
        // Se não há feedback ainda
        html += '    <div class="no-feedback">';
        html += '      <p><i class="fas fa-info-circle"></i> Aguardando feedback da administração.</p>';
        html += '    </div>';
    }
    html += '  </div>';
    
    html += '  <div class="detalhes-actions">';
    html += '    <button class="btn btn-editar-ocorrencia" data-ocorrencia-id="' + ocorrencia.id + '">';
    html += '      <i class="fas fa-edit"></i> Editar Ocorrência';
    html += '    </button>';
    html += '    <button class="btn btn-secondary" onclick="$(\'#detalhesOcorrenciaModal\').hide(); $(\'#listaOcorrenciasModal\').show();">';
    html += '      <i class="fas fa-arrow-left"></i> Voltar à Lista';
    html += '    </button>';
    html += '  </div>';
    
    html += '</div>';
    
    $('#detalhes-ocorrencia-container').html(html);
}

    // Event delegation para botão de informações do aluno
    $(document).on('click', '.btn-info-aluno', function() {
        var alunoId = $(this).data('aluno-id');
        mostrarInfoComplementarAluno(alunoId);
    });

    // Função para mostrar informações completas do aluno usando seu buscar_aluno.php
    function mostrarInfoComplementarAluno(alunoId) {
        // Mostrar loading
        var loadingHtml = '<div class="loading-info"><i class="fas fa-spinner fa-spin"></i> Carregando informações do aluno...</div>';
        $('.btn-info-aluno').after(loadingHtml);
        
        $.ajax({
            url: 'api/buscar_aluno_info.php',
            type: 'GET',
            data: { aluno_id: alunoId },
            dataType: 'json',
            success: function(response) {
                $('.loading-info').remove();
                
                if (response.success) {
                    var aluno = response.aluno;
                    var endereco = response.endereco;
                    var responsaveis = response.responsaveis;
                    var presencas = response.presencas;
                    
                    var html = '<div class="aluno-info-completa" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">';
                    html += '  <h5><i class="fas fa-user"></i> Informações Completas - ' + aluno.nome + '</h5>';
                    
                    // Informações básicas
                    html += '  <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">';
                    html += '    <div>';
                    html += '      <p><strong>Idade:</strong> ' + (aluno.idade || 'Não informada') + ' anos</p>';
                    html += '      <p><strong>Gênero:</strong> ' + (aluno.genero || 'Não informado') + '</p>';
                    html += '      <p><strong>Escola:</strong> ' + (aluno.escola || 'Não informada') + '</p>';
                    html += '      <p><strong>Série:</strong> ' + (aluno.serie || 'Não informada') + '</p>';
                    html += '    </div>';
                    html += '    <div>';
                    if (presencas) {
                        html += '      <p><strong>Taxa de Presença:</strong> ' + presencas.taxa_presenca + '%</p>';
                        html += '      <p><strong>Total de Aulas:</strong> ' + presencas.total_aulas + '</p>';
                        html += '      <p><strong>Presenças:</strong> ' + presencas.total_presencas + '</p>';
                    }
                    html += '    </div>';
                    html += '  </div>';
                    
                    // Responsáveis
                    if (responsaveis && responsaveis.length > 0) {
                        html += '  <div style="margin-top: 15px;">';
                        html += '    <strong>Responsáveis:</strong>';
                        html += '    <ul style="margin: 5px 0; padding-left: 20px;">';
                        responsaveis.forEach(function(resp) {
                            html += '      <li>' + resp.nome + ' (' + resp.parentesco + ') - ' + resp.telefone + '</li>';
                        });
                        html += '    </ul>';
                        html += '  </div>';
                    }
                    
                    // Endereço
                    if (endereco) {
                        html += '  <div style="margin-top: 15px;">';
                        html += '    <strong>Endereço:</strong> ';
                        html += endereco.logradouro + ', ' + endereco.numero;
                        if (endereco.complemento) html += ' - ' + endereco.complemento;
                        html += ' - ' + endereco.bairro + ', ' + endereco.cidade + ' - CEP: ' + endereco.cep;
                        html += '  </div>';
                    }
                    
                    html += '  <div style="margin-top: 15px; text-align: right;">';
                    html += '    <button class="btn btn-small btn-secondary btn-fechar-info">';
                    html += '      <i class="fas fa-times"></i> Fechar Informações';
                    html += '    </button>';
                    html += '  </div>';
                    html += '</div>';
                    
                    $('.btn-info-aluno').after(html);
                } else {
                    $('.btn-info-aluno').after('<div class="alert alert-error" style="margin-top: 10px;">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('.loading-info').remove();
                console.log('Erro AJAX:', status, error);
                console.log('Response:', xhr.responseText);
                $('.btn-info-aluno').after('<div class="alert alert-error" style="margin-top: 10px;">Erro ao carregar informações do aluno. Verifique se o arquivo buscar_aluno.php existe.</div>');
            }
        });
    }

    // Event delegation para fechar informações do aluno
    $(document).on('click', '.btn-fechar-info', function() {
        $('.aluno-info-completa').remove();
    });

    function editarOcorrencia(ocorrenciaId) {
        // Fechar modais abertos
        $('#listaOcorrenciasModal').hide();
        $('#detalhesOcorrenciaModal').hide();
        
        // Carregar dados da ocorrência
        $.ajax({
            url: 'api/buscar_ocorrencia.php',
            type: 'GET',
            data: { id: ocorrenciaId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    preencherFormularioEdicao(response.ocorrencia);
                    $('#modalTitleCadastroOcorrencia').text('Editar Ocorrência');
                    $('#cadastroOcorrenciaModal').show();
                } else {
                    mostrarMensagemOcorrencia('Erro ao carregar dados da ocorrência.', 'error');
                }
            },
            error: function() {
                mostrarMensagemOcorrencia('Erro ao carregar dados da ocorrência.', 'error');
            }
        });
    }

    function preencherFormularioEdicao(ocorrencia) {
        $('#ocorrencia_id').val(ocorrencia.id);
        $('#turma_ocorrencia').val(ocorrencia.turma_id);
        $('#data_ocorrencia').val(ocorrencia.data_ocorrencia);
        $('#descricao_ocorrencia').val(ocorrencia.descricao);
        $('#acoes_tomadas').val(ocorrencia.acoes_tomadas || '');
        
        // Carregar alunos da turma e selecionar o aluno
        carregarAlunosPorTurma(ocorrencia.turma_id, '#aluno_ocorrencia');
        setTimeout(function() {
            $('#aluno_ocorrencia').val(ocorrencia.aluno_id);
        }, 500);
        
        // Checkbox reunião
        if (ocorrencia.houve_reuniao_responsaveis == 1) {
            $('#houve_reuniao').prop('checked', true);
            $('#detalhes_reuniao_group').show();
            $('#detalhes_reuniao').attr('required', true).val(ocorrencia.detalhes_reuniao || '');
        } else {
            $('#houve_reuniao').prop('checked', false);
            $('#detalhes_reuniao_group').hide();
            $('#detalhes_reuniao').attr('required', false).val('');
        }
        
        // Alterar action para editar
        $('input[name="action"]').val('editar');
    }

    function limparFormularioOcorrencia() {
        $('#form-ocorrencia')[0].reset();
        $('#ocorrencia_id').val('');
        $('#aluno_ocorrencia').html('<option value="">Primeiro selecione uma turma</option>');
        $('#detalhes_reuniao_group').hide();
        $('#detalhes_reuniao').attr('required', false);
        $('input[name="action"]').val('cadastrar');
        $('#mensagem-ocorrencia').html('');
    }

    function mostrarMensagemOcorrencia(mensagem, tipo) {
        var classe = tipo === 'success' ? 'alert-success' : 'alert-error';
        $('#mensagem-ocorrencia').html('<div class="alert ' + classe + '">' + mensagem + '</div>');
        
        setTimeout(function() {
            $('#mensagem-ocorrencia').html('');
        }, 5000);
    }

    // Funções utilitárias
    function formatarData(data) {
        var d = new Date(data + 'T00:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    function formatarDataHora(dataHora) {
        var d = new Date(dataHora);
        return d.toLocaleDateString('pt-BR') + ' às ' + d.toLocaleTimeString('pt-BR');
    }

    function truncarTexto(texto, limite) {
        if (texto.length <= limite) return texto;
        return texto.substr(0, limite) + '...';
    }

    function ucfirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

});