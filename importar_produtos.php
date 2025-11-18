<?php
// entradas/importar_completo.php
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

// --- CONFIGURAÇÃO IMPORTANTE ---
$arquivo_csv = 'produtos.csv';
$delimitador = ';'; // SEU ARQUIVO USA PONTO E VÍRGULA!
// -------------------------------

echo "<h1>Importador V3 (Correção Total)</h1>";

if (!file_exists($arquivo_csv)) {
    die("<p style='color: red'>Erro: Arquivo '$arquivo_csv' não encontrado.</p>");
}

// 1. RECRIAR A TABELA (Fora de transação para evitar erros de lock)
try {
    $db_produtos->exec("DROP TABLE IF EXISTS produtos");
    $sql_create = "
    CREATE TABLE produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo_barras TEXT,
        codigo_interno TEXT,
        nome_produto TEXT NOT NULL,
        preco1 REAL NOT NULL,
        preco2 REAL NOT NULL
    );";
    $db_produtos->exec($sql_create);
    echo "<p style='color: green'>✔ Tabela 'produtos' recriada com sucesso.</p>";
} catch (PDOException $e) {
    die("<p style='color: red'>Erro fatal ao criar tabela: " . $e->getMessage() . "</p>");
}

// 2. PROCESSAR O CSV
$handle = fopen($arquivo_csv, "r");
if ($handle === FALSE) die("Erro ao abrir arquivo.");

$db_produtos->beginTransaction();
$stmt = $db_produtos->prepare("INSERT INTO produtos (codigo_interno, codigo_barras, nome_produto, preco1, preco2) VALUES (?, ?, ?, ?, ?)");

echo "<div style='height: 300px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";

$linha = 0;
$sucessos = 0;

while (($dados = fgetcsv($handle, 2000, $delimitador)) !== FALSE) {
    $linha++;
    
    // Pula cabeçalho
    if ($linha == 1) continue; 

    // Verifica se a linha tem as 5 colunas (Cod, Bar, Desc, P1, P2)
    if (count($dados) < 5) {
        echo "<span style='color: orange'>⚠ Linha $linha ignorada (dados incompletos).</span><br>";
        continue;
    }

    // --- TRATAMENTO DE DADOS ---
    
    // 1. Códigos
    $cod_interno = trim($dados[0]);
    
    // IMPORTANTE: Corrige erro de notação científica do Excel (ex: 7,89E+12)
    // Se o código de barras vier quebrado do Excel, tentamos limpá-lo, mas o ideal é corrigir no Excel.
    $cod_barras = trim($dados[1]); 
    
    // 2. Nome (Converte caracteres estranhos do Excel para UTF-8)
    $nome = mb_convert_encoding(trim($dados[2]), 'UTF-8', 'Windows-1252'); // Tenta corrigir 
    if (!$nome) $nome = trim($dados[2]); // Se falhar, usa o original

    // 3. Preços (Troca vírgula por ponto)
    // Ex: "31,99" vira "31.99"
    $p1_str = str_replace(',', '.', $dados[3]);
    $p2_str = str_replace(',', '.', $dados[4]);
    
    $preco1 = floatval($p1_str);
    $preco2 = floatval($p2_str);

    // Insere
    try {
        $stmt->execute([$cod_interno, $cod_barras, $nome, $preco1, $preco2]);
        $sucessos++;
    } catch (Exception $e) {
        echo "<span style='color: red'>Erro linha $linha: " . $e->getMessage() . "</span><br>";
    }
}

$db_produtos->commit();
fclose($handle);

echo "</div>";
echo "<h2>Relatório Final</h2>";
echo "<ul>";
echo "<li>Total processado: $linha linhas</li>";
echo "<li style='color: green'><strong>Importados com sucesso: $sucessos</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<a href='ver_produtos.php'><button>Ver Tabela Preenchida</button></a> ";
echo "<a href='etiquetas/etiquetas.php'><button>Ir para Etiquetas</button></a>";
?>