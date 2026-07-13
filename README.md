# 🎮 Asgard Store

Marketplace completo para compra e venda de contas de jogos e creditos digitais.

## 📸 Visao Geral

Plataforma fullstack com painel administrativo, sistema de usuarios, chat por compra, suporte por tickets, API publica, integracao Telegram e design cyberpunk/neon.

## ✨ Funcionalidades

### Loja
- 🛒 Listagem de anuncios com prioridade (Admin > Destaque > Regular)
- 🔍 Busca e filtros por jogo, preco e ordenacao
- 💎 Badges de vendedor oficial (diamante admin/criador)
- ⭐ Sistema de destaques premium (7/14/30 dias)
- ❤️ Sistema de favoritos

### Compras
- 💰 Pagamento via PIX e Criptomoeda
- 🔒 Garantia de 24 horas
- ⚖️ Sistema de disputas com intermediacao
- 💬 Chat por compra entre comprador e vendedor
- ✅ Confirmacao de recebimento

### Creditos
- 💎 Loja de creditos por jogo (Diamantes, CP, Robux, UC, etc.)
- 🎫 Entrega automatica de codigos
- 📊 Historico de compras

### Painel do Usuario
- 📊 Dashboard com estatisticas e atalhos
- 📢 Gerenciamento de anuncios (criar, editar, pausar)
- 📦 Historico de compras e vendas
- 💵 Saldo e solicitacao de saque
- 🔔 Notificacoes in-app
- ❤️ Lista de favoritos
- 🔑 Alteracao de senha com medidor de forca

### Painel Admin
- 📊 Dashboard com graficos (Chart.js)
- 👥 Gerenciamento de usuarios (admin, suspender, banir)
- 📢 Aprovar/reprovar anuncios
- 💰 Processar saques (PIX/Crypto)
- 🎮 CRUD jogos, categorias e creditos
- ⚖️ Resolver disputas
- ⚙️ Configuracoes gerais (comissao, garantia, manutencao)
- ⭐ Gerenciar destaques premium
- 📝 Log de auditoria completo

### Suporte
- 🎫 Sistema de tickets com prioridade
- 💬 Respostas com notificacoes
- 📊 Status: aberto, em andamento, resolvido

### Integracoes
- 🤖 Bot Telegram para notificacoes
- 🔗 Vinculacao de conta Telegram
- 🌐 Webhook de pagamento
- 📡 API publica REST

## 📁 Estrutura do Projeto

```
├── admin/                  # Painel administrativo
│   ├── api/               # APIs AJAX admin
│   ├── index.php          # Dashboard admin
│   ├── usuarios.php       # Gerenciar usuarios
│   ├── anuncios.php       # Gerenciar anuncios
│   ├── jogos.php          # CRUD jogos
│   ├── categorias.php     # CRUD categorias
│   ├── creditos.php       # CRUD creditos
│   ├── saques.php         # Processar saques
│   ├── compras.php        # Gerenciar compras
│   ├── disputas.php       # Resolver disputas
│   ├── config.php         # Configuracoes gerais
│   ├── destaques.php      # Destaques premium
│   └── redes_sociais.php  # Redes sociais
├── painel/                 # Painel do usuario
│   ├── api/               # APIs AJAX usuario
│   ├── index.php          # Dashboard usuario
│   ├── perfil.php         # Meu perfil
│   ├── anuncios.php       # Meus anuncios
│   ├── anuncio-novo.php   # Criar anuncio
│   ├── anuncio-editar.php # Editar anuncio
│   ├── compras.php        # Minhas compras
│   ├── vendas.php         # Minhas vendas
│   ├── saldo.php          # Saldo e saques
│   ├── destacar.php       # Destaque premium
│   ├── notificacoes.php   # Notificacoes
│   ├── favoritos.php      # Favoritos
│   ├── alterar-senha.php  # Alterar senha
│   └── mensagens.php      # Chat por compra
├── loja/                   # Loja publica
│   ├── index.php          # Listagem de anuncios
│   └── anuncio.php        # Detalhe do anuncio
├── suporte/                # Sistema de suporte
│   ├── index.php          # Abrir ticket
│   ├── ticket.php         # Ver/responder ticket
│   └── listar.php         # Listar tickets
├── pages/                  # Paginas estaticas
│   ├── faq.php            # Perguntas frequentes
│   ├── termos.php         # Termos de uso
│   ├── privacidade.php    # Politica de privacidade
│   ├── como-vender.php    # Guia como vender
│   └── contato.php        # Contato
├── creditos/               # Loja de creditos
│   ├── index.php          # Lista de pacotes
│   └── comprar.php        # Fluxo de compra
├── auth/                   # Autenticacao
│   ├── api/               # APIs de auth
│   ├── login.php          # Login
│   ├── cadastro.php       # Cadastro
│   ├── logout.php         # Logout
│   └── esqueci-senha.php  # Recuperacao
├── api/                    # APIs externas
│   ├── public/            # API publica REST
│   ├── webhooks/          # Webhooks pagamento
│   └── telegram/          # Bot Telegram
├── includes/               # Includes compartilhados
│   ├── functions.php      # Funcoes helper
│   ├── header.php         # Header/navbar
│   └── footer.php         # Footer
├── assets/                 # Assets estaticos
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript
│   └── img/               # Imagens
├── sql/
│   └── schema.sql         # Schema do banco
├── config.php             # Configuracoes
├── db.php                 # Abstracao banco (PDO)
└── index.php              # Landing page
```

