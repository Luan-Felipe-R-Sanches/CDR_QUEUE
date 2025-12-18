<?php
// Arquivo: index.php
require 'db.php';

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se é admin
if ($_SESSION['user_role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

// Variáveis para o JavaScript
$meuRamal = $_SESSION['user_name']; // Ex: 1001
$minhaTech = $_SESSION['user_tech']; // Ex: PJSIP
$meuNome = $_SESSION['user_realname'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VoxBlue Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- SEU CSS ORIGINAL (MANTIDO INTACTO) --- */
        :root {
            --bg-body: #f3f4f6; --bg-card: #ffffff; --text-main: #1f2937;
            --text-muted: #6b7280; --border-color: #e5e7eb; --nav-bg: #111827;
            --nav-text: #f9fafb; --input-bg: #ffffff; --input-border: #d1d5db;
        }
        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-card: #1e293b; --text-main: #ffffff;
            --text-muted: #cbd5e1; --border-color: #334155; --nav-bg: #020617;
            --nav-text: #ffffff; --input-bg: #334155; --input-border: #475569;
        }
        body { background-color: var(--bg-body); color: var(--text-main); font-family: 'Segoe UI', system-ui, sans-serif; transition: all 0.3s ease; }
        
        /* Correção para Modo Dark */
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, 
        [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
        [data-theme="dark"] .card, [data-theme="dark"] .form-label { color: #ffffff !important; }
        [data-theme="dark"] .text-muted { color: #cbd5e1 !important; }

        /* Navbar */
        .agent-statusbar { background-color: var(--nav-bg); color: var(--nav-text); padding: 15px 0; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-bottom: 1px solid var(--border-color); }

        /* Cards e Inputs */
        .control-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .form-select, .form-control { background-color: var(--input-bg); color: var(--text-main); border-color: var(--input-border); height: 48px; font-size: 1rem; }
        .form-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
        
        /* Botões */
        .btn-action { height: 48px; font-weight: 600; border-radius: 8px; text-transform: uppercase; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; transition: transform 0.1s; }
        .btn-action:active { transform: scale(0.98); }

        /* Queue Card */
        .queue-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; margin-bottom: 0; transition: transform 0.2s, box-shadow 0.2s; color: var(--text-main); }
        .queue-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-color: #3b82f6; }
        .queue-header { padding: 10px 15px; background-color: rgba(0,0,0,0.03); border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; }
        [data-theme="dark"] .queue-header { background-color: rgba(255,255,255,0.05); color: #fff !important; }

        /* Chips */
        .agent-list-container { display: flex; flex-wrap: wrap; gap: 6px; padding-top: 5px; }
        .agent-chip { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; border: 1px solid transparent; cursor: default; }
        
        .chip-free { background-color: #dcfce7; color: #14532d; border-color: #86efac; }
        .chip-busy { background-color: #fee2e2; color: #7f1d1d; border-color: #fca5a5; }
        .chip-paused { background-color: #fef3c7; color: #78350f; border-color: #fcd34d; }
        .chip-offline { background-color: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

        [data-theme="dark"] .chip-free { background-color: #064e3b; color: #ffffff; border-color: #059669; }
        [data-theme="dark"] .chip-busy { background-color: #7f1d1d; color: #ffffff; border-color: #dc2626; }
        [data-theme="dark"] .chip-paused { background-color: #78350f; color: #ffffff; border-color: #d97706; }
        [data-theme="dark"] .chip-offline { background-color: #334155; color: #cbd5e1; border-color: #475569; }

        .chip-dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
        .dot-green { background-color: #22c55e; box-shadow: 0 0 5px #22c55e; }
        .dot-red { background-color: #ef4444; }
        .dot-orange { background-color: #f59e0b; }
        .dot-grey { background-color: #94a3b8; }

        /* Calls */
        .call-item { padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-weight: 500; color: #1f2937; }
        [data-theme="dark"] .call-item { color: #000; }
        .bg-sla-ok { background-color: #d1fae5; color: #064e3b; }
        .bg-sla-warn { background-color: #fef3c7; color: #78350f; }
        .bg-sla-bad { background-color: #fee2e2; color: #7f1d1d; }

        /* Feedback */
        #cmdFeedback { display: none; margin-top: 15px; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; text-align: center; }
        .feedback-success { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .feedback-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .feedback-info { background: #e0f2fe; color: #075985; border: 1px solid #38bdf8; }

        /* Stats */
        .card-stat { border: none; border-radius: 12px; color: #fff; padding: 20px; }
        .stat-value { font-size: 2.5rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
    </style>
</head>

<body>

    <div class="agent-statusbar sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" 
                     style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <i class="fa fa-headset fs-4"></i>
                </div>
                <div style="line-height: 1.2;">
                    <div class="fw-bold fs-5">VoxBlue Workspace</div>
                    <div class="small opacity-75">
                        <i class="fa fa-user-circle me-1"></i> <?= htmlspecialchars($meuNome) ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary border-0 text-light" onclick="toggleTheme()" title="Alterar Tema">
                    <i class="fa fa-moon fs-5" id="themeIcon"></i>
                </button>

                <div class="border-start border-secondary ps-3 ms-2 text-end d-none d-md-block">
                    <span id="myCurrentStatus" class="badge bg-secondary px-3 py-2 rounded-pill">Offline</span>
                    <div id="myTimer" class="small font-monospace mt-1 opacity-75">--:--:--</div>
                </div>
                
                <a href="logout.php" class="btn btn-danger btn-sm ms-3" title="Sair">
                    <i class="fa fa-power-off"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="container pb-5">

        <div class="control-section">
            <div class="row g-4 align-items-end">
                <div class="col-md-5">
                    <label class="form-label"><i class="fa fa-filter me-1"></i> Fila de Atendimento</label>
                    <select id="myQueue" class="form-select shadow-sm">
                        <?php
                        // Carrega filas do banco ou exibe padrão
                        $filas = include 'config.php'; 
                        $filas = $filas['queues'] ?? [];
                        
                        // Tenta carregar do banco via PDO se disponível
                        try {
                            $stmt = $pdo->query("SELECT extension, descr FROM asterisk.queues_config ORDER BY extension ASC");
                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['extension']}'>{$row['extension']} - {$row['descr']}</option>";
                                }
                            } else {
                                // Fallback para config
                                foreach ($filas as $k => $v) echo "<option value='$k'>$k - $v</option>";
                            }
                        } catch (Exception $e) {
                             foreach ($filas as $k => $v) echo "<option value='$k'>$k - $v</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-7">
                    <div class="d-flex gap-2">
                        <button id="btnLogin" class="btn btn-success btn-action w-100 shadow-sm" onclick="agentCmd('login')">
                            <i class="fa fa-sign-in-alt me-2"></i> Entrar
                        </button>
                        
                        <button id="btnPause" class="btn btn-warning btn-action w-100 shadow-sm text-dark d-none" onclick="agentCmd('pause')">
                            <i class="fa fa-pause me-2"></i> Pausar
                        </button>
                        
                        <button id="btnUnpause" class="btn btn-primary btn-action w-100 shadow-sm d-none" onclick="agentCmd('unpause')">
                            <i class="fa fa-play me-2"></i> Retornar
                        </button>
                        
                        <button id="btnLogout" class="btn btn-outline-danger btn-action w-25 d-none" onclick="agentCmd('logout')" title="Sair">
                            <i class="fa fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div id="cmdFeedback"></div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card-stat bg-primary shadow h-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Em Espera</div>
                        <div class="stat-value" id="hudWaiting">0</div>
                    </div>
                    <i class="fa fa-phone-volume fa-3x opacity-25"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat bg-success shadow h-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Agentes Online</div>
                        <div class="stat-value" id="hudAgents">0</div>
                    </div>
                    <i class="fa fa-headset fa-3x opacity-25"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat bg-danger shadow h-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Tempo de Espera</div>
                        <div class="stat-value" id="hudMaxWait">0s</div>
                    </div>
                    <i class="fa fa-stopwatch fa-3x opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h6 class="m-0 fw-bold text-uppercase text-muted" style="font-size: 0.85rem; letter-spacing: 1px;">
                <i class="fa fa-desktop me-2"></i>Visão Geral das Filas
            </h6>
            <div class="d-flex flex-wrap gap-3 align-items-center mt-2 mt-md-0">
                <div class="d-flex align-items-center small text-muted"><span class="chip-dot dot-green"></span> Livre</div>
                <div class="d-flex align-items-center small text-muted"><span class="chip-dot dot-red"></span> Em Chamada</div>
                <div class="d-flex align-items-center small text-muted"><span class="chip-dot dot-orange"></span> Pausado</div>
                <div class="d-flex align-items-center small text-muted"><span class="chip-dot dot-grey"></span> Indisponível</div>
            </div>
        </div>

        <div class="row g-3" id="monitorPanel">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando dados...</p>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const API_URL = 'api.php';
        // Injetando variaveis PHP no JS para lógica de botões
        const myTech = "<?= $minhaTech ?>";
        const myExten = "<?= $meuRamal ?>";
        const myFullInterface = `${myTech}/${myExten}`; 
        
        // --- GERENCIAMENTO DE TEMA ---
        function toggleTheme() {
            const body = document.body;
            const icon = $('#themeIcon');
            const isDark = body.getAttribute('data-theme') === 'dark';
            
            if (isDark) {
                body.removeAttribute('data-theme');
                localStorage.setItem('nm_theme', 'light');
                icon.removeClass('fa-sun').addClass('fa-moon');
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('nm_theme', 'dark');
                icon.removeClass('fa-moon').addClass('fa-sun');
            }
        }

        $(document).ready(function() {
            if (localStorage.getItem('nm_queue')) $('#myQueue').val(localStorage.getItem('nm_queue'));
            
            if (localStorage.getItem('nm_theme') === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
                $('#themeIcon').removeClass('fa-moon').addClass('fa-sun');
            }

            // Inicia Loop de Monitoramento
            updateMonitor();
            // AJUSTE CRÍTICO DE PERFORMANCE: 3000ms (compatível com cache do servidor)
            setInterval(updateMonitor, 3000); 
            
            // Relógio
            setInterval(() => { $('#myTimer').text(new Date().toLocaleTimeString()); }, 1000);
        });

        // --- AÇÕES DO AGENTE ---
        function agentCmd(type) {
            const queue = $('#myQueue').val();
            localStorage.setItem('nm_queue', queue);
            
            $('button').prop('disabled', true);
            showFeedback('Processando...', 'info');

            $.post(API_URL, {
                action: 'control',
                type: type,
                queue: queue
            }, function(res) {
                $('button').prop('disabled', false);
                
                if (res && res.status === 'ok') {
                    showFeedback(res.msg || 'Comando realizado!', 'success');
                    // Força atualização imediata após comando
                    setTimeout(updateMonitor, 500);
                } else {
                    showFeedback(res.msg || 'Erro desconhecido', 'error');
                }

            }, 'json').fail(function() {
                $('button').prop('disabled', false);
                showFeedback('Erro de conexão', 'error');
            });
        }

        function showFeedback(msg, type) {
            const el = $('#cmdFeedback');
            el.removeClass('feedback-success feedback-error feedback-info');
            
            let icon = '<i class="fa fa-info-circle me-2"></i>';
            if (type === 'success') { el.addClass('feedback-success'); icon = '<i class="fa fa-check-circle me-2"></i>'; }
            else if (type === 'error') { el.addClass('feedback-error'); icon = '<i class="fa fa-exclamation-triangle me-2"></i>'; }
            else { el.addClass('feedback-info'); icon = '<i class="fa fa-spinner fa-spin me-2"></i>'; }

            el.html(icon + msg).fadeIn();
            if (type !== 'info') setTimeout(() => { el.fadeOut(); }, 4000);
        }

        // --- MONITORAMENTO AJAX ---
        function updateMonitor() {
            $.get(API_URL, { action: 'monitor' }, function(res) {
                if (res.status === 'ok') {
                    processData(res.data);
                }
            }, 'json');
        }

        // --- RENDERIZAÇÃO VISUAL (Seu código original mantido) ---
        function processData(queues) {
            let totalWaiting = 0, totalAgents = 0, maxWait = 0, html = '';
            const myTargetQueue = $('#myQueue').val();
            let meFound = false, amIPaused = false;

            // Proteção contra objeto vazio
            if (!queues || Object.keys(queues).length === 0) {
                 $('#monitorPanel').html('<div class="col-12 text-center py-5 text-muted">Sem dados das filas.</div>');
                 return;
            }

            $.each(queues, function(id, q) {
                totalWaiting += q.count;
                // Contagem segura de membros
                let membersCount = q.members ? Object.keys(q.members).length : 0;
                totalAgents += membersCount;

                // 1. CALLS
                let callsHtml = '';
                if (q.calls && q.calls.length > 0) {
                    $.each(q.calls, function(i, call) {
                        let wait = parseInt(call.wait_time);
                        if (wait > maxWait) maxWait = wait;
                        let style = wait > 60 ? 'bg-sla-bad' : (wait > 30 ? 'bg-sla-warn' : 'bg-sla-ok');
                        callsHtml += `<div class="call-item ${style}">
                            <span><i class="fa fa-phone fa-xs opacity-50 me-2"></i>${call.caller_id}</span>
                            <span>${formatTime(wait)}</span></div>`;
                    });
                } else {
                    callsHtml = `<div class="text-muted small text-center py-2 fst-italic opacity-75">Fila Livre</div>`;
                }

                // 2. AGENTS
                let agentsHtml = '';
                if (q.members) {
                    let sortedMembers = Object.keys(q.members).sort();
                    $.each(sortedMembers, function(idx, iface) {
                        const m = q.members[iface];
                        let chipClass = 'chip-offline'; let dotClass = 'dot-grey';

                        if (m.paused) { chipClass = 'chip-paused'; dotClass = 'dot-orange'; }
                        else if (m.status == 2 || m.status == 6) { chipClass = 'chip-busy'; dotClass = 'dot-red'; }
                        else if (m.status == 1) { chipClass = 'chip-free'; dotClass = 'dot-green'; }

                        let name = m.display_name || iface.split('/').pop();
                        // Lógica para destacar SEU usuário
                        let myStyle = (iface == myFullInterface || iface.includes(myExten)) ? 'border: 2px solid #3b82f6;' : '';

                        agentsHtml += `<div class="agent-chip ${chipClass}" style="${myStyle}" title="${name}">
                            <span class="chip-dot ${dotClass}"></span>${name}</div>`;
                        
                        // Verifica status do usuário atual para ajustar botões
                        if (id == myTargetQueue && (iface == myFullInterface || iface.includes(myExten))) {
                            meFound = true;
                            amIPaused = m.paused;
                        }
                    });
                }

                // 3. CARD
                html += `
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="queue-card h-100">
                        <div class="queue-header">
                            <span class="text-truncate" style="max-width: 80%;" title="${q.name}">${q.name}</span>
                            <span class="badge bg-secondary opacity-50">${id}</span>
                        </div>
                        <div class="p-3 d-flex flex-column gap-3">
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7em;">Espera</span>
                                    ${q.count > 0 ? `<span class="badge bg-danger rounded-pill">${q.count}</span>` : ''}
                                </div>
                                <div class="d-flex flex-column gap-1">${callsHtml}</div>
                            </div>
                            <div class="border-top border-light opacity-25"></div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7em;">Agentes (${membersCount})</span>
                                </div>
                                <div class="agent-list-container">${agentsHtml}</div>
                            </div>
                        </div>
                    </div>
                </div>`;
            });

            $('#monitorPanel').html(html);
            $('#hudWaiting').text(totalWaiting);
            $('#hudAgents').text(totalAgents);
            $('#hudMaxWait').text(formatTime(maxWait));
            updateButtons(meFound, amIPaused);
        }

        function updateButtons(logged, paused) {
            const badge = $('#myCurrentStatus');
            const btns = { log: $('#btnLogin'), out: $('#btnLogout'), pause: $('#btnPause'), unpause: $('#btnUnpause') };
            
            // Esconde tudo primeiro
            btns.log.addClass('d-none'); btns.out.addClass('d-none'); 
            btns.pause.addClass('d-none'); btns.unpause.addClass('d-none');

            if (!logged) {
                badge.removeClass('bg-success bg-warning').addClass('bg-secondary').text('OFFLINE');
                btns.log.removeClass('d-none');
            } else if (paused) {
                badge.removeClass('bg-success bg-secondary').addClass('bg-warning text-dark').html('<i class="fa fa-pause"></i> PAUSADO');
                btns.unpause.removeClass('d-none'); btns.out.removeClass('d-none');
            } else {
                badge.removeClass('bg-warning bg-secondary').addClass('bg-success').html('<i class="fa fa-check"></i> DISPONÍVEL');
                btns.pause.removeClass('d-none'); btns.out.removeClass('d-none');
            }
        }

        function formatTime(s) {
            if (s < 60) return s + 's';
            let m = Math.floor(s / 60), sc = s % 60;
            return m + 'm ' + (sc < 10 ? '0' : '') + sc + 's';
        }
    </script>
    <?php include_once 'softphone_widget.php'; ?>
</body>
</html>