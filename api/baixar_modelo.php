<?php
// api/baixar_modelo.php
ini_set('display_errors', 0);

// Caminho do arquivo baseado de onde este script está rodando
// Se a planilha está na raiz (antes da pasta api), usamos '../metas_modelo.csv'
$arquivo = '../metas_modelo.csv';

if (file_exists($arquivo)) {
    // Força o navegador a entender que é um download de arquivo CSV, sem redirecionamentos
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="metas_modelo.csv"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($arquivo));
    
    // Lê e entrega o arquivo direto na conexão HTTPS atual
    readfile($arquivo);
    exit;
} else {
    echo "Erro: O arquivo de modelo não foi encontrado no servidor.";
}
?>