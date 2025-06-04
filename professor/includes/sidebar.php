<!-- Sidebar para professores -->
<?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'professor'): ?>
<div class="sidebar-heading">
    Gestão de Alunos
</div>

<li class="nav-item">
    <a class="nav-link" href="alunos_turma.php">
        <i class="fas fa-clipboard-list"></i>
        <span>Avaliação de Alunos</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="minhas_avaliacoes.php">
        <i class="fas fa-chart-line"></i>
        <span>Minhas Avaliações</span>
    </a>
</li>

<!-- Restante do menu para professores... -->
<?php endif; ?>