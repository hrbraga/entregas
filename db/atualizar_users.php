<?php
if (isset($_POST['id']) && isset($_POST['nova_loja'])) {
    $id = $_POST['id'];
    $nova_loja = $_POST['nova_loja'];
    
    // Conexão com o banco (ajuste o caminho do PDO se necessário)
    $db = new PDO('sqlite:db/users.db'); 

    // Atualiza apenas a coluna referente à loja
    $stmt = $db->prepare("UPDATE usuarios SET loja = ? WHERE id = ?");
    $stmt->execute([$nova_loja, $id]);
}
?>