<?php
// deletar_usuario.php
// Coloque este arquivo na mesma pasta que o config.php

require 'config.php';

$usuario_alvo = 'NOME_DO_USUARIO_AQUI';

try {
    $stmt = $db_users->prepare("DELETE FROM user WHERE username = ?");
    $stmt->execute([$usuario_alvo]);

    if ($stmt->rowCount() > 0) {
        echo "Sucesso! O usuário <b>$usuario_alvo</b> foi removido. Ele já pode se cadastrar novamente.";
    } else {
        echo "Usuário não encontrado.";
    }

} catch (PDOException $e) {
    echo "Erro ao deletar: " . $e->getMessage();
}
?>