<?php
// Arquivo: voxblue-solutions/voxblue-panel/login.php
session_start();
require_once 'config.php';

// Se já logado, vai pro painel
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    try {
        $pdo = getConexao();
        
        // Busca usuário
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$user]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica senha
        if ($data && password_verify($pass, $data['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['user_name'] = $data['username'];
            $_SESSION['user_realname'] = $data['name'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = "Credenciais inválidas.";
        }
    } catch (Exception $e) {
        $error = "Erro no sistema: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acesso VoxBlue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #020617;
            --accent: #06b6d4; /* Ciano Neon */
            --accent-hover: #22d3ee;
            --text-white: #f8fafc;
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #020617 0%, #1e293b 50%, #0f172a 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            margin: 0; color: var(--text-white);
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-wrapper { width: 100%; max-width: 450px; padding: 20px; }

        .card {
            background: rgba(30, 41, 59, 0.6); /* Mais transparência */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            padding: 40px;
        }

        /* Ícone da Marca com Brilho */
        .brand-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--accent), #3b82f6);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: white;
            margin: 0 auto 20px;
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.4); /* Glow */
        }

        h3 { font-family: 'Rajdhani', sans-serif; font-weight: 700; letter-spacing: 1px; margin-bottom: 5px; color: #fff; }
        p.subtitle { color: #cbd5e1; font-size: 0.95rem; margin-bottom: 30px; font-weight: 300; }

        /* INPUTS FLUTUANTES DARK (Correção de cores) */
        .form-floating > .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            color: #fff !important;
            border-radius: 12px;
            height: 55px;
        }
        
        .form-floating > .form-control:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.2);
            color: #fff;
        }

        /* Cor do label flutuante */
        .form-floating > label { color: #94a3b8; }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--accent);
            font-weight: 600;
        }

        /* Correção para Autocomplete do Chrome não ficar branco */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0f172a inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Botão Principal */
        .btn-login {
            background: linear-gradient(90deg, var(--accent), #3b82f6);
            border: none; height: 55px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 1.1rem; letter-spacing: 1px;
            border-radius: 12px; margin-top: 15px;
            color: #fff;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.5); /* Mais brilho no hover */
            background: linear-gradient(90deg, #22d3ee, #60a5fa);
            color: #fff;
        }

        /* Alert de Erro */
        .alert-custom {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            font-size: 0.9rem; border-radius: 10px;
        }

        /* Link Voltar */
        .btn-back {
            display: block; text-align: center; margin-top: 25px;
            color: #94a3b8; text-decoration: none;
            font-size: 0.9rem; transition: all 0.3s;
        }
        .btn-back:hover { color: var(--accent); transform: translateX(-3px); }

        .footer-copy { color: #64748b; font-size: 0.8rem; margin-top: 30px; opacity: 0.7; }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="card">
            <div class="text-center">
                <div class="brand-icon">
                    <i class="bi bi-activity"></i>
                </div>
                <h3>VOXBLUE PANEL</h3>
                <p class="subtitle">Bem-vindo ao seu painel de controle</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-custom p-3 mb-4 d-flex align-items-center animate__animated animate__shakeX">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-floating mb-3">
                    <input type="text" name="username" class="form-control" id="floatingInput" placeholder="Usuário" required autofocus autocomplete="off">
                    <label for="floatingInput"><i class="bi bi-person me-1"></i> Usuário</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Senha" required>
                    <label for="floatingPassword"><i class="bi bi-lock me-1"></i> Senha</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-login">
                    ENTRAR <i class="bi bi-arrow-right-short ms-1" style="font-size: 1.2rem; vertical-align: middle;"></i>
                </button>
            </form>

            <a href="../../portal.php" class="btn-back">
                <i class="bi bi-arrow-left me-1"></i> Voltar ao Portal
            </a>

            <div class="text-center footer-copy">
                &copy; <?= date('Y') ?> VoxBlue Solutions • v2.0 Pro
            </div>
        </div>
    </div>

</body>
</html>