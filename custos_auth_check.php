<?php
// custos_auth_check.php
// Este script protege TODAS as páginas da seção de Custos

// O config.php já deve ter iniciado a sessão.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a sessão específica de CUSTOS existe
if (!isset($_SESSION['custos_user_id'])) {

    // Se não existir, redireciona para a nova página de login
    header('Location: inicio.php');
    exit;
}
?>