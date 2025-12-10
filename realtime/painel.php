<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitor Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --bg-body: #020617; --bg-card: #0f172a; --bg-element: #1e293b;
            --border-color: #334155; --text-primary: #f8fafc;
        }
        body { background-color: var(--bg-body); color: var(--text-primary); font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        .top-bar { 
            background: rgba(15, 23, 42, 0.95); border-bottom: 1px solid var(--border-color); 
            padding: 10px 30px; position: sticky; top: 0; z-index: 1000; 
            display: flex; justify-content: space-between; align-items: center;
        }

        .legend-bar {
            background: #0f172a; border-bottom: 1px solid var(--border-color);
            padding: 8px 30px; display: flex; gap: 20px; justify-content: center;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
        }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }

        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; padding: 25px; }

        .q-card { 
            background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; 
            display: flex; flex-direction: column; height: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .q-header { 
            background: #0b1121; padding: 12px 15px; border-bottom: 1px solid var(--border-color); 
            display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;
        }
        .q-title { font-weight: 700; font-size: 1.1rem; color: #fff; }

        .badge-stat { font-family: monospace; font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; border: 1px solid; }
        .bg-atd { background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.3); }
        .bg-prd { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

        .callers-area { background: #0f172a; padding: 10px; min-height: 50px; display: flex; flex-direction: column; gap: 5px; border-bottom: 1px solid var(--border-color); }
        .caller-box { 
            background: linear-gradient(90deg, #065f46, #047857); color: white; padding: 10px 15px; border-radius: 8px; 
            display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #34d399;
        }

        /* GRID AGENTES - 160px para conforto */
        .agent-grid { 
            padding: 15px; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; 
            background: var(--bg-card); border-radius: 0 0 12px 12px; 
        }
        
        .agent-card { 
            background: var(--bg-element); border: 1px solid var(--border-color); border-radius: 8px; 
            padding: 15px 10px; text-align: center; position: relative; overflow: hidden; 
            min-height: 150px; /* Altura aumentada para botões */
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            transition: all 0.3s ease; /* Animação suave na atualização */
        }
        .agent-card:hover .agent-overlay { opacity: 1; pointer-events: all; }
        
        .ag-avatar { 
            width: 45px; height: 45px; background: rgba(255,255,255,0.1); border-radius: 50%; 
            margin-bottom: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem;
        }
        .ag-name { font-size: 0.9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #e2e8f0; width: 100%; }
        .ag-status { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-top: 4px; }

        /* Status Styles */
        .st-free { border-bottom: 3px solid #10b981; } .st-free .ag-avatar { background: #059669; } .st-free .ag-status { color: #10b981; }
        .st-busy { border-bottom: 3px solid #ef4444; } .st-busy .ag-avatar { background: #dc2626; animation: pulse 2s infinite; } .st-busy .ag-status { color: #ef4444; }
        .st-paused { border: 1px solid #fbbf24; border-bottom: 3px solid #fbbf24; background: rgba(251, 191, 36, 0.05); } 
        .st-paused .ag-avatar { background: #d97706; color: #000; } .st-paused .ag-status { color: #fbbf24; }
        .st-off { opacity: 0.6; border: 1px dashed #475569; } .st-off .ag-avatar { background: #334155; }

        /* OVERLAY AÇÕES */
        .agent-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(2px);
            display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 8px;
            opacity: 0; pointer-events: none; transition: 0.2s; padding: 10px;
        }
        
        .action-row { display: flex; gap: 8px; justify-content: center; width: 100%; }
        .btn-icon { 
            width: 40px; height: 40px; border-radius: 8px; border: none; 
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; 
            transition: transform 0.2s; cursor: pointer;
        }
        .btn-icon:hover { transform: scale(1.1); }
        
        .btn-spy { background: #3b82f6; }
        .btn-whisper { background: #8b5cf6; }
        .btn-pause { background: #f59e0b; color: white; }
        .btn-unpause { background: #10b981; }
        .btn-remove { background: #ef4444; width: 100%; height: 30px; font-size: 0.9rem; margin-top: 5px; }

        .input-dark { background: #0f172a; border: 1px solid #334155; color: #fff; text-align: center; font-weight: bold; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }
    </style>
</head>
<body>

<nav class="top-bar">
    <div class="d-flex align-items-center gap-4">
        <h6 class="m-0 fw-bold text-white"><i class="bi bi-activity text-success me-2"></i> MONITOR</h6>
        <div class="d-flex align-items-center bg-dark rounded border border-secondary px-2 py-1">
            <span class="text-secondary small fw-bold text-uppercase me-2" style="font-size: 0.7rem;">Supervisor</span>
            <input type="text" id="myExt" class="input-dark rounded" style="width: 70px; height: 26px; font-size: 0.9rem;" placeholder="Ramal">
        </div>
    </div>
    <div class="d-flex gap-3 align-items-center">
        <div id="clock" class="font-monospace text-info fw-bold">--:--:--</div>
        <a href="../logout.php" class="btn btn-outline-secondary btn-sm px-3 fw-bold">SAIR</a>
    </div>
</nav>

<div class="legend-bar">
    <div class="legend-item"><div class="legend-dot bg-success"></div> LIVRE</div>
    <div class="legend-item"><div class="legend-dot bg-danger"></div> FALANDO</div>
    <div class="legend-item"><div class="legend-dot bg-warning"></div> PAUSA</div>
    <div class="legend-item"><div class="legend-dot bg-secondary"></div> OFF</div>
</div>

<div id="monitor" class="monitor-grid">
    <div class="text-center w-100 mt-5 text-secondary">
        <div class="spinner-border text-primary mb-3"></div><br>Carregando...
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #1e293b; color: #fff; border: 1px solid #475569;">
            <div class="modal-header border-secondary p-3">
                <h6 class="m-0 fw-bold">ADICIONAR AGENTE</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="mdQueueId">
                <div class="mb-1 text-secondary small fw-bold">FILA</div>
                <div id="mdQueueName" class="mb-3 fs-5 fw-bold text-primary">--</div>
                <div class="mb-1 text-secondary small fw-bold">AGENTE</div>
                <select id="mdUserSelect" class="form-select mb-3" style="width: 100%;"></select>
                <button class="btn btn-success w-100 fw-bold mt-2" onclick="doAddMember()">CONFIRMAR</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Recupera ramal salvo
    if(localStorage.getItem('sup_ext')) $('#myExt').val(localStorage.getItem('sup_ext'));
    $('#myExt').on('change', function(){ localStorage.setItem('sup_ext', $(this).val()); });
    
    setInterval(() => $('#clock').text(new Date().toLocaleTimeString()), 1000);

    function update() {
        $.getJSON('backend.php', function(data) {
            if(data.error) { showToast(data.error, 'danger'); return; }
            if(data.filas) render(data.filas);
        }).fail(() => {});
    }

    function render(queues) {
        if(!queues || queues.length === 0) {
            if($('#monitor').children().length === 0) 
                $('#monitor').html('<div class="text-center mt-5 text-muted">Nenhuma fila encontrada.</div>');
            return;
        }

        // Limpa loading inicial
        if($('#monitor').find('.spinner-border').length > 0) $('#monitor').empty();

        queues.forEach(q => {
            let qCard = $(`#q-card-${q.numero}`);
            
            // HTML Agentes (Gera sempre para verificar mudanças)
            let agentsHtml = '';
            if (q.membros) {
                agentsHtml = q.membros.map(m => {
                    let st = 'st-off', txt = 'OFF', icon = 'bi-person-x';
                    
                    if(m.status==1) { st='st-free'; txt='LIVRE'; icon='bi-person-check'; }
                    else if(m.status==2||m.status==6) { st='st-busy'; txt='FALANDO'; icon='bi-telephone-fill'; }
                    
                    if(m.paused) { st='st-paused'; txt='PAUSA'; icon='bi-cup-hot-fill'; }
                    
                    let ini = m.nome.substring(0,2).toUpperCase();
                    
                    // Botões Inteligentes
                    let btnPause = m.paused 
                        ? `<button class="btn-icon btn-unpause" title="Liberar" onclick="cmdPause('${q.numero}', '${m.interface}', 'false')"><i class="bi bi-play-fill"></i></button>`
                        : `<button class="btn-icon btn-pause" title="Pausar" onclick="cmdPause('${q.numero}', '${m.interface}', 'true')"><i class="bi bi-pause-fill"></i></button>`;

                    let btnRemove = m.dynamic ? `<button class="btn-icon btn-remove" title="Remover" onclick="cmdRemove('${q.numero}', '${m.interface}')"><i class="bi bi-trash"></i></button>` : '';

                    return `
                    <div class="agent-card ${st}" id="agent-${m.ramal}">
                        <div class="ag-avatar"><i class="bi ${icon}"></i></div>
                        <div class="ag-name" title="${m.nome}">${m.nome}</div>
                        <div class="ag-status">${txt}</div>
                        
                        <div class="agent-overlay">
                            <div class="action-row">
                                <button class="btn-icon btn-spy" title="Ouvir" onclick="cmdSpy('${m.ramal}', 'q')"><i class="bi bi-headset"></i></button>
                                <button class="btn-icon btn-whisper" title="Soprar" onclick="cmdSpy('${m.ramal}', 'w')"><i class="bi bi-mic-fill"></i></button>
                                ${btnPause}
                            </div>
                            ${btnRemove}
                        </div>
                    </div>`;
                }).join('');
            }

            // HTML Chamadas
            let callsHtml = q.chamadas.length ? q.chamadas.map(c => `
                <div class="caller-box">
                    <div class="caller-number"><i class="bi bi-telephone-inbound-fill me-2"></i> ${c.numero}</div>
                    <div class="caller-timer">${c.wait}</div>
                </div>`).join('') : '<div class="text-center text-muted small py-2 opacity-25">Fila Limpa</div>';

            // Cria ou Atualiza Card
            if (qCard.length === 0) {
                $('#monitor').append(`
                <div class="q-card" id="q-card-${q.numero}">
                    <div class="q-header">
                        <div class="q-title"><i class="bi bi-layers-fill text-primary me-2"></i> ${q.nome}</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-stat bg-atd" id="stat-atd-${q.numero}">${q.atendidas} Atd</span>
                            <span class="badge-stat bg-prd" id="stat-prd-${q.numero}">${q.abandonadas} Prd</span>
                            <button class="btn btn-sm btn-light border-secondary py-0 px-2" onclick="openAdd('${q.numero}', '${q.nome}')"><i class="bi bi-person-plus-fill"></i></button>
                        </div>
                    </div>
                    <div class="callers-area" id="callers-${q.numero}">${callsHtml}</div>
                    <div class="agent-grid" id="agents-${q.numero}">${agentsHtml}</div>
                </div>`);
            } else {
                // Smart Update (Só mexe no DOM se mudou)
                $(`#stat-atd-${q.numero}`).text(q.atendidas + ' Atd');
                $(`#stat-prd-${q.numero}`).text(q.abandonadas + ' Prd');
                
                let oldAgents = $(`#agents-${q.numero}`).html();
                if (oldAgents !== agentsHtml) $(`#agents-${q.numero}`).html(agentsHtml);

                let oldCalls = $(`#callers-${q.numero}`).html();
                if (oldCalls !== callsHtml) $(`#callers-${q.numero}`).html(callsHtml);
            }
        });
    }

    // Ações API
    function cmdPause(queue, iface, state) {
        $.post('api_control.php', { action:'pause_member', queue:queue, interface:iface, paused:state }, d => {
            let msg = (state === 'true') ? 'Pausado' : 'Liberado';
            showToast(`${msg}: ${d.status}`, d.status.includes('Sucesso') ? 'success' : 'primary');
            setTimeout(update, 300); // Atualiza rápido
        }, 'json');
    }

    function cmdSpy(target, mode) {
        let sup = $('#myExt').val();
        if(!sup) { showToast('Defina seu ramal!', 'warning'); $('#myExt').focus(); return; }
        $.post('api_control.php', { action:'spy', supervisor:sup, target:target, mode:mode }, d => showToast(d.status, 'primary'), 'json');
    }

    function cmdRemove(qid, iface) {
        if(!confirm(`Remover agente?`)) return;
        $.post('api_control.php', { action:'remove_member', queue:qid, interface:iface }, d => {
            showToast(d.status, d.status.includes('Sucesso')?'success':'danger');
            update();
        }, 'json');
    }

    // Modal
    const addModal = new bootstrap.Modal('#addModal');
    $('#addModal').on('shown.bs.modal', () => {
        let sel = $('#mdUserSelect');
        sel.empty().append('<option>Carregando...</option>');
        $.getJSON('api_control.php?action=get_users', function(users) {
            sel.empty().append('<option value="">Selecione...</option>');
            if(Array.isArray(users)) {
                users.forEach(u => {
                    let iface = (u.tech || 'PJSIP') + '/' + u.username;
                    sel.append(new Option(`${u.name} (${u.username})`, iface));
                });
            }
            sel.select2({ dropdownParent: $('#addModal'), theme: 'bootstrap-5', width: '100%', placeholder: 'Buscar...' });
        });
    });

    function openAdd(qid, name) {
        $('#mdQueueId').val(qid); $('#mdQueueName').text(name);
        addModal.show();
    }

    window.doAddMember = () => {
        let qid = $('#mdQueueId').val(), iface = $('#mdUserSelect').val();
        if(!iface) { showToast('Selecione um agente!', 'warning'); return; }
        $.post('api_control.php', { action:'add_member', queue:qid, interface:iface }, d => {
            addModal.hide(); showToast(d.status, 'success'); update();
        }, 'json');
    }

    function showToast(msg, type='primary') {
        let color = type==='success'?'bg-success':(type==='danger'?'bg-danger':(type==='warning'?'bg-warning text-dark':'bg-primary'));
        let el = $(`<div class="toast align-items-center text-white ${color} border-0" role="alert"><div class="d-flex"><div class="toast-body fw-bold">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`).appendTo('#toastContainer');
        new bootstrap.Toast(el[0]).show();
    }

    update(); setInterval(update, 2000);
</script>
</body>
</html>