## 🗃️ Banco de Dados (20 tabelas)

| Tabela | Descricao |
|--------|-----------|
| `usuarios` | Usuarios (compradores/vendedores/admin) |
| `jogos` | Jogos cadastrados |
| `categorias` | Categorias por jogo |
| `anuncios` | Anuncios de contas |
| `compras` | Transacoes de compra |
| `dados_conta` | Dados entregues apos compra |
| `creditos` | Pacotes de creditos |
| `compra_creditos` | Compras de creditos |
| `saques` | Solicitacoes de saque |
| `mensagens` | Chat por compra |
| `notificacoes` | Notificacoes in-app |
| `telegram_users` | Vinculacao Telegram |
| `favoritos` | Anuncios favoritos |
| `disputas` | Disputas entre usuarios |
| `suporte_tickets` | Tickets de suporte |
| `suporte_respostas` | Respostas dos tickets |
| `admin_log` | Log de auditoria |
| `configuracoes` | Configuracoes gerais |
| `destaques_premium` | Destaques pagos |
| `redes_sociais` | Redes sociais da plataforma |

## 🛠️ Stack Tecnologica

- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Backend:** PHP 7.4+ (sem framework)
- **Banco:** MySQL 8.0 / MariaDB
- **Design:** Cyberpunk/Neon (CSS variables, Font Awesome)
- **Graficos:** Chart.js (dashboard admin)
- **Auth:** Sessoes PHP com CSRF token
- **Seguranca:** bcrypt, prepared statements, sanitizacao

## 📊 Estatisticas

- 📄 61 arquivos PHP
- 🎨 4 arquivos CSS
- ⚡ 2 arquivos JS
- 🗃️ 20 tabelas no banco
- 🔌 11 endpoints API

## 🚀 Instalacao

### Requisitos
- PHP 7.4 ou superior
- MySQL 8.0 / MariaDB 10.3+
- Apache/Nginx com mod_rewrite

### Passos

1. **Clonar o repositorio**
```bash
git clone https://github.com/VSFLima/asgard-store.git
cd asgard-store
```

2. **Importar o banco de dados**
```bash
mysql -u root -p asgard_store < sql/schema.sql
```

3. **Configurar o banco**
Edite `config.php` com suas credenciais:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'asgard_store');
define('DB_USER', 'root');
define('DB_PASS', 'sua_senha');
```

4. **Configurar o Apache**
Certifique-se que o `mod_rewrite` esta habilitado e o `.htaccess` esta funcionando.

5. **Acessar**
```
http://localhost/asgard-store/
```

### Credenciais Admin
Apos importar o schema, faca login com:
- **Email:** admin@asgard.store
- **Senha:** admin123

## 🔧 Configuracao

### Comissao
Altere `COMISSAO_PADRAO` em `config.php` (padrao: 10%)

### Minimo de Saque
Altere `MINIMO_SAQUE` em `config.php` (padrao: R$ 30,00)

### Garantia
Altere `GARANTIA_HORAS` em `config.php` (padrao: 24 horas)

### Telegram Bot
1. Crie um bot via @BotFather no Telegram
2. Coloque o token em `api/telegram/bot.php`
3. Configure o webhook com seu dominio

## 📝 Licenca

Projeto educacional. Nao utilize para fins comerciais sem autorizacao.
