<?php
// financeiro_api.php - VERSÃO CORRIGIDA COM JOINS PARA ALUNOS E TURMAS + UTF-8 + FUSO HORÁRIO BRASILEIRO
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 🇧🇷 CONFIGURAR TIMEZONE BRASILEIRO LOGO NO INÍCIO
date_default_timezone_set('America/Sao_Paulo');

// Configurar encoding UTF-8 logo no início
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

// Headers PRIMEIRO - antes de qualquer output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 🇧🇷 FUNÇÃO PARA OBTER DATA/HORA ATUAL DO BRASIL
function getDataHoraBrasil() {
    return new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
}

// 🇧🇷 FUNÇÃO PARA FORMATAR DATA/HORA PARA SQL (NO TIMEZONE BRASILEIRO)
function getDataHoraBrasilSQL() {
    return getDataHoraBrasil()->format('Y-m-d H:i:s');
}

// 🇧🇷 FUNÇÃO PARA CONVERTER UTC PARA BRASIL (se necessário)
function converterUTCParaBrasil($utcDateTime) {
    if (!$utcDateTime) return null;
    
    try {
        $utc = new DateTime($utcDateTime, new DateTimeZone('UTC'));
        $utc->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        return $utc->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        error_log("Erro ao converter UTC para Brasil: " . $e->getMessage());
        return $utcDateTime;
    }
}

// Função para resposta JSON limpa com encoding UTF-8
function jsonResponse($success, $data = null, $message = null, $code = 200) {
    http_response_code($code);
    $response = ['success' => $success];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($message !== null) {
        $response['message'] = $message;
    }
    
    if (!$success && $data !== null) {
        $response['error'] = $data;
    }
    
    // Adicionar timestamp brasileiro na resposta
    $response['timestamp_brasil'] = getDataHoraBrasilSQL();
    $response['timezone'] = 'America/Sao_Paulo (GMT-3)';
    
    // Garantir encoding UTF-8 na resposta JSON
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Função para sanitizar strings UTF-8
function sanitizeUtf8($string) {
    if (!is_string($string)) {
        return $string;
    }
    
    // Remover caracteres de controle, mas manter acentos
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    
    // Garantir que a string seja válida UTF-8
    if (!mb_check_encoding($string, 'UTF-8')) {
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
    
    return trim($string);
}

// Função para sanitizar arrays recursivamente
function sanitizeArrayUtf8($array) {
    if (!is_array($array)) {
        return sanitizeUtf8($array);
    }
    
    $sanitized = [];
    foreach ($array as $key => $value) {
        $cleanKey = sanitizeUtf8($key);
        if (is_array($value)) {
            $sanitized[$cleanKey] = sanitizeArrayUtf8($value);
        } else {
            $sanitized[$cleanKey] = sanitizeUtf8($value);
        }
    }
    
    return $sanitized;
}

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Verificar se a sessão pode ser iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificação de autenticação mais flexível
    if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Usuário não autenticado', null, 401);
    }

    // Configuração do banco - tentar múltiplas estratégias
    $db_configs = [
        // Estratégia 1: Arquivo env_config.php
        function() {
            $config_files = ['../env_config.php', './env_config.php', 'env_config.php'];
            foreach ($config_files as $file) {
                if (file_exists($file)) {
                    require_once $file;
                    return [
                        'host' => $_ENV['DB_HOST'] ?? 'localhost',
                        'name' => $_ENV['DB_NAME'] ?? 'bombeiros', 
                        'user' => $_ENV['DB_USER'] ?? 'root',
                        'pass' => $_ENV['DB_PASS'] ?? ''
                    ];
                }
            }
            return false;
        },
        
        // Estratégia 2: Arquivo config_simples.php
        function() {
            if (file_exists('conexao.php')) {
                require_once 'conexao.php';
                return [
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'name' => $_ENV['DB_NAME'] ?? 'bombeiros',
                    'user' => $_ENV['DB_USER'] ?? 'root', 
                    'pass' => $_ENV['DB_PASS'] ?? ''
                ];
            }
            return false;
        },
        
        // Estratégia 3: Configuração padrão
        function() {
            return [
                'host' => 'localhost',
                'name' => 'bombeiros',
                'user' => 'root',
                'pass' => ''
            ];
        }
    ];

    $db_config = null;
    foreach ($db_configs as $strategy) {
        $config = $strategy();
        if ($config !== false) {
            $db_config = $config;
            break;
        }
    }

    if (!$db_config) {
        jsonResponse(false, 'Erro na configuração do banco de dados');
    }

    // Conectar ao banco com configurações UTF-8 otimizadas
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        // Garantir UTF-8 na conexão
        $pdo->exec("SET CHARACTER SET utf8mb4");
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 🇧🇷 CONFIGURAR TIMEZONE DO MYSQL PARA BRASIL
        $pdo->exec("SET time_zone = '-03:00'");
        
        error_log("🇧🇷 Timezone configurado para Brasil: " . getDataHoraBrasilSQL());
        
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro de conexão: ' . $e->getMessage());
    }

    // Roteamento
    $method = $_SERVER['REQUEST_METHOD'];
    $action = sanitizeUtf8($_GET['action'] ?? '');

    error_log("API Financeiro - Método: $method, Ação: $action, Horário: " . getDataHoraBrasilSQL());

    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        default:
            jsonResponse(false, 'Método não permitido', null, 405);
    }

} catch (Exception $e) {
    error_log("Erro geral na API: " . $e->getMessage());
    jsonResponse(false, 'Erro interno: ' . $e->getMessage(), null, 500);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'test':
            testConnection($pdo);
            break;
        case 'estoque':
            getEstoque($pdo);
            break;
        case 'historico':
            getHistoricoCompleto($pdo);
            break;
        case 'alunos':
            getAlunos($pdo);
            break;
        case 'turmas':
            getTurmas($pdo);
            break;
        case 'romaneio':
            getRomaneio($pdo);
            break;
        default:
            jsonResponse(false, 'Ação não encontrada: ' . $action, null, 400);
    }
}

