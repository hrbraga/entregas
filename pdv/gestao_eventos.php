<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';

$page_title = "Gestão de Eventos e PDVs";
$sessao_nome = "Eventos e PDVs";

require_once '../includes/header_franq.php';

$user_id = $_SESSION['user_id'];

// 2. Busca os eventos já cadastrados para listar na tabela
$stmt = $db_financeiro->prepare("SELECT * FROM pdv_eventos WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/pdv.css">
<link rel="stylesheet" href="../static/css/gestao_eventos.css">

<body>

    <div class="painel-container">
        <div class="header-painel">
            <h2>🎪 Gestão de Eventos e PDVs</h2>
            <div>
                <button class="btn-acao btn-novo" onclick="abrirModal('modalNovoEvento')">+ Criar Novo Evento</button>
            </div>
        </div>

        <table class="tabela-eventos">
            <thead>
                <tr>
                    <th>Cód.</th>
                    <th>Nome do Evento</th>
                    <th>Data</th>
                    <th>Controle de Estoque</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($eventos) > 0): ?>
                    <?php foreach ($eventos as $ev): ?>
                        <tr>
                            <td>#<?php echo str_pad($ev['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><strong><?php echo htmlspecialchars($ev['nome_evento']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($ev['data_evento'])); ?></td>
                            <td><?php echo $ev['controla_estoque'] ? '✅ Sim' : '❌ Não'; ?></td>
                            <td>
                                <button onclick="mudarStatusEvento(<?php echo $ev['id']; ?>, '<?php echo $ev['status']; ?>')"
                                    class="badge <?php echo $ev['status'] == 'ativo' ? 'badge-ativo' : 'badge-inativo'; ?>"
                                    style="border: none; cursor: pointer; transition: 0.2s;"
                                    title="Clique para Ativar ou Encerrar">
                                    <?php echo strtoupper($ev['status']); ?> 🔄
                                </button>
                            </td>
                            <td style="display: flex; gap: 5px; align-items: center;">
                                <button class="btn-acao btn-estoque" style="padding: 8px 12px; font-size: 0.85rem;" onclick="abrirHubEstoque(<?php echo $ev['id']; ?>)">📦 Estoque</button>
                                <a href="relatorio_evento.php?evento_id=<?php echo $ev['id']; ?>" class="btn-acao btn-relatorio" style="padding: 8px 12px; font-size: 0.85rem;">📊 Relatório</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #999;">Nenhum evento criado ainda. Clique em "Criar Novo Evento" para começar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

   <div id="modalNovoEvento" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 100%; max-width: 500px; padding: 30px; box-sizing: border-box;">
            <h3 style="margin-bottom: 20px; color: #343a40;">🎪 Configurar Novo Evento</h3>

            <div style="margin-bottom: 15px; text-align: left;">
                <label style="font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Nome do Evento / Local:</label>
                <input type="text" id="ev_nome" placeholder="Ex: Feira da Praça, Quiosque Shopping..." style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px; text-align: left;">
                <label style="font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Data de Início:</label>
                <input type="date" id="ev_data" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 25px; text-align: left;">
                <label style="font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Controlar Estoque Separado?</label>
                <select id="ev_estoque" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1.1rem; background: white; box-sizing: border-box;">
                    <option value="0">Não (Usa o estoque geral da loja)</option>
                    <option value="1">Sim (Irei subir uma planilha de produtos)</option>
                </select>
            </div>

            <div class="modal-actions" style="display: flex; gap: 10px;">
                <button class="btn-action" style="background: #0d6efd; flex: 2; padding: 15px; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;" onclick="salvarEvento()">Criar Evento</button>
                <button class="btn-action" style="background: #6c757d; flex: 1; padding: 15px; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;" onclick="fecharModal('modalNovoEvento')">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="modalHubEstoque" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 400px; padding: 25px; text-align: center; box-sizing: border-box;">
            <h3 style="margin-bottom: 20px;">📦 Estoque do Evento</h3>
            <input type="hidden" id="hub_evento_id">
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn-acao btn-estoque" style="padding: 15px;" onclick="irParaAdicionarEstoque()">➕ Adicionar Estoque</button>
                <button class="btn-acao btn-relatorio" style="background: #198754; padding: 15px;" onclick="irParaGerenciarEstoque()">📋 Gerenciar Estoque</button>
                <button class="btn-acao" style="background: #6c757d; padding: 15px; color: white;" onclick="fecharModal('modalHubEstoque')">Voltar</button>
            </div>
        </div>
    </div>

    <div id="modalOpcoesEstoque" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 400px; padding: 25px; text-align: center; box-sizing: border-box;">
            <h3 style="margin-bottom: 10px;">📦 Adicionar Estoque</h3>
            <p style="color: #666; margin-bottom: 20px;">Como deseja inserir os produtos?</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn-acao btn-pay" style="padding: 15px;" onclick="fecharModal('modalOpcoesEstoque'); abrirModal('modalEstoque')">📄 Importar Planilha CSV</button>
                <button class="btn-acao btn-novo" style="padding: 15px;" onclick="fecharModal('modalOpcoesEstoque'); abrirEstoqueManual()">⌨️ Digitar Manualmente</button>
                <button class="btn-acao" style="background: #6c757d; padding: 15px; color: white;" onclick="fecharModal('modalOpcoesEstoque'); abrirModal('modalHubEstoque')">Voltar</button>
            </div>
        </div>
    </div>

    <div id="modalEstoque" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 400px; padding: 25px; box-sizing: border-box;">
            <h3>📦 Importar CSV</h3>
            <form id="formEstoque" enctype="multipart/form-data">
                <input type="hidden" id="estoque_evento_id" name="evento_id">
                <p>Selecione o CSV (Colunas: <b>codigo_interno;quantidade</b>):</p>
                <input type="file" name="arquivo_csv" accept=".csv" required style="width: 100%; margin: 15px 0; box-sizing: border-box;">
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-acao btn-pay" style="flex: 2;" onclick="importarEstoque()">Importar</button>
                    <button type="button" class="btn-acao" style="background: #6c757d; flex: 1; color: white;" onclick="fecharModal('modalEstoque')">Fechar</button>
                </div>
            </form>
        </div>
    </div>




    <div id="modalEstoqueManual" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 650px; padding: 25px; box-sizing: border-box; border-radius: 8px; height: auto;">
            <h3 style="margin-bottom: 20px;">⌨️ Inserção Manual de Estoque</h3>

            <div style="display: flex; gap: 10px; margin-bottom: 15px; position: relative; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="font-weight: bold; font-size: 0.9rem;">Buscar Produto:</label>
                    <input type="text" id="busca_produto_manual" placeholder="Nome, Cód. Barras ou Cód. Interno..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                    <div id="dropdown_busca_manual" style="display: none; position: absolute; top: 70px; left: 0; width: 100%; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 10000;"></div>
                    <input type="hidden" id="produto_selecionado_id_manual">
                    <input type="hidden" id="produto_selecionado_nome_manual">
                </div>

                <div style="width: 80px;">
                    <label style="font-weight: bold; font-size: 0.9rem;">Qtd:</label>
                    <input type="number" id="qtd_produto_manual" value="1" min="1" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; text-align: center; box-sizing: border-box;">
                </div>

                <div>
                    <button class="btn-acao btn-pay" style="font-size: 14px; padding: 10px 15px; height: 42px; color: white;" onclick="adicionarItemManualLista()">➕ Inserir</button>
                </div>
            </div>

            <div class="caixa-scroll-estoque" style="width: 100%; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 10px; text-align: left; border: 1px solid #eee; width: 70%;">Produto</th>
                            <th style="padding: 10px; text-align: center; border: 1px solid #eee; width: 15%;">Qtd</th>
                            <th style="padding: 10px; text-align: center; border: 1px solid #eee; width: 15%;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="lista_manual_tbody"></tbody>
                </table>
            </div>

            <div style="display:flex; gap:10px;">
                <button class="btn-acao btn-pay" style="flex:2;" onclick="salvarEstoqueManual()">Salvar Estoque</button>
                <button class="btn-acao" style="background:#6c757d; flex:1; color: white;" onclick="fecharModal('modalEstoqueManual')">Fechar</button>
            </div>
        </div>
    </div>

    <div id="modalGerenciarEstoque" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 9999;">
        <div class="modal-content" style="width: 800px; max-width: 95vw; padding: 25px; box-sizing: border-box; border-radius: 8px; display: flex; flex-direction: column; max-height: 90vh;">
            
            <h3 style="margin-bottom:20px;">📋 Gerenciar Estoque do Evento</h3>

            <div style="display:flex; gap:10px; margin-bottom:15px; position:relative; align-items:flex-end; padding-bottom:15px; border-bottom:1px solid #ddd; flex-shrink: 0;">
                <div style="flex:1;">
                    <label style="font-weight:bold; font-size:0.9rem;">Adicionar Novo Item ao Evento:</label>
                    <input type="text" id="busca_produto_gerenciar" placeholder="Nome, Cód. Barras ou Cód. Interno..." style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
                    <div id="dropdown_busca_gerenciar" style="display:none; position:absolute; top:70px; left:0; width:100%; background:white; border:1px solid #ccc; max-height:200px; overflow-y:auto; box-shadow:0 4px 8px rgba(0,0,0,.1); z-index:10000;"></div>
                    <input type="hidden" id="produto_selecionado_id_gerenciar">
                </div>

                <div style="width:80px;">
                    <label style="font-weight:bold; font-size:0.9rem;">Qtd:</label>
                    <input type="number" id="qtd_produto_gerenciar" value="1" min="1" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:5px; text-align:center; box-sizing: border-box;">
                </div>

                <div>
                    <button class="btn-acao btn-pay" style="padding:10px 15px; height:42px; color: white;" onclick="adicionarNovoItemGerenciamento()">➕ Adicionar</button>
                </div>
            </div>

            <div style="width: 100%; flex: 1; overflow-y: auto; border: 1px solid #eee; margin-bottom: 20px; border-radius: 4px;">
                <table style="width:100%; border-collapse:collapse; table-layout: fixed;">
                    <thead style="background:#f8f9fa; position:sticky; top:0; z-index:1;">
                        <tr>
                            <th style="padding:10px; width:20%; text-align:left; border-bottom: 1px solid #eee;">CÓD</th>
                            <th style="padding:10px; width:45%; text-align:left; border-bottom: 1px solid #eee;">PRODUTO</th>
                            <th style="padding:10px; width:15%; text-align:center; border-bottom: 1px solid #eee;">QTD ATUAL</th>
                            <th style="padding:10px; width:20%; text-align:center; border-bottom: 1px solid #eee;">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody id="lista_gerenciar_tbody">
                        <tr><td colspan="4" style="text-align:center; padding:20px;">Carregando estoque...</td></tr>
                    </tbody>
                </table>
            </div>

            <div style="flex-shrink: 0;">
                <button class="btn-acao" style="background:#6c757d; width:100%; color: white;" onclick="fecharModal('modalGerenciarEstoque')">Fechar Janela</button>
            </div>
        </div>
    </div>


