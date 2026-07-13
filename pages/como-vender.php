<?php
/**
 * Asgard Store - Como Vender
 */

$page_title = 'Como Vender';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 10px;">
        <i class="fas fa-hand-holding-usd" style="color: var(--neon-green);"></i> Como Vender
    </h1>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 40px;">
        Siga estes passos para comecar a vender na Asgard Store e ganhar dinheiro com seus jogos.
    </p>

    <!-- Passo 1 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-green);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-green); color: #000; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">1</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Crie sua Conta</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Cadastre-se gratuitamente na Asgard Store. Preencha seus dados pessoais, 
                    incluindo nome, email e telefone. Confirme seu email para ativar a conta.
                </p>
                <a href="/auth/cadastro.php" class="btn btn-primary" style="margin-top: 10px;">
                    <i class="fas fa-user-plus"></i> Cadastrar Agora
                </a>
            </div>
        </div>
    </div>

    <!-- Passo 2 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-blue);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-blue); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">2</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Configure seu Perfil</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Va em <strong>Painel > Meu Perfil</strong> e configure:
                </p>
                <ul style="color: var(--text-muted); padding-left: 20px; margin: 10px 0;">
                    <li><strong>Chave PIX:</strong> Para receber seus pagamentos</li>
                    <li><strong>Redes Sociais:</strong> Telegram, WhatsApp, etc.</li>
                    <li><strong>Avatar:</strong> Para inspirar confianca nos compradores</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Passo 3 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-purple);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-purple); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">3</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Crie seu Anuncio</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Va em <strong>Painel > Criar Anuncio</strong> e preencha:
                </p>
                <ul style="color: var(--text-muted); padding-left: 20px; margin: 10px 0;">
                    <li><strong>Jogo:</strong> Selecione o jogo da conta</li>
                    <li><strong>Titulo:</strong> Chame a atencao (ex: "Conta Nivel 100 - Todos Desbloqueados")</li>
                    <li><strong>Descricao:</strong> Detalhe tudo que a conta tem</li>
                    <li><strong>Screenshots:</strong> Adicione ate 5 fotos da conta</li>
                    <li><strong>Preco:</strong> Defina o valor em reais</li>
                </ul>
                <div style="background: rgba(0,123,255,0.1); padding: 12px; border-radius: 8px; margin-top: 10px;">
                    <p style="color: var(--neon-blue); margin: 0; font-size: 0.9rem;">
                        <i class="fas fa-lightbulb"></i> <strong>Dica:</strong> Anuncios com boas screenshots e descricao detalhada vendem mais!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Passo 4 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-yellow);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-yellow); color: #000; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">4</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Aguarde Aprovacao</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Seu anuncio sera revisado pela equipe. Geralmente a aprovacao acontece em 
                    <strong>menos de 24 horas</strong>. Voce recebera uma notificacao quando for aprovado.
                </p>
            </div>
        </div>
    </div>

    <!-- Passo 5 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-green);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-green); color: #000; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">5</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Venda e Receba!</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Quando alguem comprar sua conta:
                </p>
                <ul style="color: var(--text-muted); padding-left: 20px; margin: 10px 0;">
                    <li>Voce recebe uma notificacao</li>
                    <li>Envie os dados da conta pelo chat da compra</li>
                    <li>O comprador confirma o recebimento</li>
                    <li>O valor (menos <?php echo COMISSAO_PADRAO; ?>% de comissao) e creditado na sua conta</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Passo 6 -->
    <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-pink);">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="background: var(--neon-pink); color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">6</div>
            <div>
                <h3 style="color: var(--text); margin-bottom: 8px;">Saque seus Ganhos</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Va em <strong>Painel > Meu Saldo</strong> e solicite um saque. 
                    Minimo: R$ <?php echo number_format(MINIMO_SAQUE, 2, ',', '.'); ?>. 
                    Pagamento via PIX ou Crypto em ate 48 horas.
                </p>
                <a href="/painel/saldo.php" class="btn btn-primary" style="margin-top: 10px;">
                    <i class="fas fa-wallet"></i> Ver Meu Saldo
                </a>
            </div>
        </div>
    </div>

    <!-- Dicas -->
    <div class="card" style="margin-top: 30px; padding: 25px;">
        <h3 style="color: var(--neon-green); margin-bottom: 15px;">
            <i class="fas fa-star"></i> Dicas para Vender Mais
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-camera" style="color: var(--neon-green); margin-top: 3px;"></i>
                <div>
                    <strong style="color: var(--text);">Screenshots de Qualidade</strong>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 3px 0 0 0;">Mostre nivel, itens, inventario e conquistas.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-pen" style="color: var(--neon-green); margin-top: 3px;"></i>
                <div>
                    <strong style="color: var(--text);">Descricao Detalhada</strong>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 3px 0 0 0;">Quanto mais informacoes, mais confianca gera.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-tag" style="color: var(--neon-green); margin-top: 3px;"></i>
                <div>
                    <strong style="color: var(--text);">Preco Competitivo</strong>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 3px 0 0 0;">Pesquise anuncios similares para precificar.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <i class="fas fa-bolt" style="color: var(--neon-green); margin-top: 3px;"></i>
                <div>
                    <strong style="color: var(--text);">Responda Rapido</strong>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 3px 0 0 0;">Quem responde rapido, vende mais.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="/painel/anuncio-novo.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle"></i> Criar Meu Primeiro Anuncio
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>