# Arquivo: server.py (OTIMIZADO)
import time
import threading
import requests
import mysql.connector
import re
import socket
from datetime import datetime
from flask import Flask, jsonify, request
from flask_cors import CORS
import os
from dotenv import load_dotenv

# --- CONFIGURAÇÕES CARREGADAS DO .ENV ---
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASS'),
    'database': os.getenv('DB_NAME')
}

# ARI
ARI_URL = os.getenv('ARI_URL')
ARI_AUTH = (os.getenv('ARI_USER'), os.getenv('ARI_PASS'))

# AMI
AMI_HOST = os.getenv('AMI_HOST', '127.0.0.1')
AMI_PORT = int(os.getenv('AMI_PORT', 5038))
AMI_USER = os.getenv('AMI_USER')
AMI_PASS = os.getenv('AMI_PASS')

app = Flask(__name__)
CORS(app)

# Estado Global
CACHE = {
    'ramais': [],
    'troncos': {'total': 0, 'lista': []},
    'filas': []
}

# Dados estáticos (atualizados raramente)
STATIC_DATA = {
    'ramais_db': {},
    'filas_map': {},
    'troncos_allowlist': [],
    'last_update': 0
}

def log(msg):
    print(f"[{datetime.now().strftime('%H:%M:%S')}] {msg}")

def get_db():
    try: return mysql.connector.connect(**DB_CONFIG)
    except: return None

def clean_num(val):
    if not val: return ""
    return ''.join(filter(str.isdigit, str(val)))

def refresh_static_data():
    """ Carrega dados do banco apenas a cada 60s para economizar recursos """
    now = time.time()
    if now - STATIC_DATA['last_update'] < 60 and STATIC_DATA['last_update'] > 0:
        return

    try:
        conn = get_db()
        if conn:
            cursor = conn.cursor(dictionary=True)
            
            # Ramais
            cursor.execute("SELECT id, description FROM asterisk.devices")
            STATIC_DATA['ramais_db'] = {str(r['id']): r['description'] for r in cursor.fetchall()}
            
            # Filas
            cursor.execute("SELECT extension, descr FROM asterisk.queues_config")
            STATIC_DATA['filas_map'] = {str(r['extension']): r['descr'] for r in cursor.fetchall()}

            # Troncos
            STATIC_DATA['troncos_allowlist'] = []
            cursor.execute("SELECT name FROM asterisk.trunks WHERE disabled = 'off'")
            for r in cursor.fetchall():
                if r['name']: STATIC_DATA['troncos_allowlist'].append(str(r['name']).strip().lower())
            
            conn.close()
            STATIC_DATA['last_update'] = now
            # log("Dados estáticos (DB) atualizados.")
    except Exception as e:
        log(f"Erro ao atualizar DB: {e}")

def get_ami_queues():
    filas_data = []
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(1) # Timeout baixo para não travar
        sock.connect((AMI_HOST, AMI_PORT))
        sock.sendall(f"Action: Login\r\nUsername: {AMI_USER}\r\nSecret: {AMI_PASS}\r\n\r\n".encode())
        time.sleep(0.05)
        sock.sendall(b"Action: QueueSummary\r\n\r\n")
        
        buffer = b""
        start = time.time()
        while time.time() - start < 1.5:
            chunk = sock.recv(4096)
            buffer += chunk
            if b"EventList: Complete" in buffer: break
        sock.close()
        
        texto = buffer.decode('utf-8', errors='ignore')
        for evt in texto.split('Event: QueueSummary'):
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
                    'nome': STATIC_DATA['filas_map'].get(q_num, f"Fila {q_num}"),
                    'logados': data.get('LoggedIn', '0'),
                    'espera': data.get('Callers', '0'),
                    'tma': data.get('HoldTime', '0'),
                    'abandonadas': data.get('Abandoned', '0'),
                    'completo': data.get('Completed', '0')
                })
    except: pass
    return filas_data

