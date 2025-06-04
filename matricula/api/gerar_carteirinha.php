<?php
// api/gerar_carteirinha.php - Carteirinha com design fixo e dados personalizados
// Habilitar exibição de erros para diagnóstico
ini_set('display_errors', 0); // Desabilitar exibição de erros para evitar problemas com headers
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

// Verificar se foi enviado alunos_ids
if (!isset($_POST['alunos_ids']) || empty($_POST['alunos_ids'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nenhum aluno selecionado']);
    exit;
}

// Carregar FPDF
if (file_exists('../vendor/setasign/fpdf/fpdf.php')) {
    require '../vendor/setasign/fpdf/fpdf.php';
} else if (file_exists('vendor/setasign/fpdf/fpdf.php')) {
    require 'vendor/setasign/fpdf/fpdf.php';
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Biblioteca FPDF não encontrada']);    
    exit;
}

// Tentar incluir o arquivo de conexão (tentando vários caminhos possíveis)
$conexaoIncluida = false;

// Primeiro tenta o caminho que estava funcionando antes
if (file_exists('.../config/database.php')) {
    require_once '../config/database.php';
    $conexaoIncluida = true;
}

// Segundo tenta o caminho atual
if (!$conexaoIncluida && file_exists('conexao.php')) {
    require_once 'conexao.php';
    $conexaoIncluida = true;
}

// Tentar outros caminhos comuns
if (!$conexaoIncluida) {
    $possiveisCaminhos = [
        'config/database.php',
        '../conexao.php',
        '../../config/database.php',
        '../../conexao.php'
    ];
    
    foreach ($possiveisCaminhos as $caminho) {
        if (file_exists($caminho)) {
            require_once $caminho;
            $conexaoIncluida = true;
            break;
        }
    }
}

// Verificar se a conexão foi incluída com sucesso
if (!$conexaoIncluida) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Arquivo de conexão com o banco de dados não encontrado']);
    exit;
}

// Verificar se a conexão foi estabelecida
if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Conexão com o banco de dados não disponível']);
    exit;
}

// Certifique-se que a conexão está utilizando UTF-8
if ($conn && method_exists($conn, 'set_charset')) {
    $conn->set_charset("utf8");
}

// Processar IDs dos alunos
$alunos_ids = explode(',', $_POST['alunos_ids']);
$alunos_ids = array_filter($alunos_ids, 'is_numeric');

if (empty($alunos_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'IDs de alunos inválidos']);
    exit;
}

// Consulta SQL com JOIN para buscar os dados necessários
$ids_string = implode(',', array_map('intval', $alunos_ids));
$query = "SELECT a.id, a.nome, a.numero_matricula, a.foto, a.serie, 
                 u.nome AS unidade_nome, 
                 t.nome_turma AS turma_nome 
          FROM alunos a
          LEFT JOIN matriculas m ON a.id = m.aluno_id
          LEFT JOIN turma t ON m.turma = t.id
          LEFT JOIN unidade u ON m.unidade = u.id
          WHERE a.id IN (" . $ids_string . ")";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Nenhum aluno encontrado com os IDs fornecidos']);
    exit;
}

// Estender FPDF para carteirinhas personalizadas
class CarteirinhaPDF extends FPDF {
    // Cores
    private $azulEscuro = [28, 63, 99]; // Azul escuro principal
    private $cinzaClaro = [230, 230, 230]; // Cinza claro para fundo
    private $branco = [255, 255, 255]; // Branco
    private $preto = [0, 0, 0]; // Preto
    private $amarelo = [240, 200, 70]; // Amarelo para logo
    
    // Sobrescrever o método Cell para lidar com UTF-8
    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        // Converte do UTF-8 para ISO-8859-1
        $txt = $this->converte_caracteres($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    // Método para converter caracteres especiais
    function converte_caracteres($texto) {
        if (function_exists('iconv')) {
            return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);
        } elseif (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        } else {
            // Se nenhuma função de conversão estiver disponível, tenta usar utf8_decode
            return utf8_decode($texto);
        }
    }
    
