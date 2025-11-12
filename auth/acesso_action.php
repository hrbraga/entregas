<?php
require '../config.php'; // Liga-se ao config (sessão, $db_portal)

$codigo_rcky = $_POST['codigo'] ?? '';

if (empty($codigo_rcky)) {
    header('Location: ../inicio.php?erro=Por favor, digite um código.');
    exit;
}

try {
    // Procura o código RCKY na base de dados 'portal_access.db'
    $stmt = $db_portal->prepare("SELECT code FROM rcky_codes WHERE code = ?");
    $stmt->execute([$codigo_rcky]);
    $rcky = $stmt->fetch();

    if ($rcky) {
        // Código RCKY encontrado!
        session_regenerate_id(true); 
        
        // Criamos a sessão Nível 1 (Portal)
        $_SESSION['rcky_code'] = $rcky['code'];
        
        // Redireciona para a página de ferramentas
        header('Location: selecao_ferramentas.php');
        exit;
    } else {
        // Código não encontrado
        header('Location: inicio.php?erro=Código de acesso inválido.');
        exit;
    }

} catch (Exception $e) {
    error_log("Erro em acesso_action.php: " . $e->getMessage());
    header('Location: inicio.php?erro=Ocorreu um erro no servidor.');
    exit;
}
?>