def update_loop():
    global CACHE
    log("Servidor OTIMIZADO V10 Iniciado...")
    
    while True:
        loop_start = time.time()
        try:
            refresh_static_data() # Verifica se precisa recarregar DB
            
            ramais_db = STATIC_DATA['ramais_db']
            ramais_ids = set(ramais_db.keys())
            ramais_ids.update(['s', 'admin', 'operator'])

            # ARI Requests (Rápido)
            try:
                channels = requests.get(f"{ARI_URL}/channels", auth=ARI_AUTH, timeout=1).json()
                endpoints = requests.get(f"{ARI_URL}/endpoints", auth=ARI_AUTH, timeout=1).json()
            except:
                channels = []; endpoints = []

            # --- PROCESSAMENTO ---
            troncos_detalhes = []
            ramais_stats = {} 
            processed_ids = set()
            map_discado = {}

            # Pré-processamento O(n)
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
                
                # Ignora canais irrelevantes para performance
                if any(x in name.lower() for x in ['rec', 'announc', 'local/', 'snoop']): continue

                # Identifica Recurso
                match = re.search(r'/(.+?)-', name)
                res_name = match.group(1) if match else name
                if '/' in res_name: res_name = res_name.split('/')[1]
                
                # Ramal
                if res_name in ramais_ids:
                    c_clean = clean_num(ch.get('caller', {}).get('number', ''))
                    d_clean = clean_num(ch.get('connected', {}).get('number', ''))
                    
                    falando_com = "..."
                    # Lógica simplificada de destino para ramal
                    discado = map_discado.get(linked_id)
                    if discado: falando_com = discado
                    else:
                        nums = [x for x in [c_clean, d_clean] if x != res_name and len(x) > 3]
                        if nums: falando_com = max(nums, key=len)

                    ramais_stats[res_name] = {
                        'status': 'FALANDO' if state == 'Up' else 'CHAMANDO',
                        'with': falando_com,
                        'inicio': creation_time
                    }
                
                # Tronco
                else:
                    if linked_id in processed_ids: continue
                    
                    c_clean = clean_num(ch.get('caller', {}).get('number', ''))
                    d_clean = clean_num(ch.get('connected', {}).get('number', ''))
                    
                    # Se for interna entre ramais conhecidos, ignora como tronco
                    if c_clean in ramais_ids and d_clean in ramais_ids: continue
                    
                    processed_ids.add(linked_id)
                    
                    destino_final = map_discado.get(linked_id, "Externo")
                    if destino_final == "Externo":
                         candidates = [x for x in [c_clean, d_clean] if x and x not in ramais_ids]
                         if candidates: destino_final = max(candidates, key=len)

                    nome_tronco = res_name.upper()
                    if clean_num(nome_tronco) == clean_num(destino_final): nome_tronco = "TRONCO / SAÍDA"

                    troncos_detalhes.append({
                        'nome': nome_tronco,
                        'canal': name,
                        'status': state,
                        'destino': destino_final,
                        'inicio': creation_time
                    })

            # Montagem Final Ramais
            lista_ramais = []
            # Transforma lista de endpoints em dict para acesso O(1)
            status_ari = {ep['resource']: 'in_use' if ep.get('channel_ids') else ep['state'] 
                          for ep in endpoints if ep['technology'] in ['PJSIP','SIP']}

            for user, desc in ramais_db.items():
                st_raw = status_ari.get(user, 'offline')
                active = ramais_stats.get(user)
                
                txt = 'OFFLINE'; css = 'st-off'; did = ""; inicio = ""
                
                if st_raw == 'online': txt, css = 'LIVRE', 'st-free'
                
                if active:
                    txt = active['status']; css = 'st-busy'
                    did = active['with']; inicio = active['inicio']
                elif st_raw == 'in_use':
                    txt = 'OCUPADO'; css = 'st-busy'

                lista_ramais.append({
                    'user': user, 'nome': desc,
                    'status': txt, 'css_class': css,
                    'did': did, 'inicio': inicio
                })
            
            # Ordenação leve
            lista_ramais.sort(key=lambda x: int(x['user']) if x['user'].isdigit() else x['user'])

            # Atualiza Cache
            CACHE = {
                'ramais': lista_ramais,
                'troncos': {'total': len(troncos_detalhes), 'lista': troncos_detalhes},
                'filas': get_ami_queues()
            }
            
            # Sleep dinâmico para manter ~1 segundo de ciclo preciso
            elapsed = time.time() - loop_start
            sleep_time = max(0.1, 1.0 - elapsed)
            time.sleep(sleep_time)

        except Exception as e:
            log(f"Erro Fatal Loop: {e}")
            time.sleep(2)

@app.route('/stats')
def stats():
    return jsonify(CACHE)

if __name__ == '__main__':
    t = threading.Thread(target=update_loop)
    t.daemon = True
    t.start()
    app.run(host='0.0.0.0', port=5000, debug=False, threaded=True)