function handlePost($pdo, $action) {
    // Ler input e garantir UTF-8
    $input_raw = file_get_contents('php://input');
    
    // Verificar se o input é válido UTF-8
    if (!mb_check_encoding($input_raw, 'UTF-8')) {
        $input_raw = mb_convert_encoding($input_raw, 'UTF-8', 'auto');
    }
    
    $input = json_decode($input_raw, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(false, 'JSON inválido: ' . json_last_error_msg(), null, 400);
    }
    
    // Sanitizar dados de entrada
    $input = sanitizeArrayUtf8($input);
    
    switch ($action) {
        case 'entrada':
            registrarEntrada($pdo, $input);
            break;
        case 'saida':
            registrarSaida($pdo, $input);
            break;
        default:
            jsonResponse(false, 'Ação não encontrada: ' . $action, null, 400);
    }
}

function testConnection($pdo) {
    try {
        // Teste básico de conexão
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        // Teste de encoding UTF-8
        $stmt = $pdo->query("SELECT 'Teste áéíóú çñü' as utf8_test");
        $utf8_result = $stmt->fetch();
        
        // 🇧🇷 TESTE DE TIMEZONE
        $stmt = $pdo->query("SELECT NOW() as mysql_time, @@session.time_zone as mysql_timezone");
        $timezone_result = $stmt->fetch();
        
        // Verificar se as tabelas existem
        $tables = [];
        $checkTables = ['estoque_materiais', 'historico_materiais', 'alunos', 'usuarios', 'turma', 'matriculas'];
        
        foreach ($checkTables as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->fetch() !== false;
                $tables[$table] = $exists ? 'OK' : 'Não encontrada';
            } catch (Exception $e) {
                $tables[$table] = 'Erro: ' . $e->getMessage();
            }
        }
        
        jsonResponse(true, [
            'status' => 'Conexão estabelecida com sucesso',
            'database' => 'Conectado',
            'timestamp_php' => getDataHoraBrasilSQL(),
            'timestamp_mysql' => $timezone_result['mysql_time'],
            'timezone_php' => date_default_timezone_get(),
            'timezone_mysql' => $timezone_result['mysql_timezone'],
            'test_query' => $result['test'] == 1 ? 'OK' : 'ERRO',
            'utf8_test' => $utf8_result['utf8_test'],
            'encoding' => [
                'internal' => mb_internal_encoding(),
                'http_output' => mb_http_output(),
                'regex' => mb_regex_encoding()
            ],
            'tables' => $tables,
            'session' => [
                'usuario_id' => $_SESSION['usuario_id'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Erro no teste: ' . $e->getMessage());
    }
}

function getEstoque($pdo) {
    try {
        // Verificar se a tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'estoque_materiais'");
        if (!$stmt->fetch()) {
            // Retornar dados de exemplo se a tabela não existir
            $dadosExemplo = [
                [
                    'id' => 1,
                    'tipo_material' => 'uniforme',
                    'item' => 'camiseta',
                    'tamanho' => 'M',
                    'quantidade' => 15,
                    'valor_unitario' => 25.00,
                    'quantidade_minima' => 5
                ],
                [
                    'id' => 2,
                    'tipo_material' => 'uniforme',
                    'item' => 'calça',
                    'tamanho' => 'M',
                    'quantidade' => 8,
                    'valor_unitario' => 45.00,
                    'quantidade_minima' => 3
                ],
                [
                    'id' => 3,
                    'tipo_material' => 'material_didático',
                    'item' => 'caderno',
                    'tamanho' => null,
                    'quantidade' => 0,
                    'valor_unitario' => 15.00,
                    'quantidade_minima' => 10
                ]
            ];
            
            jsonResponse(true, $dadosExemplo, 'Dados de exemplo (tabela não encontrada)');
            return;
        }
        
        $sql = "SELECT 
                    e.id,
                    e.tipo_material,
                    e.item,
                    e.tamanho,
                    e.quantidade_atual as quantidade,
                    e.valor_unitario,
                    e.quantidade_minima,
                    e.created_at,
                    e.updated_at
                FROM estoque_materiais e 
                WHERE e.status = 'ativo'
                ORDER BY e.tipo_material, e.item, e.tamanho";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $estoque = $stmt->fetchAll();
        
        // Sanitizar dados UTF-8
        $estoque = sanitizeArrayUtf8($estoque);
        
        jsonResponse(true, $estoque);
    } catch (Exception $e) {
        jsonResponse(false, 'Erro ao buscar estoque: ' . $e->getMessage());
    }
}

function getAlunos($pdo) {
    try {
        // Verificar se as tabelas existem
        $stmt = $pdo->query("SHOW TABLES LIKE 'alunos'");
        if (!$stmt->fetch()) {
            // Dados de exemplo
            $dadosExemplo = [
                [
                    'id' => 1,
                    'nome' => 'João Silva',
                    'cpf' => '123.456.789-00',
                    'turma_id' => 1,
                    'turma_nome' => 'Turma A - Matutino',
                    'unidade_nome' => 'Unidade Central'
                ],
                [
                    'id' => 2,
                    'nome' => 'Maria Santos',
                    'cpf' => '987.654.321-00',
                    'turma_id' => 1,
                    'turma_nome' => 'Turma A - Matutino',
                    'unidade_nome' => 'Unidade Central'
                ]
            ];
            
            jsonResponse(true, $dadosExemplo, 'Dados de exemplo (tabelas não encontradas)');
            return;
        }
        
        // Query mais robusta para buscar alunos com suas turmas
        $sql = "SELECT DISTINCT
                    a.id,
                    a.nome,
                    a.cpf,
                    COALESCE(t.id, 0) as turma_id,
                    COALESCE(t.nome_turma, 'Sem turma') as turma_nome,
                    COALESCE(u.nome, 'Sem unidade') as unidade_nome
                FROM alunos a
                LEFT JOIN matriculas m ON a.id = m.aluno_id AND m.status = 'ativo'
                LEFT JOIN turma t ON m.turma = t.id
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE a.status = 'ativo'
                ORDER BY a.nome COLLATE utf8mb4_unicode_ci";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $alunos = $stmt->fetchAll();
        
        // Sanitizar dados UTF-8
        $alunos = sanitizeArrayUtf8($alunos);
        
        jsonResponse(true, $alunos);
    } catch (Exception $e) {
        // Se der erro, tentar query mais simples
        try {
            $sql = "SELECT id, nome, cpf FROM alunos WHERE status = 'ativo' ORDER BY nome COLLATE utf8mb4_unicode_ci";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $alunos = $stmt->fetchAll();
            
            // Adicionar campos padrão e sanitizar
            foreach ($alunos as &$aluno) {
                $aluno['turma_id'] = 0;
                $aluno['turma_nome'] = 'Sem turma';
                $aluno['unidade_nome'] = 'Sem unidade';
            }
            
            $alunos = sanitizeArrayUtf8($alunos);
            
            jsonResponse(true, $alunos, 'Dados simples (sem JOINs)');
        } catch (Exception $e2) {
            jsonResponse(false, 'Erro ao buscar alunos: ' . $e2->getMessage());
        }
    }
}

function getHistoricoCompleto($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'historico_materiais'");
        if (!$stmt->fetch()) {
            // Retornar dados de exemplo se não existir
            $dadosExemplo = [
                [
                    'id' => 1,
                    'tipo_operacao' => 'saida',
                    'tipo_material' => 'uniforme',
                    'item' => 'calça',
                    'tamanho' => 'P',
                    'quantidade' => 2,
                    'valor_unitario' => 45.00,
                    'aluno_id' => 1,
                    'turma_id' => 1,
                    'aluno_nome' => 'LUÍS FILIPE E SILVA',
                    'turma_nome' => 'Turma A - Matutino',
                    'motivo' => 'Entrega uniforme',
                    'usuario_nome' => 'Administrador',
                    'observacoes' => null,
                    'created_at' => getDataHoraBrasilSQL()
                ]
            ];
            
            jsonResponse(true, $dadosExemplo, 'Dados de exemplo (tabela não encontrada)');
            return;
        }
        
        // Query com JOINs para buscar dados completos
        $sql = "SELECT 
                    h.id,
                    h.tipo_operacao,
                    h.tipo_material,
                    h.item,
                    h.tamanho,
                    h.quantidade,
                    h.valor_unitario,
                    h.aluno_id,
                    h.turma_id,
                    h.motivo,
                    h.observacoes,
                    h.fornecedor,
                    h.created_at,
                    COALESCE(a.nome, 'Aluno não encontrado') as aluno_nome,
                    COALESCE(t.nome_turma, 'Turma não encontrada') as turma_nome,
                    COALESCE(u.nome, 'Sistema') as usuario_nome,
                    COALESCE(un.nome, 'Sem unidade') as unidade_nome
                FROM historico_materiais h
                LEFT JOIN alunos a ON h.aluno_id = a.id
                LEFT JOIN turma t ON h.turma_id = t.id
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                LEFT JOIN unidade un ON t.id_unidade = un.id
                ORDER BY h.created_at DESC
                LIMIT 100";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $historico = $stmt->fetchAll();
            
            // Sanitizar dados UTF-8
            $historico = sanitizeArrayUtf8($historico);
            
            jsonResponse(true, $historico);
        } catch (Exception $e) {
            // Se der erro com JOINs, tentar query mais simples
            $sql_simples = "SELECT 
                            h.*,
                            'Aluno não encontrado' as aluno_nome,
                            'Turma não encontrada' as turma_nome,
                            'Sistema' as usuario_nome
                        FROM historico_materiais h
                        ORDER BY h.created_at DESC
                        LIMIT 100";
            
            $stmt = $pdo->prepare($sql_simples);
            $stmt->execute();
            $historico = $stmt->fetchAll();
            
            // Tentar enriquecer os dados manualmente
            $historico = enriquecerHistorico($pdo, $historico);
            
            // Sanitizar dados UTF-8
            $historico = sanitizeArrayUtf8($historico);
            
            jsonResponse(true, $historico, 'Dados com query simplificada');
        }
        
    } catch (Exception $e) {
        jsonResponse(false, 'Erro ao buscar histórico: ' . $e->getMessage());
    }
}

function enriquecerHistorico($pdo, $historico) {
    try {
        // Buscar todos os alunos
        $stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE status = 'ativo'");
        $stmt->execute();
        $alunos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Buscar todas as turmas
        $stmt = $pdo->prepare("SELECT id, nome_turma as nome FROM turma");
        $stmt->execute();
        $turmas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Enriquecer cada registro do histórico
        foreach ($historico as &$registro) {
            if ($registro['aluno_id'] && isset($alunos[$registro['aluno_id']])) {
                $registro['aluno_nome'] = $alunos[$registro['aluno_id']];
            }
            
            if ($registro['turma_id'] && isset($turmas[$registro['turma_id']])) {
                $registro['turma_nome'] = $turmas[$registro['turma_id']];
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao enriquecer histórico: " . $e->getMessage());
    }
    
    return $historico;
}

function getTurmas($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'turma'");
        if (!$stmt->fetch()) {
            // Dados de exemplo
            $dadosExemplo = [
                [
                    'id' => 1,
                    'nome' => 'Turma A - Matutino',
                    'unidade_nome' => 'Unidade Central',
                    'total_alunos' => 25
                ]
            ];
            
            jsonResponse(true, $dadosExemplo, 'Dados de exemplo (tabela não encontrada)');
            return;
        }
        
        $sql = "SELECT 
                    t.id,
                    t.nome_turma as nome,
                    COALESCE(u.nome, 'Sem unidade') as unidade_nome,
                    COALESCE(t.matriculados, 0) as total_alunos
                FROM turma t
                LEFT JOIN unidade u ON t.id_unidade = u.id
                WHERE t.status IN ('ativo', 'Em Andamento')
                ORDER BY u.nome COLLATE utf8mb4_unicode_ci, t.nome_turma COLLATE utf8mb4_unicode_ci";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $turmas = $stmt->fetchAll();
        
        // Sanitizar dados UTF-8
        $turmas = sanitizeArrayUtf8($turmas);
        
        jsonResponse(true, $turmas);
    } catch (Exception $e) {
        jsonResponse(false, 'Erro ao buscar turmas: ' . $e->getMessage());
    }
}

function getRomaneio($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'uniformes_alunos'");
        if (!$stmt->fetch()) {
            jsonResponse(true, [], 'Tabela de uniformes não encontrada');
            return;
        }
        
        // Implementação básica do romaneio
        jsonResponse(true, [], 'Romaneio em desenvolvimento');
    } catch (Exception $e) {
        jsonResponse(false, 'Erro ao gerar romaneio: ' . $e->getMessage());
    }
}

function registrarEntrada($pdo, $data) {
    try {
        // Validação básica
        if (!isset($data['tipo_material']) || !isset($data['item']) || !isset($data['quantidade'])) {
            jsonResponse(false, 'Dados obrigatórios não fornecidos', null, 400);
        }
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'estoque_materiais'");
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Tabela de estoque não encontrada - execute o script SQL primeiro');
        }
        
        // 🇧🇷 OBTER DATA/HORA ATUAL DO BRASIL
        $dataHoraBrasil = getDataHoraBrasilSQL();
        error_log("🇧🇷 Registrando entrada no horário brasileiro: $dataHoraBrasil");
        
        $pdo->beginTransaction();
        
        // Verificar se item existe
        $sql = "SELECT id, quantidade_atual FROM estoque_materiais 
                WHERE tipo_material = ? AND item = ? AND (tamanho = ? OR (tamanho IS NULL AND ? IS NULL)) AND status = 'ativo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['tipo_material'], $data['item'], $data['tamanho'] ?? null, $data['tamanho'] ?? null]);
        $item_existente = $stmt->fetch();
        
        if ($item_existente) {
            // Atualizar com timestamp brasileiro
            $nova_quantidade = $item_existente['quantidade_atual'] + $data['quantidade'];
            $sql = "UPDATE estoque_materiais SET quantidade_atual = ?, valor_unitario = ?, updated_at = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nova_quantidade, $data['valor_unitario'], $dataHoraBrasil, $item_existente['id']]);
        } else {
            // Inserir novo com timestamp brasileiro
            $sql = "INSERT INTO estoque_materiais (tipo_material, item, tamanho, quantidade_atual, valor_unitario, quantidade_minima, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 5, 'ativo', ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['tipo_material'], $data['item'], $data['tamanho'] ?? null, $data['quantidade'], $data['valor_unitario'], $dataHoraBrasil, $dataHoraBrasil]);
        }
        
        // Registrar histórico se a tabela existir
        $stmt = $pdo->query("SHOW TABLES LIKE 'historico_materiais'");
        if ($stmt->fetch()) {
            $sql = "INSERT INTO historico_materiais (tipo_operacao, tipo_material, item, tamanho, quantidade, valor_unitario, usuario_id, fornecedor, observacoes, created_at) 
                    VALUES ('entrada', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['tipo_material'], 
                $data['item'], 
                $data['tamanho'] ?? null, 
                $data['quantidade'], 
                $data['valor_unitario'],
                $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 1,
                $data['fornecedor'] ?? null,
                $data['observacoes'] ?? null,
                $dataHoraBrasil
            ]);
        }
        
        $pdo->commit();
        
        error_log("🇧🇷 Entrada registrada com sucesso no horário: $dataHoraBrasil");
        jsonResponse(true, ['data_hora_brasil' => $dataHoraBrasil], 'Entrada registrada com sucesso');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao registrar entrada: " . $e->getMessage());
        jsonResponse(false, 'Erro ao registrar entrada: ' . $e->getMessage());
    }
}

