<?php
require '../config.php';
require '../auth/auth_check.php'; // Protege a página
require '../includes/header.php'; // Traz o menu e topo do seu sistema

// Busca as campanhas já cadastradas para mostrar na tabela
try {
    $sql = "SELECT c.nome_campanha, cn.nivel, cn.vencimento_nf, cn.vencimento_royalties 
            FROM campanhas c 
            JOIN campanhas_niveis cn ON c.id = cn.id_campanha 
            ORDER BY c.id DESC, cn.vencimento_nf ASC";
    $stmt = $db_financeiro->query($sql);
    $campanhas_salvas = $stmt->fetchAll();
} catch (Exception $e) {
    $campanhas_salvas = [];
}
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/style.css">
<link rel="stylesheet" href="../static/css/financeiro.css">


<div class="financeiro-container">
    <h2>Cadastro de Campanhas e Royalties</h2>
    <p>Defina as datas para cruzamento automático. O sistema buscará preferencialmente a <b>2ª Parcela</b> do XML para definir o nível.</p>
    <br>

    <form id="formCampanha" onsubmit="salvarCampanha(event)">
        <input type="hidden" name="action" value="salvar">
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label>Nome da Campanha (Ex: MAES2026)</label>
            <input type="text" name="nome_campanha" required placeholder="Digite exatamente como vem na tag infCpl do XML">
        </div>

        <div id="container-niveis">
            <div class="linha-nivel">
                <div class="form-group">
                    <label>Nível</label>
                    <select name="nivel[]" required>
                        <option value="">Selecione...</option>
                        <option value="Ouro">Ouro</option>
                        <option value="Prata">Prata</option>
                        <option value="Bronze">Bronze</option>
                        <option value="Cobre">Cobre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Venc. Parcela Base (Geralmente a 2ª)</label>
                    <input type="date" name="venc_nf[]" required>
                </div>
                <div class="form-group">
                    <label>Vencimento Royalties</label>
                    <input type="date" name="venc_roy[]" required>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-secondary" onclick="adicionarLinha()" style="margin-top: 10px;">+ Adicionar Outro Nível</button>
        <hr style="margin: 20px 0; border: 1px solid #eee;">
        <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Campanha</button>
    </form>

    <h3>Campanhas Cadastradas</h3>
    <table>
        <thead>
            <tr>
                <th>Campanha</th>
                <th>Nível</th>
                <th>Data Parcela Base</th>
                <th>Data Royalty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campanhas_salvas as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['nome_campanha']) ?></td>
                <td><?= htmlspecialchars($c['nivel']) ?></td>
                <td><?= date('d/m/Y', strtotime($c['vencimento_nf'])) ?></td>
                <td><?= date('d/m/Y', strtotime($c['vencimento_royalties'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function adicionarLinha() {
        const container = document.getElementById('container-niveis');
        const novaLinha = document.createElement('div');
        novaLinha.className = 'linha-nivel';
        novaLinha.innerHTML = `
            <div class="form-group">
                <select name="nivel[]" required>
                    <option value="">Selecione...</option>
                    <option value="Ouro">Ouro</option>
                    <option value="Prata">Prata</option>
                    <option value="Bronze">Bronze</option>
                    <option value="Cobre">Cobre</option>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="venc_nf[]" required>
            </div>
            <div class="form-group">
                <input type="date" name="venc_roy[]" required>
            </div>
            <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">X</button>
        `;
        container.appendChild(novaLinha);
    }

    function salvarCampanha(event) {
        event.preventDefault();
        const formData = new FormData(document.getElementById('formCampanha'));

        fetch('admin_campanhas_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                location.reload(); 
            } else {
                alert(data.message);
            }
        })
        .catch(error => alert('Erro na comunicação com o servidor.'));
    }
</script>

<?php require '../includes/footer.php'; ?>