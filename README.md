# Analytics Pro - Call Center Dashboard & Realtime Monitor

**Analytics Pro** é uma solução completa de monitoramento e relatórios para Call Centers baseados em **Asterisk** (Issabel/FreePBX). O sistema oferece uma interface web moderna (SPA) para análise histórica e um painel em tempo real (Wallboard) para gestão da operação.

## 🚀 Funcionalidades

### 📊 Dashboard Executivo
- **KPIs em Tempo Real:** Nível de Serviço (SLA < 60s), Taxa de Abandono Crítico, Chamadas Longas.
- **Ranking de Performance:** Classificação automática dos melhores agentes baseada em volume de atendimentos.
- **Campeão do Dia:** Destaque visual para o agente com maior produtividade.
- **Cálculo Server-Side:** Processamento otimizado no PHP para não travar o banco de dados em grandes volumes.

### 📞 Relatórios Detalhados
- **Chamadas:** Listagem completa com filtros por data, status e agente.
- **Player Integrado:** Ouça as gravações das chamadas diretamente no navegador (sem download).
- **Pausas:** Monitoramento de pausas (Almoço, Banheiro, etc) com duração exata.
- **Sessões:** Controle de jornada de trabalho (Login/Logout) dos agentes.
- **Exportação CSV:** Todos os relatórios podem ser exportados para Excel/CSV com um clique.

### ⚡ Monitor Realtime (Wallboard)
- **Visão Ao Vivo:** Status das filas, agentes online/pausados e chamadas em espera.
- **CallerID na Tela:** Mostra quem está ligando e por qual tronco a chamada entrou antes do atendimento.
- **Zero-Database:** Conecta diretamente no AMI (Asterisk Manager Interface) via Socket para velocidade máxima sem onerar o MySQL.

---

## 🛠️ Requisitos

- **Servidor:** Linux com Asterisk 11+ (Testado no Issabel 4/5).
- **Web Server:** Apache ou Nginx com PHP 5.6 ou superior.
- **PHP Extensions:** `pdo_mysql`, `sockets`.
- **Banco de Dados:** Acesso de leitura à tabela `queue_log` do Asterisk.

---

## 📦 Instalação

1. **Copie os arquivos** para a pasta web do servidor (ex: `/var/www/html/relatorios/`).

2. **Estrutura de Pastas:**
   ```text
   relatorios/
   ├── api.php             # Backend API (Histórico)
   ├── app.php             # Frontend Principal (SPA)
   ├── config.php          # Configurações Gerais
   ├── player.php          # Streamer de Áudio
   ├── realtime/           # Módulo de Tempo Real
   │   ├── backend.php     # Conector AMI
   │   └── painel.php      # Wallboard Visual
   └── templates/          # (Opcional: Templates HTML se não estiver usando versão monolítica)
````

3.  **Permissões:**
    Garanta que o usuário do Apache (geralmente `asterisk` ou `www-data`) tenha permissão de leitura nas gravações.
    ```bash
    chown -R asterisk:asterisk /var/www/html/relatorios
    chmod -R 755 /var/www/html/relatorios
    ```

-----

## ⚙️ Configuração

### 1\. Configurar o Asterisk (AMI)

Edite o arquivo `/etc/asterisk/manager.conf` e adicione um usuário para o painel:

```ini
[php_dashboard]
secret = senha_segura_ami
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
read = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate
writetimeout = 5000
```

Recarregue o manager: `asterisk -rx "manager reload"`

### 2\. Configurar o Sistema (`config.php`)

Edite o arquivo `config.php` na raiz do projeto com suas credenciais:

```php
// Banco de Dados (MySQL/MariaDB)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'sua_senha_mysql');

// Mapeamento de Agentes (ID Técnico -> Nome Real)
$map_agentes = [
    '1050' => 'João Silva',
    '9000' => 'Suporte N1',
    // ...
];

// Mapeamento de Filas
$map_filas = [
    '9000' => 'Suporte Técnico',
    '9010' => 'Vendas'
];
```

### 3\. Configurar o Realtime (`realtime/backend.php`)

Se as credenciais do AMI forem diferentes, edite este arquivo. Caso contrário, ele herdará ou usará as definições padrão.

-----

## 🖥️ Como Usar

1.  **Acesse o Painel Principal:**
    `http://seu-ip/relatorios/app.php`

2.  **Navegação:**

      - Use o menu lateral para alternar entre **Dashboard**, **Chamadas**, **Pausas** e **Sessões**.
      - Use o filtro de datas no topo para buscar períodos específicos.

3.  **Monitoramento em Tempo Real:**

      - Clique em **"Monitor"** no menu lateral para abrir o Wallboard em tela cheia. Ideal para TVs de supervisão.

4.  **Ouvir Gravações:**

      - Na aba "Chamadas", clique no ícone de **Play** (botão azul) nas chamadas atendidas. O áudio será reproduzido instantaneamente.

-----

## 🐛 Troubleshooting (Resolução de Problemas)

  - **Dashboard não carrega ("Carregando..." infinito):**

      - Verifique a conexão com o banco no `config.php`.
      - Certifique-se de que o arquivo `api.php` não tem espaços em branco antes da tag `<?php`.

  - **Monitor Realtime vazio ou desconectado:**

      - Verifique se o usuário AMI foi criado corretamente no `manager.conf`.
      - Teste a conexão: `telnet 127.0.0.1 5038`.

  - **Erro "File not found" ao ouvir gravação:**

      - O script `player.php` busca gravações em `/var/spool/asterisk/monitor/`. Verifique se seus arquivos estão lá e se o formato da pasta é `ANO/MES/DIA`.

-----

**Desenvolvido para Alta Performance em Ambientes Asterisk.**

```
```
