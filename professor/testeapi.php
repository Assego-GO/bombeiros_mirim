<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API Atividades</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-success { background: #d4edda; border-color: #c3e6cb; }
        .test-error { background: #f8d7da; border-color: #f5c6cb; }
        .test-info { background: #d1ecf1; border-color: #bee5eb; }
        button { background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🧪 Teste da API de Atividades</h1>
    
    <div class="test-section test-info">
        <h3>ℹ️ Informações</h3>
        <p>Este script testa se a API de atividades está funcionando corretamente com sua estrutura de banco.</p>
        <p><strong>Professor ID usado nos testes:</strong> <span id="professor-id">13</span></p>
    </div>
    
    <div class="test-section">
        <h3>🔗 Testes de Conectividade</h3>
        <button onclick="testarConexao()">Testar Conexão</button>
        <button onclick="testarTurmas()">Testar Listar Turmas</button>
        <button onclick="testarAtividades()">Testar Listar Atividades</button>
        <div id="resultado-conexao"></div>
    </div>
    
    <div class="test-section">
        <h3>➕ Teste de Cadastro</h3>
        <button onclick="testarCadastro()">Cadastrar Atividade de Teste</button>
        <div id="resultado-cadastro"></div>
    </div>
    
    <div class="test-section">
        <h3>📊 Resultados</h3>
        <div id="resultado-geral"></div>
    </div>

    <script>
        // Simular sessão de professor para testes
        const PROFESSOR_ID = 13; // Baseado no banco fornecido
        
        function mostrarResultado(elementId, titulo, resultado, tipo = 'info') {
            const elemento = document.getElementById(elementId);
            const timestamp = new Date().toLocaleTimeString();
            
            let html = `
                <div class="test-${tipo}" style="margin: 10px 0; padding: 10px; border-radius: 4px;">
                    <h4>${titulo} - ${timestamp}</h4>
                    <pre>${JSON.stringify(resultado, null, 2)}</pre>
                </div>
            `;
            
            elemento.innerHTML = html + elemento.innerHTML;
        }
        
        async function testarConexao() {
            try {
                const response = await fetch('./api/atividades.php?action=turmas');
                const data = await response.json();
                
                if (response.ok && data.success) {
                    mostrarResultado('resultado-conexao', 'Conexão OK', {
                        status: response.status,
                        success: data.success,
                        message: 'API respondendo corretamente'
                    }, 'success');
                } else {
                    mostrarResultado('resultado-conexao', 'Erro na Conexão', {
                        status: response.status,
                        error: data.message || 'Erro desconhecido'
                    }, 'error');
                }
            } catch (error) {
                mostrarResultado('resultado-conexao', 'Erro de Rede', {
                    error: error.message
                }, 'error');
            }
        }
        
        async function testarTurmas() {
            try {
                const response = await fetch('./api/atividades.php?action=turmas');
                const data = await response.json();
                
                if (data.success) {
                    mostrarResultado('resultado-conexao', 'Turmas Carregadas', {
                        total_turmas: data.turmas?.length || 0,
                        turmas: data.turmas?.map(t => ({ id: t.id, nome: t.nome_turma })) || []
                    }, 'success');
                } else {
                    mostrarResultado('resultado-conexao', 'Erro ao Carregar Turmas', data, 'error');
                }
            } catch (error) {
                mostrarResultado('resultado-conexao', 'Erro ao Carregar Turmas', { error: error.message }, 'error');
            }
        }
        
        async function testarAtividades() {
            try {
                const response = await fetch('./api/atividades.php?action=listar');
                const data = await response.json();
                
                if (data.success) {
                    mostrarResultado('resultado-conexao', 'Atividades Carregadas', {
                        total_atividades: data.atividades?.length || 0,
                        atividades: data.atividades?.map(a => ({ 
                            id: a.id, 
                            tipo: a.nome_atividade, 
                            turma: a.nome_turma 
                        })) || []
                    }, 'success');
                } else {
                    mostrarResultado('resultado-conexao', 'Erro ao Carregar Atividades', data, 'error');
                }
            } catch (error) {
                mostrarResultado('resultado-conexao', 'Erro ao Carregar Atividades', { error: error.message }, 'error');
            }
        }
        
        async function testarCadastro() {
            // Primeiro, buscar uma turma válida
            try {
                const turmasResponse = await fetch('./api/atividades.php?action=turmas');
                const turmasData = await turmasResponse.json();
                
                if (!turmasData.success || !turmasData.turmas || turmasData.turmas.length === 0) {
                    mostrarResultado('resultado-cadastro', 'Erro', {
                        error: 'Nenhuma turma encontrada para teste'
                    }, 'error');
                    return;
                }
                
                const turmaId = turmasData.turmas[0].id;
                
                // Criar dados de teste
                const formData = new FormData();
                formData.append('action', 'cadastrar');
                formData.append('nome_atividade', 'Ed. Física');
                formData.append('turma_id', turmaId);
                formData.append('data_atividade', '2025-06-15');
                formData.append('hora_inicio', '08:00');
                formData.append('hora_termino', '09:30');
                formData.append('local_atividade', 'Quadra Esportiva - Teste');
                formData.append('instrutor_responsavel', 'Professor Teste');
                formData.append('objetivo_atividade', 'Teste de cadastro de atividade via API');
                formData.append('conteudo_abordado', 'Conteúdo de teste para verificar funcionamento da API');
                
                const response = await fetch('./api/atividades.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarResultado('resultado-cadastro', 'Cadastro de Teste Realizado', {
                        success: true,
                        atividade_id: data.atividade_id,
                        message: data.message,
                        dados_enviados: {
                            tipo: 'Ed. Física',
                            turma_id: turmaId,
                            data: '2025-06-15'
                        }
                    }, 'success');
                } else {
                    mostrarResultado('resultado-cadastro', 'Erro no Cadastro', data, 'error');
                }
            } catch (error) {
                mostrarResultado('resultado-cadastro', 'Erro no Teste de Cadastro', { 
                    error: error.message 
                }, 'error');
            }
        }
        
        // Executar teste inicial ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            mostrarResultado('resultado-geral', 'Página Carregada', {
                info: 'Testes prontos para execução',
                banco_estrutura: 'Compatível com as tabelas: atividades, turma, unidade, professor',
                tipos_atividades: [
                    'Ed. Física', 'Salvamento', 'Informática', 'Primeiro Socorros',
                    'Ordem Unida', 'Combate a Incêndio', 'Ética e Cidadania',
                    'Higiene Pessoal', 'Meio Ambiente', 'Educação no Trânsito',
                    'Temas Transversais', 'Combate uso de Drogas',
                    'ECA e Direitos Humanos', 'Treinamento de Formatura'
                ]
            });
        });
    </script>
</body>
</html>