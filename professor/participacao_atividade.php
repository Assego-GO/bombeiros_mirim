<?php
// participacao_atividade.php
session_start();

// Verificar se o usuário está logado e é professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header('Location: ../matricula/index.php');
    exit;
}

// Configuração do banco de dados
require "../env_config.php";

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$professor_id = $_SESSION['usuario_id'];
$atividade_id = $_GET['atividade_id'] ?? 0;

// Verificar se a atividade pertence ao professor
$stmt = $pdo->prepare("SELECT a.*, t.nome_turma, u.nome as unidade_nome 
                       FROM atividades a 
                       JOIN turma t ON a.turma_id = t.id 
                       JOIN unidade u ON t.id_unidade = u.id 
                       WHERE a.id = ? AND a.professor_id = ?");
$stmt->execute([$atividade_id, $professor_id]);
$atividade = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atividade) {
    header('Location: dashboard.php');
    exit;
}

// Buscar alunos da turma
$stmt_alunos = $pdo->prepare("SELECT a.id, a.nome, a.foto 
                              FROM alunos a 
                              JOIN matriculas m ON a.id = m.aluno_id 
                              WHERE m.turma = ? AND m.status = 'ativo'");
$stmt_alunos->execute([$atividade['turma_id']]);
$alunos = $stmt_alunos->fetchAll(PDO::FETCH_ASSOC);

// Buscar participações já registradas
$participacoes = [];
if (!empty($alunos)) {
    $aluno_ids = array_column($alunos, 'id');
    $placeholders = str_repeat('?,', count($aluno_ids) - 1) . '?';
    
    $stmt_part = $pdo->prepare("SELECT * FROM atividade_participacao 
                                WHERE atividade_id = ? AND aluno_id IN ($placeholders)");
    $stmt_part->execute(array_merge([$atividade_id], $aluno_ids));
    $participacoes_result = $stmt_part->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($participacoes_result as $part) {
        $participacoes[$part['aluno_id']] = $part;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['participacao'] as $aluno_id => $dados) {
        $sql = "INSERT INTO atividade_participacao (
                    atividade_id, aluno_id, presenca, desempenho_nota, 
                    desempenho_conceito, habilidades_desenvolvidas, 
                    comportamento, observacoes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    presenca = VALUES(presenca),
                    desempenho_nota = VALUES(desempenho_nota),
                    desempenho_conceito = VALUES(desempenho_conceito),
                    habilidades_desenvolvidas = VALUES(habilidades_desenvolvidas),
                    comportamento = VALUES(comportamento),
                    observacoes = VALUES(observacoes),
                    atualizado_em = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $atividade_id,
            $aluno_id,
            $dados['presenca'] ?? 'nao',
            $dados['desempenho_nota'] ?? null,
            $dados['desempenho_conceito'] ?? null,
            json_encode($dados['habilidades'] ?? []),
            $dados['comportamento'] ?? null,
            $dados['observacoes'] ?? null
        ]);
    }
    
    $_SESSION['sucesso'] = "Participações registradas com sucesso!";
    header("Location: participacao_atividade.php?atividade_id=" . $atividade_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Participação - <?php echo htmlspecialchars($atividade['nome_atividade']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./css/participacao.css"/>
    <style>
        .participacao-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .atividade-header {
            background: var(--white);
            padding: 20px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }
        
        .aluno-participacao {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--accent);
        }
        
        .aluno-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .aluno-foto {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            background-color: var(--gray-light);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .aluno-foto img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .aluno-foto i {
            font-size: 30px;
            color: var(--gray);
        }
        
        .participacao-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .habilidades-group {
            grid-column: 1 / -1;
        }
        
        .habilidades-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .observacoes-group {
            grid-column: 1 / -1;
        }
        
        .btn-voltar {
            background: linear-gradient(135deg, var(--gray), var(--gray-dark));
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="user-info">
                <div class="logo">
                    <img src="img/logobo.png" alt="Logo SuperAção">
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h3>
                    <p>Professor</p>
                </div>
            </div>
            
            <a href="api/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </header>

    <div class="participacao-container">
        <div class="atividade-header">
            <h1><?php echo htmlspecialchars($atividade['nome_atividade']); ?></h1>
            <p><strong>Turma:</strong> <?php echo htmlspecialchars($atividade['nome_turma']); ?> - <?php echo htmlspecialchars($atividade['unidade_nome']); ?></p>
            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($atividade['data_atividade'])); ?></p>
            <p><strong>Horário:</strong> <?php echo substr($atividade['hora_inicio'], 0, 5); ?> às <?php echo substr($atividade['hora_termino'], 0, 5); ?></p>
            <p><strong>Local:</strong> <?php echo htmlspecialchars($atividade['local_atividade']); ?></p>
        </div>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['sucesso']; 
                unset($_SESSION['sucesso']);
                ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php foreach ($alunos as $aluno): ?>
                <?php $participacao = $participacoes[$aluno['id']] ?? null; ?>
                
                <div class="aluno-participacao">
                    <div class="aluno-header">
                        <div class="aluno-foto">
                            <?php if (!empty($aluno['foto'])): ?>
                                <img src="<?php echo htmlspecialchars($aluno['foto']); ?>" alt="<?php echo htmlspecialchars($aluno['nome']); ?>">
                            <?php else: ?>
                                <i class="fas fa-user-graduate"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($aluno['nome']); ?></h3>
                    </div>
                    
                    <div class="participacao-form">
                        <div class="form-group">
                            <label class="form-label">Presença:</label>
                            <select name="participacao[<?php echo $aluno['id']; ?>][presenca]" class="form-control" required>
                                <option value="nao" <?php echo (!$participacao || $participacao['presenca'] === 'nao') ? 'selected' : ''; ?>>Não</option>
                                <option value="sim" <?php echo ($participacao && $participacao['presenca'] === 'sim') ? 'selected' : ''; ?>>Sim</option>
                                <option value="falta_justificada" <?php echo ($participacao && $participacao['presenca'] === 'falta_justificada') ? 'selected' : ''; ?>>Falta Justificada</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Desempenho (Nota 0-10):</label>
                            <input type="number" name="participacao[<?php echo $aluno['id']; ?>][desempenho_nota]" 
                                   class="form-control" min="0" max="10" step="0.1" 
                                   value="<?php echo $participacao['desempenho_nota'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Conceito:</label>
                            <select name="participacao[<?php echo $aluno['id']; ?>][desempenho_conceito]" class="form-control">
                                <option value="">Selecione</option>
                                <option value="excelente" <?php echo ($participacao && $participacao['desempenho_conceito'] === 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                                <option value="bom" <?php echo ($participacao && $participacao['desempenho_conceito'] === 'bom') ? 'selected' : ''; ?>>Bom</option>
                                <option value="regular" <?php echo ($participacao && $participacao['desempenho_conceito'] === 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="insuficiente" <?php echo ($participacao && $participacao['desempenho_conceito'] === 'insuficiente') ? 'selected' : ''; ?>>Insuficiente</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Comportamento:</label>
                            <select name="participacao[<?php echo $aluno['id']; ?>][comportamento]" class="form-control">
                                <option value="">Selecione</option>
                                <option value="excelente" <?php echo ($participacao && $participacao['comportamento'] === 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                                <option value="bom" <?php echo ($participacao && $participacao['comportamento'] === 'bom') ? 'selected' : ''; ?>>Bom</option>
                                <option value="regular" <?php echo ($participacao && $participacao['comportamento'] === 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="precisa_melhorar" <?php echo ($participacao && $participacao['comportamento'] === 'precisa_melhorar') ? 'selected' : ''; ?>>Precisa Melhorar</option>
                            </select>
                        </div>
                        
                        <div class="habilidades-group">
                            <label class="form-label">Habilidades Desenvolvidas:</label>
                            <div class="habilidades-list">
                                <?php 
                                $habilidades_selecionadas = [];
                                if ($participacao && $participacao['habilidades_desenvolvidas']) {
                                    $habilidades_selecionadas = json_decode($participacao['habilidades_desenvolvidas'], true) ?: [];
                                }
                                
                                $habilidades = [
                                    'trabalho_equipe' => 'Trabalho em Equipe',
                                    'lideranca' => 'Liderança',
                                    'responsabilidade' => 'Responsabilidade',
                                    'disciplina' => 'Disciplina',
                                    'comunicacao' => 'Comunicação',
                                    'criatividade' => 'Criatividade',
                                    'organizacao' => 'Organização',
                                    'iniciativa' => 'Iniciativa'
                                ];
                                
                                foreach ($habilidades as $key => $label): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="participacao[<?php echo $aluno['id']; ?>][habilidades][]" 
                                               value="<?php echo $key; ?>" id="hab_<?php echo $aluno['id']; ?>_<?php echo $key; ?>"
                                               <?php echo in_array($key, $habilidades_selecionadas) ? 'checked' : ''; ?>>
                                        <label for="hab_<?php echo $aluno['id']; ?>_<?php echo $key; ?>"><?php echo $label; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="observacoes-group">
                            <label class="form-label">Observações:</label>
                            <textarea name="participacao[<?php echo $aluno['id']; ?>][observacoes]" 
                                      class="form-control" rows="3"><?php echo htmlspecialchars($participacao['observacoes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="text-center" style="margin-top: 30px;">
                <a href="dashboard.php" class="btn btn-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Salvar Participações
                </button>
            </div>
        </form>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <i class="fas fa-futbol"></i> Bombeiros Mirins
                </div>
                <div class="footer-info">
                    <p>© 2025 Projeto Bombeiro Mirim Goiás</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>