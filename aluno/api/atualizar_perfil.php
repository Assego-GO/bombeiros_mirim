<?php
// api/atualizar_perfil.php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

// Verificar se é uma solicitação POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido'
    ]);
    exit;
}

// Pegar ID do aluno da sessão
$id_aluno = $_SESSION["usuario_id"];

// Resposta padrão
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Buscar dados atuais do aluno para comparação
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = :id");
    $stmt->bindParam(':id', $id_aluno, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception('Aluno não encontrado');
    }
    
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Coletar dados do formulário
    $nome = $_POST['nome'] ?? $aluno['nome'];
    $data_nascimento = $_POST['data_nascimento'] ?? $aluno['data_nascimento'];
    $rg = $_POST['rg'] ?? $aluno['rg'];
    $cpf = $_POST['cpf'] ?? $aluno['cpf'];
    $escola = $_POST['escola'] ?? $aluno['escola'];
    $serie = $_POST['serie'] ?? $aluno['serie'];
    $info_saude = $_POST['info_saude'] ?? $aluno['info_saude'];
    
    // Se a senha foi fornecida, atualiza
    $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : $aluno['senha'];
    
    // Trata o upload da foto
    $foto = $aluno['foto']; // Mantém a foto atual por padrão
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = "aluno_" . time() . "_" . $id_aluno . "." . $ext;
        
        // AJUSTE IMPORTANTE: Caminho começa com ../.. para subir até a raiz
        $dir_upload = "../../uploads/fotos/";
        $caminho_upload = $dir_upload . $novo_nome;
        $caminho_bd = "../uploads/fotos/" . $novo_nome; // Caminho para armazenar no banco
        
        // Verifica se o diretório existe, se não, cria
        if (!file_exists($dir_upload)) {
            mkdir($dir_upload, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_upload)) {
            // Ajustar o caminho da foto antiga para exclusão
            $foto_antiga = $aluno['foto'];
            if (!empty($foto_antiga)) {
                // Remover "../" do início se existir
                $foto_antiga = preg_replace('/^\.\.\//', '', $foto_antiga);
                $caminho_exclusao = "../../" . $foto_antiga;
                
                // Deleta a foto antiga se existir e não for o padrão
                if (file_exists($caminho_exclusao) && strpos($foto_antiga, 'sem_foto.png') === false) {
                    @unlink($caminho_exclusao);
                }
            }
            
            $foto = $caminho_bd; // Usa o caminho relativo para o banco de dados
            
            // Atualiza a foto na sessão
            $_SESSION["usuario_foto"] = $foto;
        } else {
            throw new Exception("Falha ao fazer upload da foto. Verifique as permissões do diretório.");
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
    $stmt_update->bindParam(':foto', $foto);
    $stmt_update->bindParam(':senha', $senha);
    $stmt_update->bindParam(':id', $id_aluno);
    $stmt_update->execute();
    
    // Verifica se tem dados de endereço
    if (isset($_POST['cep']) && !empty($_POST['cep'])) {
        // Busca endereço do aluno para ver se existe
        $stmt_end = $pdo->prepare("SELECT id FROM enderecos WHERE aluno_id = :aluno_id");
        $stmt_end->bindParam(':aluno_id', $id_aluno, PDO::PARAM_INT);
        $stmt_end->execute();
        $endereco_existe = $stmt_end->fetch(PDO::FETCH_ASSOC);
        
        $cep = $_POST['cep'];
        $logradouro = $_POST['logradouro'];
        $numero = $_POST['numero'];
        $complemento = $_POST['complemento'] ?? '';
        $bairro = $_POST['bairro'];
        $cidade = $_POST['cidade'];
        
        if ($endereco_existe) {
            // Atualiza endereço existente
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
        } else {
            // Insere novo endereço
            $stmt_update_end = $pdo->prepare("
                INSERT INTO enderecos (
                    aluno_id, cep, logradouro, numero, complemento, bairro, cidade
                ) VALUES (
                    :aluno_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade
                )
            ");
        }
        
        $stmt_update_end->bindParam(':cep', $cep);
        $stmt_update_end->bindParam(':logradouro', $logradouro);
        $stmt_update_end->bindParam(':numero', $numero);
        $stmt_update_end->bindParam(':complemento', $complemento);
        $stmt_update_end->bindParam(':bairro', $bairro);
        $stmt_update_end->bindParam(':cidade', $cidade);
        $stmt_update_end->bindParam(':aluno_id', $id_aluno);
        $stmt_update_end->execute();
    }
    
    // Atualizar dados dos responsáveis, se enviados
    if (isset($_POST['responsavel_id']) && is_array($_POST['responsavel_id'])) {
        $respIds = $_POST['responsavel_id'];
        $respNomes = $_POST['responsavel_nome'] ?? [];
        $respParentescos = $_POST['responsavel_parentesco'] ?? [];
        $respRgs = $_POST['responsavel_rg'] ?? [];
        $respCpfs = $_POST['responsavel_cpf'] ?? [];
        $respTelefones = $_POST['responsavel_telefone'] ?? [];
        $respWhatsapps = $_POST['responsavel_whatsapp'] ?? [];
        $respEmails = $_POST['responsavel_email'] ?? [];
        
        foreach ($respIds as $index => $respId) {
            // Verificar se todos os arrays necessários existem e têm o mesmo tamanho
            if (isset($respNomes[$index], $respParentescos[$index], $respRgs[$index], $respCpfs[$index], 
                       $respTelefones[$index], $respEmails[$index])) {
                
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
                
                $stmt_update_resp->bindParam(':nome', $respNomes[$index]);
                $stmt_update_resp->bindParam(':parentesco', $respParentescos[$index]);
                $stmt_update_resp->bindParam(':rg', $respRgs[$index]);
                $stmt_update_resp->bindParam(':cpf', $respCpfs[$index]);
                $stmt_update_resp->bindParam(':telefone', $respTelefones[$index]);
                $stmt_update_resp->bindParam(':whatsapp', $respWhatsapps[$index]);
                $stmt_update_resp->bindParam(':email', $respEmails[$index]);
                $stmt_update_resp->bindParam(':id', $respId);
                $stmt_update_resp->execute();
            }
        }
    }
    
    // Commit das alterações
    $pdo->commit();
    
    // Atualiza o nome na sessão
    $_SESSION["usuario_nome"] = $nome;
    
    $response['success'] = true;
    $response['message'] = 'Perfil atualizado com sucesso!';
    
} catch(Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
}

// Enviar resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);