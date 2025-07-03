document.addEventListener('DOMContentLoaded', function() {
    
    configurarSistemaMensagens();
    
    configurarCamposResponsaveis();
    configurarEnvioFormulario();
    configurarInterface();
    carregarDadosDinamicos();
    aplicarMascaras();
    configurarCamposNumericos();
});

function configurarSistemaMensagens() {
    // Criar o container para mensagens se não existir
    if (!document.getElementById('sistema-mensagens')) {
        const sistemaMensagens = document.createElement('div');
        sistemaMensagens.id = 'sistema-mensagens';
        document.body.appendChild(sistemaMensagens);
        
        // Adicionar estilos para as mensagens
        const estilosMensagens = document.createElement('style');
        estilosMensagens.textContent = `
            #sistema-mensagens {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                width: 350px;
                max-width: 90%;
            }
            
            .mensagem {
                padding: 15px 20px;
                margin-bottom: 10px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                animation: slidein 0.3s ease-out;
                max-width: 100%;
                transition: transform 0.3s, opacity 0.3s;
            }
            
            .mensagem.saindo {
                transform: translateX(150%);
                opacity: 0;
            }
            
            .mensagem-conteudo {
                flex-grow: 1;
                font-weight: 500;
            }
            
            .mensagem-fechar {
                font-weight: bold;
                cursor: pointer;
                margin-left: 15px;
                height: 24px;
                width: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.2);
            }
            
            .mensagem-fechar:hover {
                background-color: rgba(255, 255, 255, 0.4);
            }
            
            .mensagem-sucesso {
                background-color: #2ecc71;
                color: white;
            }
            
            .mensagem-erro {
                background-color: #e74c3c;
                color: white;
            }
            
            .mensagem-alerta {
                background-color: #f39c12;
                color: white;
            }
            
            .mensagem-info {
                background-color: #1a5276;
                color: white;
            }
            
            @keyframes slidein {
                from {
                    transform: translateX(150%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(estilosMensagens);
    }
}

// Função para mostrar mensagens ao usuário
function mostrarMensagem(texto, tipo = 'info', duracao = 5000) {
    // Tipos disponíveis: 'sucesso', 'erro', 'alerta', 'info'
    const sistemaMensagens = document.getElementById('sistema-mensagens');
    if (!sistemaMensagens) {
        configurarSistemaMensagens();
    }
    
    // Criar elemento da mensagem
    const mensagem = document.createElement('div');
    mensagem.className = `mensagem mensagem-${tipo}`;
    
    // Criar conteúdo da mensagem
    const conteudo = document.createElement('div');
    conteudo.className = 'mensagem-conteudo';
    conteudo.textContent = texto;
    mensagem.appendChild(conteudo);
    
    // Botão fechar
    const btnFechar = document.createElement('div');
    btnFechar.className = 'mensagem-fechar';
    btnFechar.innerHTML = '&times;';
    btnFechar.onclick = () => fecharMensagem(mensagem);
    mensagem.appendChild(btnFechar);
    
    // Adicionar mensagem ao sistema
    document.getElementById('sistema-mensagens').appendChild(mensagem);
    
    // Auto fechar após a duração definida
    if (duracao > 0) {
        setTimeout(() => {
            fecharMensagem(mensagem);
        }, duracao);
    }
    
    return mensagem;
}

// Função para fechar a mensagem com animação
function fecharMensagem(mensagem) {
    mensagem.classList.add('saindo');
    setTimeout(() => {
        if (mensagem.parentNode) {
            mensagem.parentNode.removeChild(mensagem);
        }
    }, 300); // Duração da animação de saída
}

// Funções de atalho para cada tipo de mensagem
function mensagemSucesso(texto, duracao = 5000) {
    return mostrarMensagem(texto, 'sucesso', duracao);
}

function mensagemErro(texto, duracao = 7000) {
    return mostrarMensagem(texto, 'erro', duracao);
}

function mensagemAlerta(texto, duracao = 6000) {
    return mostrarMensagem(texto, 'alerta', duracao);
}

function mensagemInfo(texto, duracao = 5000) {
    return mostrarMensagem(texto, 'info', duracao);
}

function configurarCamposResponsaveis() {
    const resideSelect = document.getElementById('reside');
    if (!resideSelect) return;
    
    const segundoResponsavelContainer = document.createElement('div');
    segundoResponsavelContainer.id = 'segundo-responsavel-container';
    segundoResponsavelContainer.style.display = 'none'; 
        
    const headerDiv = document.createElement('div');
    headerDiv.className = 'form-group full';
    headerDiv.innerHTML = '<h3>Dados do Segundo Responsável</h3>';
    segundoResponsavelContainer.appendChild(headerDiv);
    
    const campos = [
        {
            className: 'form-group',
            html: `
                <label for="nome-responsavel-2">Nome Completo:</label>
                <input type="text" id="nome-responsavel-2" name="nome-responsavel-2" required>
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="parentesco-2">Parentesco:</label>
                <select id="parentesco-2" name="parentesco-2" required>
                    <option value="">Selecione</option>
                    <option value="pai" selected>Pai</option>
                    <option value="mae">Mãe</option>
                    <option value="avó">Avó</option>
                    <option value="avô">Avô</option>
                    <option value="tio">Tio</option>
                    <option value="tia">Tia</option>
                    <option value="outro">Outro</option>
                </select>
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="rg-responsavel-2">RG:</label>
                <input type="text" id="rg-responsavel-2" name="rg-responsavel-2" required>
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="cpf-responsavel-2">CPF:</label>
                <input type="text" id="cpf-responsavel-2" name="cpf-responsavel-2" required>
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="telefone-2">Telefone:</label>
                <input type="tel" id="telefone-2" name="telefone-2" required>
            `
        },
         {
            className: 'form-group',
            html: `
                <label for="profissao-responsavel-2">Profissão:</label>
                <input type="text" id="profissao-responsavel-2" name="profissao-responsavel-2" required>
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="whatsapp-2">Link rede social:</label>
                <input type="text" id="whatsapp-2" name="whatsapp-2">
            `
        },
        {
            className: 'form-group',
            html: `
                <label for="email-2">E-mail:</label>
                <input type="email" id="email-2" name="email-2" required>
            `
        }
    ];
    
    campos.forEach(campo => {
        const div = document.createElement('div');
        div.className = campo.className;
        div.innerHTML = campo.html;
        segundoResponsavelContainer.appendChild(div);
    });
    
    const enderecoHeader = Array.from(document.querySelectorAll('.form-group.full h3'))
        .find(el => el.textContent.includes('Endereço'))
        ?.closest('.form-group.full');
    
    if (enderecoHeader) {
        enderecoHeader.parentNode.insertBefore(segundoResponsavelContainer, enderecoHeader);
    } else {
        document.getElementById('matricula-form').appendChild(segundoResponsavelContainer);
    }
    
    const parentescoSelect = document.getElementById('parentesco');
    
    resideSelect.addEventListener('change', function() {
        if (this.value === 'mae-pai') {
            segundoResponsavelContainer.style.display = 'block';
            
            if (parentescoSelect) {
                parentescoSelect.value = 'mae';
            }
            
            const camposObrigatorios = segundoResponsavelContainer.querySelectorAll('[required]');
            camposObrigatorios.forEach(campo => {
                campo.setAttribute('required', 'required');
            });
        } else {
            segundoResponsavelContainer.style.display = 'none';
            
            const camposObrigatorios = segundoResponsavelContainer.querySelectorAll('[required]');
            camposObrigatorios.forEach(campo => {
                campo.removeAttribute('required');
            });
        }
    });
    
    const previewBtn = document.getElementById('preview-btn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Substituir alert por mensagem personalizada
            mensagemInfo('Funcionalidade de prévia em desenvolvimento');
        });
    }
}

function configurarInterface() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            tabContents.forEach(content => content.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    const styleElement = document.createElement('style');
    styleElement.textContent = `
  .units-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.unit-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    height: 100%; /* Garante que todos os cards tenham a mesma altura na linha */
}

.unit-card-header {
    background-color: #1a5276;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    color: white;
}

.unit-card-body {
    padding: 15px;
    flex-grow: 1; /* Permite que o corpo cresça para ocupar espaço disponível */
}

.unit-card-footer {
    padding: 15px;
    background-color: #f4f4f4;
    text-align: center;
    border-top: 1px solid #ddd;
    margin-top: auto; /* Empurra o footer para o final do card */
}

.unit-card-footer button {
    background-color: #1a5276;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    width: 80%;
    max-width: 200px;
}

.unit-card-footer button:hover {
    background-color: #2980b9;
}

/* Estilo para o segundo responsável */
#segundo-responsavel-container {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px dashed #ccc;
}

