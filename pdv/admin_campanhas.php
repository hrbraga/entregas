<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';

$page_title = "Estratégia Comercial e Campanhas";
$sessao_nome = "Campanhas e PDV";
require_once '../includes/header_franq.php';

// Conecta ao banco de produtos para trazer os nomes
$caminho_produtos = str_replace('\\', '/', dirname(__DIR__)) . '/db/produtos.db';
$db_financeiro->exec("ATTACH DATABASE '$caminho_produtos' AS p_db");

// Busca os botões atuais do teclado
$stmtTeclado = $db_financeiro->prepare("
    SELECT t.id, t.posicao, p.nome_produto, p.codigo_interno 
    FROM pdv_teclado_rapido t
    JOIN p_db.produtos_unificados p ON t.produto_id = p.id
    ORDER BY t.posicao ASC
");
$stmtTeclado->execute();
$botoes_teclado = $stmtTeclado->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../static/css/style.css">
<style>
    .abas { 
        display: flex; 
        gap: 5px; 
        margin-bottom: 0; /* Tira a margem para grudar no conteúdo */
        position: relative; 
        z-index: 1; 
        padding-left: 10px;
    }
    .aba { 
        padding: 12px 25px; 
        cursor: pointer; 
        font-weight: bold; 
        color: #666; 
        background: #f8f9fa; /* Fundo cinza clarinho pras inativas */
        border: 1px solid #ddd; 
        border-bottom: none; 
        border-radius: 8px 8px 0 0; 
        opacity: 0.8;
    }
    .aba.ativa { 
        color: #0d6efd; 
        background: white; /* Fundo branco igual ao do conteúdo */
        opacity: 1; 
        padding-bottom: 13px; /* Empurra a linha pra baixo */
        margin-bottom: -1px; /* Cobre a borda da caixa de baixo */
        border-bottom: 1px solid white; /* Apaga a linha divisória */
    }
    .conteudo-aba { 
        display: none; 
        background: white; 
        padding: 20px; 
        border-radius: 8px; /* Arredonda tudo */
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        border: 1px solid #ddd; 
        position: relative; 
    }
    .conteudo-aba.ativo { display: block; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333;}
    .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
</style>

<body>
<div class="painel-container">
    <h2>🚀 Estratégia Comercial e PDV</h2>
    <p style="color: #666; margin-bottom: 20px;">Gerencie os atalhos de venda rápida e as regras de promoções automáticas.</p>

    <div class="abas">
        <div class="aba ativa" onclick="mudarAba('aba_teclado')">⌨️ Teclado Rápido (PDV)</div>
        <div class="aba" onclick="mudarAba('aba_promocoes')">🏷️ Motor de Promoções</div>
    </div>

    <div id="aba_teclado" class="conteudo-aba ativo">
        <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                <h3 style="margin-bottom: 10px;">Adicionar Atalho</h3>
                <hr style="margin-bottom: 15px; border: 0; border-top: 1px solid #ddd;">
                
                <div class="form-group" style="position: relative;">
                    <label>Buscar Produto:</label>
                    <input type="text" id="busca_produto_teclado" placeholder="Digite o nome ou código...">
                    <div id="dropdown_busca_teclado" style="display: none; position: absolute; top: 70px; left: 0; width: 100%; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 10;"></div>
                    <input type="hidden" id="produto_id_teclado">
                </div>

                <div class="form-group">
                    <label>Posição (1 a 50):</label>
                    <input type="number" id="posicao_teclado" min="1" max="50" value="1" placeholder="Ordem no painel">
                </div>

                <button style="width: 100%; padding: 12px; margin-top: 10px; background-color: #0d6efd; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 1rem;" onclick="salvarBotaoTeclado()">💾 SALVAR ATALHO</button>
            </div>

            <div style="flex: 2; min-width: 400px;">
                <h3 style="margin-bottom: 10px;">Atalhos Configurados</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead style="background: #eee;">
                        <tr>
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: center; width: 80px;">Posição</th>
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: left;">Produto</th>
                            <th style="padding: 10px; border: 1px solid #ccc; text-align: center; width: 100px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($botoes_teclado) > 0): ?>
                            <?php foreach ($botoes_teclado as $btn): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ccc; text-align: center; font-weight: bold;"><?php echo $btn['posicao']; ?></td>
                                    <td style="padding: 10px; border: 1px solid #ccc;"><?php echo htmlspecialchars($btn['nome_produto']); ?> <small style="color: #666;">(<?php echo $btn['codigo_interno']; ?>)</small></td>
                                    <td style="padding: 10px; border: 1px solid #ccc; text-align: center;">
                                        <button onclick="removerBotao(<?php echo $btn['id']; ?>)" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">🗑️ Remover</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; padding: 20px; color: #999;">Nenhum atalho configurado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="aba_promocoes" class="conteudo-aba">
        <h3 style="color: #999; text-align: center; padding: 50px 0;">🚧 Motor de Promoções em Construção... 🚧</h3>
    </div>
</div>

<script>
// --- Controle de Abas ---
function mudarAba(abaId) {
    document.querySelectorAll('.aba').forEach(el => el.classList.remove('ativa'));
    document.querySelectorAll('.conteudo-aba').forEach(el => el.classList.remove('ativo'));
    event.currentTarget.classList.add('ativa');
    document.getElementById(abaId).classList.add('ativo');
}

// --- Lógica do Teclado Rápido ---
let debounceTeclado;
document.getElementById('busca_produto_teclado').addEventListener('input', (e) => {
    clearTimeout(debounceTeclado);
    const termo = e.target.value.trim();
    const dropdown = document.getElementById('dropdown_busca_teclado');
    
    if (termo.length < 2) { dropdown.style.display = 'none'; return; }

    debounceTeclado = setTimeout(async () => {
        try {
            let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
            let json = await res.json();
            dropdown.innerHTML = '';
            
            if (json.success && json.produtos.length > 0) {
                json.produtos.forEach(p => {
                    let div = document.createElement('div');
                    div.style.cssText = "padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;";
                    div.innerHTML = `<strong>${p.nome}</strong>`;
                    div.onclick = () => {
                        document.getElementById('produto_id_teclado').value = p.id;
                        document.getElementById('busca_produto_teclado').value = p.nome;
                        dropdown.style.display = 'none';
                        document.getElementById('posicao_teclado').focus();
                    };
                    dropdown.appendChild(div);
                });
                dropdown.style.display = 'block';
            }
        } catch(err) { console.error(err); }
    }, 300);
});

async function salvarBotaoTeclado() {
    const produto_id = document.getElementById('produto_id_teclado').value;
    const posicao = document.getElementById('posicao_teclado').value;

    if(!produto_id) return alert("Selecione um produto primeiro!");

    try {
        const res = await fetch('../api/admin_salvar_teclado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ produto_id, posicao, acao: 'salvar' })
        });
        const json = await res.json();
        
        if(json.success) window.location.reload();
        else alert('Erro: ' + json.error);
    } catch(e) { alert('Erro de comunicação.'); }
}

async function removerBotao(id) {
    if(!confirm('Remover este atalho do PDV?')) return;
    try {
        const res = await fetch('../api/admin_salvar_teclado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id_registro: id, acao: 'remover' })
        });
        const json = await res.json();
        
        if(json.success) window.location.reload();
        else alert('Erro: ' + json.error);
    } catch(e) { alert('Erro de comunicação.'); }
}
</script>
</body>