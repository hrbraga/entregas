<?php
require '../config.php';
try {
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN data_pagamento DATE;");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN forma_pagamento TEXT;");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN banco_pagamento TEXT;");
    $db_financeiro->exec("ALTER TABLE contas_pagar ADD COLUMN valor_pago REAL;");
    echo "<h3 style='color:green'>Banco de dados atualizado com sucesso! Já podes apagar este ficheiro.</h3>";
} catch (Exception $e) {
    echo "Aviso: " . $e->getMessage() . " (Se disser 'duplicate column name', significa que já estava atualizado).";
}
?>