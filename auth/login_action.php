<?php
// login_action.php
require '../config.php';

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $db_users->prepare("SELECT * FROM user WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// 'password_verify' é o equivalente PHP ao 'user.check_password(password)'
if ($user && password_verify($password, $user['password_hash'])) {

    // Isto é o 'login_user(user)' do Flask-Login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    header('Location: ../Recebimentos/recebimentos.php'); // Redireciona para a app
    exit;
} else {
    header('Location: ../login.php?erro=1'); // Envia de volta com erro
    exit;
}
?>