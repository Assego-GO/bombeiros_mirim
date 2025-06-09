// Função para mostrar/ocultar campos condicionais
function setupConditionalFields() {
    // Criança atípica - mostra campo de laudo
    const atipicaSelect = document.getElementById('atipica');
    const laudoAtipicaGroup = document.querySelector('#laudo-atipica').closest('.form-group');
    const fotoLaudoGroup = document.querySelector('input[type="file"][accept*=".pdf"]').closest('.form-group');
    
    // Alergias/condições - mostra campo "Quais?"
    const condicaoSelect = document.getElementById('condicao-crianca');
    const condicaoDetalhesGroup = document.querySelector('#condicao-detalhes').closest('.form-group');
    
    // Medicação contínua - mostra campo "Detalhes"
    const medicacaoSelect = document.getElementById('medicacao-crianca');
    const medicacaoDetalhesGroup = document.querySelector('#medicacao-detalhes').closest('.form-group');
    
    // Cadastro único - mostra campo "Número"
    const cadastroSelect = document.getElementById('cadastro_unico');
    const numeroCadastroGroup = document.querySelector('#numero-cadunico').closest('.form-group');

    // Inicialmente ocultar todos os campos condicionais
    laudoAtipicaGroup.style.display = 'none';
    fotoLaudoGroup.style.display = 'none';
    condicaoDetalhesGroup.style.display = 'none';
    medicacaoDetalhesGroup.style.display = 'none';
    numeroCadastroGroup.style.display = 'none';

    // Criança atípica
    atipicaSelect.addEventListener('change', function() {
        if (this.value === 'sim') {
            laudoAtipicaGroup.style.display = 'block';
            document.getElementById('laudo-atipica').required = true;
        } else {
            laudoAtipicaGroup.style.display = 'none';
            fotoLaudoGroup.style.display = 'none';
            document.getElementById('laudo-atipica').required = false;
            document.getElementById('laudo-atipica').value = '';
            // Remove required do upload se estiver oculto
            const uploadLaudo = fotoLaudoGroup.querySelector('input[type="file"]');
            if (uploadLaudo) uploadLaudo.required = false;
        }
    });

    // Laudo da criança atípica
    const laudoAtipicaSelect = document.getElementById('laudo-atipica');
    laudoAtipicaSelect.addEventListener('change', function() {
        if (this.value === 'sim') {
            fotoLaudoGroup.style.display = 'block';
            const uploadLaudo = fotoLaudoGroup.querySelector('input[type="file"]');
            if (uploadLaudo) uploadLaudo.required = true;
        } else {
            fotoLaudoGroup.style.display = 'none';
            const uploadLaudo = fotoLaudoGroup.querySelector('input[type="file"]');
            if (uploadLaudo) {
                uploadLaudo.required = false;
                uploadLaudo.value = '';
            }
        }
    });

    // Alergias/condições
    condicaoSelect.addEventListener('change', function() {
        if (this.value === 'sim') {
            condicaoDetalhesGroup.style.display = 'block';
            document.getElementById('condicao-detalhes').required = true;
        } else {
            condicaoDetalhesGroup.style.display = 'none';
            document.getElementById('condicao-detalhes').required = false;
            document.getElementById('condicao-detalhes').value = '';
        }
    });

    // Medicação contínua
    medicacaoSelect.addEventListener('change', function() {
        if (this.value === 'sim') {
            medicacaoDetalhesGroup.style.display = 'block';
            document.getElementById('medicacao-detalhes').required = true;
        } else {
            medicacaoDetalhesGroup.style.display = 'none';
            document.getElementById('medicacao-detalhes').required = false;
            document.getElementById('medicacao-detalhes').value = '';
        }
    });

    // Cadastro único
    cadastroSelect.addEventListener('change', function() {
        if (this.value === 'sim') {
            numeroCadastroGroup.style.display = 'block';
            document.getElementById('numero-cadunico').required = true;
        } else {
            numeroCadastroGroup.style.display = 'none';
            document.getElementById('numero-cadunico').required = false;
            document.getElementById('numero-cadunico').value = '';
        }
    });
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', setupConditionalFields);

// Alternativa caso já tenha carregado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupConditionalFields);
} else {
    setupConditionalFields();
}


// Função para mostrar o nome do arquivo selecionado
function mostrarArquivoSelecionado() {
    // Função genérica para mostrar nome do arquivo
    function exibirNomeArquivo(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = input.closest('.foto-upload-container');
        
        input.addEventListener('change', function() {
            // Remove indicador anterior se existir
            const indicadorAnterior = container.querySelector('.arquivo-selecionado');
            if (indicadorAnterior) {
                indicadorAnterior.remove();
            }
            
            if (this.files && this.files.length > 0) {
                const arquivo = this.files[0];
                const nomeArquivo = arquivo.name;
                const tamanhoArquivo = (arquivo.size / 1024 / 1024).toFixed(2); // MB
                
                // Criar elemento para mostrar info do arquivo
                const indicador = document.createElement('div');
                indicador.className = 'arquivo-selecionado';
                indicador.style.cssText = `
                    margin-top: 8px;
                    padding: 8px 12px;
                    background-color: #e8f5e8;
                    border: 1px solid #4caf50;
                    border-radius: 4px;
                    font-size: 14px;
                    color: #2e7d32;
                `;
                
                indicador.innerHTML = `
                    <strong>✓ Arquivo selecionado:</strong><br>
                    📎 ${nomeArquivo}<br>
                    📊 Tamanho: ${tamanhoArquivo} MB
                `;
                
                container.appendChild(indicador);
            }
        });
    }
    
    // Aplicar para todos os campos de upload
    exibirNomeArquivo('foto-aluno', 'foto-aluno-container');
    exibirNomeArquivo('arquivo-laudo', 'arquivo-laudo-container');
    exibirNomeArquivo('atestado-medico', 'atestado-medico-container');
}

// Versão alternativa mais simples (apenas nome do arquivo)
function mostrarArquivoSimples() {
    const inputs = ['foto-aluno', 'arquivo-laudo', 'atestado-medico'];
    
    inputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', function() {
                const container = this.closest('.foto-upload-container');
                let indicador = container.querySelector('.nome-arquivo');
                
                if (!indicador) {
                    indicador = document.createElement('span');
                    indicador.className = 'nome-arquivo';
                    indicador.style.cssText = `
                        display: block;
                        margin-top: 5px;
                        font-size: 12px;
                        color: #666;
                        font-style: italic;
                    `;
                    container.appendChild(indicador);
                }
                
                if (this.files && this.files.length > 0) {
                    indicador.textContent = `Arquivo: ${this.files[0].name}`;
                    indicador.style.color = '#4caf50';
                } else {
                    indicador.textContent = '';
                }
            });
        }
    });
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Use uma das duas funções:
    mostrarArquivoSelecionado(); // Versão completa com tamanho
    // mostrarArquivoSimples(); // Versão simples
});

// CSS adicional (opcional - adicione no seu CSS)
const estilosCSS = `
<style>
.arquivo-selecionado {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.foto-upload-container input[type="file"] {
    margin-bottom: 5px;
}
</style>
`;

// Para adicionar o CSS automaticamente
if (!document.querySelector('#upload-styles')) {
    const style = document.createElement('style');
    style.id = 'upload-styles';
    style.textContent = `
        .arquivo-selecionado {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nome-arquivo {
            font-weight: 500;
        }
    `;
    document.head.appendChild(style);
}