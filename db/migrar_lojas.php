<?php
// migrar_lojas.php

require '../config.php';

echo "<h2>🚀 Migração de Lojas</h2>";

try {

    $db_users->beginTransaction();

    // Busca todos os franqueados
    $stmt = $db_users->query("
        SELECT id, username
        FROM user
        WHERE perfil = 'franqueado'
        ORDER BY username
    ");

    $franqueados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $criadas = 0;
    $migrados = 0;

    foreach ($franqueados as $franqueado) {

        // Verifica se já existe loja principal
        $stmt = $db_users->prepare("
            SELECT id
            FROM lojas
            WHERE id_franqueado = ?
        ");
        $stmt->execute([$franqueado['id']]);

        $loja = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($loja) {

            $idLoja = $loja['id'];

        } else {

            // Cria Loja Principal
            $stmt = $db_users->prepare("
                INSERT INTO lojas
                (nome,id_franqueado)
                VALUES (?,?)
            ");

            $stmt->execute([
                'Loja Principal',
                $franqueado['id']
            ]);

            $idLoja = $db_users->lastInsertId();

            $criadas++;
        }

        // Atualiza somente quem ainda não possui loja
        $stmt = $db_users->prepare("
            UPDATE user
            SET id_loja = ?
            WHERE id_dono = ?
            AND (id_loja IS NULL OR id_loja = '')
        ");

        $stmt->execute([
            $idLoja,
            $franqueado['id']
        ]);

        $migrados += $stmt->rowCount();
    }

    $db_users->commit();

    echo "<h3>✅ Migração concluída!</h3>";

    echo "<p>Lojas criadas: <strong>$criadas</strong></p>";
    echo "<p>Colaboradores migrados: <strong>$migrados</strong></p>";

} catch (Exception $e) {

    $db_users->rollBack();

    echo "<h3 style='color:red'>Erro!</h3>";

    echo $e->getMessage();

}