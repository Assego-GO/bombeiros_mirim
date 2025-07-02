<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug API Alunos - Passo a Passo</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 900px; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .step h3 { margin-top: 0; color: #007bff; }
        button { padding: 8px 15px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .resultado { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .sucesso { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .erro { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        input { padding: 5px; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug API Alunos - Passo a Passo</h1>
        <p><strong>Objetivo:</strong> Identificar onde está o erro 500</p>
        
        <div class="step">
            <h3>Step 1: Teste básico PHP</h3>
            <button onclick="testar('api/debug_simples.php', 'resultado1')">Testar Step 1</button>
            <div id="resultado1"></div>
        </div>
        
        <div class="step">
            <h3>Step 2: Teste com sessão</h3>
            <button onclick="testar('api/debug_step2.php', 'resultado2')">Testar Step 2</button>
            <div id="resultado2"></div>
        </div>
        
        <div class="step">
            <h3>Step 3: Teste carregamento env_config</h3>
            <button onclick="testar('api/debug_step3.php', 'resultado3')">Testar Step 3</button>
            <div id="resultado3"></div>
        </div>
        
        <div class="step">
            <h3>Step 4: Teste conexão banco</h3>
            <button onclick="testar('api/debug_step4.php', 'resultado4')">Testar Step 4</button>
            <div id="resultado4"></div>
        </div>
        
        <div class="step">
            <h3>Step 5: Teste query completa</h3>
            <input type="number" id="turma_id" placeholder="ID da turma" value="20">
            <button onclick="testarFinal()">Testar Step 5</button>
            <div id="resultado5"></div>
        </div>
        
        <div class="step">
            <h3>Teste API Original</h3>
            <input type="number" id="turma_id_original" placeholder="ID da turma" value="20">
            <button onclick="testarOriginal()">Testar API Original</button>
            <div id="resultado_original"></div>
        </div>
    </div>

    <script>
        function testar(url, resultadoId) {
            $('#' + resultadoId).html('<div class="resultado">Testando...</div>');
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#' + resultadoId).html('<div class="resultado sucesso"><pre>' + JSON.stringify(response, null, 2) + '</pre></div>');
                },
                error: function(xhr, status, error) {
                    let errorInfo = {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    };
                    $('#' + resultadoId).html('<div class="resultado erro"><strong>ERRO:</strong><pre>' + JSON.stringify(errorInfo, null, 2) + '</pre></div>');
                }
            });
        }
        
        function testarFinal() {
            const turmaId = $('#turma_id').val();
            if (!turmaId) {
                alert('Digite o ID da turma');
                return;
            }
            
            $('#resultado5').html('<div class="resultado">Testando...</div>');
            
            $.ajax({
                url: 'api/debug_final.php?turma_id=' + turmaId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#resultado5').html('<div class="resultado sucesso"><pre>' + JSON.stringify(response, null, 2) + '</pre></div>');
                },
                error: function(xhr, status, error) {
                    let errorInfo = {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    };
                    $('#resultado5').html('<div class="resultado erro"><strong>ERRO:</strong><pre>' + JSON.stringify(errorInfo, null, 2) + '</pre></div>');
                }
            });
        }
        
        function testarOriginal() {
            const turmaId = $('#turma_id_original').val();
            if (!turmaId) {
                alert('Digite o ID da turma');
                return;
            }
            
            $('#resultado_original').html('<div class="resultado">Testando...</div>');
            
            $.ajax({
                url: 'api/buscar_alunos_turma.php?turma_id=' + turmaId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#resultado_original').html('<div class="resultado sucesso"><pre>' + JSON.stringify(response, null, 2) + '</pre></div>');
                },
                error: function(xhr, status, error) {
                    let errorInfo = {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    };
                    $('#resultado_original').html('<div class="resultado erro"><strong>ERRO:</strong><pre>' + JSON.stringify(errorInfo, null, 2) + '</pre></div>');
                }
            });
        }
    </script>
</body>
</html>