<?php
require '../config.php';
require '../auth/auth_check.php';
$page_title = "Quadro de Gestão";
$sessao_nome = "Quadro de Gestão";
require_once '../includes/header.php';

$is_franqueado = ($_SESSION['perfil'] === 'franqueado');

?>
<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/quadro_gestao.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body>

<div class="container" style="position: relative; padding-bottom: 40px; margin-top: 20px;">

    <!-- Modal de Bloqueio -->
    <div id="modalPendencias" class="modal-overlay">
        <div class="modal-content">
            <h2>⚠️ Vendas Pendentes</h2>
            <p style="color: #666; font-size: 14px;">Para acessar o quadro, preencha o faturamento em atraso:</p>
            <form id="formPendencias" class="form-pendencias">
                <div id="listaPendencias"></div>
                <button type="submit" class="qg-btn qg-btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Salvar e Acessar</button>
            </form>
        </div>
    </div>

    <div id="modalCopiar" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h2>📋 Resumo do Dia</h2>
            <textarea id="textoCopia" readonly style="width:100%; height:180px; margin: 10px 0; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; resize: none;"></textarea>

            <button onclick="enviarDiretoWhatsAppModal()" class="qg-btn qg-btn-whatsapp" style="width: 100%; justify-content: center; margin-bottom: 10px;">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 8px;">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
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

    <div id="modalResetMes" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="text-align: center;">
            <h2 style="color: #dc2626;">⚠️ Tem certeza absoluta?</h2>
            <p style="margin: 15px 0; font-size: 15px; color: #4b5563; line-height: 1.5;">
                Você deseja iniciar um novo mês?<br>
                <strong>TODOS OS DADOS DO MÊS ATUAL SERÃO APAGADOS</strong> do banco de dados e esta ação não pode ser desfeita.
            </p>
            <button onclick="confirmarResetMes()" class="qg-btn" style="width: 100%; justify-content: center; background: #dc2626; color: white;">Sim, Confirmar e Apagar</button>
            <button onclick="document.getElementById('modalResetMes').style.display='none'" class="qg-btn" style="width: 100%; justify-content: center; margin-top: 10px; background: #e5e7eb;">Cancelar</button>
        </div>
    </div>

    <div id="modalErroMes" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="text-align: center;">
            <div class="qg-card-icon" style="color: #f59e0b; background: #fffbeb; width: 48px; height: 48px; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 style="color: #92400e;">Mês Incorreto</h2>
            <p style="margin: 15px 0; font-size: 15px; color: #4b5563; line-height: 1.5;">
                A planilha que você está tentando importar possui datas diferentes do mês atual.<br><br>
                Para manter os indicadores da sua loja precisos, o sistema só aceita o upload de metas do mês vigente.
            </p>
            <button onclick="document.getElementById('modalErroMes').style.display='none'" class="qg-btn" style="width: 100%; justify-content: center; background: #f59e0b; color: white;">Entendi</button>
        </div>
    </div>

    <!-- Conteúdo do Dashboard -->
    <div id="dashboardContent" class="dashboard-content dashboard-blur">

        <!-- Cabeçalho e Botões de Ação -->
        <div class="qg-header">
            <div class="qg-actions">
                <button onclick="document.getElementById('importar_metas').click()" class="qg-btn qg-btn-primary">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Importar Meta
                </button>
                <button onclick="enviarWhatsApp()" class="qg-btn qg-btn-whatsapp">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                    </svg>
                    Enviar no WhatsApp
                </button>
                <input type="file" id="importar_metas" style="display:none;" accept=".csv, .xlsx, .xls">
                <button onclick="abrirModalReset()" class="qg-btn" style="background: #dc2626; color: white;">
                    ⚠️ Iniciar Novo Mês
                </button>
            </div>
        </div>


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

        <h1 class="quadro_titulo">QUADRO DE GESTÃO</h1>

        <hr>

        <div class="qg-grid-row-1">
            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <span>Meta do Mês</span>
                </div>
                <div class="qg-card-value" id="c-meta-total">R$ 0,00</div>
            </div>

            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon" style="color: #0d6efd; background: #eef2ff;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <span>Meta Acumulada</span>
                </div>
                <div class="qg-card-value" id="c-meta-acumulada">R$ 0,00</div>
            </div>

            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon" style="color: #10b981; background: #ecfdf5;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <span>Sell-out Acumulado</span>
                </div>
                <div class="qg-card-value" id="c-venda-acumulada">R$ 0,00</div>
            </div>

            <!-- Card 4: Atingimento (Com Gráfico) -->
            <div class="qg-card qg-card-atingimento">
                <div>
                    <div class="qg-card-header">
                        <span>Atingimento</span>
                    </div>
                    <div class="qg-card-value" id="c-atingimento">0%</div>
                </div>
                <div class="qg-circular-progress" id="c-atingimento-circle" style="--progress: 0deg;">
                    <span class="qg-circular-progress-value" id="c-atingimento-circle-text">0%</span>
                </div>
            </div>
        </div>

        <!-- SEGUNDA LINHA: 3 Cards -->
        <div class="qg-grid-row-2">
            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon" style="color: #ef4444; background: #fef2f2;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <span>GAP da Meta</span>
                </div>
                <div class="qg-card-value" id="c-gap">R$ 0,00</div>
            </div>

            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 8 14"></polyline>
                        </svg>
                    </div>
                    <span>Meta de Ontem</span>
                </div>
                <div class="qg-card-value" id="c-meta-ontem">R$ 0,00</div>
            </div>

            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon" style="color: #f59e0b; background: #fffbeb;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <span>Sell-out de Ontem</span>
                </div>
                <div class="qg-card-value" id="c-venda-ontem">R$ 0,00</div>
            </div>
        </div>

        <!-- TERCEIRA LINHA: 2 Cards -->
        <div class="qg-grid-row-3">
            <div class="qg-card">
                <div class="qg-card-header">
                    <div class="qg-card-icon" style="color: #8b5cf6; background: #f5f3ff;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <span>Meta de Hoje</span>
                </div>
                <div class="qg-card-value" id="c-meta-hoje" style="color: #8b5cf6;">R$ 0,00</div>
            </div>

            <div class="qg-card qg-card-dark">
                <div class="qg-card-header">
                    <div class="qg-card-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <span>Meta de Hoje Ajustada</span>
                </div>
                <div class="qg-card-value" id="c-meta-ajustada">R$ 0,00</div>
            </div>
        </div>

        <!-- Gráfico -->
        <div style="background:#fff; border-radius:16px; padding:24px; height: 300px; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
            <canvas id="graficoEvolucao"></canvas>
        </div>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', () => {
        const target = document.getElementById('c-atingimento');
        if (target) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    let text = mutation.target.innerText;
                    let num = parseFloat(text.replace('%', '').replace(',', '.'));

                    if (!isNaN(num)) {
                        let circle = document.getElementById('c-atingimento-circle');
                        let circleText = document.getElementById('c-atingimento-circle-text');

                        // Limita o preenchimento a 360 graus para o visual do gráfico
                        let graus = (num / 100) * 360;
                        if (graus > 360) graus = 360;

                        // Atualiza a variável CSS e o texto interno da rosca
                        circle.style.setProperty('--progress', graus + 'deg');
                        circleText.innerText = Math.round(num) + '%';

                        // Muda a cor do gráfico se bater ou ultrapassar 100% (Verde Cacau Show)
                        if (num >= 100) {
                            circle.style.background = `conic-gradient(#10b981 var(--progress), #e5e7eb 0deg)`;
                        } else {
                            // Azul para andamento normal
                            circle.style.background = `conic-gradient(#3b82f6 var(--progress), #e5e7eb 0deg)`;
                        }
                    }
                });
            });
            observer.observe(target, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
    });
</script>

<script src="../static/js/quadro_gestao.js"></script>

<?php if ($is_franqueado): ?>
<div id="modalRedirect" style="display: flex; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; width: 90%;">
        <h3>🏢 Acesso Corporativo</h3>
        <p>Você é um <strong style="color: #0d6efd; font-size: 14px;">Franqueado</strong>. Para uma melhor gestão, você será redirecionado para o seu Quadro Corporativo.</p>
        <button onclick="window.location.href='../gestao/quadro_gestao_franqueado.php'" 
                style="padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Ir para Quadro Corporativo
        </button>
    </div>
</div>

<script>
    // Redirecionamento automático após 5 segundos, caso o usuário não clique
    setTimeout(function() {
        window.location.href = '../gestao/quadro_gestao_franqueado.php';
    }, 5000);
</script>
<?php endif; ?>

</body> 
<?php include '../includes/footer.php'; ?>