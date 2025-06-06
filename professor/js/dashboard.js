document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS loading...");
    
    // Modal Elements
    const turmasModal = document.getElementById('turmasModal');
    const perfilModal = document.getElementById('perfilModal');
    const alunosModal = document.getElementById('alunosModal');
    
    // Open/Close buttons
    const cardTurmas = document.getElementById('card-turmas');
    const cardPerfil = document.getElementById('card-perfil');
    const closeTurmasModal = document.getElementById('closeModal');
    const closePerfilModal = document.getElementById('closePerfilModal');
    const closeAlunosModal = document.getElementById('closeAlunosModal');
    
    // Profile sections
    const visualizarPerfil = document.getElementById('visualizar-perfil');
    const editarPerfil = document.getElementById('editar-perfil');
    const btnEditarPerfil = document.getElementById('btn-editar-perfil');
    const btnCancelarEdicao = document.getElementById('btn-cancelar-edicao');
    
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
        }
    }
    
    // CORRIGIDO: Load turma students function
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
            turmaNome = turmaItem.querySelector('h3').textContent;
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
    
    // Card de Avaliações - Direcionar para a página de avaliações
   /* const cardAvaliacoes = document.querySelector('.dashboard-card:nth-child(2)');
    if (cardAvaliacoes) {
        cardAvaliacoes.addEventListener('click', function() {
            // Primeira turma do professor
            fetch('api/get_primeira_turma.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.turma_id) {
                        window.location.href = 'avaliar_aluno.php?turma_id=' + data.turma_id;
                    } else {
                        alert('Você não possui turmas atribuídas ainda.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao carregar turmas. Por favor, tente novamente.');
                });
        });
    }*/
    
    // Toggle modals
    if (cardTurmas) {
        cardTurmas.addEventListener('click', function() {
            turmasModal.style.display = 'block';
        });
    }
    
    if (cardPerfil) {
        cardPerfil.addEventListener('click', function() {
            perfilModal.style.display = 'block';
        });
    }
    
    // Close modals
    if (closeTurmasModal) {
        closeTurmasModal.addEventListener('click', function() {
            turmasModal.style.display = 'none';
        });
    }
    
    if (closePerfilModal) {
        closePerfilModal.addEventListener('click', function() {
            perfilModal.style.display = 'none';
        });
    }
    
    if (closeAlunosModal) {
        closeAlunosModal.addEventListener('click', function() {
            alunosModal.style.display = 'none';
        });
    }
    
    // Close all modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === turmasModal) {
            turmasModal.style.display = 'none';
        }
        if (event.target === perfilModal) {
            perfilModal.style.display = 'none';
        }
        if (event.target === alunosModal) {
            alunosModal.style.display = 'none';
        }
    });
    
    // Toggle profile edit mode
    if (btnEditarPerfil) {
        btnEditarPerfil.addEventListener('click', function() {
            visualizarPerfil.style.display = 'none';
            editarPerfil.style.display = 'block';
        });
    }
    
    if (btnCancelarEdicao) {
        btnCancelarEdicao.addEventListener('click', function() {
            editarPerfil.style.display = 'none';
            visualizarPerfil.style.display = 'block';
        });
    }

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
    
    // Handle profile image preview
    const inputFoto = document.getElementById('foto');
    const previewFoto = document.getElementById('preview-foto');
    const previewFotoPlaceholder = document.getElementById('preview-foto-placeholder');
    
    if (inputFoto && previewFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewFotoPlaceholder) {
                        previewFotoPlaceholder.style.display = 'none';
                    }
                    previewFoto.style.display = 'block';
                    previewFoto.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Handle profile form submission with AJAX
    const formEditarPerfil = document.getElementById('form-editar-perfil');
    if (formEditarPerfil) {
        formEditarPerfil.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            
            // Validate form data
            const nome = formData.get('nome');
            const senha = formData.get('senha');
            const confirmaSenha = formData.get('confirma_senha');
            
            if (!nome || nome.trim() === '') {
                showMessage('mensagem-resultado', 'O nome é obrigatório.', 'danger');
                return;
            }
            
            if (senha && senha !== confirmaSenha) {
                showMessage('mensagem-resultado', 'As senhas não coincidem.', 'danger');
                return;
            }
            
            // Submit form data with AJAX
            fetch('api/atualizar_professor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('mensagem-resultado', data.message || 'Perfil atualizado com sucesso!', 'success');
                    
                    // Update displayed information
                    setTimeout(function() {
                        editarPerfil.style.display = 'none';
                        visualizarPerfil.style.display = 'block';
                        
                        // Update profile info in the view
                        if (data.professor) {
                            const p = data.professor;
                            document.querySelector('#visualizar-perfil .data-item:nth-child(1) span').textContent = p.nome;
                            
                            if (document.querySelector('#visualizar-perfil .data-item:nth-child(2) span')) {
                                document.querySelector('#visualizar-perfil .data-item:nth-child(2) span').textContent = p.email;
                            }
                            
                            if (document.querySelector('#visualizar-perfil .data-item:nth-child(3) span')) {
                                document.querySelector('#visualizar-perfil .data-item:nth-child(3) span').textContent = p.telefone;
                            }
                            
                            // Update header info
                            document.querySelector('.user-details h3').textContent = p.nome;
                            
                            // CORREÇÃO: Update photo if provided - usar caminhos relativos
                            if (p.foto) {
                                const filename = p.foto.split('/').pop();
                                const fotoPath = `../uploads/fotos/${filename}`;
                                
                                document.getElementById('p-foto').src = fotoPath;
                                if (document.querySelector('.user-avatar img')) {
                                    document.querySelector('.user-avatar img').src = fotoPath;
                                } else {
                                    // If there was no img before, create one and replace the icon
                                    const userAvatar = document.querySelector('.user-avatar');
                                    userAvatar.innerHTML = '';
                                    const img = document.createElement('img');
                                    img.src = fotoPath;
                                    img.alt = 'Foto do usuário';
                                    userAvatar.appendChild(img);
                                }
                            }
                        }
                    }, 2000);
                } else {
                    showMessage('mensagem-resultado', data.message || 'Erro ao atualizar o perfil.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('mensagem-resultado', 'Erro de conexão. Tente novamente.', 'danger');
            });
        });
    }
    
    // Handle search functionality for alunos list
    const searchAlunosInput = document.getElementById('search-alunos');
    if (searchAlunosInput) {
        searchAlunosInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const alunosItems = document.querySelectorAll('.aluno-item');
            
            alunosItems.forEach(item => {
                const alunoNome = item.querySelector('.aluno-nome').textContent.toLowerCase();
                if (alunoNome.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Handle "Editar Turma" buttons
    const btnEditarTurma = document.querySelectorAll('.btn-editar-turma');
    if (btnEditarTurma.length > 0) {
        btnEditarTurma.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const turmaId = this.getAttribute('data-turma-id');
                window.location.href = `editar_turma.php?id=${turmaId}`;
            });
        });
    }
    
    // Add mask for phone input
    const telefoneInput = document.getElementById('edit-telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                if (value.length > 9) {
                    value = value.substring(0, 9) + '-' + value.substring(9);
                }
                if (value.length > 15) {
                    value = value.substring(0, 15);
                }
            }
            e.target.value = value;
        });
    }
    
    // Add confirmation for logout
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja sair?')) {
                e.preventDefault();
            }
        });
    }
    
    // Handle presence marking for students (if needed)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-presenca')) {
            const alunoId = e.target.getAttribute('data-aluno-id');
            const turmaId = e.target.getAttribute('data-turma-id');
            const presente = e.target.classList.contains('presente') ? 0 : 1;
            
            fetch('api/marcar_presenca.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    aluno_id: alunoId,
                    turma_id: turmaId,
                    presente: presente
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (presente) {
                        e.target.classList.add('presente');
                        e.target.innerHTML = '<i class="fas fa-check"></i>';
                    } else {
                        e.target.classList.remove('presente');
                        e.target.innerHTML = '<i class="fas fa-user-check"></i>';
                    }
                } else {
                    alert(data.message || 'Erro ao marcar presença.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro de conexão. Tente novamente.');
            });
        }
    });
    
    // Botões de Avaliação e Ver Avaliações
    document.addEventListener('click', function(e) {
        // Botão "Ver Avaliações"
        if (e.target && (e.target.classList.contains('btn-ver-avaliacoes') || 
                         (e.target.parentElement && e.target.parentElement.classList.contains('btn-ver-avaliacoes')))) {
            
            const btn = e.target.classList.contains('btn-ver-avaliacoes') ? e.target : e.target.parentElement;
            const alunoId = btn.getAttribute('data-aluno-id');
            const turmaId = btn.getAttribute('data-turma-id');
            
            if (alunoId && turmaId) {
                e.preventDefault();
                window.location.href = `avaliacoes_aluno.php?aluno_id=${alunoId}&turma_id=${turmaId}`;
            }
        }
        
        // Botão "Avaliar"
        if (e.target && (e.target.classList.contains('btn-avaliar') || 
                         (e.target.parentElement && e.target.parentElement.classList.contains('btn-avaliar')))) {
            
            const btn = e.target.classList.contains('btn-avaliar') ? e.target : e.target.parentElement;
            const alunoId = btn.getAttribute('data-aluno-id');
            const turmaId = btn.getAttribute('data-turma-id');
            
            if (alunoId && turmaId) {
                e.preventDefault();
                window.location.href = `avaliar_aluno.php?aluno_id=${alunoId}&turma_id=${turmaId}`;
            }
        }
    });
    
    // Calcular IMC quando altura e peso forem alterados (para avaliar_aluno.php)
    const alturaInput = document.getElementById('altura');
    const pesoInput = document.getElementById('peso');
    const imcInput = document.getElementById('imc');
    const imcStatus = document.getElementById('imcStatus');
    
    if (alturaInput && pesoInput && imcInput) {
        const calcularIMC = function() {
            const altura = parseFloat(alturaInput.value);
            const peso = parseFloat(pesoInput.value);
            
            if (altura && peso && altura > 0) {
                const alturaMetros = altura / 100; // Converter cm para metros
                const imc = (peso / (alturaMetros * alturaMetros)).toFixed(2);
                imcInput.value = imc;
                
                // Determinar status do IMC
                let status = "";
                if (imc < 18.5) {
                    status = "Abaixo do peso";
                } else if (imc >= 18.5 && imc < 25) {
                    status = "Peso normal";
                } else if (imc >= 25 && imc < 30) {
                    status = "Sobrepeso";
                } else {
                    status = "Obesidade";
                }
                
                if (imcStatus) {
                    imcStatus.textContent = status;
                }
            } else {
                imcInput.value = "";
                if (imcStatus) {
                    imcStatus.textContent = "";
                }
            }
        };
        
        alturaInput.addEventListener('input', calcularIMC);
        pesoInput.addEventListener('input', calcularIMC);
    }
    
    // Handle download PDF in avaliacoes_aluno.php
    const btnGerarPDF = document.querySelector('.btn-gerar-pdf');
    if (btnGerarPDF) {
        btnGerarPDF.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            window.open(url, '_blank');
        });
    }
    
    console.log("Dashboard JS initialized successfully!");
});