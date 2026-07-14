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
$stmtCampanhas = $db_financeiro->prepare("SELECT * FROM motor_promocoes ORDER BY id DESC");
$stmtCampanhas->execute();
$campanhas_motor = $stmtCampanhas->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../static/css/style.css">
<style>
    .abas {
        display: flex;
        gap: 5px;
        margin-bottom: 0;
        /* Tira a margem para grudar no conteúdo */
        position: relative;
        z-index: 1;
        padding-left: 10px;
    }

    .aba {
        padding: 12px 25px;
        cursor: pointer;
        font-weight: bold;
        color: #666;
        background: #f8f9fa;
        /* Fundo cinza clarinho pras inativas */
        border: 1px solid #ddd;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        opacity: 0.8;
    }

    .aba.ativa {
        color: #0d6efd;
        background: white;
        /* Fundo branco igual ao do conteúdo */
        opacity: 1;
        padding-bottom: 13px;
        /* Empurra a linha pra baixo */
        margin-bottom: -1px;
        /* Cobre a borda da caixa de baixo */
        border-bottom: 1px solid white;
        /* Apaga a linha divisória */
    }

    .conteudo-aba {
        display: none;
        background: white;
        padding: 20px;
        border-radius: 8px;
        /* Arredonda tudo */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #ddd;
        position: relative;
    }

    .conteudo-aba.ativo {
        display: block;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

/* --- MENU KEBAB (FORA DA PRISÃO DA TABELA) --- */
    .kebab-menu {
        display: inline-block;
    }

    .kebab-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 5px 10px;
        color: #666;
        font-weight: bold;
    }

    .kebab-content {
        display: none;
        background-color: #fff;
        min-width: 190px;
        box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
        z-index: 999999 !important; /* Maior possível */
        border-radius: 4px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .kebab-content button {
        color: #333;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
    }

    .kebab-content button:hover {
        background-color: #f8f9fa;
    }

    .kebab-content button:last-child {
        border-bottom: none;
    }
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
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 20px; color: #999;">Nenhum atalho configurado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="aba_promocoes" class="conteudo-aba">
            <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #eee;">
                    <h3 style="margin-bottom: 10px;">Nova Campanha</h3>
                    <hr style="margin-bottom: 15px; border: 0; border-top: 1px solid #ddd;">

                    <div class="form-group">
                        <label>Nome da Campanha:</label>
                        <input type="text" id="promo_nome" placeholder="Ex: Festival de Trufas">
                    </div>
                    <div class="form-group">
                        <label>Mecânica:</label>
                        <select id="promo_mecanica">
                            <option value="leve_x_pague_y">Leve X Pague Y (Ex: Compre 6 Pague 5)</option>
                            <option value="preco_fixo_combo">Preço Fixo no Combo (Ex: 2 por R$ 26,90)</option>
                            <option value="desconto_valor">Desconto Fixo na 2ª Unid. (Ex: Desc. R$ 10)</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Qtd Gatilho:</label>
                            <input type="number" id="promo_gatilho" placeholder="Ex: 6" min="1">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Benefício (R$ ou Unid):</label>
                            <input type="number" id="promo_beneficio" placeholder="Ex: 1 ou 26.90" step="0.01">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Início:</label>
                            <input type="date" id="promo_inicio">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Fim:</label>
                            <input type="date" id="promo_fim">
                        </div>
                    </div>
                    <button class="btn-acao btn-pay" style="width: 100%; padding: 12px; margin-top: 10px; background-color: #0d6efd; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;" onclick="salvarCampanha()">💾 CRIAR CAMPANHA</button>
                </div>

                <div style="flex: 2; min-width: 400px;">
                    <h3 style="margin-bottom: 10px;">Campanhas Cadastradas</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead style="background: #eee;">
                            <tr>
                                <th style="padding: 10px; border: 1px solid #ccc; text-align: left;">Campanha</th>
                                <th style="padding: 10px; border: 1px solid #ccc; text-align: center;">Regra</th>
                                <th style="padding: 10px; border: 1px solid #ccc; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($campanhas_motor) > 0): ?>
                                <?php foreach ($campanhas_motor as $c): ?>
                                    <tr>
                                        <td style="padding: 10px; border: 1px solid #ccc;">
                                            <strong><?php echo htmlspecialchars($c['nome_campanha']); ?></strong><br>
                                            <small style="color: #666;">Validade: <?php echo date('d/m/Y', strtotime($c['data_inicio'])); ?> a <?php echo date('d/m/Y', strtotime($c['data_fim'])); ?></small>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #ccc; text-align: center;">
                                            <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;">
                                                <?php
                                                if ($c['tipo_mecanica'] == 'leve_x_pague_y') echo "Gatilho: {$c['qtd_gatilho']} | Grátis: " . ($c['valor_beneficio']);
                                                if ($c['tipo_mecanica'] == 'preco_fixo_combo') echo "{$c['qtd_gatilho']} por R$ " . number_format($c['valor_beneficio'], 2, ',', '.');
                                                if ($c['tipo_mecanica'] == 'desconto_valor') echo "Gatilho {$c['qtd_gatilho']} | Desc: R$ " . number_format($c['valor_beneficio'], 2, ',', '.');
                                                ?>
                                            </span>
                                        </td>
                                        <td style="padding: 10px; border: 1px solid #ccc; text-align: center;">
                                            <div class="kebab-menu" id="kebab-wrapper-<?php echo $c['id']; ?>" onclick="toggleKebab(event, <?php echo $c['id']; ?>)">
                                                <button class="kebab-btn">⋮</button>
                                                <div class="kebab-content">
                                                    <button onclick="abrirModalProdutosPromo(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nome_campanha']); ?>')">📦 Adicionar Produtos</button>
                                                    <button onclick="abrirModalEditarCampanha(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nome_campanha']); ?>', '<?php echo $c['tipo_mecanica']; ?>', <?php echo $c['qtd_gatilho']; ?>, <?php echo $c['valor_beneficio']; ?>, '<?php echo $c['data_inicio']; ?>', '<?php echo $c['data_fim']; ?>')">✏️ Editar Campanha</button>
                                                    <button onclick="excluirCampanha(<?php echo $c['id']; ?>)" style="color: #dc3545; font-weight: bold;">🗑️ Excluir</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 20px; color: #999;">Nenhuma campanha cadastrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                .modal-overlay {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100vw !important;
                    height: 100vh !important;
                    background-color: rgba(0, 0, 0, 0.7) !important;
                    z-index: 9999 !important;
                }

                .modal-content {
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                    position: relative;
                    z-index: 10000;
                    max-width: 95vw !important;
                    max-height: 90vh !important;
                    /* Limita a altura máxima do modal no ecrã */
                    display: flex !important;
                    flex-direction: column !important;
                }

                /* --- ANTÍDOTO PARA A TABELA (LARGURA E QUEBRA DE LINHA) --- */
                #modalProdutosPromo table {
                    min-width: 0 !important;
                    width: 100% !important;
                    table-layout: fixed !important;
                }

                #modalProdutosPromo th,
                #modalProdutosPromo td {
                    white-space: normal !important;
                    word-wrap: break-word !important;
                    padding: 10px !important;
                }

                /* --- CONTROLO DO SCROLL VERTICAL --- */
                #caixa-scroll-tabela {
                    max-height: 50vh !important;
                    /* A tabela ocupará no máximo 50% da altura do ecrã */
                    overflow-y: auto !important;
                    /* Faz aparecer a barra vertical se passar do limite */
                    border: 1px solid #eee;
                    margin-bottom: 15px;
                }

                /* Fixa o cabeçalho no topo enquanto faz scroll (Bónus de Usabilidade!) */
                #modalProdutosPromo thead th {
                    position: sticky;
                    top: 0;
                    background-color: #f8f9fa;
                    z-index: 2;
                    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
                }
            </style>

            <div id="modalProdutosPromo" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
                <div class="modal-content" style="width: 700px; padding: 25px; box-sizing: border-box; border-radius: 8px; max-height: 90vh; display: flex; flex-direction: column;">
                    <h3 style="margin-bottom: 15px;">📦 Produtos Vinculados: <span id="nome_campanha_modal" style="color: #0d6efd;"></span></h3>
                    <input type="hidden" id="id_campanha_modal">

                    <div style="position: relative; margin-bottom: 15px; flex-shrink: 0;">
                        <input type="text" id="busca_produto_promo" placeholder="🔍 Digite para adicionar um produto nesta promoção..." style="width: 100%; padding: 12px; border: 2px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                        <div id="dropdown_busca_promo" style="display: none; position: absolute; top: 45px; left: 0; width: 100%; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 10000;"></div>
                    </div>

                    <div style="flex: 1; overflow-y: auto; border: 1px solid #eee; margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <thead style="background: #f8f9fa; position: sticky; top: 0;">
                                <tr>
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee; width: 25%;">Cód</th>
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee; width: 60%;">Produto</th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #eee; width: 15%;">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="lista_produtos_promo_tbody"></tbody>
                        </table>
                    </div>

                    <button style="background:#6c757d; color: white; width: 100%; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; flex-shrink: 0;" onclick="document.getElementById('modalProdutosPromo').style.display = 'none'">Fechar e Voltar</button>
                </div>
            </div>
        </div>
    </div>

