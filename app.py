# app.py

from flask import Flask, jsonify, request
import subprocess
import logging
import os
import psutil
import time

from config import (
    RCON_HOST, RCON_PORT, RCON_PASSWORD,
    CS2_START_COMMAND, AGENT_PORT,
    CS2_SERVER_DIR, STEAMCMD_PATH,
    STEAMCMD_INSTALL_DIR, STEAM_APP_ID
)

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

app = Flask(__name__)

def find_cs2_process():
    for proc in psutil.process_iter(['pid', 'name']):
        try:
            if proc.info['name'] and 'cs2.exe' in proc.info['name'].lower():
                logging.info(f"Processo CS2 encontrado: PID {proc.info['pid']}")
                return proc
        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            continue
    return None

@app.route('/start_server', methods=['POST'])
def start_server():
    if find_cs2_process():
        return jsonify({'success': False, 'error': 'Servidor já está rodando.'}), 409

    try:
        logging.info(f"Iniciando servidor com comando: {CS2_START_COMMAND}")
        subprocess.Popen(
            CS2_START_COMMAND,
            cwd=CS2_SERVER_DIR,
            shell=True,
            creationflags=subprocess.CREATE_NO_WINDOW
        )
        time.sleep(2)
        return jsonify({'success': True, 'message': 'Servidor iniciado com sucesso.'})
    except Exception as e:
        logging.error(f"Erro ao iniciar servidor: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/stop_server', methods=['POST'])
def stop_server():
    process = find_cs2_process()
    if not process:
        return jsonify({'success': False, 'error': 'Servidor não está rodando.'}), 404

    try:
        logging.info(f"Parando servidor com PID: {process.pid}")
        process.terminate()
        process.wait(timeout=10)
        return jsonify({'success': True, 'message': 'Servidor parado com sucesso.'})
    except Exception as e:
        logging.error(f"Erro ao parar servidor: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/server_status', methods=['GET'])
def server_status():
    process = find_cs2_process()
    is_running = process is not None
    ram_usage = 'N/A'

    if is_running:
        try:
            ram_usage_mb = process.memory_info().rss / (1024 * 1024)
            ram_usage = f"{ram_usage_mb:.2f} MB"
        except (psutil.NoSuchProcess, psutil.AccessDenied):
            is_running = False

    return jsonify({
        'success': True,
        'status': 'Rodando' if is_running else 'Parado',
        'is_running': is_running,
        'ram_usage': ram_usage
    })

@app.route('/update_server', methods=['POST'])
def update_server():
    steamcmd_command = [
        STEAMCMD_PATH,
        '+force_install_dir', STEAMCMD_INSTALL_DIR,
        '+login', 'anonymous',
        '+app_set_config', STEAM_APP_ID,
        '+app_update', STEAM_APP_ID,
        '+quit'
    ]

    try:
        logging.info("Atualizando servidor CS2 via SteamCMD...")
        process = subprocess.Popen(
            steamcmd_command,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            shell=True
        )

        output_lines = []
        while True:
            line = process.stdout.readline()
            if not line and process.poll() is not None:
                break
            if line:
                decoded_line = line.decode('latin1').strip()  # Corrige acentuação
                logging.info(f"SteamCMD: {decoded_line}")
                output_lines.append(decoded_line)

        return jsonify({
            'success': True,
            'output': output_lines,
            'message': 'Atualização concluída.'
        })

    except Exception as e:
        logging.error(f"Erro na atualização: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=AGENT_PORT)