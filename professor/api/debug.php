<?php
// Configuração para mostrar todos os erros PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define o fuso horário (opcional)
date_default_timezone_set('America/Sao_Paulo');

// Configuração para registrar erros em um arquivo de log (opcional)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// Verificar se o diretório de logs existe, se não, tentar criá-lo
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0777, true);
}

// Função para manipular erros fatais que normalmente não são capturados
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3>Erro Fatal:</h3>';
        echo '<p><strong>Tipo:</strong> ' . $error['type'] . '</p>';
        echo '<p><strong>Mensagem:</strong> ' . $error['message'] . '</p>';
        echo '<p><strong>Arquivo:</strong> ' . $error['file'] . '</p>';
        echo '<p><strong>Linha:</strong> ' . $error['line'] . '</p>';
        echo '</div>';
    }
});

// Manipulador personalizado de exceções
set_exception_handler(function($exception) {
    echo '<div style="color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 10px; margin: 10px 0; border-radius: 5px;">';
    echo '<h3>Exceção não capturada:</h3>';
    echo '<p><strong>Mensagem:</strong> ' . $exception->getMessage() . '</p>';
    echo '<p><strong>Arquivo:</strong> ' . $exception->getFile() . '</p>';
    echo '<p><strong>Linha:</strong> ' . $exception->getLine() . '</p>';
    echo '<p><strong>Rastreamento:</strong></p>';
    echo '<pre>' . $exception->getTraceAsString() . '</pre>';
    echo '</div>';
});

// Manipulador personalizado de erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $error_type = [
        E_ERROR              => 'Erro',
        E_WARNING            => 'Aviso',
        E_PARSE              => 'Erro de análise',
        E_NOTICE             => 'Notificação',
        E_CORE_ERROR         => 'Erro de núcleo',
        E_CORE_WARNING       => 'Aviso de núcleo',
        E_COMPILE_ERROR      => 'Erro de compilação',
        E_COMPILE_WARNING    => 'Aviso de compilação',
        E_USER_ERROR         => 'Erro de usuário',
        E_USER_WARNING       => 'Aviso de usuário',
        E_USER_NOTICE        => 'Notificação de usuário',
        E_STRICT             => 'Estrito',
        E_RECOVERABLE_ERROR  => 'Erro recuperável',
        E_DEPRECATED         => 'Deprecated',
        E_USER_DEPRECATED    => 'Deprecated pelo usuário'
    ];

    $type = isset($error_type[$errno]) ? $error_type[$errno] : 'Desconhecido';
    
    // Determinar a cor de fundo com base no tipo de erro
    $bgColor = '#f8d7da'; // vermelho claro para erros
    $textColor = '#721c24';
    
    if ($errno == E_WARNING || $errno == E_USER_WARNING || $errno == E_CORE_WARNING || $errno == E_COMPILE_WARNING) {
        $bgColor = '#fff3cd'; // amarelo claro para avisos
        $textColor = '#856404';
    } else if ($errno == E_NOTICE || $errno == E_USER_NOTICE || $errno == E_STRICT || $errno == E_DEPRECATED || $errno == E_USER_DEPRECATED) {
        $bgColor = '#d1ecf1'; // azul claro para notificações
        $textColor = '#0c5460';
    }
    
    echo '<div style="color: ' . $textColor . '; background-color: ' . $bgColor . '; border: 1px solid #ccc; padding: 10px; margin: 10px 0; border-radius: 5px;">';
    echo '<h3>' . $type . ':</h3>';
    echo '<p><strong>Mensagem:</strong> ' . $errstr . '</p>';
    echo '<p><strong>Arquivo:</strong> ' . $errfile . '</p>';
    echo '<p><strong>Linha:</strong> ' . $errline . '</p>';
    echo '<p><strong>Rastreamento:</strong></p>';
    $trace = debug_backtrace();
    // Pular o primeiro item do rastreamento, que é a chamada para o manipulador de erros
    array_shift($trace);
    
    echo '<pre>';
    foreach ($trace as $i => $t) {
        echo "#$i ";
        if (isset($t['file'])) {
            echo $t['file'] . "(" . $t['line'] . "): ";
        } else {
            echo "[função interna]: ";
        }
        
        if (isset($t['class'])) {
            echo $t['class'] . $t['type'];
        }
        
        echo $t['function'] . "()\n";
    }
    echo '</pre>';
    echo '</div>';
    
    // Retornar verdadeiro para não executar o manipulador de erros padrão do PHP
    return true;
});
?>