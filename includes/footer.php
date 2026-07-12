    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <span class="logo-icon"><i class="fas fa-gamepad"></i></span>
                        <span class="logo-text">Asgard<span class="neon-green"> Store</span></span>
                    </div>
                    <p class="footer-desc">O melhor marketplace para compra e venda de contas e creditos de jogos.</p>
                    <div class="social-links">
                        <?php
                        $redes = db_fetch_all("SELECT * FROM redes_sociais WHERE ativo = 1 ORDER BY ordem ASC");
                        foreach ($redes as $rede): ?>
                        <a href="<?php echo sanitize($rede['url']); ?>" target="_blank" 
                           data-tooltip="<?php echo sanitize($rede['nome']); ?>"
                           style="<?php echo $rede['cor'] ? '--hover-color: ' . $rede['cor'] : ''; ?>">
                            <i class="<?php echo sanitize($rede['icone']); ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Links Rapidos</h4>
                    <ul>
                        <li><a href="/loja/">Loja de Contas</a></li>
                        <li><a href="/creditos/">Loja de Creditos</a></li>
                        <li><a href="/pages/como-vender/">Como Vender</a></li>
                        <li><a href="/pages/faq/">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Jogos Populares</h4>
                    <ul>
                        <li><a href="/loja/?jogo=free-fire">Free Fire</a></li>
                        <li><a href="/loja/?jogo=cod-mobile">COD Mobile</a></li>
                        <li><a href="/loja/?jogo=roblox">Roblox</a></li>
                        <li><a href="/loja/?jogo=pubg-mobile">PUBG Mobile</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Suporte</h4>
                    <ul>
                        <li><a href="/suporte/"><i class="fas fa-headset"></i> Central de Ajuda</a></li>
                        <li><a href="/pages/termos/">Termos de Uso</a></li>
                        <li><a href="/pages/privacidade/">Politica de Privacidade</a></li>
                        <li><a href="/pages/contato/">Contato</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo defined('SITE_NAME') ? SITE_NAME : 'Asgard Store'; ?>. Todos os direitos reservados.</p>
                <div class="payment-methods">
                    <span><i class="fas fa-qrcode"></i> PIX</span>
                    <span><i class="fab fa-bitcoin"></i> Crypto</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
    <?php if (isset($extra_scripts)): ?>
        <?php echo $extra_scripts; ?>
    <?php endif; ?>
</body>
</html>
