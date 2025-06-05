<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação diretamente
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// Verificar se os parâmetros necessários foram fornecidos
if (!isset($_GET['aluno_id']) || !isset($_GET['turma_id'])) {
    header('Location: alunos_turma.php');
    exit;
}

$aluno_id = $_GET['aluno_id'];
$turma_id = $_GET['turma_id'];
$professor_id = $_SESSION['usuario_id'];

// Conexão com o db
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se o professor é responsável pela turma
    $query_auth = "SELECT * FROM turma WHERE id = ? AND id_professor = ?";
    $stmt_auth = $db->prepare($query_auth);
    $stmt_auth->execute([$turma_id, $professor_id]);
    
    if ($stmt_auth->rowCount() == 0) {
        header('Location: alunos_turma.php');
        exit;
    }
    
    // Obter informações do aluno, incluindo a foto
    $query = "SELECT a.nome, a.serie, a.numero_matricula, a.foto, t.nome_turma 
              FROM alunos a
              JOIN matriculas m ON a.id = m.aluno_id
              JOIN turma t ON m.turma = t.id
              WHERE a.id = ? AND m.turma = ?";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $aluno_id);
    $stmt->bindParam(2, $turma_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        header('Location: alunos_turma.php');
        exit;
    }

    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Processar o caminho da foto
    $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $baseUrl = $serverUrl . '';
    
    // Processar o caminho da foto
    if (!empty($aluno['foto'])) {
        $filename = basename($aluno['foto']);
        $fotoPath = $baseUrl . '/uploads/fotos/' . $filename;
    } else {
        $fotoPath = $baseUrl . '/uploads/fotos/default.png';
    }
    
    // Verificar se já existe avaliação para este aluno nesta turma
    $query_avaliacao = "SELECT * FROM avaliacoes WHERE aluno_id = ? AND turma_id = ? ORDER BY data_avaliacao DESC LIMIT 1";
    $stmt_avaliacao = $db->prepare($query_avaliacao);
    $stmt_avaliacao->execute([$aluno_id, $turma_id]);
    $avaliacao = $stmt_avaliacao->fetch(PDO::FETCH_ASSOC);
    
    // Processar o formulário quando enviado
    $mensagem = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Capturar todos os campos do formulário
        $altura = !empty($_POST['altura']) ? str_replace(',', '.', $_POST['altura']) : null;
        $peso = !empty($_POST['peso']) ? str_replace(',', '.', $_POST['peso']) : null;
        $imc = null;
        $imc_status = null;
        
        // Calcular o IMC se altura e peso forem fornecidos
        if (!empty($altura) && !empty($peso) && $altura > 0) {
            $altura_metros = $altura;
            
            // Verificar se a altura está em centímetros e converter para metros
            if ($altura > 3) {
                $altura_metros = $altura / 100;
            }
            
            $imc = round($peso / ($altura_metros * $altura_metros), 1);
            
            // Determinar status do IMC
            if ($imc < 18.5) {
                $imc_status = "Abaixo do peso";
            } elseif ($imc >= 18.5 && $imc < 25) {
                $imc_status = "Peso normal";
            } elseif ($imc >= 25 && $imc < 30) {
                $imc_status = "Sobrepeso";
            } else {
                $imc_status = "Obesidade";
            }
        }
        
        // Dados de desempenho físico
        $velocidade = $_POST['velocidade'] ?? null;
        $resistencia = $_POST['resistencia'] ?? null;
        $coordenacao = $_POST['coordenacao'] ?? null;
        $agilidade = $_POST['agilidade'] ?? null;
        $forca = $_POST['forca'] ?? null;
        $desempenho_detalhes = $_POST['desempenho_detalhes'] ?? null;
        
        // Dados comportamentais
        $participacao = $_POST['participacao'] ?? null;
        $trabalho_equipe = $_POST['trabalho_equipe'] ?? null;
        $disciplina = $_POST['disciplina'] ?? null;
        $respeito_regras = $_POST['respeito_regras'] ?? null;
        $comportamento_notas = $_POST['comportamento_notas'] ?? null;
        
        // Observações gerais
        $observacoes = $_POST['observacoes'] ?? null;
        
        // Data da avaliação
        $data_avaliacao = $_POST['data_avaliacao'] ?? date('Y-m-d');
        
        try {
            if ($avaliacao) {
                // Atualizar avaliação existente
                $query_update = "UPDATE avaliacoes SET 
                    data_avaliacao = ?,
                    altura = ?,
                    peso = ?,
                    imc = ?,
                    imc_status = ?,
                    velocidade = ?,
                    resistencia = ?,
                    coordenacao = ?,
                    agilidade = ?,
                    forca = ?,
                    desempenho_detalhes = ?,
                    participacao = ?,
                    trabalho_equipe = ?,
                    disciplina = ?,
                    respeito_regras = ?,
                    comportamento_notas = ?,
                    observacoes = ?
                WHERE id = ?";
                
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([
                    $data_avaliacao,
                    $altura,
                    $peso,
                    $imc,
                    $imc_status,
                    $velocidade,
                    $resistencia,
                    $coordenacao,
                    $agilidade,
                    $forca,
                    $desempenho_detalhes,
                    $participacao,
                    $trabalho_equipe,
                    $disciplina,
                    $respeito_regras,
                    $comportamento_notas,
                    $observacoes,
                    $avaliacao['id']
                ]);
                
                $mensagem = '<div class="alert alert-success">Avaliação atualizada com sucesso!</div>';
            } else {
                // Inserir nova avaliação
                $query_insert = "INSERT INTO avaliacoes (
                    aluno_id,
                    professor_id,
                    turma_id,
                    data_avaliacao,
                    altura,
                    peso,
                    imc,
                    imc_status,
                    velocidade,
                    resistencia,
                    coordenacao,
                    agilidade,
                    forca,
                    desempenho_detalhes,
                    participacao,
                    trabalho_equipe,
                    disciplina,
                    respeito_regras,
                    comportamento_notas,
                    observacoes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->execute([
                    $aluno_id,
                    $professor_id,
                    $turma_id,
                    $data_avaliacao,
                    $altura,
                    $peso,
                    $imc,
                    $imc_status,
                    $velocidade,
                    $resistencia,
                    $coordenacao,
                    $agilidade,
                    $forca,
                    $desempenho_detalhes,
                    $participacao,
                    $trabalho_equipe,
                    $disciplina,
                    $respeito_regras,
                    $comportamento_notas,
                    $observacoes
                ]);
                
                $mensagem = '<div class="alert alert-success">Avaliação registrada com sucesso!</div>';
            }
            
            // Recarregar os dados da avaliação após salvar
            $stmt_avaliacao->execute([$aluno_id, $turma_id]);
            $avaliacao = $stmt_avaliacao->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao salvar avaliação: ' . $e->getMessage() . '</div>';
        }
    }
    
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Avaliar Aluno: <?php echo htmlspecialchars($aluno['nome']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/avaliacao.css"> <!-- Arquivo CSS separado -->
    <style>
        .aluno-profile {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .aluno-foto {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid #fff;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }
        
        .aluno-foto img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .aluno-info {
            flex: 1;
        }
        
        .aluno-nome {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #0d2d56;
        }
        
        .aluno-dados {
            display: flex;
            flex-wrap: wrap;
        }
        
        .aluno-dado {
            margin-right: 15px;
            margin-bottom: 5px;
        }
        
        .btn-actions {
            margin-left: auto;
        }
        
        @media (max-width: 768px) {
            .aluno-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .aluno-foto {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .btn-actions {
                margin-left: 0;
                margin-top: 15px;
                width: 100%;
            }
        }
        
        .imc-abaixo {
            color: #ffc107;
            font-weight: bold;
        }
        
        .imc-normal {
            color: #28a745;
            font-weight: bold;
        }
        
        .imc-sobrepeso {
            color: #fd7e14;
            font-weight: bold;
        }
        
        .imc-obesidade {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Avaliação Física e Comportamental</h1>
        
        <div class="aluno-profile">
            <div class="aluno-foto">
                <img src="<?php echo htmlspecialchars($fotoPath); ?>" alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" onerror="this.onerror=null; this.src='<?php echo $baseUrl; ?>/uploads/fotos/default.png';">
            </div>
            <div class="aluno-info">
                <div class="aluno-nome"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                <div class="aluno-dados">
                    <div class="aluno-dado"><strong>Matrícula:</strong> <?php echo htmlspecialchars($aluno['numero_matricula']); ?></div>
                    <div class="aluno-dado"><strong>Série:</strong> <?php echo htmlspecialchars($aluno['serie']); ?></div>
                    <div class="aluno-dado"><strong>Turma:</strong> <?php echo htmlspecialchars($aluno['nome_turma']); ?></div>
                </div>
            </div>
            <div class="btn-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <?php if (!empty($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="data_avaliacao"><strong>Data da Avaliação:</strong></label>
                <input type="date" class="form-control" id="data_avaliacao" name="data_avaliacao" 
                       value="<?php echo $avaliacao ? $avaliacao['data_avaliacao'] : date('Y-m-d'); ?>" required>
            </div>
            
            <!-- Medidas Físicas -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Medidas Físicas</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="altura">Altura (cm):</label>
                        <input type="text" inputmode="decimal" class="form-control" id="altura" name="altura" 
                               value="<?php echo $avaliacao ? $avaliacao['altura'] : ''; ?>" 
                               placeholder="Ex: 165">
                    </div>
                    
                    <div class="form-group">
                        <label for="peso">Peso (kg):</label>
                        <input type="text" inputmode="decimal" class="form-control" id="peso" name="peso" 
                               value="<?php echo $avaliacao ? $avaliacao['peso'] : ''; ?>" 
                               placeholder="Ex: 60.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="imc">IMC:</label>
                        <input type="text" class="form-control" id="imc" readonly 
                               value="<?php echo $avaliacao ? $avaliacao['imc'] : ''; ?>">
                        <small id="imcStatus" class="form-text <?php
                            if ($avaliacao) {
                                if ($avaliacao['imc_status'] == 'Abaixo do peso') echo 'imc-abaixo';
                                elseif ($avaliacao['imc_status'] == 'Peso normal') echo 'imc-normal';
                                elseif ($avaliacao['imc_status'] == 'Sobrepeso') echo 'imc-sobrepeso';
                                elseif ($avaliacao['imc_status'] == 'Obesidade') echo 'imc-obesidade';
                            }
                        ?>">
                            <?php echo $avaliacao ? $avaliacao['imc_status'] : ''; ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Desempenho Físico -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Desempenho Físico</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Avalie em uma escala de 1 a 10, onde 1 é muito fraco e 10 é excelente.</p>
                    
                    <div class="form-group">
                        <label for="velocidade">Velocidade:</label>
                        <input type="range" class="form-control-range" id="velocidade" name="velocidade" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['velocidade'] : '5'; ?>" 
                               oninput="document.getElementById('velocidadeValue').textContent = this.value">
                        <div class="text-center">
                            <span id="velocidadeValue"><?php echo $avaliacao ? $avaliacao['velocidade'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="resistencia">Resistência:</label>
                        <input type="range" class="form-control-range" id="resistencia" name="resistencia" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['resistencia'] : '5'; ?>" 
                               oninput="document.getElementById('resistenciaValue').textContent = this.value">
                        <div class="text-center">
                            <span id="resistenciaValue"><?php echo $avaliacao ? $avaliacao['resistencia'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="coordenacao">Coordenação:</label>
                        <input type="range" class="form-control-range" id="coordenacao" name="coordenacao" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['coordenacao'] : '5'; ?>" 
                               oninput="document.getElementById('coordenacaoValue').textContent = this.value">
                        <div class="text-center">
                            <span id="coordenacaoValue"><?php echo $avaliacao ? $avaliacao['coordenacao'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="agilidade">Agilidade:</label>
                        <input type="range" class="form-control-range" id="agilidade" name="agilidade" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['agilidade'] : '5'; ?>" 
                               oninput="document.getElementById('agilidadeValue').textContent = this.value">
                        <div class="text-center">
                            <span id="agilidadeValue"><?php echo $avaliacao ? $avaliacao['agilidade'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="forca">Força:</label>
                        <input type="range" class="form-control-range" id="forca" name="forca" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['forca'] : '5'; ?>" 
                               oninput="document.getElementById('forcaValue').textContent = this.value">
                        <div class="text-center">
                            <span id="forcaValue"><?php echo $avaliacao ? $avaliacao['forca'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="desempenho_detalhes">Detalhes do Desempenho Físico:</label>
                        <textarea class="form-control" id="desempenho_detalhes" name="desempenho_detalhes" rows="3"
                                  placeholder="Descreva detalhes sobre o desempenho físico do aluno"><?php echo $avaliacao ? htmlspecialchars($avaliacao['desempenho_detalhes']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Comportamento -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Comportamento</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Avalie em uma escala de 1 a 10, onde 1 é muito fraco e 10 é excelente.</p>
                    
                    <div class="form-group">
                        <label for="participacao">Participação:</label>
                        <input type="range" class="form-control-range" id="participacao" name="participacao" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['participacao'] : '5'; ?>" 
                               oninput="document.getElementById('participacaoValue').textContent = this.value">
                        <div class="text-center">
                            <span id="participacaoValue"><?php echo $avaliacao ? $avaliacao['participacao'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="trabalho_equipe">Trabalho em Equipe:</label>
                        <input type="range" class="form-control-range" id="trabalho_equipe" name="trabalho_equipe" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['trabalho_equipe'] : '5'; ?>" 
                               oninput="document.getElementById('trabalho_equipeValue').textContent = this.value">
                        <div class="text-center">
                            <span id="trabalho_equipeValue"><?php echo $avaliacao ? $avaliacao['trabalho_equipe'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="disciplina">Disciplina:</label>
                        <input type="range" class="form-control-range" id="disciplina" name="disciplina" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['disciplina'] : '5'; ?>" 
                               oninput="document.getElementById('disciplinaValue').textContent = this.value">
                        <div class="text-center">
                            <span id="disciplinaValue"><?php echo $avaliacao ? $avaliacao['disciplina'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="respeito_regras">Respeito às Regras:</label>
                        <input type="range" class="form-control-range" id="respeito_regras" name="respeito_regras" min="1" max="10" 
                               value="<?php echo $avaliacao ? $avaliacao['respeito_regras'] : '5'; ?>" 
                               oninput="document.getElementById('respeito_regrasValue').textContent = this.value">
                        <div class="text-center">
                            <span id="respeito_regrasValue"><?php echo $avaliacao ? $avaliacao['respeito_regras'] : '5'; ?></span>/10
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comportamento_notas">Notas sobre Comportamento:</label>
                        <textarea class="form-control" id="comportamento_notas" name="comportamento_notas" rows="3"
                                  placeholder="Descreva o comportamento do aluno durante as aulas"><?php echo $avaliacao ? htmlspecialchars($avaliacao['comportamento_notas']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Observações Gerais -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">Observações Gerais</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="observacoes">Observações:</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="4"
                                  placeholder="Observações gerais, recomendações ou comentários adicionais"><?php echo $avaliacao ? htmlspecialchars($avaliacao['observacoes']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4 mb-5">
                <button type="submit" class="btn btn-primary btn-lg">
                    <?php echo $avaliacao ? 'ATUALIZAR AVALIAÇÃO' : 'REGISTRAR AVALIAÇÃO'; ?>
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alturaInput = document.getElementById('altura');
            const pesoInput = document.getElementById('peso');
            const imcInput = document.getElementById('imc');
            const imcStatus = document.getElementById('imcStatus');
            
            function calcularIMC() {
                let altura = alturaInput.value.replace(',', '.');
                let peso = pesoInput.value.replace(',', '.');
                
                altura = parseFloat(altura);
                peso = parseFloat(peso);
                
                if (altura && peso && altura > 0) {
                    let alturaMetros = altura;
                    
                    if (altura > 3) {
                        alturaMetros = altura / 100;
                    }
                    
                    const imc = (peso / (alturaMetros * alturaMetros)).toFixed(1);
                    imcInput.value = imc;
                    
                    let status = "";
                    imcStatus.className = "form-text";
                    
                    if (imc < 18.5) {
                        status = "Abaixo do peso";
                        imcStatus.classList.add('imc-abaixo');
                    } else if (imc >= 18.5 && imc < 25) {
                        status = "Peso normal";
                        imcStatus.classList.add('imc-normal');
                    } else if (imc >= 25 && imc < 30) {
                        status = "Sobrepeso";
                        imcStatus.classList.add('imc-sobrepeso');
                    } else {
                        status = "Obesidade";
                        imcStatus.classList.add('imc-obesidade');
                    }
                    
                    imcStatus.textContent = status;
                } else {
                    imcInput.value = "";
                    imcStatus.textContent = "";
                }
            }
            
            alturaInput.addEventListener('input', calcularIMC);
            pesoInput.addEventListener('input', calcularIMC);
            
            if (alturaInput.value && pesoInput.value) {
                calcularIMC();
            }
            
            // Fix para campos numéricos em dispositivos móveis
            document.querySelectorAll('input[inputmode="decimal"]').forEach(function(input) {
                input.addEventListener('keypress', function(e) {
                    // Permite apenas números e vírgula/ponto
                    const char = String.fromCharCode(e.which);
                    if (!(/[0-9.,]/.test(char))) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>