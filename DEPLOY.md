# 🚀 Guia de Deploy - Asgard Store

## Requisitos do Servidor
- Linux (Ubuntu 22.04+ recomendado)
- PHP 8.0+ com extensoes: pdo_mysql, mbstring, json, gd
- MySQL 8.0+ / MariaDB 10.3+
- Nginx ou Apache com mod_rewrite
- Certificado SSL (Let's Encrypt)

## Passos de Deploy

### 1. Preparar o servidor
```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP
sudo apt install php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-gd php8.1-curl -y

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar Nginx
sudo apt install nginx -y

# Instalar Certbot (SSL)
sudo apt install certbot python3-certbot-nginx -y
```

### 2. Configurar o banco
```bash
# Criar banco e usuario
mysql -u root -p << 'EOF'
CREATE DATABASE asgard_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'asgard_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON asgard_store.* TO 'asgard_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Importar schema
cd /var/www/asgard-store
mysql -u root -p asgard_store < sql/schema.sql
```

### 3. Configurar o projeto
```bash
# Copiar .env
cp .env.example .env
# Editar com valores reais
nano .env

# Ajustar permissoes
chown -R www-data:www-data /var/www/asgard-store
chmod -R 755 /var/www/asgard-store
chmod -R 777 /var/www/asgard-store/assets/img/uploads
```

### 4. Configurar Nginx
```bash
# Copiar config
sudo cp nginx.conf /etc/nginx/sites-available/asgard-store
sudo ln -s /etc/nginx/sites-available/asgard-store /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### 5. SSL com Let's Encrypt
```bash
sudo certbot --nginx -d asgard.store -d www.asgard.store
```

### 6. Verificar
- Acessar https://asgard.store
- Login admin: admin@asgard.store / admin123
- Testar cadastro, anuncio, compra

## Manutencao
```bash
# Atualizar codigo
cd /var/www/asgard-store
git pull origin master
chown -R www-data:www-data .

# Backup do banco
mysqldump -u asgard_user -p asgard_store > backup_$(date +%Y%m%d).sql
```
