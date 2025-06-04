<?php
// Função para verificar se o professor tem autorização para acessar a turma
function verificarAutorizacaoTurma($db, $professor_id, $turma_id) {
    try {
        $query = "SELECT * FROM turma WHERE id = ? AND id_professor = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$turma_id, $professor_id]);
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Erro ao verificar autorização: " . $e->getMessage());
        return false;
    }
}
?>