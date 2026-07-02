<?php
require '../config.php';
require '../auth/auth_franqueado_check.php';

// Busca a lista de lojas para o dropdown
$stmt_lojas = $db_users->prepare("SELECT id, username FROM user WHERE id_dono = ? ORDER BY username ASC");
$stmt_lojas->execute([$_SESSION['user_id']]);
$lojas_equipe = $stmt_lojas->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Quadro Gestão Master";
$sessao_nome = "Quadro Gestão Master";
require_once '../includes/header_franq.php';
?>
<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/quadro_gestao.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<div class="container" style="position: relative; padding-bottom: 40px; margin: 0 auto;">

    <div id="modalCopiar" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h2>📋 Resumo da Rede</h2>
            <textarea id="textoCopia" readonly style="width:100%; height:180px; margin: 10px 0; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; resize: none;"></textarea>

            <button onclick="enviarDiretoWhatsAppModal()" class="qg-btn qg-btn-whatsapp" style="width: 100%; justify-content: center; margin-bottom: 10px;">
                Abrir no WhatsApp
            </button>

            <button onclick="copiarTexto()" class="qg-btn qg-btn-primary" style="width: 100%; justify-content: center;">
                Apenas Copiar Texto
            </button>

            <button onclick="document.getElementById('modalCopiar').style.display='none'" class="qg-btn" style="width: 100%; justify-content: center; margin-top: 10px; background: #e5e7eb;">
                Fechar
            </button>
        </div>
    </div>

    <div id="dashboardContent" class="dashboard-content">

        <div class="qg-header" style="background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #dee2e6; margin-bottom: 25px;">
            <div style="display: grid; grid-template-columns: 1fr auto; align-items: end; gap: 20px; width: 100%;">
                <div style="width: 90%;">
                    <label style="font-size:14px; font-weight: bold; color: #495057; display: block; margin-bottom: 8px;">Selecione as Lojas para Consolidar:</label>
                    <select id="seletorLojas" multiple placeholder="Selecione uma ou mais lojas" style="width: 100%;">
                        <?php foreach ($lojas_equipe as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button onclick="enviarWhatsApp()" class="qg-btn qg-btn-whatsapp" style="height: 46px; padding: 0 20px; white-space: nowrap; margin-bottom: 0;">
                        Enviar Resumo no WhatsApp
                    </button>
                </div>

            </div>
        </div>
    </div>

    <h1 class="quadro_titulo">QUADRO DE GESTÃO MASTER</h1>
    <hr>

    <div class="qg-grid-row-4" style="margin-bottom: 20px;">
        <div class="qg-card" style="border-left: 4px solid #3b82f6;">
            <div class="qg-card-header"><span>📅 Mês de Operação</span></div>
            <div class="qg-card-value" id="c-nome-mes">-</div>
        </div>
        <div class="qg-card" style="border-left: 4px solid #10b981;">
            <div class="qg-card-header"><span>📆 Dias Úteis (Com Meta)</span></div>
            <div class="qg-card-value" id="c-dias-uteis">0</div>
        </div>
        <div class="qg-card" style="border-left: 4px solid #f59e0b;">
            <div class="qg-card-header"><span>⏳ Dias Restantes</span></div>
            <div class="qg-card-value" id="c-dias-frente">0</div>
        </div>
    </div>



    <div class="qg-grid-row-1">
        <div class="qg-card">
            <div class="qg-card-header"><span>Meta do Mês</span></div>
            <div class="qg-card-value" id="c-meta-total">R$ 0,00</div>
        </div>
        <div class="qg-card">
            <div class="qg-card-header"><span>Meta Acumulada</span></div>
            <div class="qg-card-value" id="c-meta-acumulada">R$ 0,00</div>
        </div>
        <div class="qg-card">
            <div class="qg-card-header"><span>Sell-out Acumulado</span></div>
            <div class="qg-card-value" id="c-venda-acumulada">R$ 0,00</div>
        </div>
        <div class="qg-card qg-card-atingimento">
            <div>
                <div class="qg-card-header"><span>Atingimento</span></div>
                <div class="qg-card-value" id="c-atingimento">0%</div>
            </div>
            <div class="qg-circular-progress" id="c-atingimento-circle" style="--progress: 0deg;">
                <span class="qg-circular-progress-value" id="c-atingimento-circle-text">0%</span>
            </div>
        </div>
    </div>

    <div class="qg-grid-row-2">
        <div class="qg-card">
            <div class="qg-card-header"><span id="c-gap-title">GAP da Meta</span></div>
            <div class="qg-card-value" id="c-gap">R$ 0,00</div>
        </div>
        <div class="qg-card">
            <div class="qg-card-header"><span>Meta de Ontem</span></div>
            <div class="qg-card-value" id="c-meta-ontem">R$ 0,00</div>
        </div>
        <div class="qg-card">
            <div class="qg-card-header"><span>Sell-out de Ontem</span></div>
            <div class="qg-card-value" id="c-venda-ontem">R$ 0,00</div>
        </div>
    </div>

    <div class="qg-grid-row-3">
        <div class="qg-card">
            <div class="qg-card-header"><span>Meta de Hoje</span></div>
            <div class="qg-card-value" id="c-meta-hoje" style="color: #8b5cf6;">R$ 0,00</div>
        </div>
        <div class="qg-card qg-card-dark">
            <div class="qg-card-header"><span>Meta de Hoje Ajustada</span></div>
            <div class="qg-card-value" id="c-meta-ajustada">R$ 0,00</div>
        </div>
    </div>

    <div style="background:#fff; border-radius:16px; padding:24px; height: 300px; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
        <canvas id="graficoEvolucao"></canvas>
    </div>
</div>
</div>

<script src="../static/js/quadro_gestao_franqueado.js"></script>
<?php include '../includes/footer.php'; ?>