<?php
// Configuração para exibir erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeçalhos para JSON e CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para desenvolvimento
header('Access-Control-Allow-Methods: GET');

try {
    // Incluir conexão com o banco de dados
    require_once "conexao.php";
    
    // Verificar se a conexão foi bem-sucedida
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }
        
    // Verificar se o ID foi fornecido
    if (!isset($_GET['id'])) {
        throw new Exception("ID da unidade não fornecido");
    }
    
    $id = intval($_GET['id']);
    
    // Preparar consulta SQL incluindo unidade_crbm e cidade
    $sql = "SELECT 
                id, 
                nome, 
                unidade_crbm,
                endereco, 
                telefone, 
                coordenador, 
                cidade,
                data_criacao, 
                ultima_atualizacao 
            FROM unidade 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    // Executar a consulta
    if (!$stmt->execute()) {
        throw new Exception("Erro ao buscar a unidade: " . $stmt->error);
    }
    
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Nenhuma unidade encontrada com este ID'
        ]);
        exit;
    }
    
    $unidade = $resultado->fetch_assoc();
    
    // Garantir que os campos sempre tenham um valor (evitar null)
    if (!isset($unidade['cidade']) || $unidade['cidade'] === null) {
        $unidade['cidade'] = '';
    }
    
    if (!isset($unidade['unidade_crbm']) || $unidade['unidade_crbm'] === null) {
        $unidade['unidade_crbm'] = '';
    }
    
    if (!isset($unidade['telefone']) || $unidade['telefone'] === null) {
        $unidade['telefone'] = '';
    }
    
    if (!isset($unidade['coordenador']) || $unidade['coordenador'] === null) {
        $unidade['coordenador'] = '';
    }
    
    if (!isset($unidade['endereco']) || $unidade['endereco'] === null) {
        $unidade['endereco'] = '';
    }
    
    // Formatar datas para exibição mais amigável, se necessário
    if (isset($unidade['data_criacao'])) {
        $data_criacao = new DateTime($unidade['data_criacao']);
        $unidade['data_criacao'] = $data_criacao->format('Y-m-d H:i:s');
    }
    
    if (isset($unidade['ultima_atualizacao'])) {
        $ultima_atualizacao = new DateTime($unidade['ultima_atualizacao']);
        $unidade['ultima_atualizacao'] = $ultima_atualizacao->format('Y-m-d H:i:s');
    }
    
    // Converter o valor de unidade_crbm para o nome completo para referência
    $unidades_crbm = [
        'goiania' => '1º Comando Regional Bombeiro Militar - Goiânia - CBC',
        'rioVerde' => '2º Comando Regional Bombeiro Militar - Rio Verde',
        'anapolis' => '3º Comando Regional Bombeiro Militar - Anápolis',
        'luziania' => '4º Comando Regional Bombeiro Militar - Luziânia',
        'aparecidaDeGoiania' => '5º Comando Regional Bombeiro Militar – Aparecida de Goiânia',
        'goias' => '6º Comando Regional Bombeiro Militar - Goiás',
        'caldasNovas' => '7º Comando Regional Bombeiro Militar – Caldas Novas',
        'uruacu' => '8º Comando Regional Bombeiro Militar - Uruaçu',
        'Formosa' => '9º Comando Regional Bombeiro Militar - Formosa'
    ];
    
    // Adicionar o nome completo da unidade CRBM para referência
    if (isset($unidades_crbm[$unidade['unidade_crbm']])) {
        $unidade['unidade_crbm_display'] = $unidades_crbm[$unidade['unidade_crbm']];
    } else {
        $unidade['unidade_crbm_display'] = $unidade['unidade_crbm'] ?: '';
    }
    
    // Retornar dados no formato JSON
    echo json_encode([
        'status' => 'sucesso',
        'data' => $unidade
    ]);
    
} catch (Exception $e) {
    // Retornar mensagem de erro em formato JSON
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
} finally {
    // Fechar statement e conexão
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>