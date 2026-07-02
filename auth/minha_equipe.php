<?php
// auth/minha_equipe.php
require '../config.php';
require 'auth_franqueado_check.php'; 

$page_title = "Minhas lojas"; 
$sessao_nome = "Lojas"; 

require '../includes/header.php';

$id_dono = $_SESSION['user_id'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // CRIAR COLABORADOR DA LOJA
    if ($_POST['action'] === 'criar_usuario') {
        $novo_username = trim($_POST['novo_username']);
        $nova_senha = $_POST['nova_senha'];
        $perfil = 'colaborador'; // O franqueado não pode criar outros franqueados!
        
        if (!empty($novo_username) && !empty($nova_senha)) {
            try {
                $stmt = $db_users->prepare("SELECT id FROM user WHERE username = ?");
                $stmt->execute([$novo_username]);
                if ($stmt->fetch()) {
                    $mensagem = "<div style='color: red; padding: 10px;'>❌ Este nome de utilizador já existe.</div>";
                } else {
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    // O pulo do gato: salva o ID do franqueado logado na coluna id_dono
                    $stmt = $db_users->prepare("INSERT INTO user (username, password_hash, perfil, id_dono) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$novo_username, $senha_hash, $perfil, $id_dono]);
                    $mensagem = "<div style='color: green; padding: 10px;'>✅ Colaborador cadastrado com sucesso!</div>";
                }
            } catch (Exception $e) {
                $mensagem = "Erro: " . $e->getMessage();
            }
        }
    }
}

// Busca APENAS os funcionários que pertencem a este franqueado
$stmt = $db_users->prepare("SELECT id, username FROM user WHERE id_dono = ? ORDER BY username ASC");
$stmt->execute([$id_dono]);
$equipe = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../static/css/global.css">
<link rel="stylesheet" href="../static/css/financeiro.css">

<!-- Container principal com fundo branco e sombra para destacar do fundo da página -->
<div class="financeiro-wrapper" style="max-width: 900px; margin: 40px auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
    
    <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
        <h1 style="margin: 0; color: #333; font-size: 26px;">Gestão da Minha Equipe</h1>
        <a href="area_franqueado.php" class="btn btn-secondary" style="background: #007bff; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">← VOLTAR</a>
    </div>

    <?= $mensagem ?>

    <p style="color: #555; font-size: 16px; margin-bottom: 25px;">Cadastre o acesso para os colaboradores da sua loja. Eles terão acesso APENAS ao Quadro de Gestão e Controle de Validades.</p>

    <!-- Bloco de Cadastro -->
    <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 35px;">
        <h3 style="margin-top: 0; color: #333; font-size: 18px;">➕ Cadastrar Nova Loja</h3>
        <form method="POST" action="" style="display: flex; gap: 15px; margin-top: 15px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="action" value="criar_usuario">
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444; font-size: 14px;">Nome de Acesso (Login)</label>
                <input type="text" name="novo_username" required placeholder="Ex: joao_balcao" style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444; font-size: 14px;">Senha</label>
                <input type="text" name="nova_senha" required placeholder="Ex: senha123" style="width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            
            <button type="submit" style="background: #28a745; color: #fff; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; height: 46px;">Salvar</button>
        </form>
    </div>

    <h3 style="color: #333; font-size: 20px; margin-bottom: 15px;">Equipe Atual</h3>
    
    <!-- Tabela Estilizada -->
    <div style="overflow-x: auto; border: 1px solid #dee2e6; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; background: #fff; font-size: 15px;">
            <thead>
                <tr style="background: #f1f3f5; border-bottom: 2px solid #dee2e6;">
                    <th style="padding: 15px; text-align: left; color: #495057; width: 60%;">Nome de Acesso</th>
                    <th style="padding: 15px; text-align: left; color: #495057; width: 40%;">Perfil</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($equipe) === 0): ?>
                    <tr>
                        <td colspan="2" style="padding: 20px; text-align: center; color: #6c757d;">Nenhum colaborador cadastrado ainda.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipe as $u): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px; font-weight: bold; color: #212529; font-size: 16px;">
                                <?= htmlspecialchars($u['username']) ?>
                            </td>
                            <td style="padding: 15px;">
                                <span style="background: #e2e3e5; color: #383d41; padding: 5px 10px; border-radius: 4px; font-size: 13px; font-weight: 500;">
                                    Colaborador
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../includes/footer.php'; ?>