# Arquivo: server.py
import time
import threading
import requests
import mysql.connector
import re
import socket
from datetime import datetime
from flask import Flask, jsonify
from flask_cors import CORS

# --- CONFIGURAÇÕES ---
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'n3tware385br', 
    'database': 'netmaxxi_callcenter'
}

# ARI (Para Canais e Ramais)
ARI_URL = "http://127.0.0.1:8088/ari"
ARI_AUTH = ('admin', 'n3tware385br')

# AMI (Para Filas)
AMI_HOST = '127.0.0.1'
AMI_PORT = 5038
AMI_USER = 'php_dashboard'
AMI_PASS = 'senha_segura_ami'

app = Flask(__name__)
CORS(app)

CACHE = {
    'ramais': [],
    'troncos': {'total': 0, 'lista': []},
    'filas': []
}

def log(msg):
    print(f"[{datetime.now().strftime('%H:%M:%S')}] {msg}")

def get_db():
    try: return mysql.connector.connect(**DB_CONFIG)
    except: return None

def clean_num(val):
    if not val: return ""
    return ''.join(filter(str.isdigit, str(val)))

# --- FUNÇÃO DE FILAS (AMI) RESTAURADA ---
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
            if b"EventList: Complete" in buffer: break
        
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
                    'tma': data.get('HoldTime', '0'),
                    'abandonadas': data.get('Abandoned', '0'),
                    'completo': data.get('Completed', '0')
                })
    except:
        pass
    return filas_data

