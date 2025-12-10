<?php
// Arquivo: admin.php
require 'db.php';
checkAdmin();

$msg = '';
$msgType = '';

// --- 1. CARREGAR RAMAIS DO ISSABEL ---
try {
    $stmtDevices = $pdo->query("SELECT id, description FROM asterisk.devices ORDER BY id ASC");
    $listaRamais = $stmtDevices->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $listaRamais = [];
    $msg = "Aviso: Não foi possível carregar a lista de ramais do Asterisk."; 
    $msgType = "danger";
}

// --- 2. PROCESSAR FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'delete') {
            $idParaRemover = $_POST['id'];
            if ($idParaRemover == $_SESSION['user_id']) throw new Exception("Você não pode se excluir.");

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$idParaRemover]);
            $msg = "Acesso removido com sucesso."; $msgType = "success";
        }

        elseif ($action === 'save') {
            $id = $_POST['id'] ?? '';
            $ramalEscolhido = trim($_POST['username']); 
            $password = $_POST['password'];

            // Busca automática no banco do Asterisk
            $stmtInfo = $pdo->prepare("SELECT description, tech FROM asterisk.devices WHERE id = :id LIMIT 1");
            $stmtInfo->execute(['id' => $ramalEscolhido]);
            $infoDevice = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$infoDevice) throw new Exception("Ramal $ramalEscolhido não encontrado no banco de dados.");

            $name = $infoDevice['description'] ?: "Ramal $ramalEscolhido";
            $tech = strtoupper($infoDevice['tech']);
            $role = 'agent'; 

            if (!empty($id)) {
                // Update
                $sql = "UPDATE users SET username=?, name=?, tech=?, role=?";
                $params = [$ramalEscolhido, $name, $tech, $role];
                if (!empty($password)) {
                    $sql .= ", password=?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id=?";
                $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $msg = "Agente <b>$name</b> atualizado."; $msgType = "success";
            } else {
                // Insert
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$ramalEscolhido]);
                if($check->rowCount() > 0) throw new Exception("O ramal $ramalEscolhido já possui acesso.");
                
                if (empty($password)) throw new Exception("Crie uma senha para o agente.");
                
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, tech, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$ramalEscolhido, $passHash, $name, $tech, $role]);
                $msg = "Agente <b>$name</b> adicionado."; $msgType = "success";
            }
        }
    } catch (Exception $e) {
        $msg = $e->getMessage(); $msgType = "danger";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

$totalAgents = 0; $totalAdmins = 0;
foreach($users as $u) ($u['role'] === 'admin') ? $totalAdmins++ : $totalAgents++;

function getInitials($name) {
    $parts = explode(" ", $name);
    return strtoupper(substr($parts[0], 0, 1) . (count($parts)>1 ? substr(end($parts), 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root { --bg-body: #f1f5f9; --text-dark: #0f172a; --card-bg: #ffffff; --primary: #2563eb; }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-dark); }
        
        .topbar { background: var(--card-bg); padding: 15px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; }
        .bg-blue-light { background: #eff6ff; color: #3b82f6; }
        .bg-red-light { background: #fef2f2; color: #ef4444; }
        
        .table-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .table thead th { background: #f8fafc; font-size: 0.75rem; color: #64748b; padding: 18px 24px; }
        .table tbody td { padding: 18px 24px; vertical-align: middle; color: #334155; }
        
        .avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; margin-right: 12px; }
        .avatar-admin { background: #fee2e2; color: #991b1b; } .avatar-agent { background: #dbeafe; color: #1e40af; }
        .badge-role { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .role-admin { background: #fee2e2; color: #991b1b; } .role-agent { background: #dbeafe; color: #1e40af; }
        .badge-tech { background: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; border: 1px solid #e2e8f0; }
        
        .btn-action { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: none; cursor: pointer; }
        .btn-edit { background: #eff6ff; color: #3b82f6; } .btn-del { background: #fff; color: #cbd5e1; }
        
        .modal-content { border: none; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        
        /* CORREÇÃO CRÍTICA PARA O TOM SELECT DENTRO DO MODAL */
        .ts-dropdown { z-index: 1060 !important; } /* Maior que o modal do bootstrap (1055) */
        .ts-control { padding: 12px; border-radius: 8px; }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fa fa-shield-alt"></i>
                </div>
                <div><h5 class="m-0 fw-bold text-primary">Painel Admin</h5><small class="text-muted">Gerenciamento</small></div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm px-4 rounded-pill fw-semibold">Sair</a>
        </div>
    </div>

    <div class="container pb-5">
        <?php if($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="fa fa-info-circle me-2"></i> <?= $msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-6"><div class="stat-card d-flex align-items-center"><div class="stat-icon bg-blue-light"><i class="fa fa-headset"></i></div><div><h3 class="m-0 fw-bold"><?= $totalAgents ?></h3><span class="text-muted small fw-bold">Agentes</span></div></div></div>
            <div class="col-md-6"><div class="stat-card d-flex align-items-center"><div class="stat-icon bg-red-light"><i class="fa fa-user-shield"></i></div><div><h3 class="m-0 fw-bold"><?= $totalAdmins ?></h3><span class="text-muted small fw-bold">Admins</span></div></div></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark m-0">Equipe</h5>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" type="button" onclick="window.openModal()">
                <i class="fa fa-plus me-2"></i> Novo Agente
            </button>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th class="ps-4">Usuário</th><th>Ramal</th><th>Tech</th><th>Status</th><th class="text-end pe-4">Ações</th></tr></thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar <?= $u['role'] == 'admin' ? 'avatar-admin' : 'avatar-agent' ?>"><?= getInitials($u['name']) ?></div>
                                    <div><div class="fw-bold"><?= $u['name'] ?></div><div class="small text-muted">#<?= $u['id'] ?></div></div>
                                </div>
                            </td>
                            <td><span class="fw-bold font-monospace text-primary bg-light px-2 py-1 rounded"><?= $u['username'] ?></span></td>
                            <td><span class="badge-tech"><?= $u['tech'] ?: 'AUTO' ?></span></td>
                            <td><span class="badge-role <?= $u['role'] == 'admin' ? 'role-admin' : 'role-agent' ?>"><?= $u['role'] == 'admin' ? 'ADMIN' : 'AGENTE' ?></span></td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn-action btn-edit" onclick='window.openModal(<?= json_encode($u) ?>)'><i class="fa fa-pen"></i></button>
                                    <?php if($u['username'] !== 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Remover?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>"><button class="btn-action btn-del"><i class="fa fa-trash"></i></button></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Novo Agente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="userId">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Selecione o Ramal</label>
                            <select id="select-ramal" name="username" placeholder="Pesquisar..." autocomplete="off" required>
                                <option value="">Selecione...</option>
                                <?php foreach($listaRamais as $ramal): ?>
                                    <option value="<?= $ramal['id'] ?>"><?= $ramal['id'] ?> - <?= $ramal['description'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text mt-2 text-primary small"><i class="fa fa-magic me-1"></i> Dados preenchidos automaticamente.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted small fw-bold">Senha</label>
                            <input type="password" name="password" id="userPass" class="form-control form-control-lg bg-light" placeholder="******">
                            <small class="text-muted d-block mt-1" id="passHelp"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4 rounded-pill">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        // Define as variáveis fora para serem globais
        let myAgentModal;
        let myTomSelect;

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Inicializa o Modal do Bootstrap
            const modalEl = document.getElementById('agentModal');
            if (modalEl) {
                myAgentModal = new bootstrap.Modal(modalEl);
            }

            // 2. Inicializa o Tom Select
            const selectEl = document.getElementById('select-ramal');
            if (selectEl) {
                myTomSelect = new TomSelect("#select-ramal", {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: "Digite o ramal...",
                    dropdownParent: 'body' // ISSO É CRUCIAL PARA APARECER NO MODAL
                });
            }

            // 3. Define a função globalmente para ser acessada pelo HTML
            window.openModal = function(user = null) {
                if (!myAgentModal) {
                    console.error("Modal não carregado corretamente.");
                    return;
                }

                document.getElementById('userPass').value = '';
                document.getElementById('userId').value = '';

                if (user) {
                    // MODO EDIÇÃO
                    document.getElementById('modalTitle').innerText = 'Editar Agente';
                    document.getElementById('userId').value = user.id;

                    if (myTomSelect) {
                        myTomSelect.setValue(user.username);
                        myTomSelect.lock();
                    }

                    document.getElementById('userPass').removeAttribute('required');
                    document.getElementById('passHelp').innerText = 'Vazio = mantém atual.';
                } else {
                    // MODO NOVO
                    document.getElementById('modalTitle').innerText = 'Novo Agente';
                    
                    if (myTomSelect) {
                        myTomSelect.unlock();
                        myTomSelect.clear();
                    }

                    document.getElementById('userPass').setAttribute('required', 'required');
                    document.getElementById('passHelp').innerText = 'Obrigatório.';
                }

                myAgentModal.show();
            };
        });
    </script>
</body>
</html>