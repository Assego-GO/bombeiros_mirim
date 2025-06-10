// galeria.js - VERSÃO FINAL CORRIGIDA - SEM INTERFERIR EM OUTROS ELEMENTOS
(function() {
    'use strict';
    
    console.log('🎯 Galeria JS - Versão Final Carregada');
    
    // Variável global para arquivos selecionados
    let arquivosSelecionados = [];
    
    // Aguardar o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
    
    function inicializar() {
        console.log('🚀 Inicializando módulo de galeria...');
        
        // Aguardar um pouco para garantir que todos os elementos estejam prontos
        setTimeout(() => {
            configurarGaleria();
        }, 100);
    }
    
    function configurarGaleria() {
        console.log('🔧 Configurando galeria...');
        
        // Buscar o botão da galeria
        const btnGaleria = document.getElementById('galeria-fotos-btn');
        
        if (!btnGaleria) {
            console.error('❌ Botão galeria-fotos-btn não encontrado!');
            // Tentar novamente em 1 segundo
            setTimeout(configurarGaleria, 1000);
            return;
        }
        
        console.log('✅ Botão da galeria encontrado:', btnGaleria);
        
        // Criar os modais primeiro
        criarModaisGaleria();
        
        // Limpar qualquer event listener anterior do botão
        const novoBtnGaleria = btnGaleria.cloneNode(true);
        btnGaleria.parentNode.replaceChild(novoBtnGaleria, btnGaleria);
        
        // Adicionar event listener ao botão principal
        novoBtnGaleria.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Clique no botão galeria detectado!');
            abrirModalGaleria();
        });
        
        // Configurar event listeners globais
        configurarEventListeners();
        
        console.log('✅ Galeria configurada com sucesso!');
    }
    
    function configurarEventListeners() {
        console.log('🔗 Configurando event listeners...');
        
        // Usar delegação de eventos para evitar problemas de timing
        document.addEventListener('click', function(e) {
            const target = e.target;
            const closest = target.closest ? target.closest.bind(target) : null;
            
            // IMPORTANTE: Verificar se é um elemento da galeria antes de interceptar
            if (!isGaleriaElement(target)) {
                return; // Não interferir com outros elementos
            }
            
            // Botão Nova Galeria
            if (target.id === 'btn-nova-galeria' || (closest && closest('#btn-nova-galeria'))) {
                e.preventDefault();
                console.log('🆕 Botão Nova Galeria clicado');
                abrirModalNovaGaleria();
                return;
            }
            
            // Botão Cancelar Galeria
            if (target.id === 'btn-cancelar-galeria' || (closest && closest('#btn-cancelar-galeria'))) {
                e.preventDefault();
                console.log('❌ Botão Cancelar clicado');
                fecharModalNovaGaleria();
                return;
            }
            
            // Botões de fechar modais
            if (target.id === 'closeGaleriaModal') {
                console.log('❌ Fechando modal principal');
                const modal = document.getElementById('galeriaModal');
                if (modal) modal.style.display = 'none';
                return;
            }
            
            if (target.id === 'closeNovaGaleriaModal') {
                console.log('❌ Fechando modal nova galeria');
                fecharModalNovaGaleria();
                return;
            }
            
            if (target.id === 'closeDetalhesGaleriaModal') {
                console.log('❌ Fechando modal detalhes');
                const modal = document.getElementById('detalhesGaleriaModal');
                if (modal) modal.style.display = 'none';
                return;
            }
            
            // Botões Ver Galeria
            if (target.classList.contains('btn-ver-galeria') || (closest && closest('.btn-ver-galeria'))) {
                e.preventDefault();
                const button = target.classList.contains('btn-ver-galeria') ? target : closest('.btn-ver-galeria');
                const galeriaId = button.getAttribute('data-galeria-id');
                console.log('👁️ Ver galeria:', galeriaId);
                verDetalhesGaleria(galeriaId);
                return;
            }
            
            // Botões Excluir Galeria
            if (target.classList.contains('btn-excluir-galeria') || (closest && closest('.btn-excluir-galeria'))) {
                e.preventDefault();
                const button = target.classList.contains('btn-excluir-galeria') ? target : closest('.btn-excluir-galeria');
                const galeriaId = button.getAttribute('data-galeria-id');
                console.log('🗑️ Excluir galeria:', galeriaId);
                excluirGaleria(galeriaId);
                return;
            }
            
            // Botões de upload
            if (target.id === 'add-more-files' || target.id === 'add-more-files-preview') {
                e.preventDefault();
                console.log('➕ Adicionar mais arquivos');
                const inputArquivos = document.getElementById('arquivos-galeria');
                if (inputArquivos) inputArquivos.click();
                return;
            }
            
            if (target.id === 'clear-files' || target.id === 'clear-all-files-preview') {
                e.preventDefault();
                console.log('🗑️ Limpar arquivos');
                limparTodosArquivos();
                return;
            }
            
            // Botões remover arquivo individual
            if (target.classList.contains('remove-file')) {
                e.preventDefault();
                const index = parseInt(target.closest('.preview-item').getAttribute('data-index'));
                console.log('🗑️ Remover arquivo index:', index);
                removerArquivo(index);
                return;
            }
        });
        
        // Event listener para formulário
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'form-galeria') {
                e.preventDefault();
                console.log('📤 Enviando formulário da galeria');
                enviarGaleria(e);
            }
        });
        
        // Fechar modais ao clicar fora
        window.addEventListener('click', function(event) {
            const modals = ['galeriaModal', 'novaGaleriaModal', 'detalhesGaleriaModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    console.log('❌ Fechando modal por clique fora:', modalId);
                    modal.style.display = 'none';
                }
            });
        });
    }
    
    // Função para verificar se o elemento pertence à galeria
    function isGaleriaElement(element) {
        if (!element) return false;
        
        // Lista de IDs e classes relacionados à galeria
        const galeriaIds = [
            'btn-nova-galeria', 'btn-cancelar-galeria', 'closeGaleriaModal', 
            'closeNovaGaleriaModal', 'closeDetalhesGaleriaModal', 
            'add-more-files', 'add-more-files-preview', 'clear-files', 
            'clear-all-files-preview', 'form-galeria'
        ];
        
        const galeriaClasses = [
            'btn-ver-galeria', 'btn-excluir-galeria', 'remove-file'
        ];
        
        const galeriaContainers = [
            'galeriaModal', 'novaGaleriaModal', 'detalhesGaleriaModal',
            'preview-arquivos', 'upload-area'
        ];
        
        // Verificar ID do elemento
        if (galeriaIds.includes(element.id)) return true;
        
        // Verificar classes do elemento
        for (let className of galeriaClasses) {
            if (element.classList && element.classList.contains(className)) return true;
        }
        
        // Verificar se está dentro de um container da galeria
        for (let containerId of galeriaContainers) {
            if (element.closest && element.closest(`#${containerId}`)) return true;
        }
        
        return false;
    }
    
    function abrirModalGaleria() {
        console.log('📂 Abrindo modal da galeria...');
        
        let modal = document.getElementById('galeriaModal');
        
        if (!modal) {
            console.log('⚠️ Modal não encontrado, criando...');
            criarModaisGaleria();
            modal = document.getElementById('galeriaModal');
        }
        
        if (modal) {
            modal.style.display = 'block';
            console.log('✅ Modal da galeria aberto!');
            carregarGalerias();
        } else {
            console.error('❌ Erro: Não foi possível criar/abrir o modal da galeria');
        }
    }
    
    function abrirModalNovaGaleria() {
        console.log('➕ Abrindo modal nova galeria...');
        
        const modal = document.getElementById('novaGaleriaModal');
        if (!modal) {
            console.error('❌ Modal nova galeria não encontrado');
            return;
        }
        
        modal.style.display = 'block';
        
        // Reset completo
        arquivosSelecionados = [];
        
        const form = document.getElementById('form-galeria');
        if (form) form.reset();
        
        const inputArquivos = document.getElementById('arquivos-galeria');
        if (inputArquivos) inputArquivos.value = '';
        
        const previewContainer = document.getElementById('preview-arquivos');
        if (previewContainer) previewContainer.innerHTML = '';
        
        atualizarStatusArquivos(0);
        
        const mensagemContainer = document.getElementById('mensagem-galeria');
        if (mensagemContainer) mensagemContainer.innerHTML = '';
        
        // Carregar dados
        carregarTurmasSelect();
        
        // Configurar upload
        setTimeout(() => {
            configurarUploadArquivos();
        }, 200);
        
        console.log('✅ Modal nova galeria aberto e configurado!');
    }
    
    function fecharModalNovaGaleria() {
        console.log('❌ Fechando modal nova galeria...');
        const modal = document.getElementById('novaGaleriaModal');
        if (modal) {
            modal.style.display = 'none';
            arquivosSelecionados = [];
        }
    }
    
    function criarModaisGaleria() {
        console.log('🏗️ Criando modais da galeria...');
        
        // Verificar se já existem
        if (document.getElementById('galeriaModal')) {
            console.log('ℹ️ Modais já existem');
            return;
        }
        
        // Modal principal da galeria
        const galeriaModal = document.createElement('div');
        galeriaModal.id = 'galeriaModal';
        galeriaModal.className = 'galeria-modal-overlay';
        galeriaModal.innerHTML = `
            <div class="galeria-modal-content">
                <span class="galeria-close" id="closeGaleriaModal">&times;</span>
                <h2>Galeria de Fotos</h2>
                
                <div style="margin-bottom: 20px; text-align: right;">
                    <button id="btn-nova-galeria" class="galeria-btn galeria-btn-primary">
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
        novaGaleriaModal.className = 'galeria-modal-overlay';
        novaGaleriaModal.innerHTML = `
            <div class="galeria-modal-content">
                <span class="galeria-close" id="closeNovaGaleriaModal">&times;</span>
                <h2>Nova Galeria</h2>
                
                <div id="mensagem-galeria"></div>
                
                <form id="form-galeria" enctype="multipart/form-data">
                    <div class="galeria-form-group">
                        <label for="titulo-galeria" class="galeria-form-label">Título <span style="color: red;">*</span></label>
                        <input type="text" id="titulo-galeria" name="titulo" class="galeria-form-control" required 
                            placeholder="Digite o título da galeria">
                    </div>
                    
                    <div class="galeria-form-row">
                        <div class="galeria-form-col">
                            <div class="galeria-form-group">
                                <label for="turma-galeria" class="galeria-form-label">Turma <span style="color: red;">*</span></label>
                                <select id="turma-galeria" name="turma_id" class="galeria-form-control" required>
                                    <option value="">Carregando turmas...</option>
                                </select>
                            </div>
                        </div>
                        <div class="galeria-form-col">
                            <div class="galeria-form-group">
                                <label for="atividade-galeria" class="galeria-form-label">Atividade Realizada <span style="color: red;">*</span></label>
                                <input type="text" id="atividade-galeria" name="atividade_realizada" class="galeria-form-control" required 
                                    placeholder="Ex: Treino de futebol, Competição...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="galeria-form-group">
                        <label for="descricao-galeria" class="galeria-form-label">Descrição</label>
                        <textarea id="descricao-galeria" name="descricao" class="galeria-form-control" rows="3"
                                placeholder="Descrição opcional sobre a galeria..."></textarea>
                    </div>
                    
                    <div class="galeria-form-group">
                        <label for="arquivos-galeria" class="galeria-form-label">Fotos e Vídeos</label>
                        <div class="galeria-upload-area" id="upload-area">
                            <input type="file" id="arquivos-galeria" name="arquivos[]" class="galeria-form-control" 
                                multiple accept="image/*,video/*" style="display: none;">
                            <div class="galeria-upload-placeholder" id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p><strong>Clique aqui ou arraste arquivos</strong></p>
                                <p>Selecione múltiplas fotos e vídeos</p>
                                <p><small>JPG, PNG, GIF, MP4, AVI, MOV - Máximo 50MB por arquivo</small></p>
                            </div>
                            <div class="galeria-files-selected" id="files-selected" style="display: none;">
                                <p><strong><span id="files-count">0</span> arquivo(s) selecionado(s)</strong></p>
                                <button type="button" id="add-more-files" class="galeria-btn galeria-btn-success galeria-btn-sm">
                                    <i class="fas fa-plus"></i> Adicionar Mais
                                </button>
                                <button type="button" id="clear-files" class="galeria-btn galeria-btn-danger galeria-btn-sm">
                                    <i class="fas fa-trash"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="preview-arquivos"></div>
                    
                    <div class="galeria-text-center">
                        <button type="submit" class="galeria-btn galeria-btn-primary">
                            <i class="fas fa-save"></i> Criar Galeria
                        </button>
                        <button type="button" id="btn-cancelar-galeria" class="galeria-btn galeria-btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        // Modal para ver detalhes da galeria
        const detalhesGaleriaModal = document.createElement('div');
        detalhesGaleriaModal.id = 'detalhesGaleriaModal';
        detalhesGaleriaModal.className = 'galeria-modal-overlay';
        detalhesGaleriaModal.innerHTML = `
            <div class="galeria-modal-content galeria-modal-content-large">
                <span class="galeria-close" id="closeDetalhesGaleriaModal">&times;</span>
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
        
        // Adicionar estilos CSS ESPECÍFICOS da galeria
        adicionarEstilosGaleria();
        
        console.log('✅ Modais criados com sucesso!');
    }
    
    function adicionarEstilosGaleria() {
        if (document.getElementById('galeria-styles-specific')) {
            return;
        }
        
        const style = document.createElement('style');
        style.id = 'galeria-styles-specific';
        style.textContent = `
            /* ESTILOS ESPECÍFICOS DA GALERIA - NÃO INTERFEREM EM OUTROS ELEMENTOS */
            .galeria-modal-overlay {
                display: none;
                position: fixed;
                z-index: 99999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.8);
                backdrop-filter: blur(5px);
            }
            
            .galeria-modal-content {
                background-color: #ffffff;
                margin: 2% auto;
                padding: 30px;
                border: none;
                border-radius: 12px;
                width: 95%;
                max-width: 900px;
                max-height: 95vh;
                overflow-y: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                animation: galeriaModalSlideIn 0.3s ease;
                position: relative;
            }
            
            .galeria-modal-content-large {
                max-width: 95%;
                width: 1200px;
            }
            
            @keyframes galeriaModalSlideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .galeria-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                line-height: 1;
                padding: 0;
                background: none;
                border: none;
                position: absolute;
                top: 15px;
                right: 25px;
            }
            
            .galeria-close:hover,
            .galeria-close:focus {
                color: #000;
                text-decoration: none;
            }
            
            .galeria-upload-area {
                border: 2px dashed #ddd;
                border-radius: 8px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                background-color: #fafafa;
            }
            
            .galeria-upload-area:hover {
                border-color: #007bff;
                background-color: #f8f9fa;
            }
            
            .galeria-upload-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            
            .galeria-files-selected {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .galeria-files-selected .galeria-btn {
                margin: 0 5px;
            }
            
            .galeria-preview-container {
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
            
            .galeria-preview-item {
                position: relative;
                border: 2px solid #e9ecef;
                border-radius: 10px;
                overflow: hidden;
                background-color: #fff;
                transition: all 0.3s ease;
                cursor: pointer;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .galeria-preview-item:hover {
                transform: scale(1.05);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                border-color: #007bff;
            }
            
            .galeria-preview-item img,
            .galeria-preview-item video {
                width: 100%;
                height: 120px;
                object-fit: cover;
            }
            
            .galeria-preview-item .galeria-file-info {
                padding: 10px;
                font-size: 0.75rem;
                text-align: center;
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                border-top: 1px solid #dee2e6;
                font-weight: 600;
            }
            
            .galeria-preview-item .remove-file {
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
            
            .galeria-preview-item .remove-file:hover {
                background: linear-gradient(135deg, #c82333, #bd2130);
                transform: scale(1.1);
            }
            
            .galeria-preview-header {
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
            
            .galeria-preview-header .galeria-btn {
                background: rgba(255,255,255,0.2) !important;
                border: 1px solid rgba(255,255,255,0.3) !important;
                color: white !important;
                font-weight: 600;
            }
            
            .galeria-preview-header .galeria-btn:hover {
                background: rgba(255,255,255,0.3) !important;
                transform: translateY(-2px);
            }
            
            .galeria-item {
                background-color: #fff;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                border-left: 4px solid #6c757d;
                transition: all 0.3s ease;
            }
            
            .galeria-item:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            }
            
            .galeria-item h3 {
                color: #6c757d;
                margin-bottom: 15px;
                font-size: 1.2rem;
                font-weight: 600;
                border-bottom: 1px solid #e9ecef;
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
                color: #6c757d;
                font-size: 0.9rem;
                margin-bottom: 2px;
            }
            
            .galeria-field span {
                color: #333;
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
                background: linear-gradient(135deg, #dc3545, #c82333);
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
                color: #007bff;
                margin-bottom: 15px;
                font-size: 1.1rem;
                border-bottom: 1px solid #e9ecef;
                padding-bottom: 8px;
            }
            
            .galeria-arquivos-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .galeria-arquivo-item {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
                cursor: pointer;
            }
            
            .galeria-arquivo-item:hover {
                transform: scale(1.05);
            }
            
            .galeria-arquivo-item img,
            .galeria-arquivo-item video {
                width: 100%;
                height: 150px;
                object-fit: cover;
            }
            
            .galeria-arquivo-info {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(transparent, rgba(0,0,0,0.7));
                color: white;
                padding: 10px;
                font-size: 0.8rem;
            }
            
            .galeria-lightbox {
                display: none;
                position: fixed;
                z-index: 999999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.9);
            }
            
            .galeria-lightbox-content {
                position: relative;
                margin: auto;
                padding: 20px;
                width: 90%;
                max-width: 800px;
                top: 50%;
                transform: translateY(-50%);
                text-align: center;
            }
            
            .galeria-lightbox img,
            .galeria-lightbox video {
                max-width: 100%;
                max-height: 80vh;
                border-radius: 8px;
            }
            
            .galeria-lightbox-close {
                position: absolute;
                top: 10px;
                right: 25px;
                color: white;
                font-size: 35px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .galeria-lightbox-close:hover {
                color: #ccc;
            }
            
            .galeria-alert {
                padding: 12px 16px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            
            .galeria-alert-info {
                color: #0c5460;
                background-color: #d1ecf1;
                border-color: #bee5eb;
            }
            
            .galeria-alert-danger {
                color: #721c24;
                background-color: #f8d7da;
                border-color: #f5c6cb;
            }
            
            .galeria-alert-success {
                color: #155724;
                background-color: #d4edda;
                border-color: #c3e6cb;
            }
            
            .galeria-form-group {
                margin-bottom: 1rem;
            }
            
            .galeria-form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: #333;
            }
            
            .galeria-form-control {
                display: block;
                width: 100%;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                color: #495057;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                box-sizing: border-box;
            }
            
            .galeria-form-control:focus {
                color: #495057;
                background-color: #fff;
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }
            
            .galeria-form-row {
                display: flex;
                gap: 1rem;
            }
            
            .galeria-form-col {
                flex: 1;
            }
            
            .galeria-btn {
                display: inline-block;
                padding: 0.375rem 0.75rem;
                margin-bottom: 0;
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.42857143;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                cursor: pointer;
                border: 1px solid transparent;
                border-radius: 0.25rem;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            
            .galeria-btn:hover {
                text-decoration: none;
            }
            
            .galeria-btn-primary {
                color: #fff;
                background: linear-gradient(135deg, #007bff, #0056b3);
                border-color: #007bff;
            }
            
            .galeria-btn-primary:hover {
                background: linear-gradient(135deg, #0056b3, #004085);
                border-color: #0056b3;
                transform: translateY(-2px);
            }
            
            .galeria-btn-secondary {
                color: #fff;
                background: linear-gradient(135deg, #6c757d, #5a6268);
                border-color: #6c757d;
            }
            
            .galeria-btn-secondary:hover {
                background: linear-gradient(135deg, #5a6268, #545b62);
                border-color: #5a6268;
            }
            
            .galeria-btn-success {
                color: #fff;
                background: linear-gradient(135deg, #28a745, #1e7e34);
                border-color: #28a745;
            }
            
            .galeria-btn-success:hover {
                background: linear-gradient(135deg, #1e7e34, #155724);
                border-color: #1e7e34;
            }
            
            .galeria-btn-danger {
                color: #fff;
                background: linear-gradient(135deg, #dc3545, #c82333);
                border-color: #dc3545;
            }
            
            .galeria-btn-danger:hover {
                background: linear-gradient(135deg, #c82333, #bd2130);
                border-color: #c82333;
            }
            
            .galeria-btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
                line-height: 1.5;
                border-radius: 0.2rem;
            }
            
            .galeria-text-center {
                text-align: center;
            }
            
            @media (max-width: 768px) {
                .galeria-modal-content {
                    margin: 1% auto;
                    width: 98%;
                    padding: 20px;
                }
                
                .galeria-modal-content-large {
                    width: 98%;
                    padding: 20px;
                }
                
                .galeria-arquivos-grid {
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 10px;
                }
                
                .galeria-preview-container {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                }
                
                .galeria-form-row {
                    flex-direction: column;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    // [RESTO DAS FUNÇÕES MANTIDAS IGUAIS - configurarUploadArquivos, adicionarArquivos, etc...]
    
    function configurarUploadArquivos() {
        console.log('📁 Configurando upload de arquivos...');
        
        const uploadArea = document.getElementById('upload-area');
        const inputArquivos = document.getElementById('arquivos-galeria');
        
        if (!uploadArea || !inputArquivos) {
            console.warn('⚠️ Elementos de upload não encontrados, tentando novamente...');
            setTimeout(configurarUploadArquivos, 500);
            return;
        }
        
        console.log('✅ Elementos de upload encontrados');
        
        // Limpar listeners anteriores
        const newUploadArea = uploadArea.cloneNode(true);
        uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);
        
        const newInputArquivos = document.getElementById('arquivos-galeria');
        
        // Clique na área de upload
        newUploadArea.addEventListener('click', function(e) {
            if (e.target.classList.contains('galeria-btn') || e.target.closest('.galeria-btn')) {
                return;
            }
            console.log('📁 Clique na área de upload - abrindo seletor');
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
            console.log('📁 Arquivos arrastados:', files.length);
            
            if (files.length > 0) {
                adicionarArquivos(files);
            }
        });
        
        // Event listener para mudança no input
        newInputArquivos.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            console.log('📁 Arquivos selecionados via input:', files.length);
            
            if (files.length > 0) {
                const isAdding = arquivosSelecionados.length > 0;
                
                if (isAdding) {
                    console.log('➕ Adicionando aos arquivos existentes');
                    adicionarArquivos(files);
                } else {
                    console.log('🆕 Primeira seleção de arquivos');
                    arquivosSelecionados = [...files];
                    atualizarInputEPreview();
                }
            }
        });
        
        console.log('✅ Upload configurado com sucesso!');
    }
    
    function adicionarArquivos(novosArquivos) {
        console.log('➕ Adicionando', novosArquivos.length, 'arquivos aos', arquivosSelecionados.length, 'existentes');
        
        novosArquivos.forEach(novoArquivo => {
            const jaExiste = arquivosSelecionados.some(arquivo => 
                arquivo.name === novoArquivo.name && arquivo.size === novoArquivo.size
            );
            
            if (!jaExiste) {
                arquivosSelecionados.push(novoArquivo);
                console.log('✅ Arquivo adicionado:', novoArquivo.name);
            } else {
                console.warn('⚠️ Arquivo duplicado ignorado:', novoArquivo.name);
            }
        });
        
        atualizarInputEPreview();
    }
    
    function atualizarInputEPreview() {
        const inputArquivos = document.getElementById('arquivos-galeria');
        
        if (inputArquivos && arquivosSelecionados.length > 0) {
            const dt = new DataTransfer();
            arquivosSelecionados.forEach(arquivo => {
                dt.items.add(arquivo);
            });
            inputArquivos.files = dt.files;
            
            console.log('🔄 Input atualizado com', arquivosSelecionados.length, 'arquivos');
            
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
    
    function previewArquivos(files) {
        const container = document.getElementById('preview-arquivos');
        if (!container) {
            console.error('❌ Container preview-arquivos não encontrado');
            return;
        }
        
        container.innerHTML = '';
        
        if (!files || files.length === 0) {
            atualizarStatusArquivos(0);
            console.log('ℹ️ Nenhum arquivo para preview');
            return;
        }
        
        console.log('🖼️ Criando preview para', files.length, 'arquivos');
        
        // Header
        const header = document.createElement('div');
        header.className = 'galeria-preview-header';
        header.innerHTML = `
            <div>
                <strong style="font-size: 1.2rem;">
                    <i class="fas fa-images" style="margin-right: 10px;"></i>
                    ${files.length} arquivo(s) selecionado(s)
                </strong>
            </div>
            <div>
                <button type="button" id="add-more-files-preview" class="galeria-btn galeria-btn-sm">
                    <i class="fas fa-plus"></i> Adicionar Mais
                </button>
                <button type="button" id="clear-all-files-preview" class="galeria-btn galeria-btn-sm">
                    <i class="fas fa-trash"></i> Limpar Todos
                </button>
            </div>
        `;
        container.appendChild(header);
        
        // Container grid
        const gridContainer = document.createElement('div');
        gridContainer.className = 'galeria-preview-container';
        container.appendChild(gridContainer);
        
        // Criar preview para cada arquivo
        files.forEach((file, index) => {
            const isImage = file.type.startsWith('image/');
            const isVideo = file.type.startsWith('video/');
            
            if (!isImage && !isVideo) return;
            
            const div = document.createElement('div');
            div.className = 'galeria-preview-item preview-item';
            div.setAttribute('data-index', index);
            
            // Botão remover
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.innerHTML = '×';
            removeBtn.type = 'button';
            
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
            info.className = 'galeria-file-info';
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
    
    function limparTodosArquivos() {
        console.log('🗑️ Limpando todos os arquivos');
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
    }
    
    function removerArquivo(indexToRemove) {
        console.log('🗑️ Removendo arquivo index:', indexToRemove, 'de', arquivosSelecionados.length);
        
        arquivosSelecionados.splice(indexToRemove, 1);
        atualizarInputEPreview();
        
        console.log('✅ Arquivo removido. Restantes:', arquivosSelecionados.length);
    }
    
    function carregarGalerias() {
        console.log('📂 Carregando lista de galerias...');
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
                        console.log('✅ Galerias carregadas:', data.galerias.length);
                    } else {
                        container.innerHTML = '<div class="galeria-alert galeria-alert-info">Nenhuma galeria encontrada.</div>';
                    }
                } else {
                    container.innerHTML = `<div class="galeria-alert galeria-alert-danger">${data.message || 'Erro ao carregar galerias.'}</div>`;
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar galerias:', error);
                container.innerHTML = `<div class="galeria-alert galeria-alert-danger">Erro de conexão: ${error.message}</div>`;
            });
    }
    
    function carregarTurmasSelect() {
    console.log('🏫 Carregando turmas...');
    const select = document.getElementById('turma-galeria');
    const turmaLabel = document.querySelector('label[for="turma-galeria"]');
    
    if (!select) return;
    
    // Detectar se é admin via variável do PHP
    const isAdmin = window.IS_ADMIN || false;
    console.log('👤 Tipo de usuário:', isAdmin ? 'ADMIN' : 'PROFESSOR');
    
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
                if (isAdmin) {
                    // ✅ ADMIN: Turma é OPCIONAL
                    select.innerHTML = '<option value="">🎯 Sem turma específica (Opcional)</option>';
                    select.required = false;
                    
                    // Atualizar label para mostrar que é opcional
                    if (turmaLabel) {
                        turmaLabel.innerHTML = 'Turma <span style="color: #666; font-weight: normal;">(Opcional para Admin)</span>';
                    }
                    
                    if (data.turmas && data.turmas.length > 0) {
                        data.turmas.forEach(turma => {
                            select.innerHTML += `<option value="${turma.id}">${turma.nome_turma} - ${turma.unidade_nome}</option>`;
                        });
                        console.log('✅ Admin: ' + data.turmas.length + ' turmas carregadas (opcional)');
                    }
                } else {
                    // ✅ PROFESSOR: Turma é OBRIGATÓRIA
                    select.innerHTML = '<option value="">Selecione uma turma</option>';
                    select.required = true;
                    
                    // Manter label original
                    if (turmaLabel) {
                        turmaLabel.innerHTML = 'Turma <span style="color: red;">*</span>';
                    }
                    
                    if (data.turmas && data.turmas.length > 0) {
                        data.turmas.forEach(turma => {
                            select.innerHTML += `<option value="${turma.id}">${turma.nome_turma} - ${turma.unidade_nome}</option>`;
                        });
                        console.log('✅ Professor: ' + data.turmas.length + ' turmas carregadas (obrigatório)');
                    } else {
                        select.innerHTML = '<option value="">❌ Nenhuma turma atribuída a você</option>';
                        console.warn('⚠️ Professor sem turmas atribuídas');
                    }
                }
            } else {
                select.innerHTML = '<option value="">Erro ao carregar turmas</option>';
                console.error('❌ Erro na resposta:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar turmas:', error);
            select.innerHTML = '<option value="">Erro ao carregar turmas</option>';
        });
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
    
    console.log('📤 Iniciando envio da galeria');
    
    const titulo = document.getElementById('titulo-galeria').value.trim();
    const turmaSelect = document.getElementById('turma-galeria');
    const turmaId = turmaSelect.value;
    const atividade = document.getElementById('atividade-galeria').value.trim();
    const isAdmin = window.IS_ADMIN || false;
    
    console.log('📋 Dados do formulário:', {
        titulo,
        turmaId: turmaId || 'Sem turma',
        atividade,
        isAdmin,
        turmaObrigatoria: turmaSelect.required,
        arquivos: arquivosSelecionados.length
    });
    
    // Validações básicas
    if (!titulo || !atividade) {
        showMessage('mensagem-galeria', 'Preencha todos os campos obrigatórios.', 'danger');
        return;
    }
    
    // ✅ VALIDAÇÃO CONDICIONAL DA TURMA
    if (!isAdmin && !turmaId) {
        // Professor DEVE selecionar turma
        showMessage('mensagem-galeria', 'Selecione uma turma válida.', 'danger');
        return;
    }
    
    if (!validarArquivos()) {
        return;
    }
    
    const formData = new FormData(e.target);
    formData.append('action', 'criar');
    
    // Status message personalizado
    let statusMsg;
    if (isAdmin && !turmaId) {
        statusMsg = `📂 Criando galeria geral (sem turma) com ${arquivosSelecionados.length} arquivo(s)...`;
    } else if (isAdmin && turmaId) {
        statusMsg = `📂 Admin criando galeria para turma específica com ${arquivosSelecionados.length} arquivo(s)...`;
    } else {
        statusMsg = `📂 Enviando galeria com ${arquivosSelecionados.length} arquivo(s)...`;
    }
    
    showMessage('mensagem-galeria', statusMsg, 'info');
    
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
        console.log('📥 Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📥 Response data:', data);
        
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
        console.error('❌ Erro completo:', error);
        showMessage('mensagem-galeria', `Erro: ${error.message}`, 'danger');
    })
    .finally(() => {
        if (btnEnviar) {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="fas fa-save"></i> Criar Galeria';
        }
    });
}
    
    function verDetalhesGaleria(galeriaId) {
        console.log('👁️ Ver detalhes da galeria:', galeriaId);
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
                                <div class="galeria-arquivos-grid">
                        `;
                        
                        galeria.arquivos.forEach(arquivo => {
                            const isVideo = arquivo.tipo_arquivo === 'video';
                            let caminhoCorreto = arquivo.caminho;
                            
                            // Corrigir caminho
                            caminhoCorreto = caminhoCorreto.replace(/^\.\.\//, '');
                            if (!caminhoCorreto.startsWith('/luis/bombeiros_mirim/')) {
                                if (caminhoCorreto.startsWith('uploads/')) {
                                    caminhoCorreto = `/luis/bombeiros_mirim/${caminhoCorreto}`;
                                } else {
                                    caminhoCorreto = `/luis/bombeiros_mirim/uploads/galeria/${caminhoCorreto.split('/').slice(-3).join('/')}`;
                                }
                            }
                            
                            html += `
                                <div class="galeria-arquivo-item" onclick="abrirLightbox('${caminhoCorreto}', '${arquivo.tipo_arquivo}', '${arquivo.nome_original}')">
                                    ${isVideo ? 
                                        `<video src="${caminhoCorreto}" muted></video>` :
                                        `<img src="${caminhoCorreto}" alt="${arquivo.nome_original}" 
                                             onerror="this.src='/luis/bombeiros_mirim/uploads/fotos/default.png';">`
                                    }
                                    <div class="galeria-arquivo-info">
                                        <div>${arquivo.nome_original}</div>
                                        <div>${isVideo ? 'Vídeo' : 'Imagem'} • ${formatarTamanho(arquivo.tamanho)}</div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div></div>';
                    } else {
                        html += '<div class="galeria-alert galeria-alert-info">Nenhum arquivo encontrado nesta galeria.</div>';
                    }
                    
                    container.innerHTML = html;
                    criarLightbox();
                    
                } else {
                    container.innerHTML = `<div class="galeria-alert galeria-alert-danger">${data.message || 'Erro ao carregar detalhes.'}</div>`;
                }
            })
            .catch(error => {
                console.error('❌ Erro:', error);
                container.innerHTML = `<div class="galeria-alert galeria-alert-danger">Erro de conexão: ${error.message}</div>`;
            });
    }
    
    function excluirGaleria(galeriaId) {
        if (!confirm('Tem certeza que deseja excluir esta galeria? Todos os arquivos serão removidos permanentemente.')) {
            return;
        }
        
        console.log('🗑️ Excluindo galeria:', galeriaId);
        
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
            console.error('❌ Erro:', error);
            alert('Erro de conexão');
        });
    }
    
    function criarLightbox() {
        if (document.getElementById('galeria-lightbox')) {
            return;
        }
        
        const lightbox = document.createElement('div');
        lightbox.id = 'galeria-lightbox';
        lightbox.className = 'galeria-lightbox';
        lightbox.innerHTML = `
            <div class="galeria-lightbox-content">
                <span class="galeria-lightbox-close" onclick="fecharLightbox()">&times;</span>
                <div id="galeria-lightbox-media"></div>
            </div>
        `;
        
        document.body.appendChild(lightbox);
    }
    
    function formatarData(dataString) {
        if (!dataString) return 'Data não informada';
        try {
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR') + ' às ' + data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        } catch (error) {
            console.error('❌ Erro ao formatar data:', error);
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
            element.innerHTML = `<div class="galeria-alert galeria-alert-${type}">${message}</div>`;
            setTimeout(() => {
                if (type === 'success') {
                    element.innerHTML = '';
                }
            }, 5000);
        }
    }
    
    // Expor funções globalmente para uso em onclick
    window.abrirLightbox = function(caminho, tipo, nomeOriginal) {
        console.log('🔍 Abrindo lightbox:', caminho);
        const lightbox = document.getElementById('galeria-lightbox');
        const mediaContainer = document.getElementById('galeria-lightbox-media');
        
        if (!lightbox || !mediaContainer) return;
        
        if (tipo === 'video') {
            mediaContainer.innerHTML = `
                <video src="${caminho}" controls autoplay>
                    Seu navegador não suporta vídeos.
                </video>
                <p style="color: white; margin-top: 10px;">${nomeOriginal}</p>
            `;
        } else {
            mediaContainer.innerHTML = `
                <img src="${caminho}" alt="${nomeOriginal}" onerror="console.error('❌ Erro ao carregar imagem:', '${caminho}')">
                <p style="color: white; margin-top: 10px;">${nomeOriginal}</p>
            `;
        }
        
        lightbox.style.display = 'block';
    };
    
    window.fecharLightbox = function() {
        const lightbox = document.getElementById('galeria-lightbox');
        if (lightbox) {
            lightbox.style.display = 'none';
        }
    };
    
    console.log('✅ Galeria JS FINAL - Versão Corrigida sem Interferências!');
    
})();