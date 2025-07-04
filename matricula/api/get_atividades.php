<?php
// ===== API PARA BUSCAR ATIVIDADES DO BANCO =====
// Arquivo: api/get_atividades.php
//
// Esta API busca dados EXCLUSIVAMENTE do banco de dados MySQL
// Tabela: atividades (com joins em turma, unidade, professor)
// Estrutura compatível com o frontend de monitoramento

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificação de autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Configuração do banco de dados
require "../../env_config.php";

$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    // Conexão com o banco de dados com configurações UTF-8 e timezone Brasil
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    
    // Configurar timezone do MySQL para o Brasil
    $pdo->exec("SET time_zone = '-03:00'");
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

// Função para formatar data/hora brasileira
function formatarDataHoraBrasil($datetime, $formato = 'Y-m-d H:i:s') {
    if (empty($datetime)) return null;
    
    try {
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        return $dt->format($formato);
    } catch (Exception $e) {
        return $datetime; // Retorna original se der erro
    }
}

// Função para obter data/hora atual do Brasil
function agora() {
    $dt = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    return $dt->format('Y-m-d H:i:s');
}

// Processar requisições
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // ===== AÇÕES VIA POST =====
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'atualizar_status':
            $id = intval($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            
            // Validar status
            $statusValidos = ['planejada', 'em_andamento', 'concluida', 'cancelada'];
            if (!in_array($status, $statusValidos)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Status inválido'
                ]);
                exit;
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE atividades 
                    SET status = ?, atualizado_em = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Status atualizado com sucesso'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Atividade não encontrada ou status já atualizado'
                    ]);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar status: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
            break;
    }
    
} else if ($method === 'GET') {
    // ===== BUSCAR ATIVIDADES =====
    
    try {
        // Query principal com todos os JOINs necessários
        $sql = "
            SELECT 
                a.id,
                a.nome_atividade,
                a.data_atividade,
                a.hora_inicio,
                a.hora_termino,
                a.local_atividade,
                a.instrutor_responsavel,
                a.objetivo_atividade,
                a.conteudo_abordado,
                a.status,
                a.criado_em,
                a.atualizado_em,
                a.eh_voluntario,
                a.nome_voluntario,
                a.especialidade_voluntario,
                a.telefone_voluntario,
                
                -- Dados da turma
                t.id as turma_id,
                t.nome_turma as turma_nome,
                t.capacidade as turma_capacidade,
                t.matriculados as turma_matriculados,
                t.status as turma_status,
                
                -- Dados do professor
                p.id as professor_id,
                p.nome as professor_nome,
                p.email as professor_email,
                p.telefone as professor_telefone,
                
                -- Dados da unidade
                u.id as unidade_id,
                u.nome as unidade_nome,
                u.endereco as unidade_endereco,
                u.cidade as unidade_cidade,
                u.coordenador as unidade_coordenador
                
            FROM atividades a
            
            INNER JOIN turma t ON a.turma_id = t.id
            INNER JOIN professor p ON a.professor_id = p.id
            INNER JOIN unidade u ON t.id_unidade = u.id
            
            ORDER BY 
                a.data_atividade DESC, 
                a.hora_inicio DESC,
                a.id DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $atividades = $stmt->fetchAll();
        
        // Processar e formatar os dados para o frontend
        $atividadesFormatadas = [];
        
        foreach ($atividades as $atividade) {
            // Determinar o instrutor (professor ou voluntário)
            $instrutor = $atividade['eh_voluntario'] ? 
                $atividade['nome_voluntario'] . ' (Voluntário)' : 
                $atividade['professor_nome'];
            
            // Adicionar informações do voluntário se aplicável
            $infoInstrutor = $instrutor;
            if ($atividade['eh_voluntario'] && !empty($atividade['especialidade_voluntario'])) {
                $infoInstrutor .= ' - ' . $atividade['especialidade_voluntario'];
            }
            
            $atividadesFormatadas[] = [
                'id' => intval($atividade['id']),
                'nome_atividade' => $atividade['nome_atividade'],
                'data_atividade' => $atividade['data_atividade'],
                'hora_inicio' => substr($atividade['hora_inicio'], 0, 5), // HH:MM
                'hora_termino' => substr($atividade['hora_termino'], 0, 5), // HH:MM
                'local_atividade' => $atividade['local_atividade'],
                'instrutor_responsavel' => $infoInstrutor,
                'objetivo_atividade' => $atividade['objetivo_atividade'],
                'conteudo_abordado' => $atividade['conteudo_abordado'],
                'status' => $atividade['status'],
                
                // Informações do voluntário
                'eh_voluntario' => boolval($atividade['eh_voluntario']),
                'nome_voluntario' => $atividade['nome_voluntario'],
                'especialidade_voluntario' => $atividade['especialidade_voluntario'],
                'telefone_voluntario' => $atividade['telefone_voluntario'],
                
                // Dados da turma
                'turma_id' => intval($atividade['turma_id']),
                'turma_nome' => $atividade['turma_nome'],
                'turma_capacidade' => intval($atividade['turma_capacidade']),
                'turma_matriculados' => intval($atividade['turma_matriculados']),
                'turma_status' => $atividade['turma_status'],
                
                // Dados do professor
                'professor_id' => intval($atividade['professor_id']),
                'professor_nome' => $atividade['professor_nome'],
                'professor_email' => $atividade['professor_email'],
                'professor_telefone' => $atividade['professor_telefone'],
                
                // Dados da unidade
                'unidade_id' => intval($atividade['unidade_id']),
                'unidade_nome' => $atividade['unidade_nome'],
                'unidade_endereco' => $atividade['unidade_endereco'],
                'unidade_cidade' => $atividade['unidade_cidade'],
                'unidade_coordenador' => $atividade['unidade_coordenador'],
                
                // Campos de compatibilidade com o frontend existente
                'turma' => $atividade['turma_nome'],
                'instrutor' => $infoInstrutor,
                'local' => $atividade['local_atividade'],
                'objetivo' => $atividade['objetivo_atividade'],
                'conteudo' => $atividade['conteudo_abordado'],
                
                // Datas formatadas
                'criado_em' => formatarDataHoraBrasil($atividade['criado_em']),
                'atualizado_em' => formatarDataHoraBrasil($atividade['atualizado_em']),
                
                // Campos adicionais úteis
                'data_completa' => $atividade['data_atividade'] . ' ' . $atividade['hora_inicio'],
                'duracao_estimada' => calcularDuracao($atividade['hora_inicio'], $atividade['hora_termino']),
                'eh_hoje' => $atividade['data_atividade'] === date('Y-m-d'),
                'ja_passou' => $atividade['data_atividade'] < date('Y-m-d')
            ];
        }
        
        // Calcular estatísticas
        $stats = calcularEstatisticas($pdo);
        
        // Resposta final
        echo json_encode([
            'success' => true,
            'message' => 'Atividades carregadas com sucesso',
            'total' => count($atividadesFormatadas),
            'atividades' => $atividadesFormatadas,
            'stats' => $stats,
            'timestamp' => agora()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar atividades: ' . $e->getMessage(),
            'atividades' => [],
            'stats' => []
        ]);
    }
    
} else {
    // Método não permitido
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}

