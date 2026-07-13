<?php
/**
 * Asgard Store - Politica de Privacidade
 */

$page_title = 'Politica de Privacidade';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 30px;">
        <i class="fas fa-shield-alt" style="color: var(--neon-green);"></i> Politica de Privacidade
    </h1>

    <div class="card" style="padding: 30px; line-height: 1.8; color: var(--text-muted);">
        <p style="margin-bottom: 20px;"><strong>Ultima atualizacao:</strong> Julho de 2026</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">1. Informacoes Coletadas</h2>
        <p>Coletamos as seguintes informacoes:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li><strong>Dados de cadastro:</strong> nome, sobrenome, email, telefone</li>
            <li><strong>Dados de pagamento:</strong> chave PIX, tipo de PIX</li>
            <li><strong>Dados de uso:</strong> historico de compras, vendas, visualizacoes</li>
            <li><strong>Dados de dispositivo:</strong> endereco IP, navegador, sistema operacional</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">2. Uso das Informacoes</h2>
        <p>Utilizamos seus dados para:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Processar transacoes e pagamentos</li>
            <li>Comunicar sobre compras, vendas e suporte</li>
            <li>Melhorar a experiencia na plataforma</li>
            <li>Prevenir fraudes e atividades ilegais</li>
            <li>Cumprir obrigacoes legais</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">3. Compartilhamento de Dados</h2>
        <p>Nao vendemos seus dados pessoais. Compartilhamos apenas com:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li><strong>Vendedores/Compradores:</strong> Dados necessarios para concluir a transacao</li>
            <li><strong>Processadores de pagamento:</strong> PIX ou Crypto para processar pagamentos</li>
            <li><strong>Autoridades legais:</strong> Quando exigido por lei</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">4. Seguranca dos Dados</h2>
        <p>Implementamos medidas de seguranca para proteger seus dados:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Senhas armazenadas com hash bcrypt</li>
            <li>Protecao contra ataques CSRF</li>
            <li>Validacao e sanitizacao de todos os inputs</li>
            <li>Conexao segura (HTTPS)</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">5. Cookies</h2>
        <p>Utilizamos cookies para:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Manter voce logado na plataforma</li>
            <li>Lembrar suas preferencias</li>
            <li>Analisar o uso da plataforma</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">6. Retencao de Dados</h2>
        <p>Mantemos seus dados pelo tempo necessario para fornecer nossos servicos 
        e cumprir obrigacoes legais. Contas inativas podem ser desativadas apos 12 meses sem acesso.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">7. Seus Direitos</h2>
        <p>Voce tem direito a:</p>
        <ul style="padding-left: 20px; margin: 10px 0;">
            <li>Acessar seus dados pessoais</li>
            <li>Corrigir dados incorretos</li>
            <li>Solicitar exclusao de sua conta</li>
            <li>Exportar seus dados</li>
            <li>Revogar consentimentos</li>
        </ul>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">8. Menores de Idade</h2>
        <p>A plataforma nao e direcionada a menores de 18 anos. 
        Nao coletamos intencionalmente dados de menores de idade.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">9. Alteracoes nesta Politica</h2>
        <p>Esta politica pode ser atualizada periodicamente. 
        Notificaremos sobre mudancas significativas por email ou na plataforma.</p>

        <h2 style="color: var(--text); font-size: 1.1rem; margin: 25px 0 10px;">10. Contato</h2>
        <p>Para exercer seus direitos ou esclarecer duvidas, entre em contato pelo 
        <a href="/suporte/" style="color: var(--neon-green);">sistema de suporte</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>