    // Método para gerar uma carteirinha de aluno
    function gerarCarteirinha($aluno) {
        // Dimensões do cartão
        $largura = 85; // mm
        $altura = 55; // mm
        $espacamento = 8; // mm entre frente e verso
        
        // Posição inicial
        $x = 10;
        $y = 10;
        
        // FRENTE DA CARTEIRINHA - Dados personalizados do aluno
        $this->gerarFrenteCarteirinha($x, $y, $largura, $altura, $aluno);
        
        // VERSO DA CARTEIRINHA - Dados fixos para todos os alunos
        // Usando exatamente a mesma altura da frente
        $this->gerarVersoCarteirinha($x, $y + $altura + $espacamento, $largura, $altura);
    }
    
    // Método para gerar a frente da carteirinha (personalizada por aluno)
    function gerarFrenteCarteirinha($x, $y, $largura, $altura, $aluno) {
        // Base azul escuro
        $this->SetFillColor($this->azulEscuro[0], $this->azulEscuro[1], $this->azulEscuro[2]);
        $this->RoundedRect($x, $y, $largura, $altura, 5, 'F');
        
        // Título do Projeto SuperAção
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor($this->branco[0], $this->branco[1], $this->branco[2]);
        $this->SetXY($x + 30, $y + 6);
        $this->Cell($largura - 30, 8, 'ProjetoSuperAção', 0, 0, 'L');
        
        // Logo no canto superior esquerdo
        $this->desenharLogo($x + 15, $y + 10, 8);
        
        // Fundo branco para área principal
        $this->SetFillColor($this->branco[0], $this->branco[1], $this->branco[2]);
        $this->RoundedRect($x + 2, $y + 18, $largura - 4, $altura - 20, 5, 'F');
        
        // Área para foto do aluno
        $larguraFoto = $altura * 0.55;
        $alturaFoto = $larguraFoto;
        $xFoto = $x + 5;
        $yFoto = $y + 20;
        
        // Borda para a foto
        $this->SetDrawColor($this->azulEscuro[0], $this->azulEscuro[1], $this->azulEscuro[2]);
        $this->Rect($xFoto, $yFoto, $larguraFoto, $alturaFoto, 'D');
        
        // Verificar e incluir a foto do aluno
        if (isset($aluno['foto']) && !empty($aluno['foto'])) {
            // Extrair apenas o nome do arquivo da foto
            $nomeArquivo = basename($aluno['foto']);
            
            // Tentar diferentes possíveis caminhos onde a foto pode estar
            $possiveisCaminhos = [
                $aluno['foto'],                      // Caminho exato do banco de dados
                '../uploads/fotos/' . $nomeArquivo,  // Relativo ao script atual
                'uploads/fotos/' . $nomeArquivo,     // Sem o "../"
                '../../uploads/fotos/' . $nomeArquivo, // Subindo mais um nível
                '../../../uploads/fotos/' . $nomeArquivo, // Subindo ainda mais
                '../../../../uploads/fotos/' . $nomeArquivo, // Extremo caso
                $_SERVER['DOCUMENT_ROOT'] . '/uploads/fotos/' . $nomeArquivo // Caminho absoluto
            ];
            
            $fotoEncontrada = false;
            foreach ($possiveisCaminhos as $caminho) {
                if (file_exists($caminho)) {
                    // Se encontrou a foto, usar este caminho
                    $this->Image($caminho, $xFoto, $yFoto, $larguraFoto, $alturaFoto);
                    $fotoEncontrada = true;
                    break;
                }
            }
            
            // Se nenhum dos caminhos funcionou, mostrar o placeholder
            if (!$fotoEncontrada) {
                $this->SetTextColor(200, 200, 200);
                $this->SetFont('Arial', '', 12);
                $this->SetXY($xFoto, $yFoto + ($alturaFoto/2) - 3);
                $this->Cell($larguraFoto, 6, 'FOTO', 0, 0, 'C');
            }
        } else {
            // Placeholder para foto quando não há caminho de foto
            $this->SetTextColor(200, 200, 200);
            $this->SetFont('Arial', '', 12);
            $this->SetXY($xFoto, $yFoto + ($alturaFoto/2) - 3);
            $this->Cell($larguraFoto, 6, 'FOTO', 0, 0, 'C');
        }
        
        // Dados do aluno no lado direito
        $xDados = $xFoto + $larguraFoto + 3;
        $yDados = $yFoto + 2;
        $larguraDados = $largura - $larguraFoto - 8;
        
        // Nome do aluno
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($this->azulEscuro[0], $this->azulEscuro[1], $this->azulEscuro[2]);
        $this->SetXY($xDados, $yDados);
        
        // Obter dados do aluno - CORRIGIDO
        $nome = isset($aluno['nome']) ? $aluno['nome'] : 'Nome do Aluno';
        $matricula = isset($aluno['numero_matricula']) ? $aluno['numero_matricula'] : '------';
        $unidade = isset($aluno['unidade_nome']) ? $aluno['unidade_nome'] : '------';
        $turma = isset($aluno['turma_nome']) ? $aluno['turma_nome'] : 'Sub-8 Manha';
        $validade = "31/12/2024"; // Data fixa
        
        // Se o nome for muito grande, reduzir o tamanho da fonte
        if (strlen($nome) > 20) {
            $this->SetFont('Arial', 'B', 9);
        }
        
        // Imprimir dados
        $this->Cell($larguraDados, 5, $this->limitarTexto($nome, 25), 0, 1, 'L');
        
        $this->SetFont('Arial', '', 8);
        $this->SetXY($xDados, $yDados + 6);
        $this->Cell($larguraDados, 4, "Matricula: " . $matricula, 0, 1, 'L');
        
        $this->SetXY($xDados, $yDados + 11);
        $this->Cell($larguraDados, 4, "Unidade: " . $unidade, 0, 1, 'L');
        
        $this->SetXY($xDados, $yDados + 16);
        $this->Cell($larguraDados, 4, "Turma: " . $turma, 0, 1, 'L');
        
        $this->SetXY($xDados, $yDados + 21);
        $this->Cell($larguraDados, 4, "Validade: " . $validade, 0, 1, 'L');
    }
    
