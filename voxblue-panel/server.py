# Arquivo: server.py
import time
import threading
import requests
import mysql.connector
import re
import socket
from flask import Flask, jsonify
from flask_cors import CORS

# --- CONFIGURAÇÕES ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'n3tware385br', # <--- SUA SENHA MYSQL
    'database': 'netmaxxi_callcenter'
}

# ARI
ARI_URL = "http://127.0.0.1:8088/ari"
ARI_AUTH = ('admin', 'n3tware385br')

# AMI
AMI_HOST = '127.0.0.1'
AMI_PORT = 5038
AMI_USER = 'php_dashboard'
AMI_PASS = 'senha_segura_ami'

app = Flask(__name__)
CORS(app)

CACHE = {
    'ramais': [],
    'troncos': {'total': 0, 'lista': []},
    'filas': [],
    'timestamp': 0
}

# --- FUNÇÃO: LOG NO TERMINAL ---
def log(msg):
    print(f"[VoxBlue] {msg}")

# --- FUNÇÃO: CONSULTA AMI NATIVA (FILAS) ---
def get_ami_queues(nomes_filas):
    filas_data = []
    sock = None
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(2)
        sock.connect((AMI_HOST, AMI_PORT))
        
        sock.sendall(f"Action: Login\r\nUsername: {AMI_USER}\r\nSecret: {AMI_PASS}\r\n\r\n".encode())
        time.sleep(0.1)
        sock.sendall(b"Action: QueueSummary\r\n\r\n")
        
        buffer = b""
        start = time.time()
        while time.time() - start < 2:
            chunk = sock.recv(4096)
            buffer += chunk
            if b"EventList: Complete" in buffer or b"Authentication failed" in buffer:
                break
        
        sock.sendall(b"Action: Logoff\r\n\r\n")
        sock.close()
        
        texto = buffer.decode('utf-8', errors='ignore')
        eventos = texto.split('Event: QueueSummary')
        
        for evt in eventos:
            if 'Queue:' not in evt: continue
            data = {}
            for line in evt.splitlines():
                if ': ' in line:
                    k, v = line.split(': ', 1)
                    data[k.strip()] = v.strip()
            
            q_num = data.get('Queue')
            if q_num and q_num != 'default':
                filas_data.append({
                    'numero': q_num,
                    'nome': nomes_filas.get(q_num, f"Fila {q_num}"),
                    'logados': data.get('LoggedIn', '0'),
                    'espera': data.get('Callers', '0'),
                    'tma': data.get('HoldTime', '0')
                })
                
    except Exception as e:
        # Silencioso para não poluir terminal se AMI cair
        pass
        
    return filas_data

def get_db():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except Exception as e:
        log(f"Erro Conexão Banco: {e}")
        return None

