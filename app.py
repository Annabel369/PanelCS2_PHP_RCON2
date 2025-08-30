# app.py - Agente Python para gerenciar o servidor CS2 no Windows

from flask import Flask, jsonify, request
import subprocess
import logging
import os
import psutil
import time

# O CS2 não usa o protocolo RCON do Minecraft.
# Portanto, a biblioteca 'mcrcon' deve ser removida ou substituída.
# Vamos remover esta importação, pois o controle de console do CS2
# pode ser feito de forma diferente ou desnecessário para o status.
# from mcrcon import MCRcon

# Importa as configurações do arquivo config.py.
from config import (
    RCON_HOST, RCON_PORT, RCON_PASSWORD,
    CS2_START_COMMAND, AGENT_PORT,
    CS2_SERVER_DIR
)

# Configuração do logging.
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Cria a instância do aplicativo Flask.
app = Flask(__name__)
server_process = None

# --- Funções para Gerenciamento de Processos no Windows ---

def find_cs2_process():
    """
    Tenta encontrar o processo do servidor CS2 em execução.
    Ele procura por um processo chamado 'cs2.exe'.
    Retorna o objeto do processo (psutil) se encontrado, caso contrário, retorna None.
    """
    for proc in psutil.process_iter(['pid', 'name']):
        try:
            # Verifica se o nome do processo é 'cs2.exe' (case-insensitive)
            if 'cs2.exe' == proc.info['name'].lower():
                logging.info(f"Processo do servidor encontrado: PID {proc.info['pid']}")
                return proc
        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            pass
    return None

# A função is_rcon_ready() foi removida, pois ela é específica do protocolo
# RCON do Minecraft e não se aplica ao CS2 da mesma forma.
# A rota rcon_command também será removida ou ajustada.

# --- Rotas (Endpoints) para o Agente ---

@app.route('/start_server', methods=['POST'])
def start_server():
    """
    Endpoint para iniciar o servidor CS2.
    """
    if find_cs2_process():
        return jsonify({'success': False, 'error': 'O servidor já está rodando.'}), 409

    try:
        logging.info(f"Tentando iniciar servidor com comando: {CS2_START_COMMAND}")
        
        server_process = subprocess.Popen(
            CS2_START_COMMAND,
            cwd=CS2_SERVER_DIR,
            shell=True,
            creationflags=subprocess.CREATE_NO_WINDOW
        )
        time.sleep(2)
        return jsonify({'success': True, 'message': 'Servidor iniciado com sucesso.'})
    except Exception as e:
        logging.error(f"Erro inesperado ao iniciar servidor: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/stop_server', methods=['POST'])
def stop_server():
    """
    Endpoint para parar o servidor CS2.
    """
    process = find_cs2_process()
    if not process:
        return jsonify({'success': False, 'error': 'O servidor não está rodando.'}), 404

    try:
        logging.info(f"Parando processo do servidor com PID: {process.pid}")
        process.terminate()
        process.wait(timeout=10)
        return jsonify({'success': True, 'message': 'Servidor parado com sucesso.'})
    except Exception as e:
        logging.error(f"Erro ao parar o servidor: {e}")
        return jsonify({'success': False, 'error': 'Erro ao parar o servidor: ' + str(e)}), 500

@app.route('/server_status', methods=['GET'])
def server_status():
    """
    Endpoint para verificar o status do servidor CS2, incluindo uso de RAM.
    """
    process = find_cs2_process()
    is_running = process is not None
    status = 'Rodando' if is_running else 'Parado'
    ram_usage = 'N/A'
    
    if is_running:
        try:
            ram_usage_mb = process.memory_info().rss / (1024 * 1024)
            ram_usage = f"{ram_usage_mb:.2f} MB"
            
        except (psutil.NoSuchProcess, psutil.AccessDenied):
            status = 'Erro de Acesso'
            is_running = False
            
    logging.info(f"Status verificado: {status}, Uso de RAM: {ram_usage}")
    
    return jsonify({
        'success': True, 
        'status': status, 
        'is_running': is_running,
        'ram_usage': ram_usage,
        # 'is_ready' não se aplica da mesma forma que no Minecraft e foi removido.
    }), 200

# Esta rota foi removida, pois a 'mcrcon' do Minecraft não funciona com CS2.
# O controle de console do CS2, se necessário, exigiria uma implementação diferente.
# @app.route('/rcon_command', methods=['POST'])
# def rcon_command():
#    ... (código removido)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=AGENT_PORT)