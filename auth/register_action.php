<?php
// register_action.php
require '../config.php';

$username = $_POST['username'];
$password = $_POST['password'];

// Validação simples
if (strlen($username) < 4) {
    header('Location: register.php?erro=Usuário precisa de pelo menos 4 caracteres.');
    exit;
}
if (strlen($password) < 6) {
    header('Location: register.php?erro=Senha precisa de pelo menos 6 caracteres.');
    exit;
}

// Verifica se o utilizador já existe
$stmt = $db_users->prepare("SELECT id FROM user WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    header('Location: register.php?erro=Este nome de usuário já existe.');
    exit;
}

// Cria o novo utilizador
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$perfil = 'colaborador'; // Trava de segurança: Todo mundo novo nasce como colaborador
$stmt = $db_users->prepare("INSERT INTO user (username, password_hash, perfil) VALUES (?, ?, ?)");
$stmt->execute([$username, $password_hash, $perfil]);

// Redireciona para o login com mensagem de sucesso
header('Location: login.php?registrado=1');
exit;
?>