<?php
// ATENÇÃO: config.php precisa de ser atualizado (ver Passo 3) para este script funcionar
require 'config.php';

$mensagem = '';
$erro = '';

// Defina aqui uma senha de administrador para si
$admin_password = "Cshugo*20"; // <-- MUDE ISTO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_rcky = $_POST['codigo'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';

    if ($senha_digitada !== $admin_password) {
        $erro = "Senha de administrador incorreta!";
    } else if (empty($codigo_rcky)) {
        $erro = "O código RCKY não pode estar vazio.";
    } else {
        try {
            // Usa a variável $db_portal do config.php
            $stmt = $db_portal->prepare("INSERT INTO rcky_codes (code) VALUES (?)");
            $stmt->execute([$codigo_rcky]);
            $mensagem = "Código '" . htmlspecialchars($codigo_rcky) . "' adicionado com sucesso!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Erro de violação de chave (código já existe)
                $erro = "Erro: O código '" . htmlspecialchars($codigo_rcky) . "' já existe.";
            } else {
                $erro = "Erro na base de dados: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Admin - Adicionar RCKY</title>
    <link rel="stylesheet" href="static/css/login.css"> <!-- Reutiliza o CSS de login -->
</head>

<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Adicionar Código RCKY</h2>

            <?php if ($mensagem): ?>
                <p style="color: green; text-align: center;"><?php echo $mensagem; ?></p><?php endif; ?>
            <?php if ($erro): ?>
                <p style="color: red; text-align: center;"><?php echo $erro; ?></p><?php endif; ?>

            <div class="form-group">
                <label for="codigo">Novo Código RCKY (ex: 4012)</label>
                <input type="text" id="codigo" name="codigo" required>
            </div>
            <div class="form-group">
                <label for="senha">Sua Senha de Administrador</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="login-btn">Adicionar Código</button>
        </form>
    </div>
</body>

</html>