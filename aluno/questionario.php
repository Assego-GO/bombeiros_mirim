<?php
session_start();

// Verificar se o usuário está logado e é um aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    // Definir mensagem de erro na sessão
    $_SESSION['erro_login'] = "Você precisa estar logado como aluno para acessar esta página.";
    
    // Redirecionar para a página de login do aluno
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionário - Informações Adicionais/Engajamento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #e30613, #9c0202);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .form-content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .required {
            color: #e30613;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #e30613;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-option input[type="radio"] {
            accent-color: #e30613;
            width: 18px;
            height: 18px;
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .submit-btn {
            background: linear-gradient(135deg, #e30613, #9c0202);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(227, 6, 19, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .form-content {
                padding: 20px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Questionário</h1>
            <p>Informações Adicionais / Engajamento</p>
        </div>
        
        <div class="form-content">
            <div class="alert" id="alert"></div>
            
            <form id="questionarioForm" method="POST">
                <div class="form-group">
                    <label for="como_conheceu">Como conheceu o programa? <span class="required">*</span></label>
                    <textarea id="como_conheceu" name="como_conheceu" placeholder="Para análise de marketing e impacto" required></textarea>
                </div>

                <div class="form-group">
                    <label for="autorizacao_imagem">Autorização para uso de imagem: <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="imagem_sim" name="autorizacao_imagem" value="sim" required>
                            <label for="imagem_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="imagem_nao" name="autorizacao_imagem" value="nao" required>
                            <label for="imagem_nao">Não</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="vinculo_comunidade">Vínculo com a Comunidade:</label>
                    <input type="text" id="vinculo_comunidade" name="vinculo_comunidade" placeholder="ex: Associação de Moradores, Conselho Tutelar, Líder Religioso, etc.">
                </div>

                <div class="form-group">
                    <label for="engajamento_projetos">Engajamento em Projetos Sociais/Voluntariado: <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="engajamento_sim" name="engajamento_projetos" value="sim" required>
                            <label for="engajamento_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="engajamento_nao" name="engajamento_projetos" value="nao" required>
                            <label for="engajamento_nao">Não</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="grau_satisfacao">Grau de Satisfação com o Programa: <span class="required">*</span></label>
                    <select id="grau_satisfacao" name="grau_satisfacao" required>
                        <option value="">Selecione uma opção</option>
                        <option value="muito_satisfeito">Muito Satisfeito</option>
                        <option value="satisfeito">Satisfeito</option>
                        <option value="neutro">Neutro</option>
                        <option value="insatisfeito">Insatisfeito</option>
                        <option value="muito_insatisfeito">Muito Insatisfeito</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="principais_beneficios">Principais Benefícios Percebidos para a Criança:</label>
                    <textarea id="principais_beneficios" name="principais_beneficios" placeholder="Ajuda a entender o valor percebido pelo público"></textarea>
                </div>

                <div class="form-group">
                    <label for="sugestoes_criticas">Sugestões/Críticas ao Programa:</label>
                    <textarea id="sugestoes_criticas" name="sugestoes_criticas" placeholder="Feedback direto para melhorias e identificação de pontos de atrito"></textarea>
                </div>

                <div class="form-group">
                    <label for="disposicao_multiplicador">Disposição em Ser Multiplicador/Voluntário: <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="multiplicador_sim" name="disposicao_multiplicador" value="sim" required>
                            <label for="multiplicador_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="multiplicador_nao" name="disposicao_multiplicador" value="nao" required>
                            <label for="multiplicador_nao">Não</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="autoriza_contato">Autoriza Contato para Convites Específicos/Institucionais: <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="contato_sim" name="autoriza_contato" value="sim" required>
                            <label for="contato_sim">Sim</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="contato_nao" name="autoriza_contato" value="nao" required>
                            <label for="contato_nao">Não</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    Enviar Questionário
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('questionarioForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const alert = document.getElementById('alert');
            
            // Desabilita o botão e mostra loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            
            // Coleta todos os dados do formulário
            const formData = new FormData(this);
            
            // Envia via fetch
            fetch('./api/processar_questionario.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Questionário enviado com sucesso!', 'success');
                    this.reset();
                    setTimeout(() => {
                        window.location.href = './dashboard.php';
                    }, 2000);
                } else {
                    showAlert('Erro: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Erro ao enviar questionário. Tente novamente.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                // Reabilita o botão
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Questionário';
            });
        });
        
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = 'alert ' + type;
            alert.style.display = 'block';
            
            // Remove o alerta após 5 segundos
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>