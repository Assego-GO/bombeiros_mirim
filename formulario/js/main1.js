document.addEventListener('DOMContentLoaded', function() {
    // Configurar sistema de mensagens
    configurarSistemaMensagens();
    
    configurarCamposResponsaveis();
    configurarEnvioFormulario();
    configurarInterface();
    carregarDadosDinamicos();
    aplicarMascaras();
    configurarCamposNumericos();
});

// Sistema de mensagens personalizadas
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
    
    // Primeiro, encontramos a seção do primeiro responsável
    const primeiroResponsavelHeader = Array.from(document.querySelectorAll('.form-group.full h3'))
        .find(el => el.textContent.includes('Dados do Responsável'))
        ?.closest('.form-group.full');
    
    if (!primeiroResponsavelHeader) return;
    
    // Encontrar todos os elementos do primeiro responsável até o próximo cabeçalho
    const elementosResponsavel = [];
    let elemento = primeiroResponsavelHeader.nextElementSibling;
    
    // Coletar todos os elementos até o próximo cabeçalho principal (que deve ser Endereço)
    while (elemento && 
           !(elemento.classList.contains('form-group') && 
             elemento.classList.contains('full') && 
             elemento.querySelector('h3') && 
             elemento.querySelector('h3').textContent.includes('Endereço'))) {
        elementosResponsavel.push(elemento);
        elemento = elemento.nextElementSibling;
    }
    
    // Criar container para o segundo responsável
    const segundoResponsavelContainer = document.createElement('div');
    segundoResponsavelContainer.id = 'segundo-responsavel-container';
    segundoResponsavelContainer.style.display = 'none';
    
    // Criar cabeçalho para o segundo responsável (igual ao do primeiro)
    const headerDiv = document.createElement('div');
    headerDiv.className = 'form-group full';
    headerDiv.innerHTML = '<h3>Dados do Segundo Responsável</h3>';
    segundoResponsavelContainer.appendChild(headerDiv);
    
    // Clonar a estrutura do primeiro responsável para o segundo
    elementosResponsavel.forEach(elementoOriginal => {
        const elementoClonado = elementoOriginal.cloneNode(true);
        
        // Atualizar IDs e atributos dos inputs clonados
        const inputs = elementoClonado.querySelectorAll('input, select');
        inputs.forEach(input => {
            const idOriginal = input.id;
            const novoId = idOriginal + '-2';
            
            // Atualizar id e name do input
            input.id = novoId;
            if (input.name) input.name = novoId;
            
            // Limpar qualquer valor que possa ter sido clonado
            input.value = '';
            
            // Atualizar o atributo "for" do label correspondente
            const label = elementoClonado.querySelector(`label[for="${idOriginal}"]`);
            if (label) {
                label.setAttribute('for', novoId);
            }
        });
        
        segundoResponsavelContainer.appendChild(elementoClonado);
    });
    
    // Inserir o container do segundo responsável antes do cabeçalho de Endereço
    const enderecoHeader = Array.from(document.querySelectorAll('.form-group.full h3'))
        .find(el => el.textContent.includes('Endereço'))
        ?.closest('.form-group.full');
    
    if (enderecoHeader) {
        enderecoHeader.parentNode.insertBefore(segundoResponsavelContainer, enderecoHeader);
    } else {
        document.getElementById('matricula-form').appendChild(segundoResponsavelContainer);
    }
    
    // Adicionar comportamento ao select de "Mora com"
    resideSelect.addEventListener('change', function() {
        if (this.value === 'mae-pai') {
            segundoResponsavelContainer.style.display = 'block';
            
            // Se escolheu "Mãe e Pai", definir primeiro responsável como Mãe por padrão
            const parentescoSelect = document.getElementById('parentesco');
            if (parentescoSelect) {
                parentescoSelect.value = 'mae';
            }
            
            // Definir segundo responsável como Pai por padrão
            const parentesco2Select = document.getElementById('parentesco-2');
            if (parentesco2Select) {
                parentesco2Select.value = 'pai';
            }
            
            // Habilitar campos obrigatórios do segundo responsável
            const camposObrigatorios = segundoResponsavelContainer.querySelectorAll('[required]');
            camposObrigatorios.forEach(campo => {
                campo.setAttribute('required', 'required');
            });
        } else {
            segundoResponsavelContainer.style.display = 'none';
            
            // Desabilitar validação dos campos do segundo responsável
            const camposObrigatorios = segundoResponsavelContainer.querySelectorAll('[required]');
            camposObrigatorios.forEach(campo => {
                campo.removeAttribute('required');
                campo.value = ''; // Limpar os valores
            });
        }
    });
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

            fetch('/luis/superacao/formulario/listar_unidades.php')
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

    fetch('/luis/superacao/formulario/listar_turmas.php')
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

    fetch('/luis/superacao/formulario/listar_unidades.php')
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
            
            formData.append('unidade', document.getElementById('unidade').value);
            formData.append('turma', document.getElementById('turma').value);
            
            formData.append('nome-aluno', document.getElementById('nome-aluno').value);
            formData.append('data-nascimento', document.getElementById('data-nascimento').value);
            formData.append('rg-aluno', document.getElementById('rg-aluno').value);
            formData.append('cpf-aluno', document.getElementById('cpf-aluno').value);
            formData.append('escola', document.getElementById('escola').value);
            formData.append('serie', document.getElementById('serie').value);
            formData.append('info-saude', document.getElementById('info-saude').value);
            formData.append('reside', document.getElementById('reside').value);
            const inputFoto = document.getElementById('foto-aluno');
            if (inputFoto && inputFoto.files.length > 0) {
                formData.append('foto-aluno', inputFoto.files[0]);
            }
            
            formData.append('nome-responsavel', document.getElementById('nome-responsavel').value);
            formData.append('parentesco', document.getElementById('parentesco').value);
            formData.append('rg-responsavel', document.getElementById('rg-responsavel').value);
            formData.append('cpf-responsavel', document.getElementById('cpf-responsavel').value);
            formData.append('telefone', document.getElementById('telefone').value);
            formData.append('whatsapp', document.getElementById('whatsapp').value || '');
            formData.append('email', document.getElementById('email').value);
            
            const segundoResponsavelContainer = document.getElementById('segundo-responsavel-container');
            if (segundoResponsavelContainer && segundoResponsavelContainer.style.display === 'block') {
                formData.append('tem_segundo_responsavel', '1');
                
                formData.append('nome-responsavel-2', document.getElementById('nome-responsavel-2').value);
                formData.append('parentesco-2', document.getElementById('parentesco-2').value);
                formData.append('rg-responsavel-2', document.getElementById('rg-responsavel-2').value);
                formData.append('cpf-responsavel-2', document.getElementById('cpf-responsavel-2').value);
                formData.append('telefone-2', document.getElementById('telefone-2').value);
                formData.append('whatsapp-2', document.getElementById('whatsapp-2').value || '');
                formData.append('email-2', document.getElementById('email-2').value);
            } else {
                formData.append('tem_segundo_responsavel', '0');
            }
            
            formData.append('cep', document.getElementById('cep').value);
            formData.append('endereco', document.getElementById('endereco').value);
            formData.append('numero', document.getElementById('numero').value);
            formData.append('complemento', document.getElementById('complemento').value || '');
            formData.append('bairro', document.getElementById('bairro').value);
            formData.append('cidade', document.getElementById('cidade').value);
            
            formData.append('consent', document.getElementById('consent').checked ? '1' : '0');
            
            //console.log("Arquivo selecionado:", inputFoto.files[0]);
            //console.log("--- Conteúdo do FormData ---");
            
            
            // Mostrar mensagem de carregamento
            const mensagemCarregando = mensagemInfo('Processando matrícula, aguarde...', 0);
            
            fetch('/luis/superacao/formulario/processar_matricula.php', {
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
                    //enviarEmail(data.matricula, data.email);
                    //console.log(data);
                    
                    form.reset();
                    
                    if (segundoResponsavelContainer) {
                        segundoResponsavelContainer.style.display = 'none';
                    }
                } else {
                    
                    mensagemErro('Erro ao processar matrícula: ', data);
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
        document.getElementById('whatsapp'),
        document.getElementById('telefone-2'),
        document.getElementById('whatsapp-2')
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
    
    // Verifica se é o mesmo CEP da última consulta para evitar requisições repetidas
    if (cep === ultimoCepConsultado) return;
    
    // Verifica se o CEP tem 8 dígitos
    if (cep.length !== 8) {
        // Limpa os campos apenas se houver algum valor preenchido anteriormente
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
        'whatsapp',
        'cep',
        'numero',
        // Campos do segundo responsável
        'cpf-responsavel-2',
        'rg-responsavel-2',
        'telefone-2',
        'whatsapp-2'
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