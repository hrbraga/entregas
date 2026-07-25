<?php
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Conexão com o banco (ajuste o caminho do PDO se necessário)
    $db = new PDO('sqlite:users.db'); 
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
}
?>