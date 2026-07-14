#!/bin/bash
# ====================================
# Asgard Store - Deploy para InfinityFree
# ====================================
# Execute: chmod +x deploy.sh && ./deploy.sh

# Credenciais InfinityFree
FTP_HOST="ftpupload.net"
FTP_USER="if0_42401203"
FTP_PASS="70PU5Trtox6"
FTP_DIR="/htdocs"

# Verificar se lftp esta instalado
if ! command -v lftp &> /dev/null; then
    echo "Instalando lftp..."
    sudo apt-get update && sudo apt-get install -y lftp
fi

# Criar config.local.php temporario para deploy
if [ ! -f config.local.php ]; then
    echo "Criando config.local.php..."
    cat > config.local.php << 'CONFIGEOF'
<?php
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_NAME', 'if0_42401203_asgardstore');
define('DB_USER', 'if0_42401203');
define('DB_PASS', '70PU5Trtox6');
define('SITE_URL', 'https://Asgard-Store.gamer.free');
define('DEBUG_MODE', false);
CONFIGEOF
fi

# Fazer deploy via FTP
echo "Iniciando deploy para InfinityFree..."
echo "Host: $FTP_HOST"
echo "User: $FTP_USER"
echo ""

lftp -u $FTP_USER,$FTP_PASS $FTP_HOST << FTPEOF
set ssl:verify-certificate no
mirror --reverse --verbose --delete --exclude .git/ --exclude decompiled*/ --exclude backups/ --exclude builds/ --exclude scripts/ --exclude tools/ --exclude logs/ --exclude *.md --exclude *.zip --exclude DOC_* --exclude REVERSE_* --exclude NAVES_* --exclude WIKI_* --exclude CHANGELOG* --exclude net.fishlabs* --exclude GOF2* --exclude Trapcioo* . $FTP_DIR
bye
FTPEOF

echo ""
echo "Deploy concluido!"
echo "Acesse: https://Asgard-Store.gamer.free"
