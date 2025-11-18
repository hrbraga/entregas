<?php
// entradas/ver_produtos.php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

echo "<h1>Relatório de Produtos Cadastrados</h1>";

// Conta o total
$total = $db_produtos->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
echo "<p>Total de produtos no banco: <strong>$total</strong></p>";

// Lista os produtos (Incluindo Preço 2)
$stmt = $db_produtos->query("SELECT codigo_interno, codigo_barras, nome_produto, preco1, preco2 FROM produtos ORDER BY id DESC");

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; font-family: Arial;'>";
echo "<tr style='background: #eee;'>
        <th>Cód. Interno</th>
        <th>Cód. Barras</th>
        <th>Nome</th>
        <th>Preço 1 (Maior)</th>
        <th>Preço 2 (Menor)</th>
      </tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['codigo_interno']) . "</td>";
    echo "<td>" . htmlspecialchars($row['codigo_barras']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nome_produto']) . "</td>";
    echo "<td>R$ " . number_format($row['preco1'], 2, ',', '.') . "</td>";
    echo "<td>R$ " . number_format($row['preco2'], 2, ',', '.') . "</td>"; // Adicionado aqui
    echo "</tr>";
}
echo "</table>";
?>