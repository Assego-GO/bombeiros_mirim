<?php
/**
 * Script para criar apenas a estrutura da galeria
 * Execute este arquivo uma vez para criar as pastas necessárias
 * 
 * Salve como: criar_galeria.php na RAIZ do projeto (mesmo nível da pasta uploads)
 */

echo "<h2>🔧 Criando Estrutura da Galeria</h2>";

// Verificar se estamos na raiz do projeto
if (!file_exists('./uploads/')) {
    die("❌ Erro: Execute este script na raiz do projeto (onde está a pasta uploads)");
}

// Definir diretórios necessários
$base_dir = './uploads/galeria/';
$ano_atual = date('Y');
$mes_atual = date('m');

$diretorios = [
    $base_dir,
    $base_dir . $ano_atual . '/',
    $base_dir . $ano_atual . '/' . $mes_atual . '/',
    $base_dir . 'thumbnails/', // Para futuras miniaturas
];

echo "<h3>📁 Criando diretórios na estrutura existente:</h3>";
echo "<p><strong>Estrutura atual detectada:</strong> ✅ uploads/ existe</p>";
echo "<ul>";

foreach ($diretorios as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<li>✅ Criado: $dir</li>";
        } else {
            echo "<li>❌ Erro ao criar: $dir</li>";
        }
    } else {
        echo "<li>ℹ️ Já existe: $dir</li>";
    }
}

echo "</ul>";

// Verificar permissões
echo "<h3>🔒 Verificando permissões:</h3>";
echo "<ul>";

foreach ($diretorios as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? '✅ Gravável' : '❌ Não gravável';
        echo "<li>$dir - Permissão: $perms - $writable</li>";
    }
}

echo "</ul>";

// Criar arquivo .htaccess para segurança
$htaccess_content = "# Proteção da galeria
Options -Indexes

# Permitir apenas tipos de arquivo específicos
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|mp4|avi|mov|wmv|flv|webm)$\">
    Order allow,deny
    Allow from all
</FilesMatch>

# Bloquear execução de scripts
<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">
    Order allow,deny
    Deny from all
</FilesMatch>";

$htaccess_file = $base_dir . '.htaccess';
if (!file_exists($htaccess_file)) {
    if (file_put_contents($htaccess_file, $htaccess_content)) {
        echo "<h3>🔐 Arquivo .htaccess criado para segurança</h3>";
    } else {
        echo "<h3>⚠️ Não foi possível criar .htaccess</h3>";
    }
} else {
    echo "<h3>ℹ️ Arquivo .htaccess já existe</h3>";
}

echo "<h3>✅ Estrutura criada com sucesso!</h3>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Execute o SQL das tabelas no banco de dados</li>";
echo "<li>Coloque o arquivo galeria.php em professor/api/</li>";
echo "<li>Coloque o arquivo galeria.js em professor/js/</li>";
echo "<li>Adicione o script no dashboard.php</li>";
echo "<li>Delete este arquivo após executar</li>";
echo "</ol>";

// Mostrar estrutura final
echo "<h3>📋 Estrutura Final:</h3>";
echo "<pre>";
echo "uploads/ (já existe ✅)\n";
echo "├── fotos/ (fotos dos alunos - já existe ✅)\n";
echo "├── laudos/ (já existe ✅)\n";
echo "├── teste/ (já existe ✅)\n";
echo "└── galeria/ (nova pasta ✨)\n";
echo "    ├── .htaccess (segurança)\n";
echo "    ├── $ano_atual/\n";
echo "    │   └── $mes_atual/\n";
echo "    └── thumbnails/ (futuro)\n";
echo "</pre>";

echo "<h3>🎯 Próximos passos:</h3>";
echo "<ol>";
echo "<li>✅ Estrutura criada - uploads/galeria/</li>";
echo "<li>📋 Execute o SQL das tabelas no banco</li>";
echo "<li>📁 Coloque galeria.php em professor/api/</li>";
echo "<li>📁 Coloque galeria.js em professor/js/</li>";
echo "<li>🔗 Adicione &lt;script src=\"js/galeria.js\"&gt;&lt;/script&gt; no dashboard.php</li>";
echo "<li>🗑️ Delete este arquivo após testar</li>";
echo "</ol>";
?>