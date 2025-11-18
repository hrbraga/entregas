<?php
require_once 'config.php'; // Conecta ao $db_produtos

echo "<h1>Relatório de Produtos Cadastrados</h1>";

// Conta o total
$total = $db_produtos->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
echo "<p>Total de produtos no banco: <strong>$total</strong></p>";

// Lista os produtos
$stmt = $db_produtos->query("SELECT codigo_interno, nome_produto, preco1 FROM produtos ORDER BY id DESC");
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Cód. Interno</th><th>Nome</th><th>Preço 1</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['codigo_interno']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nome_produto']) . "</td>";
    echo "<td>R$ " . number_format($row['preco1'], 2, ',', '.') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>