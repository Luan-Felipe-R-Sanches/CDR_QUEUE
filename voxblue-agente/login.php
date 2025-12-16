<?php
// Arquivo: voxblue-agente/login.php
session_start();

// Verifica se o arquivo de conexão existe para evitar Erro 500 silencioso
if (!file_exists('db.php')) {
    die("Erro Crítico: Arquivo db.php não encontrado na pasta voxblue-agente.");
}
require_once 'db.php';

// Se já logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    try {
        // Query segura buscando na tabela do call center
        // Removemos "active='Y'" preventivamente caso a coluna não exista
        $sql = "SELECT id, username, password, name, role, tech 
                FROM netmaxxi_callcenter.users 
                WHERE username = :u LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['u' => $user]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Verifica senha (hash ou texto plano)
            $senhaValida = password_verify($pass, $data['password']);
            if (!$senhaValida && $data['password'] === $pass) {
                $senhaValida = true; // Fallback legado
            }

            if ($senhaValida) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['user_name'] = $data['username'];
                $_SESSION['user_realname'] = $data['name'];
                $_SESSION['user_role'] = $data['role'];
                $_SESSION['user_tech'] = $data['tech'];

                // Redireciona baseado no cargo
                if ($data['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        }
        $error = "Credenciais inválidas.";
    } catch (PDOException $e) {
        $error = "Erro no sistema: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agent Workspace | VoxBlue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-deep: #030712;
            --glass-bg: rgba(10, 20, 30, 0.7);
            --glass-border: rgba(6, 182, 212, 0.2);
            --neon-cyan: #06b6d4;
            --neon-glow: rgba(6, 182, 212, 0.4);
            --text-main: #ecfeff;
        }

        body {
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
            perspective: 1000px;
        }

        /* --- Grid Animation (Igual ao Index) --- */
        .cyber-grid {
            position: absolute;
            width: 200%; height: 200%;
            bottom: -50%; left: -50%;
            background-image: 
                linear-gradient(rgba(6, 182, 212, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6, 182, 212, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            transform: perspective(500px) rotateX(60deg);
            animation: moveGrid 10s linear infinite;
            z-index: -1;
            mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 60%);
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 60%);
        }
        @keyframes moveGrid { 0% { transform: perspective(500px) rotateX(60deg) translateY(0); } 100% { transform: perspective(500px) rotateX(60deg) translateY(50px); } }

        /* --- Cartão de Login --- */
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: 0 0 40px rgba(0,0,0,0.6), inset 0 0 0 1px rgba(255,255,255,0.05);
            animation: floatUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes floatUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Header */
        .brand-header { text-align: center; margin-bottom: 30px; }
        .icon-box {
            width: 70px; height: 70px;
            margin: 0 auto 15px;
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: var(--neon-cyan);
            box-shadow: 0 0 20px var(--neon-glow);
        }
        .brand-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 1.8rem;
            letter-spacing: 2px; margin: 0;
            text-transform: uppercase;
        }
        .brand-subtitle { font-size: 0.8rem; color: #67e8f9; opacity: 0.7; letter-spacing: 1px; }

        /* Inputs */
        .input-group-custom { margin-bottom: 20px; position: relative; }
        .form-control {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            padding: 12px 15px 12px 45px;
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .form-control:focus {
            background: rgba(0, 0, 0, 0.5);
            border-color: var(--neon-cyan);
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.2);
            color: #fff;
            outline: none;
        }
        .input-icon {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%);
            color: var(--neon-cyan);
            z-index: 10;
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #050b14 inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Botão */
        .btn-enter {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
            margin-top: 10px;
            transition: 0.3s;
            text-transform: uppercase;
        }
        .btn-enter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(6, 182, 212, 0.4);
            background: linear-gradient(135deg, #06b6d4, #22d3ee);
        }

        /* Botão Voltar */
        .btn-back {
            display: flex; align-items: center; justify-content: center;
            margin-top: 25px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 20px;
        }
        .btn-back:hover { color: var(--neon-cyan); }
        .btn-back i { margin-right: 8px; transition: 0.3s; }
        .btn-back:hover i { transform: translateX(-3px); }

        .alert-error {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #fca5a5;
            font-size: 0.85rem;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
    </style>
</head>
<body>

    <div class="cyber-grid"></div>

    <div class="login-card">
        <div class="brand-header">
            <div class="icon-box">
                <i class="bi bi-headset"></i>
            </div>
            <h1 class="brand-title">AGENTE</h1>
            <div class="brand-subtitle">VOXBLUE WORKSPACE</div>
        </div>

        <?php if ($error): ?>
            <div class="alert-error animate__animated animate__shakeX">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group-custom">
                <i class="bi bi-person-fill input-icon"></i>
                <input type="text" name="username" class="form-control" placeholder="ID do Agente" required autofocus autocomplete="username">
            </div>

            <div class="input-group-custom">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" name="password" class="form-control" placeholder="Senha de Acesso" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-enter">
                Conectar <i class="bi bi-chevron-right ms-1" style="font-size: 0.9em;"></i>
            </button>
        </form>

        <a href="../index.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Voltar à Tela Inicial
        </a>
    </div>

</body>
</html>