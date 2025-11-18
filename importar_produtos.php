<?php
// entradas/importar_produtos.php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php'; // Carrega a conexão com o banco ($db_produtos)

// Configurações
$arquivo_csv = 'produtos.csv'; // O nome do arquivo que você salvou
$delimiter = ';'; //

echo "<h1>Importador de Produtos</h1>";

// 1. Verifica se o arquivo existe
if (!file_exists($arquivo_csv)) {
    die("<p style='color: red;'>Erro: O arquivo <strong>$arquivo_csv</strong> não foi encontrado na pasta 'entradas/'.</p>");
}

// 2. Abre o arquivo
$handle = fopen($arquivo_csv, "r");
if ($handle === FALSE) {
    die("<p style='color: red;'>Erro ao abrir o arquivo.</p>");
}

// 3. Prepara a query (SQL)
// Usamos "INSERT OR REPLACE" do SQLite. 
// Isso significa: "Se o produto já existir (pelo código interno), atualize os dados. Se não, crie um novo."
$sql = "INSERT INTO produtos (codigo_interno, codigo_barras, nome_produto, preco1, preco2) 
        VALUES (:cod_int, :cod_bar, :nome, :p1, :p2)
        ON CONFLICT(codigo_interno) DO UPDATE SET
        nome_produto = excluded.nome_produto,
        preco1 = excluded.preco1,
        preco2 = excluded.preco2,
        codigo_barras = excluded.codigo_barras";

$stmt = $db_produtos->prepare($sql);

// Inicia contadores e transação (para ser ultra rápido)
$db_produtos->beginTransaction();
$linha_atual = 0;
$sucessos = 0;
$erros = 0;

echo "<p>Iniciando importação... (Isso pode levar alguns segundos)</p>";
echo "<div style='background: #f4f4f4; padding: 10px; border: 1px solid #ccc; height: 300px; overflow-y: scroll;'>";

// 4. Lê linha por linha
while (($dados = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
    $linha_atual++;

    // Pula a primeira linha (cabeçalho: Código,Cód. de Barras...)
    if ($linha_atual == 1) {
        continue; 
    }

    // Verifica se a linha tem dados suficientes (mínimo 5 colunas)
    if (count($dados) < 5) {
        continue; 
    }

    // Mapeia as colunas do seu CSV
    // CSV: Código, Cód. de Barras, Descrição, Preço 1, Preço 2
    $cod_interno = trim($dados[0]);
    $cod_barras  = trim($dados[1]);
    $nome        = trim($dados[2]);
    
    // Tratamento de Preços (troca vírgula por ponto se necessário, remove R$)
    $preco1      = floatval(str_replace(',', '.', $dados[3]));
    $preco2      = floatval(str_replace(',', '.', $dados[4]));

    // Ajustes finos
    if ($cod_barras == '') $cod_barras = null; // Se não tiver barra, deixa NULL
    
    // Tenta inserir
    try {
        $stmt->execute([
            ':cod_int' => $cod_interno,
            ':cod_bar' => $cod_barras,
            ':nome'    => $nome,
            ':p1'      => $preco1,
            ':p2'      => $preco2
        ]);
        $sucessos++;
        // echo "<small style='color: green'>✔ $nome ($cod_interno)</small><br>"; // Descomente para ver linha a linha
    } catch (PDOException $e) {
        $erros++;
        echo "<p style='color: red'>✖ Erro na linha $linha_atual ($nome): " . $e->getMessage() . "</p>";
    }
}

// Finaliza
$db_produtos->commit();
fclose($handle);

echo "</div>";
echo "<h2>Relatório Final</h2>";
echo "<ul>";
echo "<li>Linhas processadas: " . ($linha_atual - 1) . "</li>";
echo "<li style='color: green'><strong>Produtos importados/atualizados: $sucessos</strong></li>";
if ($erros > 0) echo "<li style='color: red'>Erros: $erros</li>";
echo "</ul>";

echo "<hr>";
echo "<a href='etiquetas/etiquetas.php'><button style='padding: 10px 20px; font-size: 16px; cursor: pointer;'>Ir para o Gerador de Etiquetas</button></a>";
?>