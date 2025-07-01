<?php
class Auditoria {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Método principal - registra qualquer ação
    public function log($acao, $tabela, $registro_id = null, $dados = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO auditoria 
                (usuario_id, usuario_nome, acao, tabela, registro_id, dados_novos, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Criar variáveis para bind_param (precisa ser por referência)
            $usuario_id = $_SESSION['usuario_id'] ?? null;
            $usuario_nome = $_SESSION['usuario_nome'] ?? 'Sistema';
            $dados_json = $dados ? json_encode($dados, JSON_UNESCAPED_UNICODE) : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt->bind_param("isssiss",
                $usuario_id,
                $usuario_nome,
                $acao,
                $tabela,
                $registro_id,
                $dados_json,
                $ip_address
            );
            
            $stmt->execute();
        } catch (Exception $e) {
            // Se falhar, não quebra o sistema
            error_log("Erro auditoria: " . $e->getMessage());
        }
    }
}
?>