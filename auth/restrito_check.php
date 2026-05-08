<?php
// auth/restrito_check.php
require_once 'auth_check.php';

// Se for utilizador de loja, chuta de volta para a seleção
if (isset($_SESSION['username']) && strpos($_SESSION['username'], 'loja-') === 0) {
    
    if (file_exists('selecao_ferramentas.php')) {
        $caminho = 'selecao_ferramentas.php?erro=restrito';
    } elseif (file_exists('../selecao_ferramentas.php')) {
        $caminho = '../selecao_ferramentas.php?erro=restrito';
    } else {
        $caminho = '../../selecao_ferramentas.php?erro=restrito';
    }
    
    header("Location: " . $caminho);
    exit;
}
?>