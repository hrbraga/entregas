<?php
require '../config.php';
require '../auth/auth_check.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Validades</title>
    <link rel="stylesheet" href="../static/css/global.css">
    <link rel="stylesheet" href="../static/css/style.css">
    <link rel="stylesheet" href="../static/css/validades.css">
</head>

<body>

    <nav class="navbar">
        <div class="nav-brand">
            <h1>Controle de Validades</h1>
        </div>
        <ul class="nav-links">
            <li><a href="../selecao_ferramentas.php">Voltar para Ferramentas</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="../auth/logout.php">Sair (<?= htmlspecialchars($_SESSION['username']); ?>)</a></li>
            <?php else: ?>
                <li><a href="../auth/login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container">
        <main class="container">
            <div id="modal-relatorio" class="modal-overlay">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>Relatório de Vencimentos</h3>
                        <span class="modal-close" onclick="fecharModalRelatorio()">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p>Selecione o período que deseja visualizar para impressão:</p>

                        <div class="form-row">
                            <div class="input-group">
                                <label>Mês</label>
                                <select id="rel-mes">
                                    <option value="1">Janeiro</option>
                                    <option value="2">Fevereiro</option>
                                    <option value="3">Março</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Maio</option>
                                    <option value="6">Junho</option>
                                    <option value="7">Julho</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Setembro</option>
                                    <option value="10">Outubro</option>
                                    <option value="11">Novembro</option>
                                    <option value="12">Dezembro</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Ano</label>
                                <select id="rel-ano">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="fecharModalRelatorio()">Cancelar</button>
                        <button class="btn-print-action" onclick="gerarRelatorio()">🖨️ Imprimir</button>
                    </div>
                </div>
            </div>

        </main>

        <div class="card input-section">
            <h3>Adicionar Produto</h3>
            <form id="form-add" autocomplete="off">
                <div class="form-row single-line">

                    <div class="input-group" style="flex: 2;">
                        <label>Buscar (Cód/Nome)</label>
                        <input type="text" id="search-input" placeholder="Digite para buscar..." required>
                        <div id="search-results" class="dropdown-results"></div>
                    </div>

                    <div class="input-group" style="flex: 3;">
                        <label>Produto Selecionado</label>
                        <input type="text" id="prod-name" readonly tabindex="-1" placeholder="...">
                        <input type="hidden" id="prod-code">
                    </div>

                    <div class="input-group" style="flex: 1; min-width: 130px;">
                        <label>Validade</label>
                        <input type="date" id="prod-date" required>
                    </div>

                    <div class="input-group" style="flex: 0.5; min-width: 80px;">
                        <label>Qtd</label>
                        <input type="number" id="prod-qtd" min="1" required>
                    </div>

                    <div class="input-group align-end" style="flex: 0.5;">
                        <button type="submit" class="btn-primary">Salvar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="top-header-container">
            <div class="header-actions">
                <button class="btn-report" onclick="abrirModalRelatorio()">
                    📄 Gerar Relatório
                </button>
                <div class="last-update-badge">
                    Atualizado: <span id="system-last-update">Carregando...</span>
                </div>
            </div>
        </div>
        
        <div class="tabs-container">
            <div class="tabs-header" id="tabs-header">
            </div>

            <div class="tabs-content" id="tabs-content">
            </div>
        </div>
    </main>

    <script src="../static/js/validades.js"></script>
</body>

</html>