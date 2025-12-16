<?php
// Arquivo: /var/www/html/login.php
require_once 'config_global.php';

// Se já estiver logado, redireciona
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
        
        // Busca usuário (sem verificar 'active' pois a coluna não existe)
        $stmt = $pdo->prepare("SELECT id, username, password, name, role FROM users WHERE username = :u");
        $stmt->execute(['u' => $user]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica a senha
        if ($dados) {
            // 1. Tenta validar hash (Seguro)
            if (password_verify($pass, $dados['password'])) {
                 $_SESSION['vox_user'] = $dados;
                 $_SESSION['logged_in'] = true;
                 header('Location: portal.php');
                 exit;
            } 
            // 2. Fallback para senha texto plano (Legado)
            elseif ($dados['password'] == $pass) {
                 $_SESSION['vox_user'] = $dados;
                 $_SESSION['logged_in'] = true;
                 header('Location: portal.php');
                 exit;
            }
        }
        
        $erro = "Usuário ou senha incorretos.";

    } catch (Exception $e) {
        $erro = "Erro de conexão (500). Contate o suporte.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | VoxBlue Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #020617;
            --primary-glow: #6366f1; /* Indigo Neon */
            --accent-glow: #3b82f6;  /* Blue Neon */
            --glass-bg: rgba(15, 23, 42, 0.75);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-color: #f8fafc;
        }

        body {
            background-color: var(--bg-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            overflow: hidden;
            margin: 0;
            perspective: 1000px;
        }

        /* --- ANIMAÇÃO DE FUNDO (CYBER GRID) --- */
        .cyber-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            bottom: -50%;
            left: -50%;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            transform: perspective(500px) rotateX(60deg);
            animation: moveGrid 6s linear infinite;
            z-index: -2;
            mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 80%);
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 80%);
        }

        @keyframes moveGrid {
            0% { transform: perspective(500px) rotateX(60deg) translateY(0); }
            100% { transform: perspective(500px) rotateX(60deg) translateY(60px); }
        }

        /* Partículas flutuantes */
        .particle {
            position: absolute;
            background: white;
            border-radius: 50%;
            opacity: 0.3;
            animation: floatUp linear infinite;
            z-index: -1;
            pointer-events: none;
        }
        
        @keyframes floatUp {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            50% { opacity: 0.5; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* --- CARD DE LOGIN --- */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-top: 1px solid rgba(255,255,255,0.2);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 0 20px rgba(255,255,255,0.02);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            z-index: 10;
        }

        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Cabeçalho */
        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .logo-box {
            width: 80px; height: 80px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            border: 1px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
        }
        .logo-icon {
            font-size: 2.5rem;
            color: var(--primary-glow);
        }
        .brand-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 2px;
            margin: 0;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand-subtitle {
            font-size: 0.8rem;
            color: #94a3b8;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* Inputs */
        .input-group {
            background: rgba(2, 6, 23, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            margin-bottom: 20px;
        }
        .input-group:focus-within {
            border-color: var(--primary-glow);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
            background: rgba(2, 6, 23, 0.9);
        }
        .input-group-text {
            background: transparent;
            border: none;
            color: #64748b;
            padding-left: 15px;
        }
        .form-control {
            background: transparent;
            border: none;
            color: #fff;
            padding: 15px 10px;
            font-size: 0.95rem;
        }
        .form-control:focus {
            background: transparent;
            box-shadow: none;
            color: #fff;
        }
        /* Fix Autocomplete */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0b1120 inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .input-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            display: block;
            margin-left: 4px;
            font-family: 'Rajdhani', sans-serif;
            letter-spacing: 1px;
        }

        /* Botões */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-glow), var(--accent-glow));
            border: none;
            padding: 14px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
            width: 100%;
            border-radius: 12px;
            color: white;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            text-transform: uppercase;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
            background: linear-gradient(135deg, #4f46e5, #2563eb);
            color: white;
        }

        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.3s;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .btn-back:hover {
            color: var(--primary-glow);
            transform: translateY(2px);
        }

        /* Alerta */
        .alert-custom {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #fca5a5;
            font-size: 0.9rem;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

    </style>
</head>
<body>

    <div class="cyber-grid"></div>
    <div id="particles"></div>

    <div class="login-card">
        
        <div class="brand-header">
            <div class="logo-box">
                <i class="bi bi-cpu logo-icon"></i>
            </div>
            <h3 class="brand-title">VOXBLUE</h3>
            <p class="brand-subtitle">Portal Administrativo</p>
        </div>
        
        <?php if($erro): ?>
            <div class="alert-custom">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span><?= htmlspecialchars($erro) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div>
                <label class="input-label">IDENTIFICAÇÃO DE USUÁRIO</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-bounding-box"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Usuário do sistema" required autofocus autocomplete="username">
                </div>
            </div>

            <div>
                <label class="input-label">CHAVE DE ACESSO</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-fingerprint"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                Acessar Portal <i class="bi bi-chevron-right ms-1"></i>
            </button>
        </form>

        <a href="index.php" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i> Retornar à Tela Inicial
        </a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('particles');
            const particleCount = 25; // Quantidade de partículas

            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                
                // Posição e tamanho aleatórios
                const size = Math.random() * 3 + 1;
                p.style.width = `${size}px`;
                p.style.height = `${size}px`;
                p.style.left = `${Math.random() * 100}vw`;
                
                // Duração da animação aleatória
                const duration = Math.random() * 15 + 10;
                p.style.animationDuration = `${duration}s`;
                p.style.animationDelay = `-${Math.random() * 15}s`; // Delay negativo para já começar na tela
                
                container.appendChild(p);
            }
        });
    </script>

</body>
</html>