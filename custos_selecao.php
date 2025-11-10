<?php 
    $page_title = "Caixa de Ferramentas";
    
    // CSS específico para esta página
    $additional_head_tags = '
        <link rel="stylesheet" href="static/Custos/css/global.css">
        <link rel="stylesheet" href="static/Custos/css/selecao.css">
    ';
    
    require 'config.php';       // 1. Inclui a configuração e sessão
    require 'auth_check.php'; // 2. Protege a página
    require 'includes/header.php';  // 3. Inclui o cabeçalho HTML
?>

<hr>
        <h2>Custos Produtos</h2>
        <hr>
        <section class="custos">
            <div class="campanhas">
                <div class="campanha-2 campanha">
                    <a href="custos_linha.php">
                        <img src="static/Custos/src/img/lojas-cacau.jpeg" alt="Loja Cacau Show">
                        <p>Produtos de Linha</p>
                    </a>
                </div>
                <div class="campanha-3 campanha">
                    <a href="custos_natal_2025.php">
                        <img src="static/Custos/src/img/natalCacauShow.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2025</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_bendito2025.php">
                        <img src="static/Custos/src/img/benditoCacao.jpg" alt="Banner Bendito Cacao">
                        <p>Bendito Cacao</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_criancas.php">
                        <img src="static/Custos/src/img/criancada.png" alt="Banner Campanha de Crianças">
                        <p>Crianças, Halloween e Professores</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_maes.php">
                        <img src="static/Custos/src/img/mamaes.png" alt="Banner Campanha de Mães 2025">
                        <p>Mães e Namorados</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="custos_pascoa.php">
                        <img src="static/Custos/src/img/pascoa.webp" alt="Banner Campanha de Páscoa">
                        <p>Páscoa 2025</p>
                    </a>
                </div>
                <div class="campanha-2 campanha">
                    <a href="custos_canecas.php">
                        <img src="static/Custos/src/img/canecas.jpg" alt="Banner Canecas">
                        <p>Canecas Oxford</p>
                    </a>
                </div>
                <div class="campanha-1 campanha">
                    <a href="custos_natal.php">
                        <img src="static/Custos/src/img/magia-do-cacau.jpg" alt="Banner Campanha de Natal">
                        <p>Natal 2024</p>
                    </a>
                </div>

            </div>
            </div>
        </section>
        <hr>
        <h2>Ferramentas</h2>
        <hr>
        <section class="ferramentas">
            <div class="controleEntradas campanhas">
                <div class="campanha-2 campanha">
                    <a href="login.php">
                        <img src="static/Custos/src/img/caminhoes.jfif" alt="Loja Cacau Show">
                        <p>Entregas de Natal 2025</p>
                    </a>
                </div>
        </section>
        
        <script src="static/Custos/js/campanha.js"></script>

<?php 
    require 'includes/footer.php'; // 4. Inclui o rodapé
?>