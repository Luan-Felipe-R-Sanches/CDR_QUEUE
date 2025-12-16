<?php
// Arquivo: /var/www/html/index.php
session_start();

// Se o utilizador JÁ estiver logado na raiz, redireciona para o portal
if (isset($_SESSION['vox_user'])) {
    header('Location: portal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOXBLUE | Acesso ao Sistema</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Rajdhani:wght@500;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #030712;
            --card-glass: rgba(15, 23, 42, 0.6);
            --border-glass: rgba(255, 255, 255, 0.08);
            --neon-blue: #3b82f6;
            --neon-cyan: #06b6d4;
            --neon-purple: #6366f1;
        }

        body {
            background-color: var(--bg-color);
            color: white;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
            perspective: 1000px; /* Para efeito 3D */
        }

        /* --- BACKGROUND ANIMADO (CYBER GRID) --- */
        .cyber-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            bottom: -50%;
            left: -50%;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            background-position: center center;
            transform: perspective(500px) rotateX(60deg);
            animation: moveGrid 5s linear infinite;
            z-index: -2;
            mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 80%);
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,1) 0%, transparent 80%);
        }

        @keyframes moveGrid {
            0% { transform: perspective(500px) rotateX(60deg) translateY(0); }
            100% { transform: perspective(500px) rotateX(60deg) translateY(60px); }
        }

        /* Particles */
        .particle {
            position: absolute;
            background: white;
            border-radius: 50%;
            opacity: 0.3;
            animation: floatUp linear infinite;
            z-index: -1;
        }
        
        @keyframes floatUp {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            50% { opacity: 0.5; }
            100% { transform: translateY(-100px) scale(1); opacity: 0; }
        }

        /* --- CONTEÚDO PRINCIPAL --- */
        .main-content {
            z-index: 10;
            width: 100%;
            max-width: 1100px;
            padding: 0 20px;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
        }

        .brand-text {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 800;
            font-size: 5rem;
            text-transform: uppercase;
            letter-spacing: 4px;
            background: linear-gradient(90deg, #fff 20%, #94a3b8 50%, #fff 80%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shineText 5s linear infinite;
            text-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
        }

        .brand-tagline {
            color: #94a3b8;
            font-size: 1rem;
            letter-spacing: 3px;
            margin-top: -10px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600;
        }

        @keyframes shineText { to { background-position: 200% center; } }

        /* --- CARTÕES (GLASSMORPHISM) --- */
        .card-container {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .glass-card {
            width: 380px;
            background: var(--card-glass);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 40px;
            text-decoration: none;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Efeito de brilho ao passar */
        .glass-card::before {
            content: "";
            position: absolute;
            top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.1), transparent);
            transform: skewX(-25deg);
            transition: 0.5s;
        }
        .glass-card:hover::before { left: 150%; }

        /* Estilos específicos ADMIN */
        .card-admin { border-top: 1px solid rgba(99, 102, 241, 0.3); }
        .card-admin:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.2);
            border-color: var(--neon-purple);
        }
        .card-admin .icon-circle { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.05)); color: var(--neon-purple); }
        
        /* Estilos específicos AGENTE */
        .card-agent { border-top: 1px solid rgba(6, 182, 212, 0.3); }
        .card-agent:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(6, 182, 212, 0.2);
            border-color: var(--neon-cyan);
        }
        .card-agent .icon-circle { background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05)); color: var(--neon-cyan); }

        .icon-circle {
            width: 90px; height: 90px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.4s;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.2);
        }

        .glass-card:hover .icon-circle { transform: scale(1.1) rotate(5deg); box-shadow: inset 0 0 30px rgba(255,255,255,0.1); }

        .card-title { font-family: 'Rajdhani', sans-serif; font-size: 2rem; font-weight: 700; margin-bottom: 10px; }
        .card-text { font-size: 0.95rem; color: #94a3b8; line-height: 1.6; margin-bottom: 30px; min-height: 48px; }

        .action-btn {
            padding: 12px 30px;
            border-radius: 50px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .card-admin:hover .action-btn { background: var(--neon-purple); border-color: var(--neon-purple); box-shadow: 0 0 20px rgba(99, 102, 241, 0.4); }
        .card-agent:hover .action-btn { background: var(--neon-cyan); border-color: var(--neon-cyan); box-shadow: 0 0 20px rgba(6, 182, 212, 0.4); }

        footer { position: fixed; bottom: 20px; font-size: 0.8rem; color: rgba(255,255,255,0.2); letter-spacing: 1px; width: 100%; text-align: center; z-index: 10; }

        @media (max-width: 768px) {
            .brand-text { font-size: 3.5rem; }
            .glass-card { width: 100%; max-width: 350px; margin-bottom: 20px; }
            .cyber-grid { display: none; } /* Poupa bateria em mobile */
        }
    </style>
</head>
<body>

    <div class="cyber-grid"></div>
    <div id="particles"></div>

    <div class="main-content">
        
        <div class="logo-container">
            <div class="brand-text">VOXBLUE</div>
            <div class="brand-tagline">Intelligence Suite</div>
        </div>

        <div class="card-container">
            
            <a href="login.php" class="glass-card card-admin">
                <div class="icon-circle">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="card-title">GESTOR</div>
                <div class="card-text">
                    Acesso administrativo, dashboards,<br>monitorização em tempo real e relatórios.
                </div>
                <div class="action-btn">
                    Entrar no Portal <i class="bi bi-arrow-right ms-2"></i>
                </div>
            </a>

            <a href="voxblue-agente/" class="glass-card card-agent">
                <div class="icon-circle">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="card-title">AGENTE</div>
                <div class="card-text">
                    Workspace operacional, gestão de chamadas,<br>pausas e histórico pessoal.
                </div>
                <div class="action-btn">
                    Entrar no Painel <i class="bi bi-arrow-right ms-2"></i>
                </div>
            </a>

        </div>

    </div>

    <footer>
        &copy; <?= date('Y') ?> VOXBLUE SOLUTIONS
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                
                // Posição e tamanho aleatórios
                const size = Math.random() * 3 + 1;
                p.style.width = `${size}px`;
                p.style.height = `${size}px`;
                p.style.left = `${Math.random() * 100}vw`;
                
                // Duração da animação aleatória
                const duration = Math.random() * 10 + 5;
                p.style.animationDuration = `${duration}s`;
                p.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(p);
            }
        });
    </script>
</body>
</html>