    // Método para gerar o verso da carteirinha (fixo para todos alunos)
    function gerarVersoCarteirinha($x, $y, $largura, $altura) {
        // Base azul escuro
        $this->SetFillColor($this->azulEscuro[0], $this->azulEscuro[1], $this->azulEscuro[2]);
        $this->RoundedRect($x, $y, $largura, $altura, 5, 'F');
        
        // Título informações de contato - Reduzir espaçamento
        $this->SetFont('Arial', 'B', 9); // Reduzir tamanho da fonte
        $this->SetTextColor($this->branco[0], $this->branco[1], $this->branco[2]);
        $this->SetXY($x + 5, $y + 4);
        $this->Cell($largura - 10, 4, 'Informacoes de Contato', 0, 1, 'L');
        
        // Dados de contato - Reduzir espaçamento vertical
        $this->SetFont('Arial', '', 7); // Reduzir tamanho da fonte
        
        // Endereço
        $this->SetXY($x + 5, $y + 9);
        $this->Cell(17, 3.5, 'Endereco:', 0, 0, 'L');
        $this->Cell($largura - 22, 3.5, 'Av. Contorno, Setor Oeste, Goiania - GO', 0, 1, 'L');
        
        // Telefone
        $this->SetXY($x + 5, $y + 13);
        $this->Cell(17, 3.5, 'Telefone:', 0, 0, 'L');
        $this->Cell($largura - 22, 3.5, '(62) 3333-8888', 0, 1, 'L');
        
        // Email
        $this->SetXY($x + 5, $y + 17);
        $this->Cell(17, 3.5, 'E-mail:', 0, 0, 'L');
        $this->Cell($largura - 22, 3.5, 'contato@superacao.org.br', 0, 1, 'L');
        
        // Título horários de treino
        $this->SetFont('Arial', 'B', 9); // Mesmo tamanho do título anterior
        $this->SetXY($x + 5, $y + 23);
        $this->Cell($largura - 10, 4, 'Horarios de Treino', 0, 1, 'L');
        
        // Horários
        $this->SetFont('Arial', '', 7); // Mesmo tamanho do texto anterior
        
        // Segunda e Quarta
        $this->SetXY($x + 5, $y + 28);
        $this->Cell($largura - 10, 3.5, 'Segunda e Quarta: 17:00 as 20:00 (7 a 12 Anos)', 0, 1, 'L');
        
        // Terça e Sexta
        $this->SetXY($x + 5, $y + 32);
        $this->Cell($largura - 10, 3.5, 'Terca e Sexta: 18:00 as 20:00 (Acima de 13 Anos)', 0, 1, 'L');
        
        // Observação no rodapé - Ajustar posição para ficar na parte inferior
        $this->SetFont('Arial', 'I', 5.5); // Reduzir ainda mais para caber
        $this->SetXY($x + 3, $y + $altura - 10);
        $this->Cell($largura - 6, 3, 'Em caso de perda, favor entrar em contato. Esta carteirinha e de uso', 0, 1, 'C');
        $this->SetXY($x + 3, $y + $altura - 7);
        $this->Cell($largura - 6, 3, 'pessoal e intransferivel. Apresente-a sempre ao entrar nas', 0, 1, 'C');
        $this->SetXY($x + 3, $y + $altura - 4);
        $this->Cell($largura - 6, 3, 'dependencias do projeto.', 0, 1, 'C');
    }
    
