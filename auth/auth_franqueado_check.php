<?php
// auth/auth_franqueado_check.php

// 1. Primeiro roda a verificação normal (garante que alguém está logado)
require_once 'auth_check.php';

// 2. Depois verifica se esse alguém tem a "chave" de franqueado
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'franqueado') {
    
    // Se for um colaborador tentando dar uma de espertinho na URL, 
    // manda ele de volta para o quadro de gestão.
    header('Location: ../gestao/quadro_gestao.php');
    exit;
}
?>