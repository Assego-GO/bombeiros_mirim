<?php
session_start();

// Verificação de administrador
if (!isset($_SESSION['usuario_id'])) {
  header('Location: index.php');
  exit;
}
require "../env_config.php";

$db_host =  $_ENV['DB_HOST'];
$db_name =  $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass =  $_ENV['DB_PASS'];
// Configuração do banco de dados

try {
  // Conexão com o banco de dados
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  // Verificar se o usuário é um administrador
  $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND tipo = 'admin'");
  $stmt->execute([$_SESSION['usuario_id']]);

  if ($stmt->rowCount() == 0) {
    // Não é um administrador
    header('Location: ../aluno/dashboard.php');
    exit;
  }
  
} catch(PDOException $e) {
  // Em caso de erro no banco de dados
  die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_tipo = 'Administrador';
$usuario_foto = './img/usuarios/' . ($_SESSION['usuario_foto'] ?? 'default.png');

// Get total students
$stmt = $pdo->query("SELECT COUNT(*) FROM alunos");
$totalAlunos = $stmt->fetchColumn();

// Get enrollment statistics
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM matriculas GROUP BY status");
$matriculasStats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $matriculasStats[$row['status']] = $row['total'];
}

// Get active enrollments count
$matriculasAtivas = $matriculasStats['ativo'] ?? 0;

// Get pending enrollments count
$matriculasPendentes = $matriculasStats['pendente'] ?? 0;

// Get inactive enrollments count
$matriculasInativas = $matriculasStats['inativo'] ?? 0;

// Get total classes
$stmt = $pdo->query("SELECT COUNT(*) FROM turma");
$totalTurmas = $stmt->fetchColumn();

// Get classes by status
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM turma GROUP BY status");
$turmasStatus = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $turmasStatus[$row['status']] = $row['total'];
}

// Get active classes
$turmasAtivas = $turmasStatus['Em Andamento'] ?? 0;

// Get planned classes
$turmasPlanejadas = $turmasStatus['Planejada'] ?? 0;

// Get completed classes
$turmasConcluidas = $turmasStatus['Concluída'] ?? 0;

// Get canceled classes
$turmasCanceladas = $turmasStatus['Cancelada'] ?? 0;

// Get total teachers
$stmt = $pdo->query("SELECT COUNT(*) FROM professor");
$totalProfessores = $stmt->fetchColumn();

// Get units
$stmt = $pdo->query("SELECT COUNT(*) FROM unidade");
$totalUnidades = $stmt->fetchColumn();

