<?php
// diagnostico.php - Coloque este arquivo no mesmo diretório do seu script principal
header('Content-Type: application/json');

$diagnostico = [
    'php_version' => phpversion(),
    'extensions' => get_loaded_extensions(),
    'server_info' => $_SERVER,
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'database_connection' => false,
    'tables_status' => [],
    'directory_permissions' => []
];

// Verificar conexão com o banco
try {
    require_once 'conexao.php';
    $diagnostico['database_connection'] = true;
    
    // Verificar tabelas
    $tabelas = ['alunos', 'responsaveis', 'aluno_responsavel', 'enderecos', 'matriculas'];
    foreach ($tabelas as $tabela) {
        $query = $conexao->query("SHOW TABLES LIKE '$tabela'");
        $diagnostico['tables_status'][$tabela] = $query->rowCount() > 0;
    }
    
    // Verificar estrutura da tabela alunos
    if ($diagnostico['tables_status']['alunos']) {
        $diagnostico['alunos_columns'] = [];
        $columns = $conexao->query("SHOW COLUMNS FROM alunos");
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            $diagnostico['alunos_columns'][$column['Field']] = $column;
        }
    }
} catch (Exception $e) {
    $diagnostico['database_error'] = $e->getMessage();
}

// Verificar permissões de diretórios
$diretorios = ['../uploads', '../uploads/fotos'];
foreach ($diretorios as $dir) {
    $exists = file_exists($dir);
    $isDir = $exists ? is_dir($dir) : false;
    $isWritable = $isDir ? is_writable($dir) : false;
    
    $diagnostico['directory_permissions'][$dir] = [
        'exists' => $exists,
        'is_dir' => $isDir,
        'is_writable' => $isWritable
    ];
    
    // Tentar criar o diretório se não existir
    if (!$exists) {
        $created = @mkdir($dir, 0755, true);
        $diagnostico['directory_permissions'][$dir]['created'] = $created;
        $diagnostico['directory_permissions'][$dir]['is_writable'] = $created ? is_writable($dir) : false;
    }
}

echo json_encode($diagnostico, JSON_PRETTY_PRINT);
?>