#segundo-responsavel-container h3 {
    color: #1a5276;
}

/* Estilos adicionais para garantir que as turmas sejam exibidas corretamente */
.turmas-container {
    margin-top: 10px;
}

/* Para melhorar a legibilidade em dispositivos móveis */
@media (max-width: 576px) {
    .units-grid {
        grid-template-columns: 1fr; /* Uma coluna em telas muito pequenas */
    }
    
    .unit-card-body {
        padding: 12px;
    }
}
    `;
    document.head.appendChild(styleElement);
}

function carregarDadosDinamicos() {
    let todasTurmas = [];

    function criarCardUnidade(unidade) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'unit-card';
        cardDiv.innerHTML = `
            <div class="unit-card-header">
                <h3>${unidade.nome}</h3>
            </div>
            <div class="unit-card-body">
                <p><strong>Endereço:</strong> ${unidade.endereco}</p>
                <p><strong>Telefone:</strong> ${unidade.telefone || 'Não informado'}</p>
                <p><strong>Coordenador:</strong> ${unidade.coordenador || 'Não informado'}</p>
                <p><strong>Turmas:</strong> ${obterTurmasDaUnidade(unidade.id)}</p>
            </div>
            <div class="unit-card-footer">
                <button onclick="matricularUnidade(${unidade.id})">Matricular nesta unidade</button>
            </div>
        `;
        return cardDiv;
    }

    function obterTurmasDaUnidade(unidadeId) {
        const turmasUnidade = todasTurmas.filter(turma => turma.id_unidade === unidadeId);
        const nomesTurmas = [...new Set(turmasUnidade.map(turma => 
            turma.nome_turma.replace(/^Sub\s*/, 'Sub-')
        ))];
        return turmasUnidade.length > 0 ? nomesTurmas.join(', ') : 'Nenhuma turma disponível';
    }

    function atualizarTurmasPorUnidade(unidadeId) {
        const selectTurma = document.getElementById('turma');
        if (!selectTurma) return;
        
        selectTurma.length = 1;
        
        if (!unidadeId) return;
        
        const turmasFiltradas = todasTurmas.filter(turma => turma.id_unidade == unidadeId);
        
        turmasFiltradas.forEach(turma => {
            const option = document.createElement('option');
            option.value = turma.id;
            option.textContent = turma.nome_turma;
            selectTurma.appendChild(option);
        });
        
        if (turmasFiltradas.length === 0) {
            const option = document.createElement('option');
            option.value = "";
            option.textContent = "Nenhuma turma disponível para esta unidade";
            option.disabled = true;
            selectTurma.appendChild(option);
        }
    }

    function carregarUnidades() {
        const containerUnidades = document.getElementById('unidades').querySelector('.units-grid');
        
        if (containerUnidades) {
            containerUnidades.innerHTML = '';

            fetch('listar_unidades.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(unidades => {
                unidades.forEach(unidade => {
                    const cardUnidade = criarCardUnidade(unidade);
                    containerUnidades.appendChild(cardUnidade);
                });
            })
            .catch(error => {
                console.error('Erro ao carregar unidades:', error);
                const errorDiv = document.createElement('div');
                errorDiv.textContent = 'Não foi possível carregar as unidades. Tente novamente mais tarde.';
                containerUnidades.appendChild(errorDiv);
                // Substituir alert por mensagem de erro
                mensagemErro('Não foi possível carregar as unidades. Tente novamente mais tarde.');
            });
        }
    }

    window.matricularUnidade = function(unidadeId) {
        const selectUnidade = document.getElementById('unidade');
        selectUnidade.value = unidadeId;
        
        atualizarTurmasPorUnidade(unidadeId);

        const matriculaTab = document.querySelector('.tab[data-tab="matricula"]');
        if (matriculaTab) {
            matriculaTab.click();
        }
    };

    fetch('listar_turmas.php')
    .then(response => response.json())
    .then(turmas => {
        todasTurmas = turmas;
        carregarUnidades();
    })
    .catch(error => {
        console.error('Erro ao buscar turmas:', error);
        // Substituir alert por mensagem de erro
        mensagemErro('Não foi possível carregar as turmas');
    });

    fetch('listar_unidades.php')
    .then(response => response.json())
    .then(unidades => {
        const selectUnidade = document.getElementById('unidade');
        
        selectUnidade.length = 1;
        
        unidades.forEach(unidade => {
            const option = document.createElement('option');
            option.value = unidade.id;
            option.textContent = unidade.nome;
            selectUnidade.appendChild(option);
        });
        
        selectUnidade.addEventListener('change', function() {
            const unidadeId = this.value;
            atualizarTurmasPorUnidade(unidadeId);
        });
    })
    .catch(error => {
        console.error('Erro ao buscar unidades:', error);
        // Substituir alert por mensagem de erro
        mensagemErro('Não foi possível carregar as unidades');
    });
}

function configurarEnvioFormulario() {
    const form = document.getElementById('matricula-form');
    if (!form) return;
    
    function validarFormulario() {
        let camposObrigatorios = form.querySelectorAll('[required]');
        let formularioValido = true;
        
        camposObrigatorios.forEach(campo => {
            if (!campo.value.trim()) {
                campo.style.borderColor = 'red';
                formularioValido = false;
            } else {
                campo.style.borderColor = '';
            }
        });
        
        return formularioValido;
    }
    
    form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (validarFormulario()) {
        const formData = new FormData();
        
        // Função helper para obter valor seguro
        function obterValor(id, valorPadrao = '') {
            const elemento = document.getElementById(id);
            return elemento ? (elemento.value || valorPadrao) : valorPadrao;
        }
        
        formData.append('unidade', obterValor('unidade'));
        formData.append('turma', obterValor('turma'));
        
        formData.append('nome-aluno', obterValor('nome-aluno'));
        formData.append('data-nascimento', obterValor('data-nascimento'));
        formData.append('genero', obterValor('genero'));
        formData.append('tipo-sangue', obterValor('tipo-sangue'));
        formData.append('atipica', obterValor('atipica'));
        
        // CORREÇÃO: Garantir que laudo-atipica sempre tenha um valor válido
        const atipicaValor = obterValor('atipica');
        let laudoAtipicaValor = obterValor('laudo-atipica');
        
        // Se não é atípica, força laudo como 'nao'
        if (atipicaValor === 'nao') {
            laudoAtipicaValor = 'nao';
        } else if (!laudoAtipicaValor) {
            laudoAtipicaValor = 'nao'; // valor padrão se vazio
        }
        formData.append('laudo-atipica', laudoAtipicaValor);
        
        formData.append('condicao-crianca', obterValor('condicao-crianca'));
        
        // CORREÇÃO: Condições médicas
        const condicaoValor = obterValor('condicao-crianca');
        const condicaoDetalhes = condicaoValor === 'sim' ? obterValor('condicao-detalhes') : '';
        formData.append('condicao-detalhes', condicaoDetalhes);
        
        formData.append('medicacao-crianca', obterValor('medicacao-crianca'));
        
        // CORREÇÃO: Medicação
        const medicacaoValor = obterValor('medicacao-crianca');
        const medicacaoDetalhes = medicacaoValor === 'sim' ? obterValor('medicacao-detalhes') : '';
        formData.append('medicacao-detalhes', medicacaoDetalhes);
        
        formData.append('cadastro_unico', obterValor('cadastro_unico'));
        
        // CORREÇÃO: Número do cadastro único
        const cadastroValor = obterValor('cadastro_unico');
        const numeroCadastro = cadastroValor === 'sim' ? obterValor('numero-cadunico') : '';
        formData.append('numero-cadunico', numeroCadastro);
        
        formData.append('rg-aluno', obterValor('rg-aluno'));
        formData.append('cpf-aluno', obterValor('cpf-aluno'));
        formData.append('concorrencia', obterValor('concorrencia'));
        formData.append('vaga-militar', obterValor('vaga-militar'));
        formData.append('escola', obterValor('escola'));
        formData.append('tipo-escola', obterValor('tipo-escola'));
        formData.append('endereco-escola', obterValor('endereco-escola'));
        formData.append('serie', obterValor('serie'));
        formData.append('info-saude', obterValor('info-saude'));
        formData.append('telefone-escola', obterValor('telefone-escola'));
        formData.append('nome-diretor', obterValor('nome-diretor'));

        // Arquivos
        const inputArquivoLaudo = document.getElementById('arquivo-laudo');
        if (inputArquivoLaudo && inputArquivoLaudo.files.length > 0) {
            formData.append('arquivo-laudo', inputArquivoLaudo.files[0]);
        }

        const inputAtestadoMedico = document.getElementById('atestado-medico');
        if (inputAtestadoMedico && inputAtestadoMedico.files.length > 0) {
            formData.append('atestado-medico', inputAtestadoMedico.files[0]);
        }
        
        const inputFoto = document.getElementById('foto-aluno');
        if (inputFoto && inputFoto.files.length > 0) {
            formData.append('foto-aluno', inputFoto.files[0]);
        }
        
        formData.append('nome-responsavel', obterValor('nome-responsavel'));
        formData.append('parentesco', obterValor('parentesco'));
        formData.append('rg-responsavel', obterValor('rg-responsavel'));
        formData.append('cpf-responsavel', obterValor('cpf-responsavel'));
        formData.append('telefone', obterValor('telefone'));
        formData.append('whatsapp', obterValor('whatsapp'));
        formData.append('email', obterValor('email'));
        formData.append('profissao-responsavel', obterValor('profissao-responsavel'));
        
        const segundoResponsavelContainer = document.getElementById('segundo-responsavel-container');
        if (segundoResponsavelContainer && segundoResponsavelContainer.style.display === 'block') {
            formData.append('tem_segundo_responsavel', '1');
            
            formData.append('nome-responsavel-2', obterValor('nome-responsavel-2'));
            formData.append('parentesco-2', obterValor('parentesco-2'));
            formData.append('rg-responsavel-2', obterValor('rg-responsavel-2'));
            formData.append('cpf-responsavel-2', obterValor('cpf-responsavel-2'));
            formData.append('telefone-2', obterValor('telefone-2'));
            formData.append('whatsapp-2', obterValor('whatsapp-2'));
            formData.append('email-2', obterValor('email-2'));
            formData.append('profissao-responsavel-2', obterValor('profissao-responsavel-2'));
        } else {
            formData.append('tem_segundo_responsavel', '0');
        }
        
        formData.append('cep', obterValor('cep'));
        formData.append('endereco', obterValor('endereco'));
        formData.append('numero', obterValor('numero'));
        formData.append('complemento', obterValor('complemento'));
        formData.append('bairro', obterValor('bairro'));
        formData.append('cidade', obterValor('cidade'));

        formData.append('tamanho-camisa', obterValor('tamanho-camisa'));
        formData.append('tamanho-calca', obterValor('tamanho-calca'));
        formData.append('tamanho-calcado', obterValor('tamanho-calcado'));
        
        formData.append('consent', document.getElementById('consent').checked ? '1' : '0');
        
        // DEBUG: Mostrar valores dos campos problemáticos
        console.log('=== DEBUG CAMPOS ENUM ===');
        console.log('atipica:', obterValor('atipica'));
        console.log('laudo-atipica:', laudoAtipicaValor);
        console.log('condicao-crianca:', obterValor('condicao-crianca'));
        console.log('medicacao-crianca:', obterValor('medicacao-crianca'));
        console.log('cadastro_unico:', obterValor('cadastro_unico'));
        console.log('concorrencia:', obterValor('concorrencia'));
        console.log('vaga-militar:', obterValor('vaga-militar'));
        console.log('tipo-escola:', obterValor('tipo-escola'));
        console.log('========================');
        
        // Mostrar mensagem de carregamento
        const mensagemCarregando = mensagemInfo('Processando matrícula, aguarde...', 0);
        
        fetch('processar_matricula.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Fechar mensagem de carregamento
            fecharMensagem(mensagemCarregando);
            
            if (data.success) {
                mensagemSucesso('Matrícula realizada com sucesso! Número: ' + data.matricula);
                enviarEmail(data.matricula, data.email);
                console.log(data);
                
                form.reset();
                
                if (segundoResponsavelContainer) {
                    segundoResponsavelContainer.style.display = 'none';
                }
            } else {
                mensagemErro('Erro ao processar matrícula: ' + data.message);
                // Mostrar debug se houver erro
                if (data.debug) {
                    console.log('Debug do servidor:', data.debug);
                }
            }
        })
        .catch(error => {
            fecharMensagem(mensagemCarregando);
            console.error('Erro:', error);
            mensagemErro('Erro ao enviar formulário: ' + error.message);
        });
    } else {
        mensagemAlerta('Por favor, preencha todos os campos obrigatórios.');
    }
});
}

function enviarEmail(matricula, email){
    const dados = {
        matricula: matricula,
        email: email
    };
    fetch("./enviar_email.php", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
          },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(resultado => {
        //console.log('Email enviado');

    })
    .catch(erro => {
        //console.log('Erro: ', erro);
    });

}

function aplicarMascaras() {
    function aplicarMascara(elemento, mascara) {
        if (!elemento) return;
        
        elemento.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            let novoValor = '';
            let indice = 0;
            
            for (let i = 0; i < mascara.length && indice < valor.length; i++) {
                if (mascara[i] === '#') {
                    novoValor += valor[indice];
                    indice++;
                } else {
                    novoValor += mascara[i];
                    if (valor[indice] === mascara[i]) {
                        indice++;
                    }
                }
            }
            
            e.target.value = novoValor;
        });
    }
    
    const camposCpf = [
        document.getElementById('cpf-aluno'),
        document.getElementById('cpf-responsavel'),
        document.getElementById('cpf-responsavel-2')
    ];
    
    camposCpf.forEach(campo => {
        if (campo) {
            aplicarMascara(campo, '###.###.###-##');
        }
    });
    
    const camposTelefone = [
        document.getElementById('telefone'),
        document.getElementById('telefone-2'),
        document.getElementById('telefone-escola'),
       
    ];
    
    camposTelefone.forEach(campo => {
        if (campo) {
            aplicarMascara(campo, '(##) #####-####');
        }
    });
    
    const campoCep = document.getElementById('cep');
    if (campoCep) {
        aplicarMascara(campoCep, '#####-###');
        
        campoCep.addEventListener('blur', buscarEnderecoPorCep);
    }
}

let ultimoCepConsultado = '';

function buscarEnderecoPorCep() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    
    
    if (cep === ultimoCepConsultado) return;
    
    
    if (cep.length !== 8) {
        
        const endereco = document.getElementById('endereco');
        const bairro = document.getElementById('bairro');
        const cidade = document.getElementById('cidade');
        
        if (endereco.value || bairro.value || cidade.value) {
        endereco.value = '';
        bairro.value = '';
        cidade.value = '';
        }
        return;
    }
    
    ultimoCepConsultado = cep;
    
    const endereco = document.getElementById('endereco');
    const bairro = document.getElementById('bairro');
    const cidade = document.getElementById('cidade');
    
    endereco.value = 'Carregando...';
    bairro.value = 'Carregando...';
    
    const url = `https://viacep.com.br/ws/${cep}/json/`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
        if (data.erro) {
            // Substituir alert por mensagem de erro
            mensagemErro('CEP não encontrado');
            endereco.value = '';
            bairro.value = '';
            cidade.value = '';
            return;
        }
        
        endereco.value = data.logradouro;
        bairro.value = data.bairro;
        cidade.value = data.localidade;
        document.getElementById('numero').focus();
        })
        .catch(error => {
        console.error('Erro ao buscar CEP:', error);
        endereco.value = '';
        bairro.value = '';
        cidade.value = '';
        
        // Substituir alert por mensagem de erro
        mensagemErro('Erro ao buscar o CEP. Tente novamente.');
        });
}