function registrarSaida($pdo, $data) {
    try {
        // Validação básica
        $required = ['tipo_material', 'item', 'quantidade', 'aluno_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                jsonResponse(false, "Campo obrigatório não fornecido: $field", null, 400);
            }
        }
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'estoque_materiais'");
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Tabela de estoque não encontrada');
        }
        
        // 🇧🇷 OBTER DATA/HORA ATUAL DO BRASIL
        $dataHoraBrasil = getDataHoraBrasilSQL();
        error_log("🇧🇷 Registrando saída no horário brasileiro: $dataHoraBrasil");
        
        $pdo->beginTransaction();
        
        // Verificar estoque
        $sql = "SELECT id, quantidade_atual, valor_unitario FROM estoque_materiais 
                WHERE tipo_material = ? AND item = ? AND (tamanho = ? OR (tamanho IS NULL AND ? IS NULL)) AND status = 'ativo'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['tipo_material'], $data['item'], $data['tamanho'] ?? null, $data['tamanho'] ?? null]);
        $item_estoque = $stmt->fetch();
        
        if (!$item_estoque) {
            jsonResponse(false, 'Item não encontrado no estoque');
        }
        
        if ($item_estoque['quantidade_atual'] < $data['quantidade']) {
            jsonResponse(false, 'Quantidade insuficiente no estoque');
        }
        
        // Atualizar estoque com timestamp brasileiro
        $nova_quantidade = $item_estoque['quantidade_atual'] - $data['quantidade'];
        $sql = "UPDATE estoque_materiais SET quantidade_atual = ?, updated_at = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nova_quantidade, $dataHoraBrasil, $item_estoque['id']]);
        
        // Registrar histórico com timestamp brasileiro
        $stmt = $pdo->query("SHOW TABLES LIKE 'historico_materiais'");
        if ($stmt->fetch()) {
            $sql = "INSERT INTO historico_materiais (tipo_operacao, tipo_material, item, tamanho, quantidade, valor_unitario, usuario_id, aluno_id, turma_id, motivo, observacoes, created_at) 
                    VALUES ('saida', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['tipo_material'], 
                $data['item'], 
                $data['tamanho'] ?? null, 
                $data['quantidade'],
                $item_estoque['valor_unitario'],
                $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? 1,
                $data['aluno_id'],
                $data['turma_id'] ?? null,
                $data['motivo'] ?? 'Entrega de material',
                $data['observacoes'] ?? null,
                $dataHoraBrasil
            ]);
        }
        
        $pdo->commit();
        
        error_log("🇧🇷 Saída registrada com sucesso no horário: $dataHoraBrasil");
        jsonResponse(true, ['data_hora_brasil' => $dataHoraBrasil], 'Saída registrada com sucesso');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao registrar saída: " . $e->getMessage());
        jsonResponse(false, 'Erro ao registrar saída: ' . $e->getMessage());
    }
}
?>