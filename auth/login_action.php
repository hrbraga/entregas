<?php
// auth/login_action.php
require '../config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? '';


if (empty($username) || empty($password)) {
    die("ERRO: Formulário vazio.");
}

try {
    $stmt = $db_users->prepare("SELECT id, username, password_hash, perfil FROM user WHERE username = :u LIMIT 1");
    $stmt->bindValue(':u', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ... parte do password_verify ...
    if ($user && password_verify($password, $user['password_hash'])) {
    
    // 2. Grava os dados na sessão
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // VERIFIQUE SE A COLUNA NO BANCO É 'perfil' MESMO
    // Se no seu banco de dados a coluna se chama de outra forma, mude aqui!
    $_SESSION['perfil'] = $user['perfil']; 
    
    // 3. Define o vínculo de dono (como combinamos no passo 3 da mensagem anterior)
    // Isso garante que o colaborador veja os dados do dono
    $_SESSION['id_sessao'] = (!empty($user['id_dono'])) ? $user['id_dono'] : $user['id'];

    header('Location: ../inicio.php');
    exit;
} else {
    // Login falhou
    header('Location: login.php?erro=1');
    exit;
}

} catch (Exception $e) {
    die("Erro Interno: " . $e->getMessage());
}
?>