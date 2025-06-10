    // galeria.js - VERSÃO FINAL COMPLETA CORRIGIDA
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Módulo de galeria carregado');
        
        // Verificar se o card de galeria existe
        const cardGaleria = document.querySelector('.dashboard-card:nth-child(4)'); // 4º card (Galeria de Fotos)
        
        if (!cardGaleria) {
            console.warn('Card de galeria não encontrado');
            return;
        }
        
        // Criar modais se não existirem
        criarModaisGaleria();
        
        // Event listeners
        cardGaleria.addEventListener('click', abrirModalGaleria);
        
        // Event listeners para os botões dos modais
        document.getElementById('btn-nova-galeria')?.addEventListener('click', abrirModalNovaGaleria);
        document.getElementById('btn-cancelar-galeria')?.addEventListener('click', fecharModalNovaGaleria);
        document.getElementById('form-galeria')?.addEventListener('submit', enviarGaleria);
        
        // CORRIGIDO: Configurar upload após delay maior
        setTimeout(() => {
            configurarUploadArquivos();
        }, 1000);
        
        // Event listeners para fechar modais
        document.getElementById('closeGaleriaModal')?.addEventListener('click', () => {
            document.getElementById('galeriaModal').style.display = 'none';
        });
        
        document.getElementById('closeNovaGaleriaModal')?.addEventListener('click', fecharModalNovaGaleria);
        document.getElementById('closeDetalhesGaleriaModal')?.addEventListener('click', () => {
            document.getElementById('detalhesGaleriaModal').style.display = 'none';
        });
        
        // Fechar modais ao clicar fora
        window.addEventListener('click', function(event) {
            const modals = ['galeriaModal', 'novaGaleriaModal', 'detalhesGaleriaModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // Event listeners para ações das galerias
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-ver-galeria') || e.target.closest('.btn-ver-galeria')) {
                const button = e.target.classList.contains('btn-ver-galeria') ? e.target : e.target.closest('.btn-ver-galeria');
                const galeriaId = button.getAttribute('data-galeria-id');
                verDetalhesGaleria(galeriaId);
            }
            
            if (e.target.classList.contains('btn-excluir-galeria') || e.target.closest('.btn-excluir-galeria')) {
                const button = e.target.classList.contains('btn-excluir-galeria') ? e.target : e.target.closest('.btn-excluir-galeria');
                const galeriaId = button.getAttribute('data-galeria-id');
                excluirGaleria(galeriaId);
            }
        });
    });

    // NOVA: Variável global para armazenar arquivos selecionados
    let arquivosSelecionados = [];

    // CORRIGIDA: Função para configurar upload com sistema de array global
    function configurarUploadArquivos() {
        const uploadArea = document.getElementById('upload-area');
        const inputArquivos = document.getElementById('arquivos-galeria');
        
        if (!uploadArea || !inputArquivos) {
            console.warn('Elementos de upload não encontrados, tentando novamente...');
            setTimeout(configurarUploadArquivos, 500);
            return;
        }
        
        console.log('Configurando upload de arquivos - elementos encontrados');
        
        // Limpar listeners anteriores
        uploadArea.replaceWith(uploadArea.cloneNode(true));
        const newUploadArea = document.getElementById('upload-area');
        const newInputArquivos = document.getElementById('arquivos-galeria');
        
        // Clique na área de upload
        newUploadArea.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn') || e.target.closest('.btn')) {
                return;
            }
            console.log('Clique na área de upload - abrindo seletor');
            newInputArquivos.click();
        });
        
        // Drag and drop
        newUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            newUploadArea.style.borderColor = '#007bff';
            newUploadArea.style.backgroundColor = '#f8f9fa';
        });
        
        newUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            newUploadArea.style.borderColor = '#ddd';
            newUploadArea.style.backgroundColor = 'transparent';
        });
        
        newUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            newUploadArea.style.borderColor = '#ddd';
            newUploadArea.style.backgroundColor = 'transparent';
            
            const files = Array.from(e.dataTransfer.files);
            console.log('Arquivos arrastados:', files.length);
            
            if (files.length > 0) {
                adicionarArquivos(files);
            }
        });
        
        // CORRIGIDO: Event listener para mudança no input
        newInputArquivos.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            console.log('Arquivos selecionados via input:', files.length);
            
            if (files.length > 0) {
                // IMPORTANTE: Verificar se é para adicionar ou substituir
                const isAdding = arquivosSelecionados.length > 0;
                
                if (isAdding) {
                    console.log('Adicionando aos arquivos existentes');
                    adicionarArquivos(files);
                } else {
                    console.log('Primeira seleção de arquivos');
                    arquivosSelecionados = [...files];
                    atualizarInputEPreview();
                }
            }
            
            // IMPORTANTE: Não limpar o input aqui para manter a funcionalidade
        });
    }

    // NOVA: Função para adicionar arquivos ao array global
    function adicionarArquivos(novosArquivos) {
        console.log('Adicionando', novosArquivos.length, 'arquivos aos', arquivosSelecionados.length, 'existentes');
        
        novosArquivos.forEach(novoArquivo => {
            // Verificar duplicados
            const jaExiste = arquivosSelecionados.some(arquivo => 
                arquivo.name === novoArquivo.name && arquivo.size === novoArquivo.size
            );
            
            if (!jaExiste) {
                arquivosSelecionados.push(novoArquivo);
                console.log('Arquivo adicionado:', novoArquivo.name);
            } else {
                console.warn('Arquivo duplicado ignorado:', novoArquivo.name);
            }
        });
        
        atualizarInputEPreview();
    }

    // NOVA: Função para atualizar input e preview
    function atualizarInputEPreview() {
        const inputArquivos = document.getElementById('arquivos-galeria');
        
        if (inputArquivos && arquivosSelecionados.length > 0) {
            // Atualizar o input com todos os arquivos
            const dt = new DataTransfer();
            arquivosSelecionados.forEach(arquivo => {
                dt.items.add(arquivo);
            });
            inputArquivos.files = dt.files;
            
            console.log('Input atualizado com', arquivosSelecionados.length, 'arquivos');
            
            // Atualizar preview
            previewArquivos(arquivosSelecionados);
            atualizarStatusArquivos(arquivosSelecionados.length);
        }
    }

    function atualizarStatusArquivos(count) {
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        const filesSelected = document.getElementById('files-selected');
        const filesCount = document.getElementById('files-count');
        
        if (count > 0) {
            if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
            if (filesSelected) filesSelected.style.display = 'block';
            if (filesCount) filesCount.textContent = count;
        } else {
            if (uploadPlaceholder) uploadPlaceholder.style.display = 'block';
            if (filesSelected) filesSelected.style.display = 'none';
        }
    }

    function criarModaisGaleria() {
        // Verificar se os modais já existem
        if (document.getElementById('galeriaModal')) {
            return;
        }
        
        // Modal principal da galeria
        const galeriaModal = document.createElement('div');
        galeriaModal.id = 'galeriaModal';
        galeriaModal.className = 'modal';
        galeriaModal.innerHTML = `
            <div class="modal-content">
                <span class="close" id="closeGaleriaModal">&times;</span>
                <h2>Galeria de Fotos</h2>
                
                <div style="margin-bottom: 20px; text-align: right;">
                    <button id="btn-nova-galeria" class="btn">
                        <i class="fas fa-plus"></i> Nova Galeria
                    </button>
                </div>
                
                <div id="galerias-lista-container">
                    <p>Carregando galerias...</p>
                </div>
            </div>
        `;
        
        // Modal para criar nova galeria
        const novaGaleriaModal = document.createElement('div');
        novaGaleriaModal.id = 'novaGaleriaModal';
        novaGaleriaModal.className = 'modal';
        novaGaleriaModal.innerHTML = `
            <div class="modal-content">
                <span class="close" id="closeNovaGaleriaModal">&times;</span>
                <h2>Nova Galeria</h2>
                
                <div id="mensagem-galeria"></div>
                
                <form id="form-galeria" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="titulo-galeria" class="form-label">Título <span style="color: red;">*</span></label>
                        <input type="text" id="titulo-galeria" name="titulo" class="form-control" required 
                            placeholder="Digite o título da galeria">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="turma-galeria" class="form-label">Turma <span style="color: red;">*</span></label>
                                <select id="turma-galeria" name="turma_id" class="form-control" required>
                                    <option value="">Carregando turmas...</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="atividade-galeria" class="form-label">Atividade Realizada <span style="color: red;">*</span></label>
                                <input type="text" id="atividade-galeria" name="atividade_realizada" class="form-control" required 
                                    placeholder="Ex: Treino de futebol, Competição...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao-galeria" class="form-label">Descrição</label>
                        <textarea id="descricao-galeria" name="descricao" class="form-control" rows="3"
                                placeholder="Descrição opcional sobre a galeria..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="arquivos-galeria" class="form-label">Fotos e Vídeos</label>
                        <div class="upload-area" id="upload-area">
                            <input type="file" id="arquivos-galeria" name="arquivos[]" class="form-control" 
                                multiple accept="image/*,video/*" style="display: none;">
                            <div class="upload-placeholder" id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p><strong>Clique aqui ou arraste arquivos</strong></p>
                                <p>Selecione múltiplas fotos e vídeos</p>
                                <p><small>JPG, PNG, GIF, MP4, AVI, MOV - Máximo 50MB por arquivo</small></p>
                            </div>
                            <div class="files-selected" id="files-selected" style="display: none;">
                                <p><strong><span id="files-count">0</span> arquivo(s) selecionado(s)</strong></p>
                                <button type="button" id="add-more-files" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Adicionar Mais
                                </button>
                                <button type="button" id="clear-files" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="preview-arquivos"></div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Criar Galeria
                        </button>
                        <button type="button" id="btn-cancelar-galeria" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        // Modal para ver detalhes da galeria
        const detalhesGaleriaModal = document.createElement('div');
        detalhesGaleriaModal.id = 'detalhesGaleriaModal';
        detalhesGaleriaModal.className = 'modal';
        detalhesGaleriaModal.innerHTML = `
            <div class="modal-content modal-content-large">
                <span class="close" id="closeDetalhesGaleriaModal">&times;</span>
                <h2 id="titulo-detalhes-galeria">Detalhes da Galeria</h2>
                
                <div id="detalhes-galeria-container">
                    <p>Carregando...</p>
                </div>
            </div>
        `;
        
        // Adicionar modais ao body
        document.body.appendChild(galeriaModal);
        document.body.appendChild(novaGaleriaModal);
        document.body.appendChild(detalhesGaleriaModal);
        
        // Adicionar estilos CSS
        adicionarEstilosGaleria();
    }

    function adicionarEstilosGaleria() {
        if (document.getElementById('galeria-styles')) {
            return;
        }
        
        const style = document.createElement('style');
        style.id = 'galeria-styles';
        style.textContent = `
            .modal-content-large {
                max-width: 90%;
                width: 1200px;
            }
            
            .upload-area {
                border: 2px dashed #ddd;
                border-radius: 8px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                background-color: #fafafa;
            }
            
            .upload-area:hover {
                border-color: #007bff;
                background-color: #f8f9fa;
            }
            
            .upload-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            
            .files-selected {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .files-selected .btn {
                margin: 0 5px;
            }
            
            .preview-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
                max-height: 400px;
                overflow-y: auto;
                padding: 15px;
                border: 2px solid #e9ecef;
                border-radius: 10px;
                background: linear-gradient(135deg, #f8f9fa, #ffffff);
                margin-top: 20px;
            }
            
            .preview-item {
                position: relative;
                border: 2px solid #e9ecef;
                border-radius: 10px;
                overflow: hidden;
                background-color: #fff;
                transition: all 0.3s ease;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .preview-item:hover {
                transform: scale(1.05);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                border-color: #007bff;
            }
            
            .preview-item img,
            .preview-item video {
                width: 100%;
                height: 120px;
                object-fit: cover;
            }
            
            .preview-item .file-info {
                padding: 10px;
                font-size: 0.75rem;
                text-align: center;
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                border-top: 1px solid #dee2e6;
                font-weight: 600;
            }
            
            .preview-item .remove-file {
                position: absolute;
                top: 8px;
                right: 8px;
                background: linear-gradient(135deg, #dc3545, #c82333);
                color: white;
                border: none;
                border-radius: 50%;
                width: 28px;
                height: 28px;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
                transition: all 0.3s ease;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            }
            
            .preview-item .remove-file:hover {
                background: linear-gradient(135deg, #c82333, #bd2130);
                transform: scale(1.1);
            }
            
            .preview-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding: 15px;
                background: linear-gradient(135deg, #007bff, #0056b3);
                border-radius: 10px;
                color: white;
                box-shadow: 0 4px 12px rgba(0,123,255,0.3);
            }
            
            .preview-header .btn {
                background: rgba(255,255,255,0.2);
                border: 1px solid rgba(255,255,255,0.3);
                color: white;
                font-weight: 600;
            }
            
            .preview-header .btn:hover {
                background: rgba(255,255,255,0.3);
                transform: translateY(-2px);
            }
            
            .galeria-item {
                background-color: var(--white);
                border-radius: var(--border-radius);
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: var(--box-shadow);
                border-left: 4px solid #6c757d;
                transition: var(--transition);
            }
            
            .galeria-item:hover {
                transform: translateY(-5px);
                box-shadow: var(--box-shadow-hover);
            }
            
            .galeria-item h3 {
                color: #6c757d;
                margin-bottom: 15px;
                font-size: 1.2rem;
                font-weight: 600;
                border-bottom: 1px solid var(--gray-light);
                padding-bottom: 10px;
            }
            
            .galeria-info {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .galeria-field {
                display: flex;
                flex-direction: column;
                margin-bottom: 8px;
            }
            
            .galeria-field label {
                font-weight: 600;
                color: var(--gray-dark);
                font-size: 0.9rem;
                margin-bottom: 2px;
            }
            
            .galeria-field span {
                color: var(--dark);
                font-size: 0.95rem;
            }
            
            .galeria-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 15px;
                gap: 8px;
                flex-wrap: wrap;
            }
            
            .btn-ver-galeria {
                background: linear-gradient(135deg, #6c757d, #5a6268);
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
            }
            
            .btn-ver-galeria:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
            }
            
            .btn-excluir-galeria {
                background: linear-gradient(135deg, var(--danger), #c82333);
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
            }
            
            .btn-excluir-galeria:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(255, 59, 48, 0.3);
            }
            
            .galeria-detalhes {
                margin-bottom: 25px;
            }
            
            .galeria-detalhes h4 {
                color: var(--primary);
                margin-bottom: 15px;
                font-size: 1.1rem;
                border-bottom: 1px solid var(--gray-light);
                padding-bottom: 8px;
            }
            
            .arquivos-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .arquivo-item {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
                cursor: pointer;
            }
            
            .arquivo-item:hover {
                transform: scale(1.05);
            }
            
            .arquivo-item img,
            .arquivo-item video {
                width: 100%;
                height: 150px;
                object-fit: cover;
            }
            
            .arquivo-info {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(transparent, rgba(0,0,0,0.7));
                color: white;
                padding: 10px;
                font-size: 0.8rem;
            }
            
            .lightbox {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.9);
            }
            
            .lightbox-content {
                position: relative;
                margin: auto;
                padding: 20px;
                width: 90%;
                max-width: 800px;
                top: 50%;
                transform: translateY(-50%);
                text-align: center;
            }
            
            .lightbox img,
            .lightbox video {
                max-width: 100%;
                max-height: 80vh;
                border-radius: 8px;
            }
            
            .lightbox-close {
                position: absolute;
                top: 10px;
                right: 25px;
                color: white;
                font-size: 35px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .lightbox-close:hover {
                color: #ccc;
            }
            
            @media (max-width: 768px) {
                .modal-content-large {
                    width: 95%;
                    padding: 15px;
                }
                
                .arquivos-grid {
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 10px;
                }
                
                .preview-container {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                }
            }
        `;
        
        document.head.appendChild(style);
    }

    function abrirModalGaleria() {
        const modal = document.getElementById('galeriaModal');
        if (modal) {
            modal.style.display = 'block';
            carregarGalerias();
        }
    }

    function abrirModalNovaGaleria() {
        const modal = document.getElementById('novaGaleriaModal');
        if (modal) {
            modal.style.display = 'block';
            carregarTurmasSelect();
            
            // IMPORTANTE: Resetar array global de arquivos
            arquivosSelecionados = [];
            
            // Reset completo do formulário
            const form = document.getElementById('form-galeria');
            if (form) {
                form.reset();
            }
            
            // Reset do input de arquivos
            const inputArquivos = document.getElementById('arquivos-galeria');
            if (inputArquivos) {
                inputArquivos.value = '';
            }
            
            // Reset dos previews e status
            const previewContainer = document.getElementById('preview-arquivos');
            if (previewContainer) {
                previewContainer.innerHTML = '';
            }
            
            atualizarStatusArquivos(0);
            
            const mensagemContainer = document.getElementById('mensagem-galeria');
            if (mensagemContainer) {
                mensagemContainer.innerHTML = '';
            }
            
            // Reconfigurar eventos
            setTimeout(() => {
                configurarBotoesUpload();
            }, 200);
        }
    }

    // CORRIGIDA: Configurar botões com sistema correto
    function configurarBotoesUpload() {
        const addMoreBtn = document.getElementById('add-more-files');
        const clearFilesBtn = document.getElementById('clear-files');
        const inputArquivos = document.getElementById('arquivos-galeria');
        
        if (addMoreBtn && inputArquivos) {
            // Remover listeners anteriores
            addMoreBtn.replaceWith(addMoreBtn.cloneNode(true));
            const newAddMoreBtn = document.getElementById('add-more-files');
            
            newAddMoreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Botão adicionar mais clicado - total atual:', arquivosSelecionados.length);
                inputArquivos.click();
            });
        }
        
        if (clearFilesBtn) {
            clearFilesBtn.replaceWith(clearFilesBtn.cloneNode(true));
            const newClearBtn = document.getElementById('clear-files');
            
            newClearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Limpando todos os arquivos');
                limparTodosArquivos();
            });
        }
    }

    function fecharModalNovaGaleria() {
        const modal = document.getElementById('novaGaleriaModal');
        if (modal) {
            modal.style.display = 'none';
            // Limpar arquivos ao fechar
            arquivosSelecionados = [];
        }
    }

    function carregarGalerias() {
        const container = document.getElementById('galerias-lista-container');
        if (!container) return;
        
        container.innerHTML = '<p>Carregando galerias...</p>';
        
        fetch('./api/galeria.php?action=listar')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.galerias && data.galerias.length > 0) {
                        let html = '';
                        data.galerias.forEach(galeria => {
                            html += `
                                <div class="galeria-item">
                                    <h3>${galeria.titulo}</h3>
                                    
                                    <div class="galeria-info">
                                        <div class="galeria-field">
                                            <label>Turma:</label>
                                            <span>${galeria.nome_turma || 'N/A'} - ${galeria.unidade_nome || 'N/A'}</span>
                                        </div>
                                        <div class="galeria-field">
                                            <label>Atividade:</label>
                                            <span>${galeria.atividade_realizada}</span>
                                        </div>
                                        <div class="galeria-field">
                                            <label>Data:</label>
                                            <span>${formatarData(galeria.data_criacao)}</span>
                                        </div>
                                        <div class="galeria-field">
                                            <label>Arquivos:</label>
                                            <span>${galeria.total_arquivos || 0} arquivo(s)</span>
                                        </div>
                                    </div>
                                    
                                    ${galeria.descricao ? `
                                        <div class="galeria-field" style="margin-top: 15px;">
                                            <label>Descrição:</label>
                                            <span>${galeria.descricao}</span>
                                        </div>
                                    ` : ''}
                                    
                                    <div class="galeria-actions">
                                        <button class="btn-ver-galeria" data-galeria-id="${galeria.id}">
                                            <i class="fas fa-eye"></i> Ver Galeria
                                        </button>
                                        <button class="btn-excluir-galeria" data-galeria-id="${galeria.id}">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<div class="alert alert-info">Nenhuma galeria encontrada.</div>';
                    }
                } else {
                    container.innerHTML = `<div class="alert alert-danger">${data.message || 'Erro ao carregar galerias.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                container.innerHTML = `<div class="alert alert-danger">Erro de conexão: ${error.message}</div>`;
            });
    }

    function carregarTurmasSelect() {
        const select = document.getElementById('turma-galeria');
        if (!select) return;
        
        select.innerHTML = '<option value="">Carregando turmas...</option>';
        
        fetch('./api/galeria.php?action=turmas')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    select.innerHTML = '<option value="">Selecione uma turma</option>';
                    if (data.turmas && data.turmas.length > 0) {
                        data.turmas.forEach(turma => {
                            select.innerHTML += `<option value="${turma.id}">${turma.nome_turma} - ${turma.unidade_nome}</option>`;
                        });
                    } else {
                        select.innerHTML = '<option value="">Nenhuma turma encontrada</option>';
                    }
                } else {
                    select.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                select.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            });
    }

    // CORRIGIDA: Função de preview usando array global
    function previewArquivos(files) {
        const container = document.getElementById('preview-arquivos');
        if (!container) {
            console.error('Container preview-arquivos não encontrado');
            return;
        }
        
        container.innerHTML = '';
        
        if (!files || files.length === 0) {
            atualizarStatusArquivos(0);
            console.log('Nenhum arquivo para preview');
            return;
        }
        
        console.log('Criando preview para', files.length, 'arquivos');
        
        // Header melhorado
        const header = document.createElement('div');
        header.className = 'preview-header';
        header.innerHTML = `
            <div>
                <strong style="font-size: 1.2rem;">
                    <i class="fas fa-images" style="margin-right: 10px;"></i>
                    ${files.length} arquivo(s) selecionado(s)
                </strong>
            </div>
            <div>
                <button type="button" id="add-more-files-preview" class="btn btn-sm">
                    <i class="fas fa-plus"></i> Adicionar Mais
                </button>
                <button type="button" id="clear-all-files-preview" class="btn btn-sm">
                    <i class="fas fa-trash"></i> Limpar Todos
                </button>
            </div>
        `;
        container.appendChild(header);
        
        // Event listeners do header
        header.querySelector('#add-more-files-preview').addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Adicionar mais (preview) - total:', arquivosSelecionados.length);
            document.getElementById('arquivos-galeria').click();
        });
        
        header.querySelector('#clear-all-files-preview').addEventListener('click', function(e) {
            e.preventDefault();
            limparTodosArquivos();
        });
        
        // Container grid
        const gridContainer = document.createElement('div');
        gridContainer.className = 'preview-container';
        container.appendChild(gridContainer);
        
        // Criar preview para cada arquivo
        files.forEach((file, index) => {
            const isImage = file.type.startsWith('image/');
            const isVideo = file.type.startsWith('video/');
            
            if (!isImage && !isVideo) return;
            
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.setAttribute('data-index', index);
            
            // Botão remover
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '×';
            removeBtn.type = 'button';
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                removerArquivo(index);
            });
            
            // Mídia
            if (isImage) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                div.appendChild(img);
            } else if (isVideo) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.controls = false;
                video.muted = true;
                div.appendChild(video);
                
                const videoIcon = document.createElement('div');
                videoIcon.innerHTML = '<i class="fas fa-play-circle"></i>';
                videoIcon.style.cssText = `
                    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    color: white; font-size: 2rem; text-shadow: 0 0 8px rgba(0,0,0,0.8);
                    pointer-events: none;
                `;
                div.appendChild(videoIcon);
            }
            
            // Info
            const info = document.createElement('div');
            info.className = 'file-info';
            const fileName = file.name.length > 18 ? file.name.substring(0, 18) + '...' : file.name;
            const fileSize = formatarTamanho(file.size);
            const fileType = isVideo ? '🎥 Vídeo' : '🖼️ Imagem';
            
            info.innerHTML = `
                <div style="margin-bottom: 4px;">${fileName}</div>
                <div style="color: #6c757d;">${fileType} • ${fileSize}</div>
            `;
            
            div.appendChild(removeBtn);
            div.appendChild(info);
            gridContainer.appendChild(div);
        });
        
        atualizarStatusArquivos(files.length);
    }

    // NOVA: Função para limpar usando array global
    function limparTodosArquivos() {
        arquivosSelecionados = [];
        
        const inputArquivos = document.getElementById('arquivos-galeria');
        if (inputArquivos) {
            inputArquivos.value = '';
        }
        
        const container = document.getElementById('preview-arquivos');
        if (container) {
            container.innerHTML = '';
        }
        
        atualizarStatusArquivos(0);
        console.log('Todos os arquivos foram limpos');
    }

    // CORRIGIDA: Função para remover usando array global
    function removerArquivo(indexToRemove) {
        console.log('Removendo arquivo index:', indexToRemove, 'de', arquivosSelecionados.length);
        
        // Remover do array global
        arquivosSelecionados.splice(indexToRemove, 1);
        
        // Atualizar input e preview
        atualizarInputEPreview();
        
        console.log('Arquivos restantes:', arquivosSelecionados.length);
    }

    function validarArquivos() {
        if (arquivosSelecionados.length === 0) {
            showMessage('mensagem-galeria', 'Selecione pelo menos um arquivo para a galeria.', 'danger');
            return false;
        }
        
        const maxFiles = 50;
        if (arquivosSelecionados.length > maxFiles) {
            showMessage('mensagem-galeria', `Máximo de ${maxFiles} arquivos permitidos. Você selecionou ${arquivosSelecionados.length}.`, 'danger');
            return false;
        }
        
        const maxSize = 50 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 
                            'video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/flv', 'video/webm'];
        
        for (let i = 0; i < arquivosSelecionados.length; i++) {
            const file = arquivosSelecionados[i];
            
            if (file.size > maxSize) {
                showMessage('mensagem-galeria', `Arquivo "${file.name}" é muito grande. Máximo 50MB por arquivo.`, 'danger');
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                showMessage('mensagem-galeria', `Arquivo "${file.name}" não é um tipo permitido.`, 'danger');
                return false;
            }
        }
        
        return true;
    }

    function enviarGaleria(e) {
        e.preventDefault();
        
        console.log('Iniciando envio da galeria');
        
        // Validar campos
        const titulo = document.getElementById('titulo-galeria').value.trim();
        const turmaId = document.getElementById('turma-galeria').value;
        const atividade = document.getElementById('atividade-galeria').value.trim();
        
        if (!titulo || !turmaId || !atividade) {
            showMessage('mensagem-galeria', 'Preencha todos os campos obrigatórios.', 'danger');
            return;
        }
        
        // Validar arquivos
        if (!validarArquivos()) {
            return;
        }
        
        const formData = new FormData(e.target);
        formData.append('action', 'criar');
        
        // Log
        console.log('Enviando', arquivosSelecionados.length, 'arquivos:');
        arquivosSelecionados.forEach((file, i) => {
            console.log(`- ${file.name} (${formatarTamanho(file.size)})`);
        });
        
        // Loading
        showMessage('mensagem-galeria', `Enviando galeria com ${arquivosSelecionados.length} arquivo(s)...`, 'info');
        
        const btnEnviar = e.target.querySelector('button[type="submit"]');
        if (btnEnviar) {
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        }
        
        fetch('./api/galeria.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                showMessage('mensagem-galeria', data.message || 'Galeria criada com sucesso!', 'success');
                setTimeout(() => {
                    fecharModalNovaGaleria();
                    carregarGalerias();
                }, 2000);
            } else {
                showMessage('mensagem-galeria', data.message || 'Erro ao criar galeria.', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro completo:', error);
            showMessage('mensagem-galeria', `Erro: ${error.message}. Verifique se as tabelas foram criadas no banco.`, 'danger');
        })
        .finally(() => {
            if (btnEnviar) {
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<i class="fas fa-save"></i> Criar Galeria';
            }
        });
    }

    // FUNÇÃO CORRIGIDA - Ver detalhes da galeria com caminhos corretos
    // FUNÇÃO CORRIGIDA - Ver detalhes da galeria com caminhos normalizados
function verDetalhesGaleria(galeriaId) {
    const modal = document.getElementById('detalhesGaleriaModal');
    const container = document.getElementById('detalhes-galeria-container');
    const titulo = document.getElementById('titulo-detalhes-galeria');
    
    if (!modal || !container) return;
    
    modal.style.display = 'block';
    container.innerHTML = '<p>Carregando detalhes...</p>';
    
    fetch(`./api/galeria.php?action=detalhes&id=${galeriaId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const galeria = data.galeria;
                
                if (titulo) {
                    titulo.textContent = galeria.titulo;
                }
                
                let html = `
                    <div class="galeria-detalhes">
                        <h4>Informações da Galeria</h4>
                        <div class="galeria-info">
                            <div class="galeria-field">
                                <label>Turma:</label>
                                <span>${galeria.nome_turma || 'N/A'} - ${galeria.unidade_nome || 'N/A'}</span>
                            </div>
                            <div class="galeria-field">
                                <label>Atividade:</label>
                                <span>${galeria.atividade_realizada}</span>
                            </div>
                            <div class="galeria-field">
                                <label>Data:</label>
                                <span>${formatarData(galeria.data_criacao)}</span>
                            </div>
                            <div class="galeria-field">
                                <label>Criado por:</label>
                                <span>${galeria.criado_por_nome || 'N/A'}</span>
                            </div>
                        </div>
                        
                        ${galeria.descricao ? `
                            <div class="galeria-field" style="margin-top: 15px;">
                                <label>Descrição:</label>
                                <span style="white-space: pre-wrap;">${galeria.descricao}</span>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                if (galeria.arquivos && galeria.arquivos.length > 0) {
                    html += `
                        <div class="galeria-detalhes">
                            <h4>Fotos e Vídeos (${galeria.arquivos.length})</h4>
                            <div class="arquivos-grid">
                    `;
                    
                    galeria.arquivos.forEach(arquivo => {
                        const isVideo = arquivo.tipo_arquivo === 'video';
                        
                        // CORREÇÃO PRINCIPAL: Normalizar o caminho removendo "../" e outros problemas
                        let caminhoCorreto = arquivo.caminho;
                        
                        // Remover "../" do início se existir
                        caminhoCorreto = caminhoCorreto.replace(/^\.\.\//, '');
                        
                        // Garantir que comece com o caminho correto
                        if (!caminhoCorreto.startsWith('/luis/bombeiros_mirim/')) {
                            // Se o caminho não começar corretamente, construir o path correto
                            if (caminhoCorreto.startsWith('uploads/')) {
                                caminhoCorreto = `/luis/bombeiros_mirim/${caminhoCorreto}`;
                            } else {
                                // Fallback para garantir o path correto
                                caminhoCorreto = `/luis/bombeiros_mirim/uploads/galeria/${caminhoCorreto.split('/').slice(-3).join('/')}`;
                            }
                        }
                        
                        console.log('Caminho original:', arquivo.caminho);
                        console.log('Caminho corrigido:', caminhoCorreto);
                        
                        html += `
                            <div class="arquivo-item" onclick="abrirLightbox('${caminhoCorreto}', '${arquivo.tipo_arquivo}', '${arquivo.nome_original}')">
                                ${isVideo ? 
                                    `<video src="${caminhoCorreto}" muted></video>` :
                                    `<img src="${caminhoCorreto}" alt="${arquivo.nome_original}" 
                                         onload="console.log('✅ Imagem carregada:', '${caminhoCorreto}')" 
                                         onerror="console.error('❌ Erro ao carregar imagem:', '${caminhoCorreto}'); this.src='/luis/bombeiros_mirim/uploads/fotos/default.png';">`
                                }
                                <div class="arquivo-info">
                                    <div>${arquivo.nome_original}</div>
                                    <div>${isVideo ? 'Vídeo' : 'Imagem'} • ${formatarTamanho(arquivo.tamanho)}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                } else {
                    html += '<div class="alert alert-info">Nenhum arquivo encontrado nesta galeria.</div>';
                }
                
                container.innerHTML = html;
                criarLightbox();
                
            } else {
                container.innerHTML = `<div class="alert alert-danger">${data.message || 'Erro ao carregar detalhes.'}</div>`;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = `<div class="alert alert-danger">Erro de conexão: ${error.message}</div>`;
        });
}

    function excluirGaleria(galeriaId) {
        if (!confirm('Tem certeza que deseja excluir esta galeria? Todos os arquivos serão removidos permanentemente.')) {
            return;
        }
        
        fetch(`./api/galeria.php?action=excluir&id=${galeriaId}`, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message || 'Galeria excluída com sucesso!');
                carregarGalerias();
            } else {
                alert(data.message || 'Erro ao excluir galeria');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão');
        });
    }

    function criarLightbox() {
        if (document.getElementById('lightbox')) {
            return;
        }
        
        const lightbox = document.createElement('div');
        lightbox.id = 'lightbox';
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <span class="lightbox-close" onclick="fecharLightbox()">&times;</span>
                <div id="lightbox-media"></div>
            </div>
        `;
        
        document.body.appendChild(lightbox);
    }

    // FUNÇÃO CORRIGIDA - Abrir lightbox com caminho correto
    function abrirLightbox(caminho, tipo, nomeOriginal) {
        const lightbox = document.getElementById('lightbox');
        const mediaContainer = document.getElementById('lightbox-media');
        
        if (!lightbox || !mediaContainer) return;
        
        console.log('Abrindo lightbox com caminho:', caminho);
        
        if (tipo === 'video') {
            mediaContainer.innerHTML = `
                <video src="${caminho}" controls autoplay>
                    Seu navegador não suporta vídeos.
                </video>
                <p style="color: white; margin-top: 10px;">${nomeOriginal}</p>
            `;
        } else {
            mediaContainer.innerHTML = `
                <img src="${caminho}" alt="${nomeOriginal}" onload="console.log('✅ Imagem carregada:', '${caminho}')" onerror="console.error('❌ Erro ao carregar imagem:', '${caminho}')">
                <p style="color: white; margin-top: 10px;">${nomeOriginal}</p>
            `;
        }
        
        lightbox.style.display = 'block';
    }

    // FUNÇÃO CORRIGIDA - Fechar lightbox (estava faltando!)
    function fecharLightbox() {
        const lightbox = document.getElementById('lightbox');
        if (lightbox) {
            lightbox.style.display = 'none';
        }
    }

    // Utilitários
    function formatarData(dataString) {
        if (!dataString) return 'Data não informada';
        try {
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR') + ' às ' + data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return 'Data inválida';
        }
    }

    function formatarTamanho(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showMessage(elementId, message, type) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                if (type === 'success') {
                    element.innerHTML = '';
                }
            }, 5000);
        }
    }

    console.log('🎯 Galeria JS FINAL COMPLETA - Todas as funções corrigidas e funcionando!');