// ===== FUNÇÕES AUXILIARES =====

/**
 * Calcula a duração estimada entre dois horários
 */
function calcularDuracao($horaInicio, $horaFim) {
    try {
        $inicio = new DateTime($horaInicio);
        $fim = new DateTime($horaFim);
        $duracao = $inicio->diff($fim);
        
        $horas = $duracao->h;
        $minutos = $duracao->i;
        
        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}min";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } elseif ($minutos > 0) {
            return "{$minutos}min";
        } else {
            return "0min";
        }
    } catch (Exception $e) {
        return "N/A";
    }
}

/**
 * Calcula estatísticas das atividades
 */
function calcularEstatisticas($pdo) {
    try {
        // Estatísticas por status
        $stmt = $pdo->query("
            SELECT 
                status, 
                COUNT(*) as total 
            FROM atividades 
            GROUP BY status
        ");
        $statusStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Estatísticas por tipo de atividade
        $stmt = $pdo->query("
            SELECT 
                nome_atividade, 
                COUNT(*) as total 
            FROM atividades 
            GROUP BY nome_atividade 
            ORDER BY total DESC
        ");
        $tipoStats = $stmt->fetchAll();
        
        // Estatísticas por turma
        $stmt = $pdo->query("
            SELECT 
                t.nome_turma,
                COUNT(a.id) as total_atividades
            FROM turma t
            LEFT JOIN atividades a ON t.id = a.turma_id
            GROUP BY t.id, t.nome_turma
            ORDER BY total_atividades DESC
        ");
        $turmaStats = $stmt->fetchAll();
        
        // Atividades desta semana
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM atividades 
            WHERE YEARWEEK(data_atividade, 1) = YEARWEEK(CURDATE(), 1)
        ");
        $atividadesSemana = $stmt->fetchColumn();
        
        // Atividades deste mês
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM atividades 
            WHERE YEAR(data_atividade) = YEAR(CURDATE()) 
            AND MONTH(data_atividade) = MONTH(CURDATE())
        ");
        $atividadesMes = $stmt->fetchColumn();
        
        // Atividades com voluntários
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM atividades 
            WHERE eh_voluntario = 1
        ");
        $atividadesVoluntarios = $stmt->fetchColumn();
        
        return [
            'por_status' => $statusStats,
            'por_tipo' => $tipoStats,
            'por_turma' => $turmaStats,
            'esta_semana' => intval($atividadesSemana),
            'este_mes' => intval($atividadesMes),
            'com_voluntarios' => intval($atividadesVoluntarios),
            'total_geral' => array_sum($statusStats)
        ];
        
    } catch (PDOException $e) {
        return [
            'erro' => 'Erro ao calcular estatísticas: ' . $e->getMessage()
        ];
    }
}

?>