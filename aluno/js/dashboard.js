document.addEventListener("DOMContentLoaded", function() {
    // Modal para matrícula
    const matriculaModal = document.getElementById('gerenciaModal');
    const closeMatriculaModal = document.getElementById('closeModal');
    const openMatriculaModal = document.getElementById('card-matricula');
    
    // Modal para perfil
    const perfilModal = document.getElementById('perfilModal');
    const closePerfilModal = document.getElementById('closePerfilModal');
    const openPerfilModal = document.getElementById('card-perfil');
    
    // Card de avaliações (pegando o terceiro card)
    const cardAvaliacoes = document.querySelector('.dashboard-card:nth-child(3)');
    
    // Evento para abrir o modal de matrícula
    if (openMatriculaModal) {
        openMatriculaModal.addEventListener("click", function(){
            buscarMatricula();
            matriculaModal.style.display = "block";
        });
    }
    
    // Evento para fechar o modal de matrícula
    if (closeMatriculaModal) {
        closeMatriculaModal.addEventListener("click", function(){
            matriculaModal.style.display = "none";
        });
    }
    
    // Evento para abrir o modal de perfil
    if (openPerfilModal) {
        openPerfilModal.addEventListener("click", function(){
            buscarPerfil();
            perfilModal.style.display = "block";
        });
    }
    
    // Evento para fechar o modal de perfil
    if (closePerfilModal) {
        closePerfilModal.addEventListener("click", function(){
            perfilModal.style.display = "none";
        });
    }
    
    // Evento para o botão de avaliações
    if (cardAvaliacoes) {
        cardAvaliacoes.addEventListener("click", function() {
            // Usar método POST para verificar avaliações
            fetch("./api/verificar_avaliacoes.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
                // Não precisa enviar o ID do aluno, pois ele já está na sessão
            })
            .then(resposta => resposta.json())
            .then(dados => {
                if (dados.success) {
                    if (dados.tem_avaliacoes) {
                        // Redirecionar para a página de visualização de avaliações
                        window.location.href = "minhas_avaliacoes.php";
                    } else {
                        // Mostrar alerta informando que não há avaliações
                        alert("Você ainda não possui avaliações registradas pelos professores.");
                    }
                } else {
                    console.error("Erro ao verificar avaliações:", dados.message);
                    alert("Não foi possível verificar suas avaliações. " + dados.message);
                }
            })
            .catch(error => {
                console.error("Erro na requisição:", error);
                alert("Erro ao conectar com o servidor. Por favor, tente novamente mais tarde.");
            });
        });
    }
    
    // Fechar modais quando clicar fora deles
    window.addEventListener("click", function(event) {
        if (event.target == matriculaModal) {
            matriculaModal.style.display = "none";
        }
        if (perfilModal && event.target == perfilModal) {
            perfilModal.style.display = "none";
        }
    });
    
    // Função para buscar dados da matrícula
    function buscarMatricula(){
        fetch("./api/buscar_matricula.php")
        .then(resposta => resposta.json())
        .then(dados => {
            if(dados.success){
                const info = dados.dados;
                document.getElementById('m-nome-aluno').textContent = info.nome;
                document.getElementById('m-matricula-aluno').textContent = info.numero_matricula;
                document.getElementById('m-data-matricula').textContent = info.data_matricula;
                document.getElementById('m-status-matricula').textContent = info.status;
                document.getElementById('m-unidade').textContent = info.nome_unidade;
                document.getElementById('m-unidade-endereco').textContent = info.endereco_unidade;
                document.getElementById('m-unidade-telefone').textContent = info.telefone_unidade;
                document.getElementById('m-unidade-coordenador').textContent = info.coordenador;
                document.getElementById('m-turma').textContent = info.nome_turma;
                
                // Adicionar classe de status
                const statusElement = document.getElementById('m-status-matricula');
                statusElement.className = ''; // Limpa classes anteriores
                statusElement.classList.add('status-' + info.status.toLowerCase());
            }
            else{
                console.error("Erro ao buscar matrícula:", dados.message);
                // Você pode exibir uma mensagem para o usuário
                alert("Não foi possível encontrar informações sobre sua matrícula. " + dados.message);
            }
        })
        .catch(error => {
            console.error("Erro na requisição:", error);
            alert("Erro ao conectar com o servidor. Por favor, tente novamente mais tarde.");
        });
    }
    
    // Função para buscar dados do perfil do aluno
    function buscarPerfil(){
        fetch("./api/buscar_perfil.php")
        .then(resposta => resposta.json())
        .then(dados => {
            if(dados.success){
                const aluno = dados.aluno;
                const endereco = dados.endereco;
                const responsaveis = dados.responsaveis;
                
                // Ajustar o caminho da foto para apontar corretamente
                let fotoPath = aluno.foto ? aluno.foto : '../uploads/fotos/sem_foto.png';
                
                // Preencher dados do aluno
                document.getElementById('p-foto').src = fotoPath;
                document.getElementById('preview-foto').src = fotoPath;
                document.getElementById('p-nome').textContent = aluno.nome;
                document.getElementById('p-data-nascimento').textContent = formatarData(aluno.data_nascimento);
                document.getElementById('p-rg').textContent = aluno.rg || 'Não informado';
                document.getElementById('p-cpf').textContent = aluno.cpf || 'Não informado';
                document.getElementById('p-escola').textContent = aluno.escola;
                document.getElementById('p-serie').textContent = aluno.serie;
                document.getElementById('p-matricula').textContent = aluno.numero_matricula;
                document.getElementById('p-info-saude').textContent = aluno.info_saude || 'Nenhuma informação cadastrada';
                
                // Armazenar ID do aluno para edição
                document.getElementById('aluno-id').value = aluno.id;
                
                // Preencher formulário de edição
                document.getElementById('edit-nome').value = aluno.nome;
                document.getElementById('edit-data-nascimento').value = aluno.data_nascimento;
                document.getElementById('edit-rg').value = aluno.rg || '';
                document.getElementById('edit-cpf').value = aluno.cpf || '';
                document.getElementById('edit-escola').value = aluno.escola;
                document.getElementById('edit-serie').value = aluno.serie;
                document.getElementById('edit-info-saude').value = aluno.info_saude || '';
                
                // Preencher dados de endereço
                if (endereco) {
                    document.getElementById('p-endereco').textContent = 
                        `${endereco.logradouro}, ${endereco.numero}${endereco.complemento ? ', ' + endereco.complemento : ''} - ${endereco.bairro}, ${endereco.cidade} - CEP: ${endereco.cep}`;
                    
                    // Preencher formulário de edição de endereço
                    document.getElementById('edit-cep').value = endereco.cep;
                    document.getElementById('edit-logradouro').value = endereco.logradouro;
                    document.getElementById('edit-numero').value = endereco.numero;
                    document.getElementById('edit-complemento').value = endereco.complemento || '';
                    document.getElementById('edit-bairro').value = endereco.bairro;
                    document.getElementById('edit-cidade').value = endereco.cidade;
                } else {
                    document.getElementById('p-endereco').textContent = 'Endereço não cadastrado';
                }
                
                // Preencher dados dos responsáveis - Seção de Visualização
                const responsaveisContainer = document.getElementById('p-responsaveis-container');
                responsaveisContainer.innerHTML = '';
                
                if (responsaveis && responsaveis.length > 0) {
                    responsaveis.forEach((resp, index) => {
                        const respDiv = document.createElement('div');
                        respDiv.className = 'responsavel-item';
                        respDiv.innerHTML = `
                            <h4>${resp.nome} (${resp.parentesco})</h4>
                            <p><strong>Contatos:</strong> ${resp.telefone} / ${resp.whatsapp}</p>
                            <p><strong>E-mail:</strong> ${resp.email}</p>
                            <p><strong>Documentos:</strong> RG: ${resp.rg} | CPF: ${resp.cpf}</p>
                        `;
                        responsaveisContainer.appendChild(respDiv);
                    });
                } else {
                    responsaveisContainer.innerHTML = '<p class="text-warning">Nenhum responsável cadastrado.</p>';
                }
                
                // Preencher formulário de edição de responsáveis
                const responsaveisForm = document.getElementById('responsaveis-form-container');
                responsaveisForm.innerHTML = '';
                
                if (responsaveis && responsaveis.length > 0) {
                    responsaveis.forEach((resp, index) => {
                        const respFormHtml = `
                            <div class="responsavel-form-item">
                                <h4>Responsável ${index + 1}</h4>
                                <input type="hidden" name="responsavel_id[]" value="${resp.id}">
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-nome" class="form-label">Nome:</label>
                                            <input type="text" id="resp-${index}-nome" name="responsavel_nome[]" class="form-control" value="${resp.nome}">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-parentesco" class="form-label">Parentesco:</label>
                                            <input type="text" id="resp-${index}-parentesco" name="responsavel_parentesco[]" class="form-control" value="${resp.parentesco}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-rg" class="form-label">RG:</label>
                                            <input type="text" id="resp-${index}-rg" name="responsavel_rg[]" class="form-control" value="${resp.rg}">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-cpf" class="form-label">CPF:</label>
                                            <input type="text" id="resp-${index}-cpf" name="responsavel_cpf[]" class="form-control" value="${resp.cpf}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-telefone" class="form-label">Telefone(Whatsapp):</label>
                                            <input type="text" id="resp-${index}-telefone" name="responsavel_telefone[]" class="form-control" value="${resp.telefone}">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label for="resp-${index}-whatsapp" class="form-label">Rede Social:</label>
                                            <input type="text" id="resp-${index}-whatsapp" name="responsavel_whatsapp[]" class="form-control" value="${resp.whatsapp || ''}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="resp-${index}-email" class="form-label">E-mail:</label>
                                    <input type="email" id="resp-${index}-email" name="responsavel_email[]" class="form-control" value="${resp.email}">
                                </div>
                            </div>
                        `;
                        
                        responsaveisForm.innerHTML += respFormHtml;
                    });
                } else {
                    responsaveisForm.innerHTML = '<p class="text-warning">Nenhum responsável cadastrado.</p>';
                }
                
                // Mostrar seção de visualização, esconder edição
                document.getElementById('visualizar-perfil').style.display = 'block';
                document.getElementById('editar-perfil').style.display = 'none';
            }
            else{
                console.error("Erro ao buscar perfil:", dados.message);
                alert("Não foi possível encontrar informações sobre seu perfil. " + dados.message);
            }
        })
        .catch(error => {
            console.error("Erro na requisição:", error);
            alert("Erro ao conectar com o servidor. Por favor, tente novamente mais tarde.");
        });
    }
    
    // Função para alternar para modo de edição
    const btnEditarPerfil = document.getElementById('btn-editar-perfil');
    if (btnEditarPerfil) {
        btnEditarPerfil.addEventListener('click', function() {
            document.getElementById('visualizar-perfil').style.display = 'none';
            document.getElementById('editar-perfil').style.display = 'block';
        });
    }
    
    // Função para cancelar edição
    const btnCancelarEdicao = document.getElementById('btn-cancelar-edicao');
    if (btnCancelarEdicao) {
        btnCancelarEdicao.addEventListener('click', function() {
            document.getElementById('visualizar-perfil').style.display = 'block';
            document.getElementById('editar-perfil').style.display = 'none';
        });
    }
    
    // Função para submeter o formulário de edição
    const formEditarPerfil = document.getElementById('form-editar-perfil');
    if (formEditarPerfil) {
        formEditarPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('./api/atualizar_perfil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensagem de sucesso
                    const mensagemDiv = document.getElementById('mensagem-resultado');
                    mensagemDiv.innerHTML = '<div class="alert alert-success">Perfil atualizado com sucesso!</div>';
                    
                    // Após 3 segundos, recarregar os dados do perfil
                    setTimeout(function() {
                        buscarPerfil();
                        // Voltar para a visualização
                        document.getElementById('visualizar-perfil').style.display = 'block';
                        document.getElementById('editar-perfil').style.display = 'none';
                        mensagemDiv.innerHTML = '';
                    }, 3000);
                } else {
                    // Mostrar mensagem de erro
                    document.getElementById('mensagem-resultado').innerHTML = 
                        '<div class="alert alert-danger">Erro ao atualizar perfil: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('mensagem-resultado').innerHTML = 
                    '<div class="alert alert-danger">Ocorreu um erro ao tentar atualizar o perfil.</div>';
            });
        });
    }
    
    // Função para formatar data
    function formatarData(dataStr) {
        if (!dataStr) return 'Não informada';
        
        const data = new Date(dataStr);
        return data.toLocaleDateString('pt-BR');
    }
    
    // Pré-visualização da foto
    const inputFoto = document.getElementById('foto');
    const previewFoto = document.getElementById('preview-foto');
    
    if (inputFoto && previewFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewFoto.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Busca CEP para formulário de edição
    const inputCep = document.getElementById('edit-cep');
    if (inputCep) {
        inputCep.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('edit-logradouro').value = data.logradouro;
                        document.getElementById('edit-bairro').value = data.bairro;
                        document.getElementById('edit-cidade').value = data.localidade;
                    }
                })
                .catch(error => console.error('Erro na consulta do CEP:', error));
            }
        });
    }
});