// Get unit list with class counts
$stmt = $pdo->query("
    SELECT u.nome as unidade, COUNT(t.id) as total_turmas 
    FROM unidade u
    LEFT JOIN turma t ON u.id = t.id_unidade
    GROUP BY u.id
    ORDER BY total_turmas DESC
");
$unidadesTurmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent enrollments (last 30 days)
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM matriculas 
    WHERE data_matricula >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$matriculasRecentes = $stmt->fetchColumn();

// Get class capacity statistics
$stmt = $pdo->query("
    SELECT 
        SUM(capacidade) as capacidade_total,
        SUM(matriculados) as alunos_matriculados
    FROM turma
");
$capacidadeStats = $stmt->fetch(PDO::FETCH_ASSOC);
$capacidadeTotal = $capacidadeStats['capacidade_total'] ?? 0;
$alunosMatriculados = $capacidadeStats['alunos_matriculados'] ?? 0;
$taxaOcupacao = ($capacidadeTotal > 0) ? round(($alunosMatriculados / $capacidadeTotal) * 100, 1) : 0;

// Monthly enrollments for the chart (last 6 months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(data_matricula, '%Y-%m') as mes,
        COUNT(*) as total
    FROM 
        matriculas
    WHERE 
        data_matricula >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY 
        DATE_FORMAT(data_matricula, '%Y-%m')
    ORDER BY 
        mes ASC
");

$matriculasMensais = $stmt->fetchAll(PDO::FETCH_ASSOC);
$mesesLabel = [];
$matriculasMesData = [];

foreach ($matriculasMensais as $row) {
    // Convert YYYY-MM to Month YYYY
    $timestamp = strtotime($row['mes'] . '-01');
    $mesesLabel[] = date('M Y', $timestamp);
    $matriculasMesData[] = $row['total'];
}

// Get student age distribution
$stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 10 THEN 'Até 10 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 10 AND 14 THEN '10-14 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 15 AND 18 THEN '15-18 anos'
            ELSE 'Acima de 18 anos'
        END as faixa_etaria,
        COUNT(*) as total
    FROM 
        alunos
    GROUP BY 
        faixa_etaria
    ORDER BY 
        faixa_etaria
");

$faixasEtarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the data for the chart
$faixasLabel = [];
$faixasData = [];

foreach ($faixasEtarias as $row) {
    $faixasLabel[] = $row['faixa_etaria'];
    $faixasData[] = $row['total'];
}

// Get attendance statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_presencas,
        SUM(CASE WHEN presente = 1 THEN 1 ELSE 0 END) as presentes,
        SUM(CASE WHEN presente = 0 THEN 1 ELSE 0 END) as ausentes
    FROM 
        presencas
    WHERE 
        data_aula >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
");
$presencasStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalPresencas = $presencasStats['total_presencas'] ?? 0;
$presentes = $presencasStats['presentes'] ?? 0;
$ausentes = $presencasStats['ausentes'] ?? 0;
$taxaPresenca = ($totalPresencas > 0) ? round(($presentes / $totalPresencas) * 100, 1) : 0;

// Get top classes by number of students
$stmt = $pdo->query("
    SELECT 
        t.nome_turma, 
        t.matriculados,
        t.capacidade,
        p.nome as professor,
        u.nome as unidade
    FROM 
        turma t
    JOIN 
        professor p ON t.id_professor = p.id
    JOIN 
        unidade u ON t.id_unidade = u.id
    ORDER BY 
        t.matriculados DESC
    LIMIT 5
");
$topTurmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Administrativo - Sistema Superação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
          --primary: #0a2647;
          --primary-light: #144272;
          --primary-dark: #071c35;
          --secondary: #ffc233;
          --secondary-light: #ffd566;
          --secondary-dark: #e9b424;
          --accent: #34c759;
          --accent-light: #4cd377;
          --accent-dark: #26a344;
          --danger: #f64e60;
          --danger-light: #ff6b7d;
          --light: #f5f7fd;
          --light-hover: #ecf0f9;
          --dark: #1a2b4b;
          --gray: #7c8db5;
          --gray-light: #d6dff0;
          --gray-dark: #4b5e88;
          --white: #ffffff;
          --box-shadow: 0 5px 15px rgba(10, 38, 71, 0.07);
          --box-shadow-hover: 0 8px 25px rgba(10, 38, 71, 0.12);
          --box-shadow-card: 0 10px 30px rgba(10, 38, 71, 0.05);
          --border-radius: 10px;
          --border-radius-lg: 12px;
          --border-radius-xl: 16px;
          --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
          
          --section-navy: #0a2647;
          --section-navy-dark: #071c35;
          --section-blue: #144272;
          --section-blue-dark: #0a2647;
          --section-amber: #ffc233;
          --section-amber-dark: #e9b424;
          --section-slate: #4b5e88;
          --section-slate-dark: #1a2b4b;
        }

        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
          background-color: var(--primary);
          color: var(--dark);
          line-height: 1.6;
          letter-spacing: 0.01em;
          background-image: radial-gradient(circle at 10% 20%, rgba(20, 66, 114, 0.4) 0%, rgba(20, 66, 114, 0.4) 50.3%, transparent 50.3%, transparent 100%),
            radial-gradient(circle at 85% 85%, rgba(20, 66, 114, 0.4) 0%, rgba(20, 66, 114, 0.4) 50.9%, transparent 50.9%, transparent 100%);
          background-attachment: fixed;
          font-size: 16px;
          min-height: 100vh;
        }

        .main-header {
          background-color: var(--primary-dark);
          color: white;
          padding: 1rem 0;
          box-shadow: var(--box-shadow);
          position: sticky;
          top: 0;
          z-index: 1000;
        }

        .header-container {
          max-width: 1300px;
          margin: 0 auto;
          padding: 0 1.5rem;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }

        .app-title {
          font-size: 1.5rem;
          font-weight: 700;
          letter-spacing: 0.015em;
        }

        .user-info {
          display: flex;
          align-items: center;
          gap: 0.75rem;
        }

        .user-name {
          font-weight: 500;
        }

        .container {
          max-width: 1300px;
          margin: 0 auto;
          padding: 1.5rem;
        }

        .page-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 1.5rem;
          padding-bottom: 1rem;
          border-bottom: 1px solid var(--gray-light);
          flex-wrap: wrap;
          gap: 1rem;
        }

        .page-title {
          color: var(--white);
          font-weight: 700;
          font-size: 1.8rem;
          letter-spacing: -0.01em;
        }

        .btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 0.6rem 1.25rem;
          border-radius: var(--border-radius);
          font-weight: 600;
          font-size: 0.9rem;
          cursor: pointer;
          text-decoration: none;
          transition: var(--transition);
          border: none;
          gap: 0.5rem;
          letter-spacing: 0.02em;
        }

        .btn-primary {
          background-color: var(--primary);
          color: var(--white);
        }

        .btn-primary:hover {
          background-color: var(--primary-light);
          transform: translateY(-2px);
          box-shadow: var(--box-shadow-hover);
        }

        .btn-light {
          background-color: var(--secondary);
          color: var(--primary-dark);
        }

        .btn-light:hover {
          background-color: var(--secondary-light);
          transform: translateY(-2px);
          box-shadow: var(--box-shadow-hover);
        }

        .card {
          background-color: var(--white);
          border-radius: var(--border-radius-lg);
          box-shadow: var(--box-shadow-card);
          margin-bottom: 1.5rem;
          overflow: hidden;
          border: none;
          transition: var(--transition);
        }

        .card:hover {
          box-shadow: var(--box-shadow-hover);
          transform: translateY(-3px);
        }

        .section-header {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          padding: 1rem 1.5rem;
        }

        .section-icon {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 42px;
          height: 42px;
          border-radius: 50%;
          font-size: 1.2rem;
          color: white;
          box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }

        .section-title {
          font-weight: 600;
          font-size: 1.2rem;
          color: var(--white);
          letter-spacing: 0.02em;
        }

        .section-navy {
          background-color: var(--section-navy);
        }

        .section-navy .section-icon {
          background-color: var(--section-navy-dark);
        }

        .section-blue {
          background-color: var(--section-blue);
        }

        .section-blue .section-icon {
          background-color: var(--section-blue-dark);
        }

        .section-amber {
          background-color: var(--section-amber);
        }

        .section-amber .section-icon {
          background-color: var(--section-amber-dark);
        }

        .section-amber .section-title {
          color: var(--primary-dark);
        }

        .section-slate {
          background-color: var(--section-slate);
        }

        .section-slate .section-icon {
          background-color: var(--section-slate-dark);
        }

        .card-body {
          padding: 1.5rem;
        }

        .row {
          display: flex;
          flex-wrap: wrap;
          margin-right: -0.75rem;
          margin-left: -0.75rem;
        }

        .col {
          flex: 1 0 0%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .col-12 {
          flex: 0 0 100%;
          max-width: 100%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .col-md-4 {
          flex: 0 0 100%;
          max-width: 100%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .col-md-6 {
          flex: 0 0 100%;
          max-width: 100%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .col-md-8 {
          flex: 0 0 100%;
          max-width: 100%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .col-xl-3, .col-lg-6, .col-xl-4, .col-lg-7, .col-xl-8, .col-lg-5 {
          flex: 0 0 100%;
          max-width: 100%;
          padding-right: 0.75rem;
          padding-left: 0.75rem;
        }

        .mb-4 {
          margin-bottom: 1.5rem;
        }

        .py-2 {
          padding-top: 0.5rem;
          padding-bottom: 0.5rem;
        }

        .py-3 {
          padding-top: 0.75rem;
          padding-bottom: 0.75rem;
        }

        .py-4 {
          padding-top: 1.5rem;
          padding-bottom: 1.5rem;
        }

        .pt-4 {
          padding-top: 1.5rem;
        }

        .pb-2 {
          padding-bottom: 0.5rem;
        }

        .h-100 {
          height: 100%;
        }

        .d-flex {
          display: flex;
        }

        .align-items-center {
          align-items: center;
        }

        .justify-content-between {
          justify-content: space-between;
        }

        .font-weight-bold {
          font-weight: 700 !important;
        }

        .text-xs {
          font-size: 0.7rem;
          font-weight: 700;
          text-transform: uppercase;
          letter-spacing: 0.05em;
        }

        .text-primary {
          color: var(--primary) !important;
        }

        .text-success {
          color: var(--accent) !important;
        }

        .text-warning {
          color: var(--secondary-dark) !important;
        }

        .text-info {
          color: #36b9cc !important;
        }

        .text-uppercase {
          text-transform: uppercase !important;
        }

        .mb-1 {
          margin-bottom: 0.25rem !important;
        }

        .h5 {
          font-size: 1.25rem;
          font-weight: 700;
        }

        .text-gray-800 {
          color: #5a5c69 !important;
        }

        .no-gutters {
          margin-right: 0;
          margin-left: 0;
        }

        .no-gutters > .col,
        .no-gutters > [class*="col-"] {
          padding-right: 0;
          padding-left: 0;
        }

        .mr-2 {
          margin-right: 0.5rem !important;
        }

        .icon-circle {
          height: 2.5rem;
          width: 2.5rem;
          border-radius: 100%;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .bg-primary-light {
          background-color: rgba(10, 38, 71, 0.1) !important;
        }

        .bg-success-light {
          background-color: rgba(52, 199, 89, 0.1) !important;
        }

        .bg-info-light {
          background-color: rgba(54, 185, 204, 0.1) !important;
        }

        .bg-warning-light {
          background-color: rgba(255, 194, 51, 0.1) !important;
        }

        .col-auto {
          flex: 0 0 auto;
          width: auto;
          max-width: none;
        }

        .shadow {
          box-shadow: var(--box-shadow-card) !important;
        }

        .m-0 {
          margin: 0 !important;
        }

        .chart-area, .chart-pie {
          position: relative;
          height: 20rem;
          width: 100%;
        }

        .mb-4 {
          margin-bottom: 1.5rem !important;
        }

        .small {
          font-size: 80% !important;
        }

        .float-end {
          float: right !important;
        }

        .progress {
          display: flex;
          height: 0.5rem;
          overflow: hidden;
          font-size: 0.75rem;
          background-color: #eaecf4;
          border-radius: 0.25rem;
          margin-bottom: 1rem;
        }

        .progress-bar {
          display: flex;
          flex-direction: column;
          justify-content: center;
          color: #fff;
          text-align: center;
          white-space: nowrap;
          background-color: var(--primary);
          transition: width 0.6s ease;
        }

        .progress-bar.bg-success {
          background-color: var(--accent) !important;
        }

        .mt-3 {
          margin-top: 1rem !important;
        }

        .table-responsive {
          display: block;
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }

        .table {
          width: 100%;
          margin-bottom: 1rem;
          color: #858796;
          border-collapse: collapse;
        }

        .table th {
          background-color: #edf2f7;
          color: var(--primary-dark);
          font-weight: 600;
          padding: 0.85rem 1rem;
          text-align: left;
          border-bottom: 2px solid #cbd5e0;
          text-transform: uppercase;
          letter-spacing: 0.03em;
          font-size: 0.8rem;
        }

        .table td {
          padding: 0.85rem 1rem;
          border-bottom: 1px solid #e2e8f0;
          font-weight: 500;
        }

        .table tbody tr:hover {
          background-color: #f8f9fa;
        }
        
        .badge {
          display: inline-block;
          padding: 0.35em 0.65em;
          font-size: 0.75em;
          font-weight: 700;
          line-height: 1;
          color: #fff;
          text-align: center;
          white-space: nowrap;
          vertical-align: baseline;
          border-radius: 10rem;
        }

        .bg-success {
          background-color: var(--accent) !important;
        }

        .bg-warning {
          background-color: var(--secondary) !important;
        }

        .bg-danger {
          background-color: var(--danger) !important;
        }

        .px-2 {
          padding-left: 0.5rem !important;
          padding-right: 0.5rem !important;
        }

        .py-1 {
          padding-top: 0.25rem !important;
          padding-bottom: 0.25rem !important;
        }

        @media (min-width: 768px) {
          .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
          }
          
          .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
          }
          
          .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
          }
          
          .d-md-flex {
            display: flex !important;
          }
          
          .d-md-inline-block {
            display: inline-block !important;
          }
        }

        @media (min-width: 992px) {
          .col-lg-5 {
            flex: 0 0 41.666667%;
            max-width: 41.666667%;
          }
          
          .col-lg-6 {
            flex: 0 0 50%;
            max-width: 50%;
          }
          
          .col-lg-7 {
            flex: 0 0 58.333333%;
            max-width: 58.333333%;
          }
        }

        @media (min-width: 1200px) {
          .col-xl-3 {
            flex: 0 0 25%;
            max-width: 25%;
          }
          
          .col-xl-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
          }
          
          .col-xl-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
          }
        }

        @media (max-width: 768px) {
          .container {
            padding: 1rem;
          }
          
          .page-header {
            flex-direction: column;
            align-items: flex-start;
          }
          
          .page-title {
            font-size: 1.5rem;
          }
          
          .chart-area, .chart-pie {
            height: 15rem;
          }
        }

        @media (max-width: 480px) {
          .page-title {
            font-size: 1.3rem;
          }
          
          .section-header {
            padding: 0.8rem 1rem;
          }
          
          .card-body {
            padding: 1rem;
          }
          
          .section-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
          }
          
          .section-title {
            font-size: 1.1rem;
          }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="app-title">
                <i class="fas fa-graduation-cap me-1"></i> Superação
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $usuario_nome; ?></span>
                <span class="user-role">(<?php echo $usuario_tipo; ?>)</span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Dashboard Administrativo</h1>
            <div>
                <a href="painel.php" class="btn btn-light">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
            </div>
        </div>
        
        <!-- Main Stats Row -->
        <div class="row">
            <!-- Total Students -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Alunos Cadastrados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalAlunos ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-primary-light">
                                    <i class="fas fa-users text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Active Enrollments -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Matrículas Ativas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $matriculasAtivas ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-success-light">
                                    <i class="fas fa-user-check text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Enrollments -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Matrículas Pendentes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $matriculasPendentes ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-warning-light">
                                    <i class="fas fa-user-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Classes -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Turmas Totais</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalTurmas ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-info-light">
                                    <i class="fas fa-chalkboard-teacher text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row Stats -->
        <div class="row">
            <!-- Active Classes -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Turmas em Andamento</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $turmasAtivas ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-primary-light">
                                    <i class="fas fa-chalkboard text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Professors -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Professores</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalProfessores ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-success-light">
                                    <i class="fas fa-user-tie text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Units -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Unidades</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalUnidades ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-warning-light">
                                    <i class="fas fa-school text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Enrollments -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Matrículas Recentes (30 dias)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $matriculasRecentes ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-info-light">
                                    <i class="fas fa-user-plus text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <!-- Monthly Enrollments Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="section-header section-blue">
                        <div class="section-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="section-title">Matrículas Mensais</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="matriculasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Age Distribution Pie Chart -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="section-header section-amber">
                        <div class="section-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3 class="section-title">Distribuição por Idade</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="ageChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Capacity and Attendance Row -->
        <div class="row">
            <!-- Capacity Utilization -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="section-header section-navy">
                        <div class="section-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h3 class="section-title">Taxa de Ocupação das Turmas</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="small font-weight-bold">Ocupação Geral <span class="float-end"><?= $taxaOcupacao ?>%</span></h4>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $taxaOcupacao ?>%"
                                aria-valuenow="<?= $taxaOcupacao ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <div>Capacidade Total</div>
                                <div class="font-weight-bold"><?= $capacidadeTotal ?> vagas</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Alunos Matriculados</div>
                                <div class="font-weight-bold"><?= $alunosMatriculados ?> alunos</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Vagas Disponíveis</div>
                                <div class="font-weight-bold"><?= $capacidadeTotal - $alunosMatriculados ?> vagas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Rate -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="section-header section-blue">
                        <div class="section-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="section-title">Taxa de Presença (últimos 90 dias)</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="small font-weight-bold">Taxa de Presença <span class="float-end"><?= $taxaPresenca ?>%</span></h4>
                        <div class="progress mb-4">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $taxaPresenca ?>%"
                                aria-valuenow="<?= $taxaPresenca ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <div>Total de Registros</div>
                                <div class="font-weight-bold"><?= $totalPresencas ?> aulas</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Presenças</div>
                                <div class="font-weight-bold"><?= $presentes ?> alunos</div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>Faltas</div>
                                <div class="font-weight-bold"><?= $ausentes ?> alunos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Classes Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="section-header section-slate">
                        <div class="section-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3 class="section-title">Top 5 Turmas com Mais Alunos</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Turma</th>
                                        <th>Professor</th>
                                        <th>Unidade</th>
                                        <th>Alunos</th>
                                        <th>Capacidade</th>
                                        <th>Ocupação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topTurmas as $turma): ?>
                                    <?php 
                                        $ocupacaoTurma = ($turma['capacidade'] > 0) ? 
                                            round(($turma['matriculados'] / $turma['capacidade']) * 100, 1) : 0;
                                        
                                        $badgeClass = 'bg-success';
                                        if ($ocupacaoTurma > 90) {
                                            $badgeClass = 'bg-danger';
                                        } elseif ($ocupacaoTurma > 75) {
                                            $badgeClass = 'bg-warning';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $turma['nome_turma'] ?></td>
                                        <td><?= $turma['professor'] ?></td>
                                        <td><?= $turma['unidade'] ?></td>
                                        <td><?= $turma['matriculados'] ?></td>
                                        <td><?= $turma['capacidade'] ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?> px-2 py-1">
                                                <?= $ocupacaoTurma ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Units and Classes -->
        <div class="row">
            <!-- Units with Classes -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="section-header section-navy">
                        <div class="section-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="section-title">Unidades e Turmas</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Unidade</th>
                                        <th>Total de Turmas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unidadesTurmas as $unidade): ?>
                                    <tr>
                                        <td><?= $unidade['unidade'] ?></td>
                                        <td><?= $unidade['total_turmas'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Class Status -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="section-header section-amber">
                        <div class="section-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="section-title">Status das Turmas</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="classStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Monthly Enrollments Chart - Usando dados reais com tratamento adequado
        const matriculasChart = document.getElementById('matriculasChart');
        
        // Obter dados reais
        const mesesReais = <?= json_encode($mesesLabel) ?>;
        const dadosReais = <?= json_encode($matriculasMesData) ?>;
        
        // Preparar dados cumulativos
        const dadosCumulativos = [];
        let acumulado = 0;
        
        dadosReais.forEach(valor => {
            acumulado += valor;
            dadosCumulativos.push(acumulado);
        });
        
        // Adicionar nota informativa sobre o gráfico se temos poucos dados
        if (mesesReais.length <= 1) {
            const notaInfo = document.createElement('div');
            notaInfo.style.padding = '10px';
            notaInfo.style.marginTop = '15px';
            notaInfo.style.backgroundColor = 'rgba(255, 194, 51, 0.1)';
            notaInfo.style.borderLeft = '3px solid rgba(255, 194, 51, 1)';
            notaInfo.style.borderRadius = '4px';
            notaInfo.style.fontSize = '13px';
            notaInfo.style.color = '#071c35';
            notaInfo.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Nota:</strong> O gráfico mostra dados apenas do mês atual. A evolução das matrículas será exibida automaticamente conforme novos dados forem registrados nos próximos meses.';
            
            // Inserir nota após a div do gráfico
            matriculasChart.parentNode.parentNode.appendChild(notaInfo);
        }
        
        new Chart(matriculasChart, {
            type: 'line',
            data: {
                labels: mesesReais,
                datasets: [
                    {
                        label: 'Matrículas Mensais',
                        data: dadosReais,
                        borderColor: "rgba(255, 194, 51, 1)",
                        backgroundColor: "rgba(255, 194, 51, 0.2)",
                        pointBackgroundColor: "rgba(255, 194, 51, 1)", 
                        pointBorderColor: "#fff",
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        lineTension: 0.4,
                        fill: true,
                        order: 2
                    },
                    {
                        label: 'Total Acumulado',
                        data: dadosCumulativos,
                        borderColor: "rgba(10, 38, 71, 1)",
                        backgroundColor: "rgba(10, 38, 71, 0.05)",
                        pointBackgroundColor: "rgba(10, 38, 71, 1)",
                        pointBorderColor: "#fff",
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        lineTension: 0.4,
                        fill: false,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        min: 0,
                        ticks: {
                            stepSize: 5,
                            precision: 0,
                            font: {
                                family: 'Poppins',
                                size: 12
                            }
                        },
                        grid: {
                            color: "rgba(0, 0, 0, 0.05)",
                            drawBorder: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Poppins',
                                size: 13,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(10, 38, 71, 0.85)',
                        titleFont: {
                            family: 'Poppins',
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            family: 'Poppins',
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        caretSize: 8,
                        displayColors: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear'
                    }
                }
            }
        });
        
        // Age Distribution Chart
        const ageChart = document.getElementById('ageChart');
        new Chart(ageChart, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($faixasLabel) ?>,
                datasets: [{
                    data: <?= json_encode($faixasData) ?>,
                    backgroundColor: [
                        '#0a2647',
                        '#144272',
                        '#ffc233',
                        '#34c759'
                    ],
                    hoverBackgroundColor: [
                        '#071c35',
                        '#0a2647',
                        '#e9b424',
                        '#26a344'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Class Status Chart
        const classStatusChart = document.getElementById('classStatusChart');
        new Chart(classStatusChart, {
            type: 'doughnut',
            data: {
                labels: ['Em Andamento', 'Planejada', 'Concluída', 'Cancelada'],
                datasets: [{
                    data: [
                        <?= $turmasAtivas ?>, 
                        <?= $turmasPlanejadas ?>, 
                        <?= $turmasConcluidas ?>, 
                        <?= $turmasCanceladas ?>
                    ],
                    backgroundColor: [
                        '#34c759',
                        '#144272',
                        '#0a2647',
                        '#f64e60'
                    ],
                    hoverBackgroundColor: [
                        '#26a344',
                        '#0a2647',
                        '#071c35',
                        '#e73c4e'
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>