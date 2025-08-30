# run_server.py - Inicia o servidor Minecraft e exibe a saída do console

import subprocess
import os
import sys

# Importa as configurações do arquivo config.py
# Certifique-se de que MINECRAFT_START_COMMAND e MINECRAFT_SERVER_DIR
# estão configurados corretamente no seu config.py.
from config import (
    MINECRAFT_START_COMMAND,
    MINECRAFT_SERVER_DIR
)

def run_minecraft_server():
    """
    Inicia o servidor Minecraft em um novo processo e conecta a saída
    padrão (stdout) e a saída de erro (stderr) à sua janela de terminal atual.
    """
    print("Iniciando o servidor Minecraft...")
    print(f"Diretório do servidor: {MINECRAFT_SERVER_DIR}")
    print(f"Comando de início: {' '.join(MINECRAFT_START_COMMAND)}\n")
    
    try:
        # Usa subprocess.run para executar o comando.
        # 'cwd' (current working directory) é crucial para que o comando
        # encontre o arquivo server.jar.
        # 'check=True' fará com que o script gere um erro se o comando falhar.
        # A saída do servidor será exibida diretamente no seu terminal.
        subprocess.run(
            ['java', '-Xmx4G', '-jar', 'fabric-server-launch.jar', 'nogui'],
            cwd=r"C:\Users\Astral\Desktop\Servidor 1.21.6 Fabric Minecraft",
            shell=True,
            check=True
        )
        
    except FileNotFoundError:
        print("Erro: O comando ou o arquivo 'fabric-server-launch.jar' não foi encontrado.")
        print("Verifique se o caminho no seu config.py está correto.")
    except subprocess.CalledProcessError as e:
        print(f"O servidor terminou com um erro: {e}")
    except Exception as e:
        print(f"Ocorreu um erro inesperado: {e}")

if __name__ == '__main__':
    run_minecraft_server()