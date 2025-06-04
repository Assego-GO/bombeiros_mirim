<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: ../matricula/index.php');
    exit;
}

// Verificar parâmetros
if ((!isset($_GET['aluno_id']) || !isset($_GET['turma_id'])) && !isset($_GET['avaliacao_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Se você ainda não tiver a biblioteca TCPDF, precisará instalá-la
// Você pode baixá-la de: https://github.com/tecnickcom/TCPDF
// Ou usar Composer: composer require tecnickcom/tcpdf

// Incluir biblioteca TCPDF (mude o caminho se necessário)
require_once('tcpdf/tcpdf.php');

// Conexão com o banco
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (isset($_GET['avaliacao_id'])) {
        // Buscar dados de uma avaliação específica
        $avaliacao_id = $_GET['avaliacao_id'];
        
        $query = "SELECT a.*, al.nome as aluno_nome, al.numero_matricula, al.serie, 
                         t.nome_turma, p.nome as professor_nome
                  FROM avaliacoes a
                  JOIN alunos al ON a.aluno_id = al.id
                  JOIN turma t ON a.turma_id = t.id
                  JOIN professor p ON a.professor_id = p.id
                  WHERE a.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$avaliacao_id]);
        
        if ($stmt->rowCount() == 0) {
            header('Location: dashboard.php');
            exit;
        }
        
        $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);
        $aluno_nome = $avaliacao['aluno_nome'];
        $avaliacoes = [$avaliacao];
        $titulo = "Avaliação de {$aluno_nome} - " . date('d/m/Y', strtotime($avaliacao['data_avaliacao']));
        
    } else {
        // Buscar todas as avaliações de um aluno em uma turma
        $aluno_id = $_GET['aluno_id'];
        $turma_id = $_GET['turma_id'];
        
        // Verificar se o aluno existe nessa turma
        $query = "SELECT a.nome as aluno_nome, a.numero_matricula, a.serie, t.nome_turma
                  FROM alunos a
                  JOIN matriculas m ON a.id = m.aluno_id
                  JOIN turma t ON m.turma = t.id
                  WHERE a.id = ? AND m.turma = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$aluno_id, $turma_id]);
        
        if ($stmt->rowCount() == 0) {
            header('Location: dashboard.php');
            exit;
        }
        
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        $aluno_nome = $aluno['aluno_nome'];
        
        // Buscar todas as avaliações
        $query = "SELECT a.*, p.nome as professor_nome
                  FROM avaliacoes a
                  JOIN professor p ON a.professor_id = p.id
                  WHERE a.aluno_id = ? AND a.turma_id = ?
                  ORDER BY a.data_avaliacao DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$aluno_id, $turma_id]);
        $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($avaliacoes)) {
            header('Location: avaliacoes_aluno.php?aluno_id=' . $aluno_id . '&turma_id=' . $turma_id);
            exit;
        }
        
        // Adicionar informações do aluno a cada avaliação
        foreach ($avaliacoes as &$avaliacao) {
            $avaliacao['aluno_nome'] = $aluno['aluno_nome'];
            $avaliacao['numero_matricula'] = $aluno['numero_matricula'];
            $avaliacao['serie'] = $aluno['serie'];
            $avaliacao['nome_turma'] = $aluno['nome_turma'];
        }
        
        $titulo = "Relatório de Avaliações - {$aluno_nome}";
    }
    
    // Criar PDF
    class MYPDF extends TCPDF {
        public function Header() {
            // Logo
            $image_file = 'img/logo-escolinha.png';
            if (file_exists($image_file)) {
                $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Título do cabeçalho
            $this->SetFont('helvetica', 'B', 12);
            $this->SetXY(40, 10);
            $this->Cell(0, 10, 'Escolinha de Futebol - Relatório de Avaliação', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $this->SetXY(40, 16);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 10, 'Sistema de Avaliação de Desempenho', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            
            // Linha horizontal
            $this->SetY(25);
            $this->Line(10, 25, $this->getPageWidth() - 10, 25);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }
    
    $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('Escolinha de Futebol');
    $pdf->SetAuthor('Sistema de Avaliação');
    $pdf->SetTitle($titulo);
    $pdf->SetSubject('Relatório de Avaliação');
    
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    $pdf->AddPage();
    
    // Título
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Para cada avaliação
    foreach ($avaliacoes as $avaliacao) {
        // Informações do Aluno
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Informações do Aluno', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(50, 7, 'Nome:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['aluno_nome'], 0, 1, 'L');
        
        $pdf->Cell(50, 7, 'Matrícula:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['numero_matricula'], 0, 1, 'L');
        
        $pdf->Cell(50, 7, 'Série:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['serie'], 0, 1, 'L');
        
        $pdf->Cell(50, 7, 'Turma:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['nome_turma'], 0, 1, 'L');
        
        $pdf->Cell(50, 7, 'Data da Avaliação:', 0, 0, 'L');
        $pdf->Cell(0, 7, date('d/m/Y', strtotime($avaliacao['data_avaliacao'])), 0, 1, 'L');
        
        $pdf->Cell(50, 7, 'Professor:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['professor_nome'], 0, 1, 'L');
        
        $pdf->Ln(5);
        
        // Medidas Físicas
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Medidas Físicas', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        if (!empty($avaliacao['altura']) || !empty($avaliacao['peso']) || !empty($avaliacao['imc'])) {
            if (!empty($avaliacao['altura'])) {
                $pdf->Cell(50, 7, 'Altura:', 0, 0, 'L');
                $pdf->Cell(0, 7, $avaliacao['altura'] . ' cm', 0, 1, 'L');
            }
            
            if (!empty($avaliacao['peso'])) {
                $pdf->Cell(50, 7, 'Peso:', 0, 0, 'L');
                $pdf->Cell(0, 7, $avaliacao['peso'] . ' kg', 0, 1, 'L');
            }
            
            if (!empty($avaliacao['imc'])) {
                $pdf->Cell(50, 7, 'IMC:', 0, 0, 'L');
                $pdf->Cell(0, 7, $avaliacao['imc'] . ' (' . $avaliacao['imc_status'] . ')', 0, 1, 'L');
            }
        } else {
            $pdf->Cell(0, 7, 'Não foram registradas medidas físicas nesta avaliação.', 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Desempenho Físico
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Desempenho Físico', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        // Função para desenhar barras coloridas
        $drawBar = function($pdf, $y, $value, $color = array(13, 45, 86)) {
            $barWidth = 100; // Comprimento total da barra
            $barHeight = 5; // Altura da barra
            $x = 65; // Posição X inicial da barra
            
            // Fundo da barra (cinza claro)
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Rect($x, $y, $barWidth, $barHeight, 'F');
            
            // Barra preenchida (valor)
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->Rect($x, $y, $barWidth * ($value/10), $barHeight, 'F');
            
            // Borda da barra
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->Rect($x, $y, $barWidth, $barHeight, 'D');
        };
        
        $pdf->Cell(50, 7, 'Velocidade:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['velocidade'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['velocidade']);
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Resistência:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['resistencia'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['resistencia']);
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Coordenação:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['coordenacao'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['coordenacao']);
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Agilidade:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['agilidade'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['agilidade']);
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Força:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['forca'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['forca']);
        $pdf->Ln(7);
        
        if (!empty($avaliacao['desempenho_detalhes'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, 'Detalhes do Desempenho:', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            // Definir estilo para o texto dos detalhes
            $pdf->SetFillColor(248, 249, 250);
            $pdf->SetDrawColor(230, 230, 230);
            
            // Calcular altura necessária para o texto
            $texto = $avaliacao['desempenho_detalhes'];
            
            // Adicionar o texto com fundo cinza claro
            $pdf->MultiCell(0, 0, $texto, 1, 'L', true);
            $pdf->Ln(5);
        }
        
        // Comportamento
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Comportamento', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        
        $pdf->Cell(50, 7, 'Participação:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['participacao'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['participacao'], array(52, 199, 89)); // Verde
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Trabalho em Equipe:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['trabalho_equipe'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['trabalho_equipe'], array(52, 199, 89));
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Disciplina:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['disciplina'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['disciplina'], array(52, 199, 89));
        $pdf->Ln(7);
        
        $pdf->Cell(50, 7, 'Respeito às Regras:', 0, 0, 'L');
        $pdf->Cell(0, 7, $avaliacao['respeito_regras'] . '/10', 0, 1, 'L');
        $drawBar($pdf, $pdf->GetY(), $avaliacao['respeito_regras'], array(52, 199, 89));
        $pdf->Ln(7);
        
        if (!empty($avaliacao['comportamento_notas'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, 'Notas sobre Comportamento:', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            // Definir estilo para o texto dos detalhes
            $pdf->SetFillColor(248, 249, 250);
            
            // Adicionar o texto com fundo cinza claro
            $pdf->MultiCell(0, 0, $avaliacao['comportamento_notas'], 1, 'L', true);
            $pdf->Ln(5);
        }
        
        // Observações
        if (!empty($avaliacao['observacoes'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Observações', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 12);
            
            // Definir estilo para o texto das observações
            $pdf->SetFillColor(248, 249, 250);
            
            // Adicionar o texto com fundo cinza claro
            $pdf->MultiCell(0, 0, $avaliacao['observacoes'], 1, 'L', true);
            $pdf->Ln(5);
        }
        
        // Se tiver mais de uma avaliação, adiciona uma nova página
        if (count($avaliacoes) > 1 && end($avaliacoes) !== $avaliacao) {
            $pdf->AddPage();
        }
    }
    
    // Adicionar assinatura na última página
    $pdf->SetY(-50);
    $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Assinatura do Professor', 0, 1, 'C');
    
    // Adicionar área para assinatura dos pais/responsáveis
    $pdf->Ln(10);
    $pdf->Cell(0, 0, '_________________________________', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Assinatura do Responsável', 0, 1, 'C');
    
    // Saída do PDF
    $pdf->Output('avaliacao_' . str_replace(' ', '_', $aluno_nome) . '.pdf', 'I');
    
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>