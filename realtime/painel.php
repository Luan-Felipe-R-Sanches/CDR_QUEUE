<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Monitor Realtime</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0f1219; color: #e2e8f0; font-family: 'Rajdhani', sans-serif; overflow-x: hidden; }
        
        .top-bar { background: #1a202c; padding: 15px 30px; border-bottom: 1px solid #2d3748; display: flex; justify-content: space-between; align-items: center; }
        .monitor-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px; padding: 20px; }
        
        .q-card { background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; display: flex; flex-direction: column; height: 100%; }
        .q-header { padding: 15px 20px; background: #0f172a; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .q-title { font-size: 1.4rem; font-weight: 700; color: #f8fafc; }
        
        /* AREA DE CHAMADAS (Onde aparece o DID e Tronco) */
        .callers-area { background: #2d3748; padding: 10px; min-height: 50px; display: flex; flex-direction: column; gap: 8px; border-bottom: 1px solid #334155; }
        .caller-box { 
            background: #dc2626; color: white; padding: 10px 15px; border-radius: 6px; 
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4); animation: slideIn 0.3s ease;
        }
        .caller-main { display: flex; justify-content: space-between; align-items: center; }
        .caller-number { font-size: 1.4rem; font-weight: 700; letter-spacing: 0.5px; line-height: 1; }
        .caller-time { font-family: monospace; font-weight: 700; background: rgba(0,0,0,0.3); padding: 3px 8px; border-radius: 4px; font-size: 1.1rem; }
        
        .caller-details { margin-top: 5px; display: flex; gap: 10px; font-size: 0.85rem; opacity: 0.9; }
        .badge-trunk { background: #f59e0b; color: #000; padding: 1px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; }
        
        /* Agentes */
        .agent-grid { padding: 15px; display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; }
        .agent-card { background: #1a202c; border: 1px solid #334155; border-radius: 8px; padding: 8px; text-align: center; position: relative; }
        .ag-name { font-weight: 700; font-size: 0.9rem; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ag-state { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; }
        .ag-status-dot { width: 8px; height: 8px; border-radius: 50%; position: absolute; top: 8px; right: 8px; }

        /* Cores Status */
        .st-free { border-bottom: 3px solid #22c55e; } .st-free .ag-state { color: #22c55e; } .st-free .ag-status-dot { background: #22c55e; box-shadow: 0 0 5px #22c55e; }
        .st-busy { border-bottom: 3px solid #ef4444; background: #2d1a1a; } .st-busy .ag-state { color: #ef4444; } .st-busy .ag-status-dot { background: #ef4444; }
        .st-paused { border-bottom: 3px solid #f59e0b; background: #2d2515; } .st-paused .ag-state { color: #f59e0b; } .st-paused .ag-status-dot { background: #f59e0b; }
        .st-off { opacity: 0.4; border-bottom: 3px solid #64748b; }

        .pulse { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="d-flex align-items-center gap-2">
        <h4 class="m-0 fw-bold text-white"><span class="text-success pulse">●</span> REALTIME</h4>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-end text-white">
            <div class="fw-bold" id="clock-time">--:--:--</div>
        </div>
        <a href="../app.php" class="btn btn-outline-light btn-sm">Voltar</a>
    </div>
</div>

<div id="monitor" class="monitor-grid">
    <div class="text-center w-100 mt-5 text-muted">Conectando ao PABX...</div>
</div>

<script>
    setInterval(() => { document.getElementById('clock-time').innerText = new Date().toLocaleTimeString(); }, 1000);

    function updateBoard() {
        fetch('backend.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) { document.getElementById('monitor').innerHTML = `<div class="text-danger text-center mt-5">${data.error}</div>`; return; }
                render(data);
            })
            .catch(err => console.error(err));
    }

    function render(queues) {
        const container = document.getElementById('monitor');
        let html = '';

        queues.forEach(q => {
            // Chamadas na Fila (Agora com Tronco)
            let callersHtml = '';
            if (q.chamadas.length > 0) {
                q.chamadas.forEach(c => {
                    callersHtml += `
                    <div class="caller-box">
                        <div>
                            <div class="caller-main">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-telephone-inbound-fill animate-pulse"></i>
                                    <span class="caller-number">${c.numero}</span>
                                </div>
                                <span class="caller-time">${c.wait}</span>
                            </div>
                            <div class="caller-details">
                                <span class="badge-trunk">${c.tronco}</span>
                            </div>
                        </div>
                    </div>`;
                });
            } else {
                callersHtml = '<div class="text-center text-muted small p-2" style="font-style:italic; opacity:0.5">Fila Limpa</div>';
            }

            // Agentes
            let agentsHtml = '';
            q.membros.forEach(m => {
                let stClass = 'st-off', stText = 'OFFLINE';
                if (m.status == 1) { stClass = 'st-free'; stText = 'LIVRE'; }
                else if (m.status == 2 || m.status == 6) { stClass = 'st-busy'; stText = 'FALANDO'; }
                if (m.paused) { stClass = 'st-paused'; stText = 'PAUSA'; }

                agentsHtml += `
                <div class="agent-card ${stClass}">
                    <div class="ag-status-dot"></div>
                    <div class="ag-name" title="${m.nome}">${m.nome}</div>
                    <div class="ag-state">${stText}</div>
                    <div style="font-size:0.65rem; color:#64748b; margin-top:2px;">${m.calls} atd</div>
                </div>`;
            });

            html += `
            <div class="q-card">
                <div class="q-header">
                    <div class="q-title">${q.nome}</div>
                    <div class="d-flex gap-3 font-monospace fw-bold">
                        <span class="text-success" title="Atendidas"><i class="bi bi-check"></i> ${q.atendidas}</span>
                        <span class="text-danger" title="Abandonadas"><i class="bi bi-x"></i> ${q.abandonadas}</span>
                    </div>
                </div>
                <div class="callers-area">${callersHtml}</div>
                <div class="agent-grid">${agentsHtml}</div>
            </div>`;
        });

        container.innerHTML = html;
    }

    updateBoard();
    setInterval(updateBoard, 1000); // Atualiza a cada 1 segundo
</script>
</body>
</html>