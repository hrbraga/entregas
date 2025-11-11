<?php
// custos_auth_check.php
// Protege o Nível 1 (Portal de Custos e Hub)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a sessão Nível 1 (RCKY) existe
if (!isset($_SESSION['rcky_code'])) {
    // Se não existir, redireciona para o login de RCKY
    header('Location: inicio.php');
    exit;
}
?>