<?php
require 'config.php'; // Usa a sua conexão atual $db_produtos

echo "<h1>Iniciando Migração Segura...</h1>";

try {
    // 1. Cria a nova tabela unificada (NÃO apaga as antigas)
    $sql_create = "
    CREATE TABLE IF NOT EXISTS produtos_unificados (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo_barras TEXT UNIQUE,
        codigo_interno TEXT UNIQUE,
        nome_produto TEXT NOT NULL,
        campanha TEXT,
        preco_venda REAL, -- Vai receber o preco1 ou o preco de custos
        preco2 REAL,
        qtCaixa REAL,
        valorUn REAL,
        royalties REAL,
        st REAL,
        ipi REAL,
        txsAdicionais REAL,
        txMidia REAL,
        custoCaixa REAL,
        custoUn REAL,
        mbLiquida REAL,
        mbBruta REAL
    );
    ";
    $db_produtos->exec($sql_create);
    echo "<p>✔️ Tabela 'produtos_unificados' criada com sucesso.</p>";

    // 2. Busca todos os produtos da tabela de ETIQUETAS
    $stmt_etiq = $db_produtos->query("SELECT * FROM produtos");
    $etiquetas = $stmt_etiq->fetchAll(PDO::FETCH_ASSOC);

    $inseridos = 0;
    $atualizados = 0;

    // 3. Insere primeiro a base de ETIQUETAS
    $stmt_insert = $db_produtos->prepare("
        INSERT INTO produtos_unificados (codigo_barras, codigo_interno, nome_produto, preco_venda, preco2) 
        VALUES (:codigo_barras, :codigo_interno, :nome_produto, :preco_venda, :preco2)
    ");

    foreach ($etiquetas as $etiq) {
        try {
            $stmt_insert->execute([
                ':codigo_barras' => $etiq['codigo_barras'],
                ':codigo_interno' => $etiq['codigo_interno'],
                ':nome_produto' => $etiq['nome_produto'],
                ':preco_venda' => $etiq['preco1'],
                ':preco2' => $etiq['preco2']
            ]);
            $inseridos++;
        } catch (Exception $e) {
            // Ignora duplicidades de UNIQUE constraint nesta etapa
        }
    }
    echo "<p>✔️ $inseridos produtos base (etiquetas) migrados.</p>";

    // 4. Busca os produtos de CUSTOS e faz o MERGE
    $stmt_custos = $db_produtos->query("SELECT * FROM custos_produtos");
    $custos = $stmt_custos->fetchAll(PDO::FETCH_ASSOC);

    // Update para quando o produto já existe (procura por codigo_interno ou barras)
    $stmt_update_custos = $db_produtos->prepare("
        UPDATE produtos_unificados SET 
            campanha = :campanha, qtCaixa = :qtCaixa, valorUn = :valorUn, royalties = :royalties,
            st = :st, ipi = :ipi, txsAdicionais = :txsAdicionais, txMidia = :txMidia,
            custoCaixa = :custoCaixa, custoUn = :custoUn, mbLiquida = :mbLiquida, mbBruta = :mbBruta
        WHERE codigo_interno = :codigo OR codigo_barras = :codigo
    ");

    // Insert para quando o custo não estava na tabela de etiquetas
    $stmt_insert_custos = $db_produtos->prepare("
        INSERT INTO produtos_unificados (
            codigo_interno, nome_produto, campanha, preco_venda, qtCaixa, valorUn, royalties, 
            st, ipi, txsAdicionais, txMidia, custoCaixa, custoUn, mbLiquida, mbBruta
        ) VALUES (
            :codigo, :descricao, :campanha, :preco, :qtCaixa, :valorUn, :royalties, 
            :st, :ipi, :txsAdicionais, :txMidia, :custoCaixa, :custoUn, :mbLiquida, :mbBruta
        )
    ");

    $custos_novos = 0;
    foreach ($custos as $c) {
        $stmt_update_custos->execute([
            ':codigo' => $c['codigo'], // Tenta bater o 'codigo' com 'codigo_interno' ou 'codigo_barras'
            ':campanha' => $c['campanha'],
            ':qtCaixa' => $c['qtCaixa'],
            ':valorUn' => $c['valorUn'],
            ':royalties' => $c['royalties'],
            ':st' => $c['st'],
            ':ipi' => $c['ipi'],
            ':txsAdicionais' => $c['txsAdicionais'],
            ':txMidia' => $c['txMidia'],
            ':custoCaixa' => $c['custoCaixa'],
            ':custoUn' => $c['custoUn'],
            ':mbLiquida' => $c['mbLiquida'],
            ':mbBruta' => $c['mbBruta']
        ]);

        // Se o rowCount for 0, significa que esse produto só existia nos custos e não nas etiquetas
        if ($stmt_update_custos->rowCount() == 0) {
            try {
                $stmt_insert_custos->execute([
                    ':codigo' => $c['codigo'],
                    ':descricao' => $c['descricao'],
                    ':campanha' => $c['campanha'],
                    ':preco' => $c['preco'],
                    ':qtCaixa' => $c['qtCaixa'],
                    ':valorUn' => $c['valorUn'],
                    ':royalties' => $c['royalties'],
                    ':st' => $c['st'],
                    ':ipi' => $c['ipi'],
                    ':txsAdicionais' => $c['txsAdicionais'],
                    ':txMidia' => $c['txMidia'],
                    ':custoCaixa' => $c['custoCaixa'],
                    ':custoUn' => $c['custoUn'],
                    ':mbLiquida' => $c['mbLiquida'],
                    ':mbBruta' => $c['mbBruta']
                ]);
                $custos_novos++;
            } catch (Exception $e) { }
        } else {
            $atualizados++;
        }
    }

    echo "<p>✔️ $atualizados produtos atualizados com dados de custos.</p>";
    echo "<p>✔️ $custos_novos produtos exclusivos de custos adicionados.</p>";
    echo "<h2 style='color: green;'>Migração concluída com sucesso! Os dados originais NÃO foram alterados.</h2>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Erro Banco: " . $e->getMessage() . "</h2>";
}
?>