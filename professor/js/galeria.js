// galeria.js - Módulo para gerenciar galeria de fotos
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
    
    // Preview de arquivos selecionados
    document.getElementById('arquivos-galeria')?.addEventListener('change', function(e) {
        previewArquivos(e.target.files);
    });
});

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
                    <input type="file" id="arquivos-galeria" name="arquivos[]" class="form-control" 
                           multiple accept="image/*,video/*">
                    <small class="form-text">Selecione fotos (JPG, PNG, GIF) e vídeos (MP4, AVI, MOV). Máximo 50MB por arquivo.</small>
                </div>
                
                <div id="preview-arquivos" class="preview-container"></div>
                
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
        
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background-color: #f9f9f9;
        }
        
        .preview-item img,
        .preview-item video {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        
        .preview-item .file-info {
            padding: 5px;
            font-size: 0.8rem;
            text-align: center;
            background-color: #fff;
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
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
        document.getElementById('form-galeria').reset();
        document.getElementById('preview-arquivos').innerHTML = '';
        document.getElementById('mensagem-galeria').innerHTML = '';
    }
}

function fecharModalNovaGaleria() {
    const modal = document.getElementById('novaGaleriaModal');
    if (modal) {
        modal.style.display = 'none';
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

function previewArquivos(files) {
    const container = document.getElementById('preview-arquivos');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (files.length === 0) return;
    
    Array.from(files).forEach((file, index) => {
        const isImage = file.type.startsWith('image/');
        const isVideo = file.type.startsWith('video/');
        
        if (!isImage && !isVideo) return;
        
        const div = document.createElement('div');
        div.className = 'preview-item';
        
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
        }
        
        const info = document.createElement('div');
        info.className = 'file-info';
        info.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;
        div.appendChild(info);
        
        container.appendChild(div);
    });
}

function enviarGaleria(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'criar');
    
    // Validação básica
    const titulo = formData.get('titulo').trim();
    const turmaId = formData.get('turma_id');
    const atividade = formData.get('atividade_realizada').trim();
    
    if (!titulo || !turmaId || !atividade) {
        showMessage('mensagem-galeria', 'Preencha todos os campos obrigatórios.', 'danger');
        return;
    }
    
    // Mostrar loading
    showMessage('mensagem-galeria', 'Enviando galeria...', 'info');
    
    fetch('./api/galeria.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showMessage('mensagem-galeria', data.message || 'Galeria criada com sucesso!', 'success');
            setTimeout(() => {
                fecharModalNovaGaleria();
                carregarGalerias(); // Recarregar lista principal
            }, 2000);
        } else {
            showMessage('mensagem-galeria', data.message || 'Erro ao criar galeria.', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showMessage('mensagem-galeria', 'Erro de conexão. Tente novamente.', 'danger');
    });
}

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
                        html += `
                            <div class="arquivo-item" onclick="abrirLightbox('${arquivo.caminho}', '${arquivo.tipo_arquivo}', '${arquivo.nome_original}')">
                                ${isVideo ? 
                                    `<video src="${arquivo.caminho}" muted></video>` :
                                    `<img src="${arquivo.caminho}" alt="${arquivo.nome_original}">`
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
                
                // Criar lightbox se não existir
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
            carregarGalerias(); // Recarregar lista
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

function abrirLightbox(caminho, tipo, nomeOriginal) {
    const lightbox = document.getElementById('lightbox');
    const mediaContainer = document.getElementById('lightbox-media');
    
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
            <img src="${caminho}" alt="${nomeOriginal}">
            <p style="color: white; margin-top: 10px;">${nomeOriginal}</p>
        `;
    }
    
    lightbox.style.display = 'block';
}

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