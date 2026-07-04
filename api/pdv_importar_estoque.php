<?php
// Blinda o arquivo para não imprimir nenhum erro HTML do PHP
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require '../config.php';

// Inicia a sessão apenas se ela já não estiver rodando
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Verifica se os dados realmente chegaram
    if (!isset($_POST['evento_id']) || !isset($_FILES['arquivo_csv'])) {
        throw new Exception("Dados incompletos. Verifique o formulário.");
    }

    $evento_id = $_POST['evento_id'];
    $file = $_FILES['arquivo_csv']['tmp_name'];

    if (empty($file)) {
        throw new Exception("Arquivo CSV vazio ou não suportado.");
    }

    if (($handle = fopen($file, "r")) !== FALSE) {
        
        // Pula o cabeçalho se a sua planilha tiver um (opcional, mas recomendado)
        // fgetcsv($handle, 1000, ";"); 
        
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $cod_interno = trim($data[0]); 
            $qtd = (float)str_replace(',', '.', $data[1]); 

            $stmt = $db_produtos->prepare("SELECT id FROM produtos_unificados WHERE codigo_interno = ?");
            $stmt->execute([$cod_interno]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($prod) {
                // NOVA LÓGICA: Consulta se o produto já existe no evento
                $stmtCheck = $db_financeiro->prepare("SELECT id FROM pdv_estoque_evento WHERE evento_id = ? AND produto_id = ?");
                $stmtCheck->execute([$evento_id, $prod['id']]);
                $estoqueAtual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($estoqueAtual) {
                    // Já existe: Faz o UPDATE somando a quantidade nova à antiga
                    $stmtUp = $db_financeiro->prepare("
                        UPDATE pdv_estoque_evento 
                        SET quantidade_inicial = quantidade_inicial + ?, 
                            quantidade_atual = quantidade_atual + ? 
                        WHERE id = ?
                    ");
                    $stmtUp->execute([$qtd, $qtd, $estoqueAtual['id']]);
                } else {
                    // Não existe: Faz o INSERT limpo
                    $stmtIns = $db_financeiro->prepare("
                        INSERT INTO pdv_estoque_evento (evento_id, produto_id, quantidade_inicial, quantidade_atual) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtIns->execute([$evento_id, $prod['id'], $qtd, $qtd]);
                }
            }
        }
        fclose($handle);
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Falha ao abrir o arquivo CSV.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>