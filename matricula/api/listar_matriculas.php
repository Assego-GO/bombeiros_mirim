<?php
// ===== CONFIGURAÇÕES ESSENCIAIS =====
// 1. Configurar timezone do Brasil PRIMEIRO
date_default_timezone_set('America/Sao_Paulo');

// 2. Configurar charset UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// 3. Headers JSON com UTF-8
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 4. Debug (mantenha para teste, depois pode remover)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "conexao.php";

// ===== CONFIGURAR CONEXÃO =====
if (isset($conn) && $conn instanceof mysqli) {
    // Configurar charset da conexão
    $conn->set_charset('utf8mb4');
    
    // Configurar timezone do MySQL
    $conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("SET time_zone = '-03:00'"); // Horário de Brasília
}

// ===== QUERY CORRIGIDA COM FORMATAÇÃO DE DATA =====
$sql = "
    SELECT 
        a.id AS aluno_id,
        a.nome AS aluno_nome,
        m.id AS matricula_id,
        
        -- FORMATAÇÃO DA DATA CORRIGIDA
        m.data_matricula AS data_original,
        DATE_FORMAT(m.data_matricula, '%d/%m/%Y') AS data_matricula_formatada,
        
        m.status,
        m.turma AS turma_id,
        m.unidade AS unidade_id,
        t.nome_turma AS turma,
        u.nome AS unidade,
        
        (SELECT GROUP_CONCAT(r.nome SEPARATOR ', ')
         FROM aluno_responsavel ar
         JOIN responsaveis r ON ar.responsavel_id = r.id
         WHERE ar.aluno_id = a.id
        ) AS responsaveis
        
    FROM alunos a
    LEFT JOIN matriculas m ON a.id = m.aluno_id
    LEFT JOIN turma t ON m.turma = t.id
    LEFT JOIN unidade u ON m.unidade = u.id
    
    -- Filtrar apenas registros com matrícula válida
    WHERE m.id IS NOT NULL
    
    ORDER BY a.nome ASC
";

$result = $conn->query($sql);
$dados = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        
        // ===== TRATAMENTO DA DATA =====
        $data_formatada = 'Data não informada';
        $data_original = $row['data_original']; // Salvar original antes de processar
        
        if (!empty($data_original)) {
            // Usar a data formatada do MySQL (mais eficiente)
            if (!empty($row['data_matricula_formatada'])) {
                $data_formatada = $row['data_matricula_formatada'];
            } else {
                // Fallback: tentar formatar em PHP
                try {
                    $dt = new DateTime($data_original);
                    $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                    $data_formatada = $dt->format('d/m/Y');
                } catch (Exception $e) {
                    $data_formatada = date('d/m/Y'); // Data atual como fallback
                    error_log("Erro ao formatar data para aluno {$row['aluno_id']}: " . $e->getMessage());
                }
            }
        }
        
        // ===== GARANTIR UTF-8 EM TODOS OS CAMPOS DE TEXTO =====
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        }
        
        // ===== CONSTRUIR RESULTADO FINAL =====
        $dados[] = [
            'aluno_id' => (int)$row['aluno_id'],
            'aluno_nome' => $row['aluno_nome'] ?? 'Nome não informado',
            'matricula_id' => (int)$row['matricula_id'],
            
            // ===== DATAS =====
            'data_matricula' => $data_formatada, // Data para exibição (formato brasileiro)
            'data_original' => $data_original, // Data original do banco (para referência)
            'data_matricula_formatada' => $data_formatada, // Backup do formato brasileiro
            
            // ===== STATUS E IDENTIFICADORES =====
            'status' => ucfirst(strtolower($row['status'] ?? 'pendente')),
            'turma_id' => (int)$row['turma_id'],
            'unidade_id' => (int)$row['unidade_id'],
            
            // ===== NOMES COM FALLBACKS =====
            'turma' => $row['turma'] ?: ('Turma ID: ' . $row['turma_id']),
            'unidade' => $row['unidade'] ?: ('Unidade ID: ' . $row['unidade_id']),
            'responsaveis' => $row['responsaveis'] ?: 'Responsável não cadastrado'
        ];
    }
    
    // ===== LOG DE SUCESSO =====
    error_log("✅ Matrículas carregadas com sucesso: " . count($dados) . " registros");
    
} else {
    // ===== LOG DE ERRO =====
    error_log("❌ Erro na consulta SQL: " . $conn->error);
    
    // Retornar erro em JSON
    echo json_encode([
        'success' => false,
        'error' => 'Erro na consulta ao banco de dados',
        'message' => $conn->error,
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== FECHAR CONEXÃO =====
if (isset($conn)) {
    $conn->close();
}

// ===== RESPOSTA JSON FINAL =====
echo json_encode([
    'success' => true,
    'total' => count($dados),
    'matriculas' => $dados,
    'timestamp' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
?>