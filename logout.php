<?php
// logout.php (Versão ATUALIZADA)
// Faz logout do Nível 2 (Loja Específica)

require 'config.php'; // Apenas para iniciar a sessão

// Apaga APENAS as credenciais da loja (Nível 2)
unset($_SESSION['user_id']);
unset($_SESSION['username']);

// Não usamos session_destroy(), pois isso apagaria o login RCKY (Nível 1).

// Redireciona de volta para a página de login de "Entregas"
// para que o utilizador possa escolher outra loja.
header('Location: login.php'); 
exit;
?>