# --- WORKER ---
def update_loop():
    global CACHE
    log("Iniciando Loop de Monitoramento...")
    
    while True:
        try:
            conn = get_db()
            if not conn:
                time.sleep(5)
                continue

            cursor = conn.cursor(dictionary=True)
            
            # 1. Carregar Ramais
            cursor.execute("SELECT id, description FROM asterisk.devices")
            ramais_db = {str(r['id']): r['description'] for r in cursor.fetchall()}
            
            # 2. Carregar Troncos (CRÍTICO: Lista de nomes permitidos)
            cursor.execute("SELECT name, channelid FROM asterisk.trunks WHERE disabled = 'off'")
            troncos_allowlist = []
            for r in cursor.fetchall():
                if r['name']: troncos_allowlist.append(str(r['name']).strip())
                if r['channelid']: troncos_allowlist.append(str(r['channelid']).strip())
            
            # Remove duplicados
            troncos_allowlist = list(set(troncos_allowlist))
            
            # 3. Carregar Filas
            cursor.execute("SELECT extension, descr FROM asterisk.queues_config")
            filas_map = {str(r['extension']): r['descr'] for r in cursor.fetchall()}
            
            conn.close()

            # 4. ARI Requests
            try:
                ch_req = requests.get(f"{ARI_URL}/channels", auth=ARI_AUTH, timeout=2)
                channels = ch_req.json()
                ep_req = requests.get(f"{ARI_URL}/endpoints", auth=ARI_AUTH, timeout=2)
                endpoints = ep_req.json()
            except:
                channels = []
                endpoints = []

            # 5. Processamento
            troncos_detalhes = []
            mapa_dids = {}

            # DEBUG: Mostra o que veio do ARI se tiver canal ativo
            if len(channels) > 0:
                # log(f"Canais Ativos: {len(channels)}")
                pass

            for ch in channels:
                name = ch.get('name', '') # Ex: PJSIP/22222122-00003
                state = ch.get('state', '')
                caller_num = ch.get('caller', {}).get('number', '')
                connected_num = ch.get('connected', {}).get('number', '')

                # --- LÓGICA DE TRONCO MELHORADA ---
                is_tronco = False
                
                # Tenta extrair o "recurso" do canal. Ex: de PJSIP/2222-01 pega "2222"
                # Regex: Pega tudo entre a primeira barra / e o ultimo traço -
                match_resource = re.search(r'/(.+)-', name)
                
                if match_resource:
                    resource_name = match_resource.group(1) # Ex: 22222122
                    
                    # Verifica se esse recurso está na nossa lista do banco
                    if resource_name in troncos_allowlist:
                        is_tronco = True
                    else:
                        # Backup: Procura substring (para casos como PJSIP/TroncoVivo/1199...)
                        for t in troncos_allowlist:
                            if t in name:
                                is_tronco = True
                                break
                
                if is_tronco:
                    # Descobrir quem está usando (Ramal ou Destino)
                    uso = "Conectando..."
                    if len(caller_num) < 6 and caller_num != "": uso = f"Ramal {caller_num}"
                    elif len(connected_num) < 6 and connected_num != "": uso = f"Ramal {connected_num}"
                    else:
                        # Se for chamada sainte, pega o numero longo
                        if len(connected_num) > 6: uso = f"L: {connected_num}"
                        elif len(caller_num) > 6: uso = f"R: {caller_num}"

                    troncos_detalhes.append({
                        'nome': resource_name if match_resource else name,
                        'canal': name,
                        'status': state,
                        'uso': uso
                    })
                
                # --- LÓGICA DE DID (RAMAIS) ---
                # Se não é tronco, deve ser ramal
                if not is_tronco:
                    match_ramal = re.search(r'/(?P<ramal>\d+)-', name)
                    if match_ramal:
                        ramal = match_ramal.group('ramal')
                        did = ''
                        # Prioridade para achar o numero externo
                        if connected_num and len(connected_num) > 5: did = connected_num
                        elif caller_num and len(caller_num) > 5: did = caller_num
                        
                        if did: mapa_dids[ramal] = did

            # 6. Monta Lista de Ramais
            lista_ramais = []
            status_ari = {}
            
            # Mapeia endpoints ARI
            for ep in endpoints:
                if ep['technology'] in ['PJSIP', 'SIP', 'IAX2']:
                    res = ep['resource']
                    st = ep['state']
                    if len(ep.get('channel_ids', [])) > 0: st = 'in_use'
                    status_ari[res] = st

            for user, nome_desc in ramais_db.items():
                raw_state = status_ari.get(user, 'offline')
                did_val = mapa_dids.get(user, '')
                
                st_text = 'OFFLINE'; css = 'st-off'
                
                if raw_state == 'online': 
                    st_text = 'LIVRE'; css = 'st-free'
                elif raw_state == 'in_use': 
                    st_text = 'FALANDO'; css = 'st-busy'
                    if not did_val: did_val = "Em Linha"
                
                if did_val:
                    st_text = 'FALANDO'; css = 'st-busy'

                lista_ramais.append({
                    'user': user,
                    'nome': nome_desc,
                    'status_text': st_text,
                    'css_class': css,
                    'did': did_val
                })
            
            lista_ramais.sort(key=lambda x: int(x['user']) if x['user'].isdigit() else x['user'])

            # 7. Filas via AMI
            lista_filas = get_ami_queues(filas_map)

            # ATUALIZA CACHE
            CACHE = {
                'ramais': lista_ramais,
                'troncos': {'total': len(troncos_detalhes), 'lista': troncos_detalhes},
                'filas': lista_filas
            }
            
            time.sleep(1)

        except Exception as e:
            log(f"ERRO FATAL NO LOOP: {e}")
            time.sleep(5)

@app.route('/stats')
def stats():
    return jsonify(CACHE)

if __name__ == '__main__':
    print("------------------------------------------------")
    print("VOXBLUE PYTHON SERVER INICIADO")
    print(f"Banco: {DB_CONFIG['host']}")
    print(f"ARI: {ARI_URL}")
    print("Aguardando dados...")
    print("------------------------------------------------")
    
    t = threading.Thread(target=update_loop)
    t.daemon = True
    t.start()
    
    app.run(host='0.0.0.0', port=5000, debug=False)