# config.py

# Configurações do RCON
RCON_HOST = '100.114.210.67'
RCON_PORT = 27018
RCON_PASSWORD = 'GZPWA3PyZ7zonPf'

# Diretório do servidor CS2
CS2_SERVER_DIR = r'C:\cs2-ds\game\bin\win64'

# Comando para iniciar o servidor CS2
CS2_START_COMMAND = [
    'cs2.exe',
    '-dedicated',
    '-usercon',
    '-ip', RCON_HOST,
    '-port', str(RCON_PORT),
    '-insecure',
    '-developer', '1',
    '+map', 'de_anubis',
    '-maxplayers', '32',
    '+sv_setsteamaccount', '020094507D2372836C9840E106FD8E2C',
    '+servercfgfile', 'server.cfg',
    '+game_type', '0',
    '+game_mode', '0'
]

# Caminho do SteamCMD e diretório de instalação
STEAMCMD_PATH = r'C:\steamcmd\steamcmd.exe'
STEAMCMD_INSTALL_DIR = r'C:\cs2-ds'
STEAM_APP_ID = '730'

# Porta do agente Flask
AGENT_PORT = 5000