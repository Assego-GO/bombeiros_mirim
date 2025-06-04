<?php

$env_paths = [
    __DIR__ . "/../env_config.php",
    __DIR__ . "/../../env_config.php",
    dirname(__DIR__) . "/env_config.php",
    dirname(dirname(__DIR__)) . "/env_config.php"
];

$loaded = false;
foreach ($env_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die("Não foi possível carregar as configurações do ambiente.");
}

$host =  $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$senha = $_ENV['DB_PASS'];
$db_name =  $_ENV['DB_NAME'];



$conn = new mysqli($host, $usuario, $senha, $db_name);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>