function configurarCamposNumericos() {
    // Lista de campos que devem aceitar apenas números
    const camposNumericos = [
        'cpf-aluno',
        'rg-aluno',
        'cpf-responsavel',
        'rg-responsavel',
        'telefone',
        'cep',
        'numero',
        // Campos do segundo responsável
        'cpf-responsavel-2',
        'rg-responsavel-2',
        'telefone-2',
    
    ];
    
    // Para cada campo numérico, aplicar a validação de entrada
    camposNumericos.forEach(function(id) {
        const campo = document.getElementById(id);
        if (campo) {
            // Impede entrada de caracteres não numéricos ao digitar
            campo.addEventListener('keypress', function(e) {
                // Permite apenas dígitos 0-9
                if (e.key < '0' || e.key > '9') {
                    e.preventDefault();
                }
                
                // Limita o RG a 14 dígitos
                if ((id === 'rg-aluno' || id === 'rg-responsavel' || id === 'rg-responsavel-2') && 
                    this.value.replace(/\D/g, '').length >= 14 && 
                    e.key >= '0' && e.key <= '9') {
                    e.preventDefault();
                }
            });
            
            // Remove caracteres não numéricos ao colar texto
            campo.addEventListener('paste', function(e) {
                // Permite colar apenas com setTimeout para garantir que o valor será capturado após a colagem
                setTimeout(() => {
                    let valor = this.value.replace(/\D/g, '');
                    
                    // Limita o RG a 14 dígitos
                    if ((id === 'rg-aluno' || id === 'rg-responsavel' || id === 'rg-responsavel-2') && 
                        valor.length > 14) {
                        valor = valor.substring(0, 14);
                    }
                    
                    // Se o campo tem uma máscara, deixe a função aplicarMascara lidar com isso
                    if (id.includes('cpf') || id.includes('telefone') || id.includes('whatsapp') || id === 'cep') {
                        this.value = valor;
                        // Dispara um evento de input para permitir que a máscara seja aplicada
                        this.dispatchEvent(new Event('input'));
                    } else {
                        this.value = valor;
                    }
                }, 0);
            });
            
            // Limpa caracteres não numéricos ao perder o foco
            campo.addEventListener('blur', function() {
                let valor = this.value.replace(/\D/g, '');
                
                // Limita o RG a 14 dígitos
                if ((id === 'rg-aluno' || id === 'rg-responsavel' || id === 'rg-responsavel-2') && 
                    valor.length > 14) {
                    valor = valor.substring(0, 14);
                    this.value = valor;
                }
            });
        }
    });

}