 <?php require 'config.php'; ?>
 
 <nav class="navbar">
        <div class="nav-brand">
            <h1>Controle de Entregas</h1>
        </div>
        <ul class="nav-links">
            <li><a href="{{ url_for('recebimentos') }}">Entregas</a></li>
            <li><a href="{{ url_for('historico') }}">Histórico</a></li>
            <li><a href="{{ url_for('dashboard') }}">Dashboard</a></li>
            <li><a href="{{ url_for('custos_static_files', filename='html/selecao.html') }}">Custos Produtos</a></li>

     <?php if (isset($_SESSION['user_id'])): ?>
    <li><a href="logout.php">Sair (<?= htmlspecialchars($_SESSION['username']); ?>)</a></li>
<?php else: ?>
    <li><a href="login.php">Login</a></li>
<?php endif; ?>
        </ul>
    </nav>