<div id="modalEditarPromo" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
    <div class="modal-content" style="width: 500px; padding: 25px; box-sizing: border-box; border-radius: 8px;">
        <h3 style="margin-bottom: 15px;">✏️ Editar Campanha</h3>
        <input type="hidden" id="edit_promo_id">
        
        <div class="form-group">
            <label>Nome da Campanha:</label>
            <input type="text" id="edit_promo_nome">
        </div>
        <div class="form-group">
            <label>Mecânica:</label>
            <select id="edit_promo_mecanica">
                <option value="leve_x_pague_y">Leve X Pague Y (Ex: Compre 6 Pague 5)</option>
                <option value="preco_fixo_combo">Preço Fixo no Combo (Ex: 2 por R$ 26,90)</option>
                <option value="desconto_valor">Desconto Fixo na 2ª Unid. (Ex: Desc. R$ 10)</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Qtd Gatilho:</label>
                <input type="number" id="edit_promo_gatilho" min="1">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Benefício (R$ ou Unid):</label>
                <input type="number" id="edit_promo_beneficio" step="0.01">
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <div class="form-group" style="flex: 1;">
                <label>Início:</label>
                <input type="date" id="edit_promo_inicio">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Fim:</label>
                <input type="date" id="edit_promo_fim">
            </div>
        </div>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button class="btn-acao btn-pay" style="flex: 2; padding: 12px; background-color: #198754; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;" onclick="salvarEdicaoCampanha()">Salvar Alterações</button>
            <button style="flex: 1; padding: 12px; background-color: #6c757d; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;" onclick="fecharModalEditarPromo()">Cancelar</button>
        </div>
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

            if (termo.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

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
                } catch (err) {
                    console.error(err);
                }
            }, 300);
        });

        async function salvarBotaoTeclado() {
            const produto_id = document.getElementById('produto_id_teclado').value;
            const posicao = document.getElementById('posicao_teclado').value;

            if (!produto_id) return alert("Selecione um produto primeiro!");

            try {
                const res = await fetch('../api/admin_salvar_teclado.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        produto_id,
                        posicao,
                        acao: 'salvar'
                    })
                });
                const json = await res.json();

                if (json.success) window.location.reload();
                else alert('Erro: ' + json.error);
            } catch (e) {
                alert('Erro de comunicação.');
            }
        }

        async function removerBotao(id) {
            if (!confirm('Remover este atalho do PDV?')) return;
            try {
                const res = await fetch('../api/admin_salvar_teclado.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_registro: id,
                        acao: 'remover'
                    })
                });
                const json = await res.json();

                if (json.success) window.location.reload();
                else alert('Erro: ' + json.error);
            } catch (e) {
                alert('Erro de comunicação.');
            }
        }

        // ==========================================
        // LÓGICA DO MOTOR DE PROMOÇÕES
        // ==========================================
        async function salvarCampanha() {
            const payload = {
                acao: 'criar_campanha',
                nome: document.getElementById('promo_nome').value,
                mecanica: document.getElementById('promo_mecanica').value,
                gatilho: document.getElementById('promo_gatilho').value,
                beneficio: document.getElementById('promo_beneficio').value,
                inicio: document.getElementById('promo_inicio').value,
                fim: document.getElementById('promo_fim').value
            };

            if (!payload.nome || !payload.gatilho || !payload.beneficio || !payload.inicio || !payload.fim) {
                return alert("Preencha todos os campos da campanha!");
            }

            try {
                const res = await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) window.location.reload();
                else alert('Erro: ' + json.error);
            } catch (e) {
                alert('Erro ao salvar campanha.');
            }
        }

        async function excluirCampanha(id) {
            if (!confirm("Atenção: Isso excluirá a campanha e removerá o vínculo de todos os produtos nela. Continuar?")) return;
            try {
                const res = await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        acao: 'excluir_campanha',
                        id: id
                    })
                });
                const json = await res.json();
                if (json.success) window.location.reload();
            } catch (e) {
                alert('Erro ao excluir.');
            }
        }

        // === GERENCIAMENTO DOS PRODUTOS DA CAMPANHA ===
        function abrirModalProdutosPromo(id, nome) {
            document.getElementById('id_campanha_modal').value = id;
            document.getElementById('nome_campanha_modal').innerText = nome;
            document.getElementById('busca_produto_promo').value = '';
            document.getElementById('modalProdutosPromo').style.display = 'flex';
            carregarProdutosDaCampanha();
        }

        async function carregarProdutosDaCampanha() {
            const id = document.getElementById('id_campanha_modal').value;
            const tbody = document.getElementById('lista_produtos_promo_tbody');
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 10px;">Carregando...</td></tr>';

            try {
                const res = await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        acao: 'listar_produtos',
                        promocao_id: id
                    })
                });
                const json = await res.json();

                tbody.innerHTML = '';
                if (json.produtos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: #999; padding: 10px;">Nenhum produto vinculado ainda. Busque acima para adicionar.</td></tr>';
                } else {
                    json.produtos.forEach(p => {
                        tbody.innerHTML += `
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${p.codigo_interno}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${p.nome_produto}</td>
                        <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">
                            <button onclick="removerProdutoDaCampanha(${p.id})" style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">X</button>
                        </td>
                    </tr>`;
                    });
                }
            } catch (e) {}
        }

        let debounceBuscaPromo;
        let focoBuscaPromo = -1; // Variável para rastrear onde a setinha está

        document.getElementById('busca_produto_promo').addEventListener('input', (e) => {
            clearTimeout(debounceBuscaPromo);
            const termo = e.target.value.trim();
            const dropdown = document.getElementById('dropdown_busca_promo');
            focoBuscaPromo = -1; // Reseta o foco ao digitar

            if (termo.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            debounceBuscaPromo = setTimeout(async () => {
                try {
                    let res = await fetch(`../api/buscar_produto_pdv.php?q=${termo}`);
                    let json = await res.json();
                    dropdown.innerHTML = '';

                    if (json.success && json.produtos.length > 0) {
                        json.produtos.forEach((p, index) => {
                            let div = document.createElement('div');
                            div.className = 'item-busca-promo'; // Classe para o teclado encontrar
                            div.style.cssText = "padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;";
                            div.innerHTML = `<strong>${p.nome}</strong> <small style="color:#666;">(${p.codigo_interno})</small>`;

                            div.onclick = () => {
                                dropdown.style.display = 'none';
                                document.getElementById('busca_produto_promo').value = '';
                                adicionarProdutoNaCampanha(p.id); // Adiciona na mesma hora
                                document.getElementById('busca_produto_promo').focus(); // Devolve o cursor piscando
                            };

                            // Sincroniza o mouse com a setinha do teclado
                            div.onmouseover = () => {
                                focoBuscaPromo = index;
                                atualizarFocoPromo();
                            };

                            dropdown.appendChild(div);
                        });
                        dropdown.style.display = 'block';
                    }
                } catch (e) {}
            }, 250);
        });

        // NAVEGAÇÃO COM AS SETAS E LEITOR DE CÓDIGO
        document.getElementById('busca_produto_promo').addEventListener('keydown', (e) => {
            const dropdown = document.getElementById('dropdown_busca_promo');
            const itens = dropdown.getElementsByClassName('item-busca-promo');

            // Se a lista estiver aberta...
            if (dropdown.style.display === 'block' && itens.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    focoBuscaPromo++;
                    if (focoBuscaPromo >= itens.length) focoBuscaPromo = 0;
                    atualizarFocoPromo(itens);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    focoBuscaPromo--;
                    if (focoBuscaPromo < 0) focoBuscaPromo = itens.length - 1;
                    atualizarFocoPromo(itens);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (focoBuscaPromo > -1) {
                        itens[focoBuscaPromo].click(); // Adiciona o selecionado
                    } else {
                        itens[0].click(); // Ou joga o primeiro (ex: ao usar leitor de código de barras)
                    }
                } else if (e.key === 'Escape') {
                    dropdown.style.display = 'none';
                }
            }
            // Impede o Enter de recarregar a tela atoa
            else if (e.key === 'Enter') {
                e.preventDefault();
            }
        });

        // Função para pintar o fundo de cinza no item focado
        function atualizarFocoPromo(itens = null) {
            if (!itens) itens = document.getElementById('dropdown_busca_promo').getElementsByClassName('item-busca-promo');
            for (let i = 0; i < itens.length; i++) itens[i].style.backgroundColor = "white";
            if (focoBuscaPromo >= 0 && focoBuscaPromo < itens.length) {
                itens[focoBuscaPromo].style.backgroundColor = "#e9ecef";
                itens[focoBuscaPromo].scrollIntoView({
                    block: "nearest"
                });
            }
        }

        async function adicionarProdutoNaCampanha(produto_id) {
            const promo_id = document.getElementById('id_campanha_modal').value;
            try {
                await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        acao: 'add_produto',
                        promocao_id: promo_id,
                        produto_id: produto_id
                    })
                });
                carregarProdutosDaCampanha();
            } catch (e) {}
        }

        async function removerProdutoDaCampanha(id_registro) {
            try {
                await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        acao: 'del_produto',
                        id_registro: id_registro
                    })
                });
                carregarProdutosDaCampanha();
            } catch (e) {}
        }

      // --- MENU KEBAB (O CÓDIGO SUPREMO) ---
        function toggleKebab(event, id) {
            event.stopPropagation(); 
            
            const content = document.querySelector('#kebab-wrapper-' + id + ' .kebab-content');
            const isAberto = content.style.display === 'block';

            // 1. Fecha TODOS os menus primeiro
            document.querySelectorAll('.kebab-content').forEach(menu => {
                menu.style.display = 'none';
            });

            // 2. Se já estava aberto, ele fechou no passo anterior, então encerramos aqui
            if (isAberto) return;

            // 3. O PULO DO GATO: Calcula a posição exata do botão na tela
            const btn = document.querySelector('#kebab-wrapper-' + id + ' .kebab-btn');
            const rect = btn.getBoundingClientRect();
            
            // 4. Posiciona o menu livremente na tela, ignorando qualquer tabela
            content.style.position = 'fixed'; // Desprende da página
            content.style.top = (rect.bottom) + 'px'; // Cola exatamente embaixo do botão
            content.style.left = (rect.right - 190) + 'px'; // 190 é a largura do menu, alinha à direita
            content.style.display = 'block';
        }

        // Se clicar em qualquer lugar da tela, fecha os menus
        document.addEventListener('click', () => {
            document.querySelectorAll('.kebab-content').forEach(menu => {
                menu.style.display = 'none';
            });
        });

        // Se a pessoa rolar a página com o scroll do mouse, fecha o menu (para não ficar flutuando solto)
        document.addEventListener('scroll', () => {
            document.querySelectorAll('.kebab-content').forEach(menu => {
                menu.style.display = 'none';
            });
        }, true);

        // --- EDIÇÃO DE CAMPANHAS ---
        function abrirModalEditarCampanha(id, nome, mecanica, gatilho, beneficio, inicio, fim) {
            document.getElementById('edit_promo_id').value = id;
            document.getElementById('edit_promo_nome').value = nome;
            document.getElementById('edit_promo_mecanica').value = mecanica;
            document.getElementById('edit_promo_gatilho').value = gatilho;
            document.getElementById('edit_promo_beneficio').value = beneficio;
            document.getElementById('edit_promo_inicio').value = inicio;
            document.getElementById('edit_promo_fim').value = fim;
            document.getElementById('modalEditarPromo').style.display = 'flex';
        }

        function fecharModalEditarPromo() {
            document.getElementById('modalEditarPromo').style.display = 'none';
        }

        async function salvarEdicaoCampanha() {
            const payload = {
                acao: 'editar_campanha',
                id: document.getElementById('edit_promo_id').value,
                nome: document.getElementById('edit_promo_nome').value,
                mecanica: document.getElementById('edit_promo_mecanica').value,
                gatilho: document.getElementById('edit_promo_gatilho').value,
                beneficio: document.getElementById('edit_promo_beneficio').value,
                inicio: document.getElementById('edit_promo_inicio').value,
                fim: document.getElementById('edit_promo_fim').value
            };

            if (!payload.nome || !payload.gatilho || !payload.beneficio || !payload.inicio || !payload.fim) {
                return alert("Preencha todos os campos da campanha!");
            }

            try {
                const res = await fetch('../api/admin_motor_promocoes.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) window.location.reload();
                else alert('Erro: ' + json.error);
            } catch (e) {
                alert('Erro ao salvar edição.');
            }
        }
    </script>
</body>