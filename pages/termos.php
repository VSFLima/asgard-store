<?php
/**
 * Asgard Store - Termos de Uso
 */

$page_title = 'Termos de Uso';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 30px;">
        <i class="fas fa-file-contract" style="color: var(--neon-green);"></i> Termos de Uso
    </h1>

    <div class="card" style="padding: 30px; line-height: 1.8; color: var(--text-muted);">
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
            <strong>Ultima atualizacao:</strong> Julho de 2026
        </p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">1. Aceitacao dos Termos</h2>
        <p>Ao acessar e utilizar a Asgard Store, voce concorda com estes Termos de Uso. 
        Caso nao concorde, nao utilize a plataforma.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">2. Descricao do Servico</h2>
        <p>A Asgard Store e uma plataforma de compra e venda de contas e itens de jogos digitais. 
        A plataforma atua como intermediaria entre compradores e vendedores.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">3. Cadastro e Conta</h2>
        <p>Para utilizar a plataforma, voce deve:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Ser maior de 18 anos ou ter autorizacao de responsavel legal</li>
            <li>Fornecer dados verdadeiros e atualizados</li>
            <li>Manter a seguranca de sua conta e senha</li>
            <li>Nao compartilhar credenciais de acesso</li>
            <li>Notificar imediatamente qualquer uso nao autorizado</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">4. Obrigações do Vendedor</h2>
        <p>O vendedor se compromete a:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Fornecer informacoes verdadeiras sobre os produtos</li>
            <li>Entregar os dados da conta apos a confirmacao do pagamento</li>
            <li>Fornecer suporte durante o periodo de garantia</li>
            <li>Nao vender contas obtidas ilegalmente ou roubadas</li>
            <li>Cumprir as politicas da plataforma</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">5. Obrigacoes do Comprador</h2>
        <p>O comprador se compromete a:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Verificar os dados da conta antes de confirmar o recebimento</li>
            <li>Nao alterar dados da conta antes da confirmacao final</li>
            <li>Comunicar qualquer problema dentro do prazo de garantia</li>
            <li>Nao abrir disputas sem motivo justificado</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">6. Garantia e Disputas</h2>
        <p>A garantia padrao e de <?php echo GARANTIA_HORAS; ?> horas apos a entrega. 
        Disputas abertas fora deste periodo serao analisadas caso a caso. 
        A decisao final e da equipe de moderacao da Asgard Store.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">7. Comissao e Pagamentos</h2>
        <p>A plataforma cobra uma comissao de <?php echo COMISSAO_PADRAO; ?>% sobre cada venda concluida. 
        Saques sao processados em ate 48 horas uteis, com valor minimo de R$ <?php echo number_format(MINIMO_SAQUE, 2, ',', '.'); ?>.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">8. Proibicoes</h2>
        <p>Estrictamente proibido:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Vender contas roubadas, hackeadas ou obtidas ilegalmente</li>
            <li>Praticar golpes ou tentar enganar outros usuarios</li>
            <li>Usar a plataforma para lavagem de dinheiro</li>
            <li>Fazer spam ou publicidade nao autorizada</li>
            <li>Tentar manipular o sistema de disputas</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">9. Suspensao e Banimento</h2>
        <p>A Asgard Store reserva-se o direito de suspender ou banir contas que violem estes termos, 
        sem aviso previo e a seu unico criterio.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">10. Limitacao de Responsabilidade</h2>
        <p>A Asgard Store nao se responsabiliza por:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Perdas decorrentes de contas vendidas entre usuarios</li>
            <li>Bloqueio de contas por parte dos desenvolvedores de jogos</li>
            <li>Problemas tecnicos temporarios na plataforma</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">11. Alteracoes nos Termos</h2>
        <p>Estes termos podem ser alterados a qualquer momento. 
        O uso continuado da plataforma apos alteracoes constitui aceitacao das novas condicoes.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">12. Contato</h2>
        <p>Em caso de duvidas sobre estes termos, entre em contato atraves do nosso 
        <a href="/suporte/" style="color: var(--neon-green);">sistema de suporte</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>