<style>
        /* GARANTE QUE O FUNDO ESCURO CUBRA TUDO E FIXE O MODAL NO ECRÃ */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: rgba(0, 0, 0, 0.7) !important;
            z-index: 9999 !important;
        }

        /* TRAVA A ALTURA DOS DOIS MODAIS (ESTOQUE MANUAL E GERENCIAR) */
        #modalEstoqueManual .modal-content,
        #modalGerenciarEstoque .modal-content {
            max-height: 90vh !important; /* Limite máximo de 90% do ecrã */
            display: flex !important;
            flex-direction: column !important;
            max-width: 95vw !important;
        }

        /* --- ANTÍDOTO PARA AS TABELAS (LARGURA E QUEBRA DE LINHA) --- */
        #modalEstoqueManual table,
        #modalGerenciarEstoque table {
            min-width: 0 !important; 
            width: 100% !important;
            table-layout: fixed !important;
        }

        #modalEstoqueManual th, #modalEstoqueManual td,
        #modalGerenciarEstoque th, #modalGerenciarEstoque td {
            white-space: normal !important; 
            word-wrap: break-word !important;
            padding: 10px !important;
        }

        /* --- CONTROLO DO SCROLL VERTICAL DINÂMICO --- */
        .caixa-scroll-estoque {
            flex: 1 !important; /* Faz a tabela crescer apenas até ao limite do modal */
            overflow-y: auto !important; /* Ativa a barra de scroll */
            border: 1px solid #eee;
            margin-bottom: 15px;
            min-height: 200px;
        }

        /* Cabeçalho fixo no topo enquanto se faz scroll */
        #modalEstoqueManual thead th,
        #modalGerenciarEstoque thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 2;
            box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
        }
    </style>
    
    <script src="../static/js/gestao_eventos.js"></script>

</body>

</html>