<?php
// Arquivo: /var/www/html/login.php
require_once 'config_global.php';

if (isset($_SESSION['vox_user'])) {
    header('Location: portal.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    try {
        $pdo = getGlobalConnection();
        // Verifica na tabela users (ajuste as colunas conforme seu banco real)
        $stmt = $pdo->prepare("SELECT id, username, password, name, role FROM users WHERE username = :u AND active = 'Y'");
        $stmt->execute(['u' => $user]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica a senha (hash ou texto plano - ajuste conforme seu sistema)
        // Se suas senhas forem hash (recomendado):
        if ($dados && password_verify($pass, $dados['password'])) {
             $_SESSION['vox_user'] = $dados;
             $_SESSION['logged_in'] = true; // Compatibilidade com módulos antigos
             header('Location: portal.php');
             exit;
        } 
        // Fallback se senha for texto plano (apenas para legado, evite se possível)
        elseif ($dados && $dados['password'] == $pass) {
             $_SESSION['vox_user'] = $dados;
             $_SESSION['logged_in'] = true;
             header('Location: portal.php');
             exit;
        } else {
            $erro = "Credenciais inválidas.";
        }
    } catch (Exception $e) {
        $erro = "Erro interno.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | VoxBlue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #020617; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .login-card { width: 100%; max-width: 400px; background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .form-control { background: #1e293b; border: 1px solid #334155; color: #fff; padding: 12px; }
        .form-control:focus { background: #1e293b; color: #fff; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25); }
        .btn-login { background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 12px; font-weight: 600; font-size: 1rem; margin-top: 20px; transition: 0.3s; }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }
        .logo-area { text-align: center; margin-bottom: 30px; }
        .logo-icon { font-size: 3rem; color: #3b82f6; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-area">
            <i class="bi bi-headset logo-icon"></i>
            <h3 class="text-white fw-bold mt-2">VOXBLUE</h3>
            <p class="text-secondary small">Call Center Suite</p>
        </div>
        
        <?php if($erro): ?>
            <div class="alert alert-danger py-2 text-center small"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="text-secondary small fw-bold mb-1">USUÁRIO</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Seu usuário" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="text-secondary small fw-bold mb-1">SENHA</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login">ACESSAR SISTEMA</button>
        </form>
    </div>
</body>
</html>