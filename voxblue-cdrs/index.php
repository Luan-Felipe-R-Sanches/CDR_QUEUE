<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Em Desenvolvimento - VoxBlue</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #020617 0%, #0f172a 100%);
            --accent: #3b82f6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
        }

        /* Fundo com formas suaves */
        .bg-shape {
            position: absolute;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: pulse 8s infinite alternate;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: #3b82f6;
            top: -100px;
            left: -100px;
            border-radius: 50%;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: #8b5cf6;
            bottom: -50px;
            right: -50px;
            border-radius: 50%;
            animation-delay: 2s;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }

            100% {
                transform: scale(1.1);
                opacity: 0.5;
            }
        }

        /* Cartão Central */
        .dev-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 60px 40px;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        /* Linha de destaque no topo */
        .dev-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: var(--accent);
            font-size: 2.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            animation: floatIcon 6s ease-in-out infinite;
        }

        @keyframes floatIcon {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        h1 {
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 15px;
        }

        p {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-back {
            background: white;
            color: #0f172a;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.15);
            background: #f1f5f9;
        }

        .progress-bar-container {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 70%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 10px;
            animation: loading 2s infinite ease-in-out;
            background-size: 200% 100%;
        }

        @keyframes loading {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }
    </style>
</head>

<body>

    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="dev-card">
        <div class="icon-wrapper">
            <i class="bi bi-rocket-takeoff-fill"></i>
        </div>

        <h1>Em Construção</h1>

        <div class="progress-bar-container">
            <div class="progress-bar-fill"></div>
        </div>

        <p>
            Estamos preparando algo incrível para você.<br>
            Este módulo está sendo desenvolvido com as melhores tecnologias para otimizar sua operação.
        </p>

        <a href="../../portal.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Voltar ao Portal
        </a>
    </div>

</body>

</html>