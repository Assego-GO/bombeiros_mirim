<?php
// lista_alunos.php - Página para listar alunos
session_start();
require_once 'conexao.php';

try {
    // Busca todos os alunos ordenados por nome
    $stmt = $pdo->prepare("SELECT id, nome, numero_matricula, foto FROM alunos ORDER BY nome");
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Erro ao buscar alunos: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .lista-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        .aluno-foto {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container lista-container">
        <h1 class="mb-4">Lista de Alunos</h1>
        
        <div class="mb-3">
            <input type="text" class="form-control" id="busca" placeholder="Buscar aluno...">
        </div>
        
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alunos) > 0): ?>
                    <?php foreach ($alunos as $aluno): ?>
                        <tr>
                            <td>
                                <img src="<?php echo !empty($aluno['foto']) ? $aluno['foto'] : '../uploads/fotos/sem_foto.png'; ?>" class="aluno-foto" alt="Foto do aluno">
                            </td>
                            <td><?php echo $aluno['nome']; ?></td>
                            <td><?php echo $aluno['numero_matricula']; ?></td>
                            <td>
                                <a href="perfil_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn btn-primary btn-sm">Editar Perfil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Nenhum aluno encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para filtrar alunos na busca
        document.getElementById('busca').addEventListener('keyup', function() {
            let termo = this.value.toLowerCase();
            let linhas = document.querySelectorAll('tbody tr');
            
            linhas.forEach(function(linha) {
                let nome = linha.children[1].textContent.toLowerCase();
                let matricula = linha.children[2].textContent.toLowerCase();
                
                if (nome.includes(termo) || matricula.includes(termo)) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>