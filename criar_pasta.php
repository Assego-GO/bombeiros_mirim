<?php
// criar_pasta_mes.php
// Execute este arquivo para criar a pasta do mês atual

echo "<h2>📁 Criando Pasta do Mês Atual</h2>";

$ano = date('Y');
$mes = date('m');
$pasta_mes = "uploads/galeria/$ano/$mes/";

echo "<p><strong>Pasta a ser criada:</strong> $pasta_mes</p>";

if (!file_exists($pasta_mes)) {
    if (mkdir($pasta_mes, 0755, true)) {
        echo "<p>✅ <strong>Sucesso!</strong> Pasta criada: $pasta_mes</p>";
        
        // Verificar permissões
        $perms = substr(sprintf('%o', fileperms($pasta_mes)), -4);
        $writable = is_writable($pasta_mes) ? 'gravável' : 'NÃO gravável';
        echo "<p>📋 Permissões: $perms ($writable)</p>";
        
    } else {
        echo "<p>❌ <strong>Erro!</strong> Não foi possível criar a pasta.</p>";
        echo "<p>Tente criar manualmente ou verificar permissões.</p>";
    }
} else {
    echo "<p>ℹ️ A pasta já existe!</p>";
}

echo "<hr>";
echo "<h3>🔧 Comandos Alternativos (via terminal):</h3>";
echo "<pre>";
echo "mkdir -p uploads/galeria/$ano/$mes/\n";
echo "chmod 755 uploads/galeria/$ano/$mes/";
echo "</pre>";

echo "<h3>✅ Próximo passo:</h3>";
echo "<p>Agora teste novamente a criação da galeria!</p>";
?>