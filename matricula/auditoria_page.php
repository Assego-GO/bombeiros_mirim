<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado - usuário não logado");
}

// USAR A MESMA CONEXÃO DAS SUAS APIs
include "api/conexao.php";

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_tipo = 'Administrador';
$usuario_foto = './img/usuarios/' . ($_SESSION['usuario_foto'] ?? 'default.png');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria do Sistema - Bombeiro Mirim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="./css/matricula.css"/>
    <style>
        .user-info-container {
            position: relative;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .dropdown-menu {
            position: absolute;
            top: 55px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 999;
        }
        .dropdown-menu.show {
            display: block;
        }
        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
        }
        .dropdown-menu a:hover {
            background: #f0f0f0;
        }
        .action-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .action-criar { background: #d4edda; color: #155724; }
        .action-editar { background: #fff3cd; color: #856404; }
        .action-excluir { background: #f8d7da; color: #721c24; }
        .action-login_sucesso { background: #cce5ff; color: #004085; }
        .action-login_falha { background: #f8d7da; color: #721c24; }
        .action-logout { background: #e2e3e5; color: #383d41; }
        .action-gerar { background: #d1ecf1; color: #0c5460; }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .details-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        .details-btn:hover {
            background: #5a6268;
        }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
        }
        
        .modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 80%;
            max-height: 80%;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        
        .json-viewer {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="./img/logobo.png" alt="Logo Bombeiro Mirim" style="width: 100px; height: auto;"/>
                <div>
                    <h1>Bombeiro Mirim</h1>
                    <small>Auditoria do Sistema</small>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-info-container" onclick="document.getElementById('user-menu').classList.toggle('show');">
                    <img src="<?= $usuario_foto ?>" alt="Foto do usuário" class="user-photo">
                    <div>
                        <div class="user-name"><?= $usuario_nome ?></div>
                        <small><?= $usuario_tipo ?></small>
                    </div>
                    <div id="user-menu" class="dropdown-menu">
                        <a href="painel.php"><i class="fas fa-dashboard"></i> Painel</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shield-alt"></i> Auditoria do Sistema</h1>
            <p>Monitoramento de ações e alterações no sistema</p>
        </div>

        <div class="filter-section">
            <div class="filter-header">
                <h3><i class="fas fa-filter"></i> Filtros</h3>
                <button id="toggle-filter" class="btn btn-outline btn-sm">
                    <span class="filter-icon"><i class="fas fa-search"></i></span> Mostrar/Ocultar Filtros
                </button>
            </div>
            
            <div id="filter-container" class="filter-container" style="display: none;">
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label>Ação</label>
                            <select name="acao">
                                <option value="">Todas as ações</option>
                                <option value="CRIAR_TURMA" <?= $_GET['acao'] == 'CRIAR_TURMA' ? 'selected' : '' ?>>Criar Turma</option>
                                <option value="EDITAR_TURMA" <?= $_GET['acao'] == 'EDITAR_TURMA' ? 'selected' : '' ?>>Editar Turma</option>
                                <option value="EXCLUIR_TURMA" <?= $_GET['acao'] == 'EXCLUIR_TURMA' ? 'selected' : '' ?>>Excluir Turma</option>
                                <option value="CRIAR_UNIDADE" <?= $_GET['acao'] == 'CRIAR_UNIDADE' ? 'selected' : '' ?>>Criar Unidade</option>
                                <option value="EDITAR_UNIDADE" <?= $_GET['acao'] == 'EDITAR_UNIDADE' ? 'selected' : '' ?>>Editar Unidade</option>
                                <option value="EXCLUIR_UNIDADE" <?= $_GET['acao'] == 'EXCLUIR_UNIDADE' ? 'selected' : '' ?>>Excluir Unidade</option>
                                <option value="CRIAR_PROFESSOR" <?= $_GET['acao'] == 'CRIAR_PROFESSOR' ? 'selected' : '' ?>>Criar Professor</option>
                                <option value="EDITAR_PROFESSOR" <?= $_GET['acao'] == 'EDITAR_PROFESSOR' ? 'selected' : '' ?>>Editar Professor</option>
                                <option value="EXCLUIR_PROFESSOR" <?= $_GET['acao'] == 'EXCLUIR_PROFESSOR' ? 'selected' : '' ?>>Excluir Professor</option>
                                <option value="LOGIN_SUCESSO" <?= $_GET['acao'] == 'LOGIN_SUCESSO' ? 'selected' : '' ?>>Login Sucesso</option>
                                <option value="LOGIN_FALHA" <?= $_GET['acao'] == 'LOGIN_FALHA' ? 'selected' : '' ?>>Login Falha</option>
                                <option value="LOGOUT" <?= $_GET['acao'] == 'LOGOUT' ? 'selected' : '' ?>>Logout</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Data</label>
                            <input type="date" name="data" value="<?= $_GET['data'] ?? '' ?>">
                        </div>
                        
                        <div class="filter-item">
                            <label>Usuário</label>
                            <input type="text" name="usuario" placeholder="Nome do usuário" value="<?= $_GET['usuario'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" id="limpar-filtros" class="btn btn-outline">
                            <i class="fas fa-eraser"></i> Limpar Filtros
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contador de resultados -->
        <div class="results-counter">
            <span id="total-results">0</span> registros encontrados
        </div>

        <div class="table-container">
            <?php
            try {
                // Verificar se a tabela auditoria existe
                $stmt_check = $conn->query("SHOW TABLES LIKE 'auditoria'");
                if ($stmt_check->num_rows == 0) {
                    throw new Exception("Tabela 'auditoria' não existe no banco de dados");
                }
                
                $sql = "SELECT * FROM auditoria WHERE 1=1";
                $params = [];
                $types = "";
                
                if (!empty($_GET['acao'])) {
                    $sql .= " AND acao = ?";
                    $params[] = $_GET['acao'];
                    $types .= "s";
                }
                
                if (!empty($_GET['data'])) {
                    $sql .= " AND DATE(data_hora) = ?";
                    $params[] = $_GET['data'];
                    $types .= "s";
                }
                
                if (!empty($_GET['usuario'])) {
                    $sql .= " AND usuario_nome LIKE ?";
                    $params[] = '%' . $_GET['usuario'] . '%';
                    $types .= "s";
                }
                
                $sql .= " ORDER BY data_hora DESC LIMIT 100";
                
                $stmt = $conn->prepare($sql);
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $logs = $result->fetch_all(MYSQLI_ASSOC);
                
                echo '<script>document.getElementById("total-results").textContent = "' . count($logs) . '";</script>';
                
                echo "<table>";
                echo "<tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Tabela</th>
                        <th>ID Registro</th>
                        <th>IP</th>
                        <th>Detalhes</th>
                      </tr>";
                
                if (empty($logs)) {
                    echo "<tr><td colspan='7' style='text-align: center;'>Nenhum registro encontrado.</td></tr>";
                } else {
                    foreach ($logs as $log) {
                        $acao_class = strtolower(str_replace(['_', '-'], '-', $log['acao']));
                        
                        echo "<tr>";
                        echo "<td>" . date('d/m/Y H:i:s', strtotime($log['data_hora'])) . "</td>";
                        echo "<td>" . htmlspecialchars($log['usuario_nome'] ?? 'N/A') . "</td>";
                        echo "<td><span class='action-badge action-{$acao_class}'>" . htmlspecialchars($log['acao'] ?? 'N/A') . "</span></td>";
                        echo "<td>" . htmlspecialchars($log['tabela'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($log['registro_id'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($log['ip_address'] ?? 'N/A') . "</td>";
                        echo "<td>";
                        if (!empty($log['dados_novos'])) {
                            echo "<button class='details-btn' onclick='verDetalhes(" . json_encode($log) . ")'>";
                            echo "<i class='fas fa-eye'></i> Ver";
                            echo "</button>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                }
                
                echo "</table>";
                
            } catch (Exception $e) {
                echo "<div class='error-message'>";
                echo "<h3>Erro:</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <!-- Modal para detalhes -->
    <div id="details-modal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3>Detalhes da Auditoria</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="modal-body">
                <!-- Conteúdo será preenchido dinamicamente -->
            </div>
        </div>
    </div>

    <script>
        // Toggle filtros
        document.getElementById('toggle-filter').addEventListener('click', function() {
            const container = document.getElementById('filter-container');
            container.style.display = container.style.display === 'none' ? 'block' : 'none';
        });

        // Limpar filtros
        document.getElementById('limpar-filtros').addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });

        // Fechar dropdown ao clicar fora
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('user-menu');
            const userInfo = document.querySelector('.user-info-container');
            if (menu && !userInfo.contains(e.target)) {
                menu.classList.remove('show');
            }
        });

        // Ver detalhes
        function verDetalhes(log) {
            const modal = document.getElementById('details-modal');
            const modalBody = document.getElementById('modal-body');
            
            let dados = '';
            try {
                if (log.dados_novos) {
                    dados = JSON.stringify(JSON.parse(log.dados_novos), null, 2);
                }
            } catch (e) {
                dados = log.dados_novos || 'Nenhum dado disponível';
            }
            
            modalBody.innerHTML = `
                <p><strong>ID:</strong> ${log.id}</p>
                <p><strong>Usuário:</strong> ${log.usuario_nome}</p>
                <p><strong>Ação:</strong> ${log.acao}</p>
                <p><strong>Tabela:</strong> ${log.tabela}</p>
                <p><strong>ID do Registro:</strong> ${log.registro_id || '-'}</p>
                <p><strong>Data/Hora:</strong> ${new Date(log.data_hora).toLocaleString('pt-BR')}</p>
                <p><strong>IP:</strong> ${log.ip_address}</p>
                <p><strong>Dados:</strong></p>
                <div class="json-viewer">${dados}</div>
            `;
            
            modal.style.display = 'block';
        }

        // Fechar modal
        function closeModal() {
            document.getElementById('details-modal').style.display = 'none';
        }

        // Fechar modal ao clicar fora
        document.getElementById('details-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>