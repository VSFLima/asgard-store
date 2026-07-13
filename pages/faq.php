<?php
/**
 * Asgard Store - Perguntas Frequentes (FAQ)
 */

$page_title = 'Perguntas Frequentes';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 10px;">
        <i class="fas fa-question-circle" style="color: var(--neon-green);"></i> Perguntas Frequentes
    </h1>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 40px;">
        Encontre respostas para as duvidas mais comuns sobre a Asgard Store.
    </p>

    <!-- Compras -->
    <h2 style="color: var(--neon-green); margin-bottom: 15px; font-size: 1.2rem;">
        <i class="fas fa-shopping-cart"></i> Compras
    </h2>

    <div class="card" style="margin-bottom: 15px;">
        <details style="cursor: pointer;">
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                Como funciona a compra?
                <i class="fas fa-chevron-down" style="color: var(--neon-green); transition: transform 0.3s;"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                1. Escolha o anuncio que deseja na loja<br>
                2. Clique em "Comprar" e escolha o metodo de pagamento (PIX ou Crypto)<br>
                3. Envie o comprovante e aguarde confirmacao do vendedor<br>
                4. Apos confirmacao, o vendedor entrega os dados da conta<br>
                5. Voce confirma o recebimento e o vendedor recebe o pagamento
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Qual a garantia de compra?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Voce tem <?php echo GARANTIA_HORAS; ?> horas de garantia apos a entrega da conta. 
                Caso haja qualquer problema, voce pode abrir uma disputa e nossa equipe intermediara.
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Posso pedir reembolso?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Sim, caso a conta nao corresponda ao descrito no anuncio ou apresente problemas, 
                voce pode abrir uma disputa. Nossa equipe avaliara e, se procedente, o valor sera estornado.
            </div>
        </details>
    </div>

    <!-- Vendas -->
    <h2 style="color: var(--neon-green); margin-bottom: 15px; margin-top: 30px; font-size: 1.2rem;">
        <i class="fas fa-hand-holding-usd"></i> Vendas
    </h2>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Como vender na Asgard Store?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                1. Cadastre-se ou faca login<br>
                2. Va em "Painel" > "Criar Anuncio"<br>
                3. Preencha os dados do jogo, nivel, itens e preco<br>
                4. Adicione screenshots para aumentar a credibilidade<br>
                5. Aguarde a aprovacao do admin (geralmente em menos de 24h)<br>
                6. Quando alguem comprar, voce recebera uma notificacao
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Qual a comissao por venda?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                A comissao padrao e de <?php echo COMISSAO_PADRAO; ?>% sobre o valor da venda. 
                O restante e creditado na sua conta e pode ser sacado via PIX ou Crypto.
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Como sacar meus ganhos?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Va em "Painel" > "Meu Saldo" e solicite um saque. 
                O minimo para saque e R$ <?php echo number_format(MINIMO_SAQUE, 2, ',', '.'); ?>. 
                O pagamento e processado em ate 48 horas uteis.
            </div>
        </details>
    </div>

    <!-- Pagamentos -->
    <h2 style="color: var(--neon-green); margin-bottom: 15px; margin-top: 30px; font-size: 1.2rem;">
        <i class="fas fa-credit-card"></i> Pagamentos
    </h2>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Quais metodos de pagamento sao aceitos?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Aceitamos PIX (transferencia instantanea) e criptomoedas. 
                O vendedor escolhe quais metodos aceita em seu anuncio.
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                O pagamento e seguro?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Sim! O pagamento fica retido na plataforma ate voce confirmar o recebimento da conta. 
                Caso haja problemas, voce pode abrir uma disputa e nosso time mediara a situacao.
            </div>
        </details>
    </div>

    <!-- Conta -->
    <h2 style="color: var(--neon-green); margin-bottom: 15px; margin-top: 30px; font-size: 1.2rem;">
        <i class="fas fa-user"></i> Conta
    </h2>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Esqueci minha senha, o que faco?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Clique em "Esqueci minha senha" na pagina de login. 
                Um email de recuperacao sera enviado para o endereco cadastrado.
            </div>
        </details>
    </div>

    <div class="card" style="margin-bottom: 15px;">
        <details>
            <summary style="padding: 15px; color: var(--text); font-weight: 600; list-style: none; display: flex; justify-content: space-between;">
                Como alterar meus dados?
                <i class="fas fa-chevron-down" style="color: var(--neon-green);"></i>
            </summary>
            <div style="padding: 0 15px 15px 15px; color: var(--text-muted); line-height: 1.6;">
                Va em "Painel" > "Meu Perfil" para atualizar seus dados pessoais, 
                PIX, redes sociais e outras informacoes.
            </div>
        </details>
    </div>

    <!-- Contato -->
    <div style="text-align: center; margin-top: 40px; padding: 30px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="color: var(--text); margin-bottom: 10px;">Ainda tem duvidas?</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Nossa equipe esta pronta para ajudar.</p>
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="/suporte/" class="btn btn-primary"><i class="fas fa-headset"></i> Abrir Ticket</a>
            <a href="/pages/contato.php" class="btn btn-secondary"><i class="fas fa-envelope"></i> Contato</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>