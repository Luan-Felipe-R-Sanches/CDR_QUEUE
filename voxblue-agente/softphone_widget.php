<style>
    /* Estilo do Botão Flutuante */
    #vox-extension-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 999999;
    }
    .btn-vox {
        width: 60px;
        height: 60px;
        background-color: #28a745;
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 26px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s ease;
    }
    .btn-vox:hover { transform: scale(1.1); background-color: #218838; }
</style>

<div id="vox-extension-btn">
    <button class="btn-vox" onclick="abrirSoftphoneVox()" title="Abrir Telefone">📞</button>
</div>

<script>
function abrirSoftphoneVox() {
    const host = window.location.hostname;
    
    // ATENÇÃO: Ajuste este caminho para onde a pasta "Phone" está no seu servidor
    const urlPhone = 'Phone/index.html'; 
    const urlSSL = `https://${host}:8089/ws`;

    // 1. Abrimos o Telefone PRIMEIRO para garantir que o navegador não bloqueie
    const largura = 360;
    const altura = 580;
    const esquerda = (window.screen.width - largura - 30);
    const topo = (window.screen.height - altura - 100);
    const specs = `width=${largura},height=${altura},left=${esquerda},top=${topo},menubar=no,status=no,toolbar=no,location=no,resizable=no,scrollbars=no`;

    const winPhone = window.open(urlPhone, 'SoftphoneVoxBlue', specs);

    if (!winPhone) {
        alert('❌ Erro: Popup bloqueado! Clique no ícone de "X" na barra de endereços e escolha "Sempre permitir popups".');
        return;
    }

    // 2. Abrimos a validação SSL em uma janela secundária "escondida"
    // Ela vai abrir e você a fecha manualmente se for a primeira vez (para aceitar o certificado)
    // Depois da primeira vez, ela fechará automaticamente pelo script
    const winSSL = window.open(urlSSL, 'SSL_Helper', 'width=100,height=100,left=0,top=0');
    
    if (winSSL) {
        setTimeout(() => {
            winSSL.close();
            winPhone.focus();
        }, 3000); // 3 segundos para o handshake SSL
    }
}
</script>