    // Método para desenhar o logo
    function desenharLogo($x, $y, $tamanho) {
        // Círculo azul externo
        $this->SetFillColor($this->azulEscuro[0], $this->azulEscuro[1], $this->azulEscuro[2]);
        $this->Circle($x, $y, $tamanho, 'F');
        
        // Círculo amarelo interno
        $this->SetFillColor($this->amarelo[0], $this->amarelo[1], $this->amarelo[2]);
        $this->Circle($x, $y, $tamanho * 0.8, 'F');
    }
    
    // Método para limitar o tamanho do texto
    function limitarTexto($texto, $maxChars) {
        if (strlen($texto) <= $maxChars) {
            return $texto;
        }
        return substr($texto, 0, $maxChars - 3) . '...';
    }
    
    // Método para desenhar retângulos arredondados
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
    
    // Método para desenhar círculos
    function Circle($x, $y, $r, $style = 'D') {
        $this->Ellipse($x, $y, $r, $r, $style);
    }

    function Ellipse($x, $y, $rx, $ry, $style = 'D') {
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
            
        $lx = 4/3 * (M_SQRT2-1) * $rx;
        $ly = 4/3 * (M_SQRT2-1) * $ry;
        $k = $this->k;
        $h = $this->h;
        
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x)*$k, ($h-$y)*$k,
            ($x+$lx)*$k, ($h-$y)*$k,
            ($x+$rx)*$k, ($h-$y+$ly)*$k,
            ($x+$rx)*$k, ($h-$y+$ry)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x+$rx)*$k, ($h-$y+$ry+$ly)*$k,
            ($x+$lx)*$k, ($h-$y+$ry+$ry)*$k,
            ($x)*$k, ($h-$y+$ry+$ry)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x-$lx)*$k, ($h-$y+$ry+$ry)*$k,
            ($x-$rx)*$k, ($h-$y+$ry+$ly)*$k,
            ($x-$rx)*$k, ($h-$y+$ry)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x-$rx)*$k, ($h-$y+$ly)*$k,
            ($x-$lx)*$k, ($h-$y)*$k,
            ($x)*$k, ($h-$y)*$k,
            $op));
    }
}

try {
    // Criar PDF
    $pdf = new CarteirinhaPDF();
    $pdf->SetAutoPageBreak(false);

    // Para cada aluno, gerar uma carteirinha em uma nova página
    while ($aluno = $result->fetch_assoc()) {
        $pdf->AddPage('L', [180, 150]); // Orientação paisagem com tamanho adequado
        $pdf->gerarCarteirinha($aluno);
    }

    // Saída do PDF
    $timestamp = time();
    $filename = "carteirinhas_" . $timestamp . ".pdf";

    // Saída direta para o navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $pdf->Output('D', $filename);
    exit;
} catch (Exception $e) {
    // Em caso de erro, retornar mensagem JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao gerar PDF: ' . $e->getMessage()]);
    exit;
}