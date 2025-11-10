<?php 
    $page_title = "Cadastro";
    
    // Inclui o config para iniciar a sessão
    require 'config.php';

    // Se o utilizador já estiver logado, redireciona
    if (isset($_SESSION['user_id'])) {
        header('Location: recebimentos.php');
        exit;
    }

    // Inclui o header
    require 'includes/header.php';
?>

<link rel="stylesheet" href="static/css/login.css">

<div class="login-container">
    <form class="login-form" method="POST" action="register_action.php">
        <h2>Cadastro - Controle de Entregas</h2>
        
        <?php if (isset($_GET['erro'])): ?>
            <div class="flash-error">
                <?= htmlspecialchars($_GET['erro']); ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Usuário (mín. 4 caracteres)</label>
            <input type="text" id="username" name="username" required minlength="4">
        </div>
        <div class="form-group">
            <label for="password">Senha (mín. 6 caracteres)</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <button type="submit" class="login-btn">Cadastrar</button>
        <p class="toggle-link">Já tem uma conta? <a href="login.php">Faça login</a></p>
    </form>
</div>

<?php 
    // Inclui o rodapé
    require 'includes/footer.php'; 
?>