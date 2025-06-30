<?php
session_start();

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
  header('Location: index.php');
  exit;
}

require "../env_config.php";

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
  // Conexão com o banco de dados
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

// Buscar dados de saídas
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_data_inicial = $_GET['data_inicial'] ?? '';
$filtro_data_final = $_GET['data_final'] ?? '';

$sql = "SELECT 
    h.id,
    h.tipo_operacao,
    h.tipo_material,
    h.item,
    h.tamanho,
    h.quantidade,
    h.valor_unitario,
    h.motivo,
    h.observacoes,
    h.created_at,
    u.nome as usuario_nome,
    a.nome as aluno_nome,
    t.nome_turma as turma_nome
FROM historico_materiais h
LEFT JOIN usuarios u ON h.usuario_id = u.id
LEFT JOIN alunos a ON h.aluno_id = a.id
LEFT JOIN turma t ON h.turma_id = t.id
WHERE h.tipo_operacao = 'saida'";

$params = [];

if ($filtro_tipo) {
    $sql .= " AND h.tipo_material = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_data_inicial) {
    $sql .= " AND DATE(h.created_at) >= ?";
    $params[] = $filtro_data_inicial;
}

if ($filtro_data_final) {
    $sql .= " AND DATE(h.created_at) <= ?";
    $params[] = $filtro_data_final;
}

$sql .= " ORDER BY h.created_at DESC LIMIT 100";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $saidas = $stmt->fetchAll();
} catch(Exception $e) {
    $saidas = [];
    $erro_busca = "Erro ao buscar dados: " . $e->getMessage();
}

// Buscar dados de entrada para comparação
$sql_entradas = "SELECT COUNT(*) as total_entradas FROM historico_materiais WHERE tipo_operacao = 'entrada'";
$stmt_entradas = $pdo->query($sql_entradas);
$total_entradas = $stmt_entradas->fetch()['total_entradas'];

$sql_saidas_count = "SELECT COUNT(*) as total_saidas FROM historico_materiais WHERE tipo_operacao = 'saida'";
$stmt_saidas_count = $pdo->query($sql_saidas_count);
$total_saidas = $stmt_saidas_count->fetch()['total_saidas'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bombeiro Mirim - Visualizar Saídas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 5px solid #e74c3c;
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filters h3 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #e74c3c;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            justify-content: center;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #343a40;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-uniforme {
            background: #e3f2fd;
            color: #1565c0;
        }

        .badge-material {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-equipamento {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #dc3545;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-sign-out-alt"></i> Visualizar Saídas de Materiais</h1>
        <p>Acompanhe todas as saídas registradas no sistema</p>
    </div>

    <div class="container">
        <!-- Cards de Estatísticas -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3>Total de Entradas</h3>
                <div class="number"><?= $total_entradas ?></div>
            </div>
            <div class="stat-card">
                <h3>Total de Saídas</h3>
                <div class="number"><?= $total_saidas ?></div>
            </div>
            <div class="stat-card">
                <h3>Saídas Filtradas</h3>
                <div class="number"><?= count($saidas) ?></div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="action-buttons">
            <a href="matricula.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
            <a href="?clear=1" class="btn btn-primary">
                <i class="fas fa-refresh"></i> Limpar Filtros
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filtros de Pesquisa</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Tipo de Material</label>
                    <select name="tipo">
                        <option value="">Todos os tipos</option>
                        <option value="uniforme" <?= $filtro_tipo === 'uniforme' ? 'selected' : '' ?>>Uniforme</option>
                        <option value="material_didatico" <?= $filtro_tipo === 'material_didatico' ? 'selected' : '' ?>>Material Didático</option>
                        <option value="equipamento" <?= $filtro_tipo === 'equipamento' ? 'selected' : '' ?>>Equipamento</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicial" value="<?= $filtro_data_inicial ?>">
                </div>
                
                <div class="form-group">
                    <label>Data Final</label>
                    <input type="date" name="data_final" value="<?= $filtro_data_final ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($erro_busca)): ?>
            <div class="erro">
                <i class="fas fa-exclamation-triangle"></i>
                <?= $erro_busca ?>
            </div>
        <?php endif; ?>

        <!-- Tabela de Saídas -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Histórico de Saídas</h3>
                <span><?= count($saidas) ?> registros encontrados</span>
            </div>

            <?php if (empty($saidas)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>Nenhuma saída encontrada</h3>
                    <p>Não há registros de saída para os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>Item</th>
                            <th>Tamanho</th>
                            <th>Quantidade</th>
                            <th>Aluno</th>
                            <th>Turma</th>
                            <th>Motivo</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saidas as $saida): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($saida['created_at'])) ?></strong><br>
                                    <small style="color: #666;"><?= date('H:i:s', strtotime($saida['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= str_replace('_', '', $saida['tipo_material']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $saida['tipo_material'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= ucfirst($saida['item']) ?></strong>
                                </td>
                                <td>
                                    <?= $saida['tamanho'] ?: '-' ?>
                                </td>
                                <td>
                                    <strong style="color: #e74c3c;"><?= $saida['quantidade'] ?></strong>
                                </td>
                                <td>
                                    <?= $saida['aluno_nome'] ?: '-' ?>
                                </td>
                                <td>
                                    <?= $saida['turma_nome'] ?: '-' ?>
                                </td>
                                <td>
                                    <small><?= ucfirst(str_replace('_', ' ', $saida['motivo'] ?: '')) ?></small>
                                </td>
                                <td>
                                    <?= $saida['usuario_nome'] ?: 'Sistema' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($saidas)): ?>
            <div style="margin-top: 20px; text-align: center; color: #666;">
                <small>
                    <i class="fas fa-info-circle"></i>
                    Mostrando os últimos 100 registros. Use os filtros para refinar sua pesquisa.
                </small>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>