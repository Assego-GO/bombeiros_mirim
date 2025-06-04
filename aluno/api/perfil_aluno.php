<?php
// perfil_aluno.php - Página para consultar e editar perfil do aluno
session_start();
require_once 'conexao.php';

// Verifica se o ID do aluno está definido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID do aluno não especificado";
    exit();
}

$id_aluno = $_GET['id'];

// Busca dados do aluno
try {
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
    $stmt->bindParam(':id', $id_aluno, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "Aluno não encontrado";
        exit();
    }
    
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Busca responsáveis do aluno
    $stmt_resp = $pdo->prepare("
        SELECT r.* 
        FROM responsaveis r
        INNER JOIN aluno_responsavel ar ON r.id = ar.responsavel_id
        WHERE ar.aluno_id = :aluno_id
    ");
    $stmt_resp->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
    $stmt_resp->execute();
    
    $responsaveis = $stmt_resp->fetchAll(PDO::FETCH_ASSOC);
    
    // Busca endereço do aluno
    $stmt_end = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = :aluno_id");
    $stmt_end->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
    $stmt_end->execute();
    
    $endereco = $stmt_end->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Erro ao buscar dados: " . $e->getMessage();
    exit();
}

// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Debug para verificar os dados recebidos
        // echo "<pre>"; print_r($_POST); print_r($_FILES); echo "</pre>"; die();
        
        // Atualiza dados do aluno
        $nome = $_POST['nome'] ?? $aluno['nome'];
        $data_nascimento = $_POST['data_nascimento'] ?? $aluno['data_nascimento'];
        $rg = $_POST['rg'] ?? $aluno['rg'];
        $cpf = $_POST['cpf'] ?? $aluno['cpf'];
        $escola = $_POST['escola'] ?? $aluno['escola'];
        $serie = $_POST['serie'] ?? $aluno['serie'];
        $info_saude = $_POST['info_saude'] ?? $aluno['info_saude'];
        $numero_matricula = $_POST['numero_matricula'] ?? $aluno['numero_matricula'];
        
        // Se a senha foi fornecida, atualiza
        $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : $aluno['senha'];
        
        // Trata o upload da foto
        $foto = $aluno['foto']; // Mantém a foto atual por padrão
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $novo_nome = "aluno_" . time() . "_" . $id_aluno . "." . $ext;
            $caminho_upload = "../uploads/fotos/" . $novo_nome;
            
            // Verifica se o diretório existe, se não, cria
            if (!file_exists("../uploads/fotos/")) {
                mkdir("../uploads/fotos/", 0777, true);
            }
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_upload)) {
                // Deleta a foto antiga se existir e não for o padrão
                if (!empty($aluno['foto']) && file_exists($aluno['foto']) && $aluno['foto'] != "../uploads/fotos/sem_foto.png") {
                    @unlink($aluno['foto']);
                }
                $foto = $caminho_upload;
            }
        }
        
        // Atualiza os dados do aluno
        $stmt_update = $pdo->prepare("
            UPDATE alunos SET 
            nome = :nome, 
            data_nascimento = :data_nascimento, 
            rg = :rg, 
            cpf = :cpf, 
            escola = :escola, 
            serie = :serie, 
            info_saude = :info_saude, 
            numero_matricula = :numero_matricula, 
            foto = :foto, 
            senha = :senha
            WHERE id = :id
        ");
        
        $stmt_update->bindParam(':nome', $nome);
        $stmt_update->bindParam(':data_nascimento', $data_nascimento);
        $stmt_update->bindParam(':rg', $rg);
        $stmt_update->bindParam(':cpf', $cpf);
        $stmt_update->bindParam(':escola', $escola);
        $stmt_update->bindParam(':serie', $serie);
        $stmt_update->bindParam(':info_saude', $info_saude);
        $stmt_update->bindParam(':numero_matricula', $numero_matricula);
        $stmt_update->bindParam(':foto', $foto);
        $stmt_update->bindParam(':senha', $senha);
        $stmt_update->bindParam(':id', $id_aluno);
        $stmt_update->execute();
        
        // Atualiza o endereço do aluno se existir
        if (isset($endereco) && $endereco) {
            $cep = $_POST['cep'] ?? $endereco['cep'];
            $logradouro = $_POST['logradouro'] ?? $endereco['logradouro'];
            $numero = $_POST['numero'] ?? $endereco['numero'];
            $complemento = $_POST['complemento'] ?? $endereco['complemento'];
            $bairro = $_POST['bairro'] ?? $endereco['bairro'];
            $cidade = $_POST['cidade'] ?? $endereco['cidade'];
            
            $stmt_update_end = $pdo->prepare("
                UPDATE enderecos SET 
                cep = :cep,
                logradouro = :logradouro,
                numero = :numero,
                complemento = :complemento,
                bairro = :bairro,
                cidade = :cidade
                WHERE aluno_id = :aluno_id
            ");
            
            $stmt_update_end->bindParam(':cep', $cep);
            $stmt_update_end->bindParam(':logradouro', $logradouro);
            $stmt_update_end->bindParam(':numero', $numero);
            $stmt_update_end->bindParam(':complemento', $complemento);
            $stmt_update_end->bindParam(':bairro', $bairro);
            $stmt_update_end->bindParam(':cidade', $cidade);
            $stmt_update_end->bindParam(':aluno_id', $id_aluno);
            $stmt_update_end->execute();
        }
        
        // Atualiza os dados dos responsáveis
        foreach ($responsaveis as $index => $responsavel) {
            $resp_id = $responsavel['id'];
            $resp_prefix = "responsavel_" . $index . "_";
            
            if (isset($_POST[$resp_prefix . 'nome'])) {
                $resp_nome = $_POST[$resp_prefix . 'nome'];
                $resp_parentesco = $_POST[$resp_prefix . 'parentesco'];
                $resp_rg = $_POST[$resp_prefix . 'rg'];
                $resp_cpf = $_POST[$resp_prefix . 'cpf'];
                $resp_telefone = $_POST[$resp_prefix . 'telefone'];
                $resp_whatsapp = $_POST[$resp_prefix . 'whatsapp'];
                $resp_email = $_POST[$resp_prefix . 'email'];
                
                $stmt_update_resp = $pdo->prepare("
                    UPDATE responsaveis SET 
                    nome = :nome,
                    parentesco = :parentesco,
                    rg = :rg,
                    cpf = :cpf,
                    telefone = :telefone,
                    whatsapp = :whatsapp,
                    email = :email
                    WHERE id = :id
                ");
                
                $stmt_update_resp->bindParam(':nome', $resp_nome);
                $stmt_update_resp->bindParam(':parentesco', $resp_parentesco);
                $stmt_update_resp->bindParam(':rg', $resp_rg);
                $stmt_update_resp->bindParam(':cpf', $resp_cpf);
                $stmt_update_resp->bindParam(':telefone', $resp_telefone);
                $stmt_update_resp->bindParam(':whatsapp', $resp_whatsapp);
                $stmt_update_resp->bindParam(':email', $resp_email);
                $stmt_update_resp->bindParam(':id', $resp_id);
                $stmt_update_resp->execute();
            }
        }
        
        $pdo->commit();
        
        // Atualiza os dados após a edição
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
        $stmt->bindParam(':id', $id_aluno, PDO::PARAM_INT);
        $stmt->execute();
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Recarrega os dados do endereço
        $stmt_end = $pdo->prepare("SELECT * FROM enderecos WHERE aluno_id = :aluno_id");
        $stmt_end->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
        $stmt_end->execute();
        $endereco = $stmt_end->fetch(PDO::FETCH_ASSOC);
        
        $mensagem = "Dados atualizados com sucesso!";
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $mensagem_erro = "Erro ao atualizar dados: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .perfil-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        .foto-perfil {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container perfil-container">
        <h1 class="mb-4">Perfil do Aluno</h1>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensagem_erro)): ?>
            <div class="alert alert-danger"><?php echo $mensagem_erro; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?php echo !empty($aluno['foto']) ? $aluno['foto'] : '../uploads/fotos/sem_foto.png'; ?>" class="foto-perfil" alt="Foto do aluno">
                        <div class="mt-2">
                            <label for="foto" class="form-label">Alterar foto (Atual: <?php echo !empty($aluno['foto']) ? basename($aluno['foto']) : 'Sem foto'; ?>)</label>
                            <input type="file" class="form-control" id="foto" name="foto">
                            <small class="form-text text-muted">A foto será salva em: ../uploads/fotos/</small>
                        </div>
                    </div>
                    
                    <h2 class="mb-3">Dados Pessoais</h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $aluno['nome']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?php echo $aluno['data_nascimento']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="rg" class="form-label">RG</label>
                            <input type="text" class="form-control" id="rg" name="rg" value="<?php echo $aluno['rg']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="cpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo $aluno['cpf']; ?>">
                        </div>
                    </div>
                    
                    <h2 class="mt-4 mb-3">Dados Escolares</h2>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="escola" class="form-label">Escola</label>
                            <input type="text" class="form-control" id="escola" name="escola" value="<?php echo $aluno['escola']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="serie" class="form-label">Série</label>
                            <input type="text" class="form-control" id="serie" name="serie" value="<?php echo $aluno['serie']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="numero_matricula" class="form-label">Número de Matrícula</label>
                            <input type="text" class="form-control" id="numero_matricula" name="numero_matricula" value="<?php echo $aluno['numero_matricula']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="senha" class="form-label">Nova senha (deixe em branco para manter)</label>
                            <input type="password" class="form-control" id="senha" name="senha">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="info_saude" class="form-label">Informações de Saúde</label>
                        <textarea class="form-control" id="info_saude" name="info_saude" rows="3"><?php echo $aluno['info_saude']; ?></textarea>
                    </div>
                    
                    <h2 class="mt-4 mb-3">Endereço</h2>
                    
                    <?php if (isset($endereco) && $endereco): ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="cep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep" name="cep" value="<?php echo $endereco['cep']; ?>">
                        </div>
                        <div class="col-md-8">
                            <label for="logradouro" class="form-label">Logradouro</label>
                            <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?php echo $endereco['logradouro']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="numero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="numero" name="numero" value="<?php echo $endereco['numero']; ?>">
                        </div>
                        <div class="col-md-9">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="complemento" name="complemento" value="<?php echo $endereco['complemento']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro" value="<?php echo $endereco['bairro']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="cidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo $endereco['cidade']; ?>">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        Endereço não cadastrado para este aluno.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2 class="mb-3">Responsáveis</h2>
            
            <?php if (count($responsaveis) > 0): ?>
                <?php foreach ($responsaveis as $index => $responsavel): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="mb-3">Responsável <?php echo $index + 1; ?></h3>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="responsavel_<?php echo $index; ?>_nome" class="form-label">Nome completo</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_nome" name="responsavel_<?php echo $index; ?>_nome" value="<?php echo $responsavel['nome']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="responsavel_<?php echo $index; ?>_parentesco" class="form-label">Parentesco</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_parentesco" name="responsavel_<?php echo $index; ?>_parentesco" value="<?php echo $responsavel['parentesco']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="responsavel_<?php echo $index; ?>_rg" class="form-label">RG</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_rg" name="responsavel_<?php echo $index; ?>_rg" value="<?php echo $responsavel['rg']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="responsavel_<?php echo $index; ?>_cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_cpf" name="responsavel_<?php echo $index; ?>_cpf" value="<?php echo $responsavel['cpf']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="responsavel_<?php echo $index; ?>_telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_telefone" name="responsavel_<?php echo $index; ?>_telefone" value="<?php echo $responsavel['telefone']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="responsavel_<?php echo $index; ?>_whatsapp" class="form-label">WhatsApp</label>
                                    <input type="text" class="form-control" id="responsavel_<?php echo $index; ?>_whatsapp" name="responsavel_<?php echo $index; ?>_whatsapp" value="<?php echo $responsavel['whatsapp']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="responsavel_<?php echo $index; ?>_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="responsavel_<?php echo $index; ?>_email" name="responsavel_<?php echo $index; ?>_email" value="<?php echo $responsavel['email']; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    Nenhum responsável cadastrado para este aluno.
                </div>
            <?php endif; ?>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
                <a href="lista_alunos.php" class="btn btn-secondary btn-lg">Voltar</a>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para consulta de CEP via API
        document.getElementById('cep').addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('logradouro').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                        }
                    })
                    .catch(error => console.error('Erro na consulta do CEP:', error));
            }
        });
    </script>
</body>
</html>