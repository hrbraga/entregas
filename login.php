<?php 
    $page_title = "Login";
    
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
    <form class="login-form" method="POST" action="login_action.php">
        <h2>Login - Controle de Entregas</h2>
        
        <?php if (isset($_GET['erro'])): ?>
            <div class="flash-error">
                Usuário ou senha inválidos.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['registrado'])): ?>
            <div class="feedback-message success" style="display:block; background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                Cadastro realizado com sucesso! Faça o login.
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="username">Usuário</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="login-btn">Entrar</button>
        <p class="toggle-link">Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
    </form>
</div>

<?php 
    // Inclui o rodapé
    require 'includes/footer.php'; 
?>