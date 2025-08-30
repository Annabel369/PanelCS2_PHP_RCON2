# config.py - Configurações para o agente de controle do servidor CS2

# Configurações do RCON do Counter-Strike 2
RCON_HOST = '100.114.210.67'
RCON_PORT = 27515
RCON_PASSWORD = 'GZPWA3PyZ7zonPf' # Senha RCON para controle remoto

# Diretório onde o servidor CS2 está localizado
CS2_SERVER_DIR = r'C:\cs2-ds\game\bin\win64'

# Comando para iniciar o servidor CS2
CS2_START_COMMAND = [
    'cs2.exe',
    '-dedicated',
    '-usercon',
    '-ip',
    '100.114.210.67',
    '-port',
    '27018',
    '+map',
    'de_anubis',
    '-maxplayers',
    '32',
    '+sv_setsteamaccount',
    '020094507D2372836C9840E106FD8E2C',
    '+servercfgfile',
    'server.cfg',
    '+game_type',
    '0',
    '+game_mode',
    '0'
]

# Porta que o agente Python vai escutar
AGENT_PORT = 5000