<?php
// planejador/config_pascoa.php
session_start();
require_once '../auth/auth_check.php'; // Sua trava de segurança atual

// Define qual loja está acessando
$loja_logada = $_SESSION['usuario']; // Ajuste se a sua variável de sessão tiver outro nome

// Conecta a um NOVO banco de dados exclusivo para a Páscoa
$db_path = __DIR__ . '/../db/pascoa.db';
$db_pascoa = new PDO('sqlite:' . $db_path);
$db_pascoa->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cria a tabela isolando os pedidos por loja
$db_pascoa->exec("CREATE TABLE IF NOT EXISTS pedidos_pascoa (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    loja_id TEXT,
    codigo_sap TEXT,
    descricao TEXT,
    grupo TEXT,
    qtd_caixa INTEGER,
    sugestao_caixas INTEGER DEFAULT 0,
    pedido_loja INTEGER DEFAULT 0,
    pedido_vd INTEGER DEFAULT 0,
    UNIQUE(loja_id, codigo_sap)
)");
?>