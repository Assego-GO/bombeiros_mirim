<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$resposta = [
    'success' => false,
    'message' => '',
    'matricula' => '',
    'debug' => []
];

$resposta['debug']['php_info'] = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads')
];

$resposta['debug']['files_raw'] = $_FILES;
$resposta['debug']['request_method'] = $_SERVER['REQUEST_METHOD'];
$resposta['debug']['content_type'] = $_SERVER['CONTENT_TYPE'] ?? 'não definido';

// Função para traduzir códigos de erro de upload
function traduzirErro($codigo) {
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
            return "O arquivo excede o tamanho máximo permitido pelo PHP.";
        case UPLOAD_ERR_FORM_SIZE:
            return "O arquivo excede o tamanho máximo permitido pelo formulário.";
        case UPLOAD_ERR_PARTIAL:
            return "O upload foi interrompido.";
        case UPLOAD_ERR_NO_FILE:
            return "Nenhum arquivo foi enviado.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Pasta temporária ausente.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Falha ao escrever o arquivo no disco.";
        case UPLOAD_ERR_EXTENSION:
            return "Upload interrompido por uma extensão PHP.";
        default:
            return "Erro desconhecido.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexao.php';
    
    try {
        
        $unidade = limparDados($_POST['unidade'] ?? '');
        $turma = limparDados($_POST['turma'] ?? '');
        
        $nomeAluno = limparDados($_POST['nome-aluno'] ?? '');
        $dataNascimento = limparDados($_POST['data-nascimento'] ?? '');
        $genero = limparDados($_POST['genero'] ?? '');
        // Convertendo cadastro_unico para lowercase também
        $cadastro_unico = strtolower(limparDados($_POST['cadastro_unico'] ?? ''));
        $rgAluno = limparDados($_POST['rg-aluno'] ?? '');
        $cpfAluno = limparDados($_POST['cpf-aluno'] ?? '');
        $escola = limparDados($_POST['escola'] ?? '');
        $serie = limparDados($_POST['serie'] ?? '');
        $infoSaude = limparDados($_POST['info-saude'] ?? '');
        $telefoneEscola = limparDados($_POST['telefone-escola'] ?? '');
        $diretorEscola = limparDados($_POST['nome-diretor'] ?? '');

        // NOVOS CAMPOS ADICIONADOS
        $tipoSangue = strtolower(limparDados($_POST['tipo-sangue'] ?? ''));
        $criancaAtipica = strtolower(limparDados($_POST['atipica'] ?? ''));
        $atipicaComLaudo = strtolower(limparDados($_POST['laudo-atipica'] ?? ''));
        $temAlergiasCondicoes = strtolower(limparDados($_POST['condicao-crianca'] ?? ''));
        $detalhesAlergiasCondicoes = limparDados($_POST['condicao-detalhes'] ?? '');
        $medicacaoContinua = strtolower(limparDados($_POST['medicacao-crianca'] ?? ''));
        $detalhesMedicacao = limparDados($_POST['medicacao-detalhes'] ?? '');
        $numeroCadastroUnico = limparDados($_POST['numero-cadunico'] ?? '');
        $tipoEscola = strtolower(limparDados($_POST['tipo-escola'] ?? ''));
        $enderecoEscola = limparDados($_POST['endereco-escola'] ?? '');
        
        // Campos de concorrência e vaga militar
        $amplaConcorrencia = strtolower(limparDados($_POST['concorrencia'] ?? ''));
        $vagaMilitar = strtolower(limparDados($_POST['vaga-militar'] ?? ''));

        // Campos do uniforme
        $tamanhoCamisa = strtolower(limparDados($_POST['tamanho-camisa'] ?? ''));
        $tamanhoCalca = strtolower(limparDados($_POST['tamanho-calca'] ?? ''));
        $tamanhoCalcado = limparDados($_POST['tamanho-calcado'] ?? '');

        
        $nomeResponsavel = limparDados($_POST['nome-responsavel'] ?? '');
        $parentesco = limparDados($_POST['parentesco'] ?? '');
        $rgResponsavel = limparDados($_POST['rg-responsavel'] ?? '');
        $cpfResponsavel = limparDados($_POST['cpf-responsavel'] ?? '');
        $telefone = limparDados($_POST['telefone'] ?? '');
        $whatsapp = limparDados($_POST['whatsapp'] ?? '');
        $email = limparDados($_POST['email'] ?? '');
        $profissao = limparDados($_POST['profissao-responsavel'] ?? '');
        
        $cep = limparDados($_POST['cep'] ?? '');
        $endereco = limparDados($_POST['endereco'] ?? '');
        $numero = limparDados($_POST['numero'] ?? '');
        $complemento = limparDados($_POST['complemento'] ?? '');
        $bairro = limparDados($_POST['bairro'] ?? '');
        $cidade = limparDados($_POST['cidade'] ?? '');
        
        $consentimento = isset($_POST['consent']) ? 1 : 0;
        
        $dadosProcessados = [
            'unidade' => $unidade,
            'turma' => $turma,
            'nomeAluno' => $nomeAluno,
            'dataNascimento' => $dataNascimento,
            'genero' => $genero,
            'cadastro_unico' => $cadastro_unico,
            'nomeResponsavel' => $nomeResponsavel,
            'email' => $email,
            'consentimento' => $consentimento,
            'tipoSangue' => $tipoSangue,
            'criancaAtipica' => $criancaAtipica,
            'tamanhoCamisa' => $tamanhoCamisa,
            'tamanhoCalca' => $tamanhoCalca,
            'tamanhoCalcado' => $tamanhoCalcado
        ];
        
        $resposta['debug']['processed_data'] = $dadosProcessados;
        
        $camposVazios = [];
        
        if (empty($unidade)) $camposVazios[] = 'unidade';
        if (empty($turma)) $camposVazios[] = 'turma';
        if (empty($nomeAluno)) $camposVazios[] = 'nome-aluno';
        if (empty($dataNascimento)) $camposVazios[] = 'data-nascimento';
        if (empty($genero)) $camposVazios[] = 'genero';
        if (empty($cadastro_unico)) $camposVazios[] = 'cadastro_unico';
        if (empty($escola)) $camposVazios[] = 'escola';
        if (empty($serie)) $camposVazios[] = 'serie';
        if (empty($telefoneEscola)) $camposVazios[] = 'telefone-escola';
        if (empty($diretorEscola)) $camposVazios[] = 'nome-diretor';
        if (empty($nomeResponsavel)) $camposVazios[] = 'nome-responsavel';
        if (empty($parentesco)) $camposVazios[] = 'parentesco';
        if (empty($rgResponsavel)) $camposVazios[] = 'rg-responsavel';
        if (empty($cpfResponsavel)) $camposVazios[] = 'cpf-responsavel';
        if (empty($telefone)) $camposVazios[] = 'telefone';
        if (empty($email)) $camposVazios[] = 'email';
        if (empty($cep)) $camposVazios[] = 'cep';
        if (empty($endereco)) $camposVazios[] = 'endereco';
        if (empty($numero)) $camposVazios[] = 'numero';
        if (empty($bairro)) $camposVazios[] = 'bairro';
        if (empty($cidade)) $camposVazios[] = 'cidade';
        if ($consentimento !== 1) $camposVazios[] = 'consent';
        
        // VALIDAÇÕES DOS NOVOS CAMPOS
        if (empty($tipoSangue)) $camposVazios[] = 'tipo-sangue';
        if (empty($criancaAtipica)) $camposVazios[] = 'atipica';
        if (empty($temAlergiasCondicoes)) $camposVazios[] = 'condicao-crianca';
        if (empty($medicacaoContinua)) $camposVazios[] = 'medicacao-crianca';
        if (empty($tipoEscola)) $camposVazios[] = 'tipo-escola';
        if (empty($enderecoEscola)) $camposVazios[] = 'endereco-escola';
        if (empty($amplaConcorrencia)) $camposVazios[] = 'concorrencia';
        if (empty($vagaMilitar)) $camposVazios[] = 'vaga-militar';
        if (empty($tamanhoCamisa)) $camposVazios[] = 'tamanho-camisa';
        if (empty($tamanhoCalca)) $camposVazios[] = 'tamanho-calca';
        if (empty($tamanhoCalcado)) $camposVazios[] = 'tamanho-calcado';
        
        if (!empty($camposVazios)) {
            $resposta['debug']['empty_fields'] = $camposVazios;
            throw new Exception('Preencha todos os campos obrigatórios: ' . implode(', ', $camposVazios));
        }
        
        // Validações específicas para os novos campos
        if (!in_array($genero, ['feminino', 'masculino'])) {
            throw new Exception('Gênero deve ser "feminino" ou "masculino"');
        }

        if (!in_array($cadastro_unico, ['sim', 'nao'])) {
            throw new Exception('Campo "Cadastro Único" deve ser "sim" ou "nao"');
        }

        if (!in_array($parentesco, ['pai', 'mae', 'avó', 'avô', 'tio', 'tia', 'outro'])) {
            throw new Exception('Campo "Parentesco" deve ter um valor válido');
        }

        // VALIDAÇÕES DOS NOVOS CAMPOS
        $tiposSanguineos = ['a+', 'a-', 'b+', 'b-', 'ab+', 'ab-', 'o+', 'o-'];
        if (!in_array(strtolower($tipoSangue), $tiposSanguineos)) {
            throw new Exception('Tipo sanguíneo deve ser válido');
        }

        if (!in_array($criancaAtipica, ['sim', 'nao'])) {
            throw new Exception('Campo "Criança atípica" deve ser "sim" ou "nao"');
        }

        if (!in_array($temAlergiasCondicoes, ['sim', 'nao'])) {
            throw new Exception('Campo "Alergias/condições" deve ser "sim" ou "nao"');
        }

        if (!in_array($medicacaoContinua, ['sim', 'nao'])) {
            throw new Exception('Campo "Medicação contínua" deve ser "sim" ou "nao"');
        }

        if (!in_array($tipoEscola, ['publica', 'particular'])) {
            throw new Exception('Campo "Tipo da escola" deve ser "publica" ou "particular"');
        }

        if (!in_array($amplaConcorrencia, ['sim', 'nao'])) {
            throw new Exception('Campo "Ampla concorrência" deve ser "sim" ou "nao"');
        }

        if (!in_array($vagaMilitar, ['sim', 'nao'])) {
            throw new Exception('Campo "Vaga militar" deve ser "sim" ou "nao"');
        }

        // VALIDAÇÕES DOS CAMPOS DO UNIFORME
        $tamanhosCamisa = ['pp', 'p', 'm', 'g', 'gg', '6', '8', '10', '12', '14', '16'];
        if (!in_array($tamanhoCamisa, $tamanhosCamisa)) {
            throw new Exception('Tamanho da camisa deve ser válido');
        }

        $tamanhosCalca = ['pp', 'p', 'm', 'g', 'gg', '6', '8', '10', '12', '14', '16'];
        if (!in_array($tamanhoCalca, $tamanhosCalca)) {
            throw new Exception('Tamanho da calça deve ser válido');
        }

        $tamanhosCalcado = ['20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40'];
        if (!in_array($tamanhoCalcado, $tamanhosCalcado)) {
            throw new Exception('Tamanho do calçado deve ser válido');
        }
        
        // Processa o upload da foto do aluno
        $caminhoFoto = null; // Valor padrão caso não haja foto

        // Verifica e processa o upload da foto
        if(isset($_FILES['foto-aluno']) && $_FILES['foto-aluno']['error'] === UPLOAD_ERR_OK) {
            // Diretório onde a imagem será salva
            $diretorio_destino = "../uploads/fotos/";
            
            // Cria o diretório se não existir
            if (!file_exists($diretorio_destino)) {
                mkdir($diretorio_destino, 0755, true);
            }
            
            // Obtém informações do arquivo
            $nome_arquivo = $_FILES['foto-aluno']['name'];
            $arquivo_temporario = $_FILES['foto-aluno']['tmp_name'];
            $tamanho_arquivo = $_FILES['foto-aluno']['size'];
            $tipo_arquivo = $_FILES['foto-aluno']['type'];
            
            // Verifica o tamanho do arquivo (5MB = 5 * 1024 * 1024 bytes)
            if ($tamanho_arquivo > 5 * 1024 * 1024) {
                throw new Exception("O arquivo é muito grande. Tamanho máximo permitido: 5MB");
            }
            
            // Gera um nome único para o arquivo 
            $extensao = pathinfo($nome_arquivo, PATHINFO_EXTENSION);
            $nome_unico = uniqid() . '_' . date('YmdHis') . '.' . $extensao;
            $caminho_completo = $diretorio_destino . $nome_unico;
            
            // Verifica o tipo de arquivo
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (in_array($tipo_arquivo, $tipos_permitidos)) {
                // Move o arquivo do diretório temporário para o destino final
                if (move_uploaded_file($arquivo_temporario, $caminho_completo)) {
                    $caminhoFoto = $caminho_completo; // Salva o caminho para inserir no banco
                } else {
                    throw new Exception("Erro ao mover o arquivo.");
                }
            } else {
                throw new Exception("Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos.");
            }
        }

        // PROCESSA UPLOAD DO LAUDO DA CRIANÇA ATÍPICA
        $caminhoArquivoLaudo = null;
        if(isset($_FILES['arquivo-laudo']) && $_FILES['arquivo-laudo']['error'] === UPLOAD_ERR_OK) {
            $diretorio_laudos = "../uploads/laudos/";
            
            if (!file_exists($diretorio_laudos)) {
                mkdir($diretorio_laudos, 0755, true);
            }
            
            $nome_arquivo_laudo = $_FILES['arquivo-laudo']['name'];
            $arquivo_temporario_laudo = $_FILES['arquivo-laudo']['tmp_name'];
            $tamanho_arquivo_laudo = $_FILES['arquivo-laudo']['size'];
            $tipo_arquivo_laudo = $_FILES['arquivo-laudo']['type'];
            
            if ($tamanho_arquivo_laudo > 10 * 1024 * 1024) { // 10MB para documentos
                throw new Exception("O laudo é muito grande. Tamanho máximo permitido: 10MB");
            }
            
            $extensao_laudo = pathinfo($nome_arquivo_laudo, PATHINFO_EXTENSION);
            $nome_unico_laudo = 'laudo_' . uniqid() . '_' . date('YmdHis') . '.' . $extensao_laudo;
            $caminho_completo_laudo = $diretorio_laudos . $nome_unico_laudo;
            
            $tipos_permitidos_laudo = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (in_array($tipo_arquivo_laudo, $tipos_permitidos_laudo)) {
                if (move_uploaded_file($arquivo_temporario_laudo, $caminho_completo_laudo)) {
                    $caminhoArquivoLaudo = $caminho_completo_laudo;
                } else {
                    throw new Exception("Erro ao mover o arquivo do laudo.");
                }
            } else {
                throw new Exception("Tipo de arquivo do laudo não permitido. Apenas JPG, PNG, GIF, PDF, DOC e DOCX são aceitos.");
            }
        }

        // PROCESSA UPLOAD DO ATESTADO MÉDICO
        $caminhoAtestadoMedico = null;
        if(isset($_FILES['atestado-medico']) && $_FILES['atestado-medico']['error'] === UPLOAD_ERR_OK) {
            $diretorio_atestados = "../uploads/atestados/";
            
            if (!file_exists($diretorio_atestados)) {
                mkdir($diretorio_atestados, 0755, true);
            }
            
            $nome_arquivo_atestado = $_FILES['atestado-medico']['name'];
            $arquivo_temporario_atestado = $_FILES['atestado-medico']['tmp_name'];
            $tamanho_arquivo_atestado = $_FILES['atestado-medico']['size'];
            $tipo_arquivo_atestado = $_FILES['atestado-medico']['type'];
            
            if ($tamanho_arquivo_atestado > 10 * 1024 * 1024) { // 10MB para documentos
                throw new Exception("O atestado é muito grande. Tamanho máximo permitido: 10MB");
            }
            
            $extensao_atestado = pathinfo($nome_arquivo_atestado, PATHINFO_EXTENSION);
            $nome_unico_atestado = 'atestado_' . uniqid() . '_' . date('YmdHis') . '.' . $extensao_atestado;
            $caminho_completo_atestado = $diretorio_atestados . $nome_unico_atestado;
            
            $tipos_permitidos_atestado = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (in_array($tipo_arquivo_atestado, $tipos_permitidos_atestado)) {
                if (move_uploaded_file($arquivo_temporario_atestado, $caminho_completo_atestado)) {
                    $caminhoAtestadoMedico = $caminho_completo_atestado;
                } else {
                    throw new Exception("Erro ao mover o arquivo do atestado.");
                }
            } else {
                throw new Exception("Tipo de arquivo do atestado não permitido. Apenas JPG, PNG, GIF, PDF, DOC e DOCX são aceitos.");
            }
        }
        
        $numeroMatricula = 'SA' . date('Y') . mt_rand(1000, 9999);
        $dataMatricula = date('Y-m-d H:i:s');
        
        try {
            $checarTabela = $conexao->query("SHOW TABLES LIKE 'alunos'");
            if ($checarTabela->rowCount() == 0) {
                throw new Exception("A tabela 'alunos' não existe. Execute o script SQL para criar as tabelas.");
            }
        } catch (PDOException $e) {
            throw new Exception("Erro ao verificar tabelas: " . $e->getMessage());
        }
        
        $conexao->beginTransaction();
        
        // Consulta SQL ATUALIZADA para incluir os novos campos
        $sqlAluno = "INSERT INTO alunos (
            nome, data_nascimento, genero, cadastro_unico, rg, cpf, escola, serie, info_saude, 
            numero_matricula, data_matricula, foto, telefone_escola, diretor_escola,
            tipo_sanguineo, crianca_atipica, atipica_com_laudo, arquivo_laudo, tem_alergias_condicoes, 
            detalhes_alergias_condicoes, medicacao_continua, detalhes_medicacao, 
            numero_cadastro_unico, tipo_escola, endereco_escola, atestado_medico,
            ampla_concorrencia, vaga_militar, tamanho_camisa, tamanho_calca, tamanho_calcado
        ) VALUES (
            :nome, :data_nascimento, :genero, :cadastro_unico, :rg, :cpf, :escola, :serie, :info_saude, 
            :numero_matricula, :data_matricula, :foto, :telefone_escola, :diretor_escola,
            :tipo_sanguineo, :crianca_atipica, :atipica_com_laudo, :arquivo_laudo, :tem_alergias_condicoes, 
            :detalhes_alergias_condicoes, :medicacao_continua, :detalhes_medicacao, 
            :numero_cadastro_unico, :tipo_escola, :endereco_escola, :atestado_medico,
            :ampla_concorrencia, :vaga_militar, :tamanho_camisa, :tamanho_calca, :tamanho_calcado
        )";
        
        $stmtAluno = $conexao->prepare($sqlAluno);
        $stmtAluno->bindParam(':nome', $nomeAluno);
        $stmtAluno->bindParam(':data_nascimento', $dataNascimento);
        $stmtAluno->bindParam(':genero', $genero);
        $stmtAluno->bindParam(':cadastro_unico', $cadastro_unico);
        $stmtAluno->bindParam(':rg', $rgAluno);
        $stmtAluno->bindParam(':cpf', $cpfAluno);
        $stmtAluno->bindParam(':escola', $escola);
        $stmtAluno->bindParam(':serie', $serie);
        $stmtAluno->bindParam(':info_saude', $infoSaude);
        $stmtAluno->bindParam(':numero_matricula', $numeroMatricula);
        $stmtAluno->bindParam(':data_matricula', $dataMatricula);
        $stmtAluno->bindParam(':telefone_escola', $telefoneEscola);
        $stmtAluno->bindParam(':diretor_escola', $diretorEscola);
        $stmtAluno->bindParam(':foto', $caminhoFoto);
        
        // BIND DOS NOVOS CAMPOS
        $stmtAluno->bindParam(':tipo_sanguineo', $tipoSangue);
        $stmtAluno->bindParam(':crianca_atipica', $criancaAtipica);
        $stmtAluno->bindParam(':atipica_com_laudo', $atipicaComLaudo);
        $stmtAluno->bindParam(':arquivo_laudo', $caminhoArquivoLaudo);
        $stmtAluno->bindParam(':tem_alergias_condicoes', $temAlergiasCondicoes);
        $stmtAluno->bindParam(':detalhes_alergias_condicoes', $detalhesAlergiasCondicoes);
        $stmtAluno->bindParam(':medicacao_continua', $medicacaoContinua);
        $stmtAluno->bindParam(':detalhes_medicacao', $detalhesMedicacao);
        $stmtAluno->bindParam(':numero_cadastro_unico', $numeroCadastroUnico);
        $stmtAluno->bindParam(':tipo_escola', $tipoEscola);
        $stmtAluno->bindParam(':endereco_escola', $enderecoEscola);
        $stmtAluno->bindParam(':atestado_medico', $caminhoAtestadoMedico);
        $stmtAluno->bindParam(':ampla_concorrencia', $amplaConcorrencia);
        $stmtAluno->bindParam(':vaga_militar', $vagaMilitar);
        $stmtAluno->bindParam(':tamanho_camisa', $tamanhoCamisa);
        $stmtAluno->bindParam(':tamanho_calca', $tamanhoCalca);
        $stmtAluno->bindParam(':tamanho_calcado', $tamanhoCalcado);
        
        $stmtAluno->execute();
        
        $alunoId = $conexao->lastInsertId();
        
        $temSegundoResponsavel = isset($_POST['tem_segundo_responsavel']) && $_POST['tem_segundo_responsavel'] == '1';

        $sqlResponsavel = "INSERT INTO responsaveis (
            nome, parentesco, rg, cpf, telefone, whatsapp, email, profissao
        ) VALUES (
            :nome, :parentesco, :rg, :cpf, :telefone, :whatsapp, :email, :profissao
        )";

        $stmtResponsavel = $conexao->prepare($sqlResponsavel);
        $stmtResponsavel->bindParam(':nome', $nomeResponsavel);
        $stmtResponsavel->bindParam(':parentesco', $parentesco);
        $stmtResponsavel->bindParam(':rg', $rgResponsavel);
        $stmtResponsavel->bindParam(':cpf', $cpfResponsavel);
        $stmtResponsavel->bindParam(':telefone', $telefone);
        $stmtResponsavel->bindParam(':whatsapp', $whatsapp);
        $stmtResponsavel->bindParam(':email', $email);
        $stmtResponsavel->bindParam(':profissao', $profissao);
        $stmtResponsavel->execute();

        // Recupera o ID do primeiro responsável
        $responsavelId = $conexao->lastInsertId();

        // Cria a primeira relação aluno-responsável
        $sqlAlunoResp = "INSERT INTO aluno_responsavel (aluno_id, responsavel_id) VALUES (:aluno_id, :responsavel_id)";
        $stmtAlunoResp = $conexao->prepare($sqlAlunoResp);
        $stmtAlunoResp->bindParam(':aluno_id', $alunoId);
        $stmtAlunoResp->bindParam(':responsavel_id', $responsavelId);
        $stmtAlunoResp->execute();

        if ($temSegundoResponsavel) {
            $nomeResponsavel2 = limparDados($_POST['nome-responsavel-2'] ?? '');
            $parentesco2 = limparDados($_POST['parentesco-2'] ?? '');
            $rgResponsavel2 = limparDados($_POST['rg-responsavel-2'] ?? '');
            $cpfResponsavel2 = limparDados($_POST['cpf-responsavel-2'] ?? '');
            $telefone2 = limparDados($_POST['telefone-2'] ?? '');
            $whatsapp2 = limparDados($_POST['whatsapp-2'] ?? '');
            $email2 = limparDados($_POST['email-2'] ?? '');
            $profissao2 = limparDados($_POST['profissao-responsavel-2'] ?? '');
            
            $sqlResponsavel2 = "INSERT INTO responsaveis (
                nome, parentesco, rg, cpf, telefone, whatsapp, email, profissao
            ) VALUES (
                :nome, :parentesco, :rg, :cpf, :telefone, :whatsapp, :email, :profissao
            )";
            
            $stmtResponsavel2 = $conexao->prepare($sqlResponsavel2);
            $stmtResponsavel2->bindParam(':nome', $nomeResponsavel2);
            $stmtResponsavel2->bindParam(':parentesco', $parentesco2);
            $stmtResponsavel2->bindParam(':rg', $rgResponsavel2);
            $stmtResponsavel2->bindParam(':cpf', $cpfResponsavel2);
            $stmtResponsavel2->bindParam(':telefone', $telefone2);
            $stmtResponsavel2->bindParam(':whatsapp', $whatsapp2);
            $stmtResponsavel2->bindParam(':email', $email2);
            $stmtResponsavel2->bindParam(':profissao', $profissao2);
            $stmtResponsavel2->execute();
            
            $responsavelId2 = $conexao->lastInsertId();
            
            $sqlAlunoResp2 = "INSERT INTO aluno_responsavel (aluno_id, responsavel_id) VALUES (:aluno_id, :responsavel_id)";
            $stmtAlunoResp2 = $conexao->prepare($sqlAlunoResp2);
            $stmtAlunoResp2->bindParam(':aluno_id', $alunoId);
            $stmtAlunoResp2->bindParam(':responsavel_id', $responsavelId2);
            $stmtAlunoResp2->execute();
        }

        $sqlEndereco = "INSERT INTO enderecos (
            aluno_id, cep, logradouro, numero, complemento, bairro, cidade
        ) VALUES (
            :aluno_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade
        )";
        
        $stmtEndereco = $conexao->prepare($sqlEndereco);
        $stmtEndereco->bindParam(':aluno_id', $alunoId);
        $stmtEndereco->bindParam(':cep', $cep);
        $stmtEndereco->bindParam(':logradouro', $endereco);
        $stmtEndereco->bindParam(':numero', $numero);
        $stmtEndereco->bindParam(':complemento', $complemento);
        $stmtEndereco->bindParam(':bairro', $bairro);
        $stmtEndereco->bindParam(':cidade', $cidade);
        $stmtEndereco->execute();
        
        $sqlMatricula = "INSERT INTO matriculas (
            aluno_id, unidade, turma, data_matricula, consentimento
        ) VALUES (
            :aluno_id, :unidade, :turma, :data_matricula, :consentimento
        )";
        
        $stmtMatricula = $conexao->prepare($sqlMatricula);
        $stmtMatricula->bindParam(':aluno_id', $alunoId);
        $stmtMatricula->bindParam(':unidade', $unidade);
        $stmtMatricula->bindParam(':turma', $turma);
        $stmtMatricula->bindParam(':data_matricula', $dataMatricula);
        $stmtMatricula->bindParam(':consentimento', $consentimento);
        $stmtMatricula->execute();
        
        $conexao->commit();
        
        $resposta['success'] = true;
        $resposta['message'] = 'Matrícula realizada com sucesso!';
        $resposta['matricula'] = $numeroMatricula;
        $resposta['email'] = $email;
        
    } catch (Exception $e) {
        
        if (isset($conexao) && $conexao->inTransaction()) {
            $conexao->rollBack();
        }
        
        $resposta['message'] = $e->getMessage();
        $resposta['debug']['error'] = $e->getMessage();
        $resposta['debug']['trace'] = $e->getTraceAsString();
    }
} else {
    $resposta['message'] = 'Método de requisição inválido.';
    $resposta['debug']['request_method'] = $_SERVER['REQUEST_METHOD'];
}

header('Content-Type: application/json');
echo json_encode($resposta);
?>