def update_loop():
    global CACHE
    log("Servidor V9: Full Stack (Ramais + Filas + Troncos + Internas)...")
    
    while True:
        try:
            # 1. Carrega Dados do Banco
            ramais_db = {}
            filas_map = {}
            troncos_allowlist = []
            
            conn = get_db()
            if conn:
                try:
                    cursor = conn.cursor(dictionary=True)
                    # Ramais
                    cursor.execute("SELECT id, description FROM asterisk.devices")
                    for r in cursor.fetchall(): ramais_db[str(r['id'])] = r['description']
                    
                    # Filas
                    cursor.execute("SELECT extension, descr FROM asterisk.queues_config")
                    for r in cursor.fetchall(): filas_map[str(r['extension'])] = r['descr']

                    # Troncos
                    cursor.execute("SELECT name, channelid FROM asterisk.trunks WHERE disabled = 'off'")
                    for r in cursor.fetchall():
                        if r['name']: troncos_allowlist.append(str(r['name']).strip().lower())
                        if r['channelid']: troncos_allowlist.append(str(r['channelid']).strip().lower())
                    
                    conn.close()
                except: pass
            
            ramais_ids = set(ramais_db.keys())
            ramais_ids.update(['s', 'admin', 'operator'])

            # 2. ARI Requests
            try:
                channels = requests.get(f"{ARI_URL}/channels", auth=ARI_AUTH, timeout=2).json()
                endpoints = requests.get(f"{ARI_URL}/endpoints", auth=ARI_AUTH, timeout=2).json()
            except:
                channels = []
                endpoints = []

            # 3. Processamento de Chamadas
            troncos_detalhes = []
            ramais_stats = {} 
            processed_ids = set()

            # Mapa Dialplan (para chamadas saintes)
            map_discado = {}
            for ch in channels:
                linked_id = ch.get('linkedid', ch.get('id'))
                exten = ch.get('dialplan', {}).get('exten', '')
                if exten and exten not in ['s', 'h'] and len(exten) > 3:
                    map_discado[linked_id] = exten

            for ch in channels:
                name = ch.get('name', '')
                state = ch.get('state', '')
                linked_id = ch.get('linkedid', ch.get('id'))
                creation_time = ch.get('creationtime', '')
                
                # Limpeza de números
                c_raw = ch.get('caller', {}).get('number', '')
                d_raw = ch.get('connected', {}).get('number', '')
                c_clean = clean_num(c_raw)
                d_clean = clean_num(d_raw)

                # Identifica Recurso
                match = re.search(r'/(.+?)-', name)
                res_name = match.group(1) if match else name
                if '/' in res_name: res_name = res_name.split('/')[1]
                
                # --- É CHAMADA DE RAMAL? ---
                if res_name in ramais_ids:
                    falando_com = "..."
                    
                    # Verifica se é RAMAL x RAMAL
                    if c_clean in ramais_ids and d_clean in ramais_ids:
                        # Se eu sou o caller, falo com o connected
                        if c_clean == res_name: falando_com = "Ramal " + d_clean
                        else: falando_com = "Ramal " + c_clean
                    else:
                        # Ramal x Externo
                        # Tenta pegar do dialplan primeiro
                        discado = map_discado.get(linked_id)
                        if discado: falando_com = discado
                        else:
                             # Pega o maior número que não seja eu
                             nums = [x for x in [c_clean, d_clean] if x != res_name and len(x) > 3]
                             if nums: falando_com = max(nums, key=len)
                    
                    ramais_stats[res_name] = {
                        'status': 'FALANDO' if state == 'Up' else 'CHAMANDO',
                        'with': falando_com,
                        'inicio': creation_time
                    }
                
                # --- É TRONCO? ---
                else:
                    # Filtra lixo
                    if any(x in name.lower() for x in ['rec', 'announc', 'local/', 'snoop']): continue
                    
                    # Se não é ramal e não é lixo, é tronco
                    if linked_id in processed_ids: continue
                    processed_ids.add(linked_id)

                    destino_final = "---"
                    
                    # 1. Checa se é Ramal x Ramal (Não deve aparecer em Troncos)
                    if c_clean in ramais_ids and d_clean in ramais_ids:
                        continue # Pula, não é tronco

                    # 2. Define Destino Externo
                    discado = map_discado.get(linked_id)
                    if discado: 
                        destino_final = discado
                    else:
                        # Pega o número que não é ramal
                        candidates = [x for x in [c_clean, d_clean] if x and x not in ramais_ids]
                        if candidates: destino_final = max(candidates, key=len)
                        else: destino_final = "Externo"

                    nome_tronco = res_name.upper()
                    if clean_num(nome_tronco) == clean_num(destino_final): nome_tronco = "TRONCO / SAÍDA"

                    troncos_detalhes.append({
                        'nome': nome_tronco,
                        'canal': name,
                        'status': state,
                        'destino': destino_final,
                        'inicio': creation_time
                    })

            # 4. Lista de Ramais Final
            lista_ramais = []
            status_ari = {ep['resource']: 'in_use' if ep.get('channel_ids') else ep['state'] 
                          for ep in endpoints if ep['technology'] in ['PJSIP','SIP']}

            for user, desc in ramais_db.items():
                st_raw = status_ari.get(user, 'offline')
                active_data = ramais_stats.get(user)
                
                txt = 'OFFLINE'; css = 'st-off'; did = ""; inicio = ""
                
                if st_raw == 'online': 
                    txt = 'LIVRE'; css = 'st-free'
                
                if active_data:
                    txt = active_data['status']
                    css = 'st-busy'
                    did = active_data['with']
                    inicio = active_data['inicio']
                elif st_raw == 'in_use':
                    txt = 'OCUPADO'; css = 'st-busy'

                lista_ramais.append({
                    'user': user, 'nome': desc,
                    'status': txt, 'css_class': css,
                    'did': did, 'inicio': inicio
                })
            
            lista_ramais.sort(key=lambda x: int(x['user']) if x['user'].isdigit() else x['user'])

            # 5. Filas (AMI)
            lista_filas = get_ami_queues(filas_map)

            CACHE = {
                'ramais': lista_ramais,
                'troncos': {'total': len(troncos_detalhes), 'lista': troncos_detalhes},
                'filas': lista_filas
            }
            time.sleep(1)

        except Exception as e:
            log(f"Erro: {e}")
            time.sleep(2)

@app.route('/stats')
def stats():
    return jsonify(CACHE)

if __name__ == '__main__':
    t = threading.Thread(target=update_loop)
    t.daemon = True
    t.start()
    app.run(host='0.0.0.0', port=5000, debug=False)