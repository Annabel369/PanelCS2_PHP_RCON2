<?php
require_once 'rcon2.php';

$cs2_rcon_host = '100.114.210.67';
$cs2_rcon_port = 27018;
$cs2_rcon_password = 'GZPWA3PyZ7zonPf';
$rcon_timeout = 3;

$output_message = "Nenhum comando executado ainda.";

function callPythonAgent($endpoint, $method = 'GET', $data = []) {
    $agent_url = "http://localhost:5000";
    $url = $agent_url . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => 'Content-type: application/json',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        return ['success' => false, 'error' => 'Falha na conexão com o agente Python.'];
    }

    $response_data = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida do agente.'];
    }
    return $response_data;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["cs2_command_action"] ?? null;

    switch ($action) {
        case "start":
            $response = callPythonAgent('/start_server', 'POST');
            $output_message = $response['success']
                ? "<span style='color: lime;'>Servidor iniciado com sucesso!</span>"
                : "<span style='color: red;'>Erro ao iniciar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            break;

        case "stop":
            $response = callPythonAgent('/stop_server', 'POST');
            $output_message = $response['success']
                ? "<span style='color: red;'>Servidor parado com sucesso!</span>"
                : "<span style='color: red;'>Erro ao parar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            break;

        case "status":
            $response = callPythonAgent('/server_status', 'GET');
            if ($response['success']) {
                $status = ($response['is_running'] ?? false)
                    ? "<span style='color: lime;'>✔ Rodando</span>"
                    : "<span style='color: red;'>✘ Parado</span>";
                $ram_usage = $response['ram_usage'] ?? 'N/A';
                $output_message = "Status do Servidor:<br>• {$status}<br>• Uso de RAM: {$ram_usage}";
            } else {
                $output_message = "<span style='color: red;'>Erro ao obter status: " . ($response['error'] ?? 'desconhecido') . "</span>";
            }
            break;

        case "rcon":
            $command = $_POST["rcon_command_input"] ?? '';
            if (!empty($command)) {
                $rcon = new Rcon($cs2_rcon_host, $cs2_rcon_port, $cs2_rcon_password, $rcon_timeout);
                if ($rcon->connect()) {
                    $response_rcon = $rcon->send_command($command);
                    $output_message = $response_rcon !== false
                        ? "Comando RCON enviado: `{$command}`<br>Resposta: <pre>" . htmlspecialchars($response_rcon) . "</pre>"
                        : "<span style='color: red;'>Erro ao enviar comando: " . htmlspecialchars($rcon->get_response()) . "</span>";
                    $rcon->disconnect();
                } else {
                    $output_message = "<span style='color: red;'>Falha ao conectar ao RCON.</span>";
                }
            } else {
                $output_message = "Por favor, digite um comando RCON.";
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel CS2 Híbrido</title>
    <style>
        body {
            background: #222; color: #fff; font-family: Arial, sans-serif;
            margin: 0; padding: 20px; box-sizing: border-box;
        }
        .layout {
            display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;
            max-width: 1200px; margin: auto;
        }
        .sidebar, .container {
            background: #333; padding: 20px; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .sidebar {
            width: 200px;
        }
        .container {
            flex: 1; min-width: 300px;
        }
        h2, h3 { color: #e48716; margin-top: 0; }
        button {
            width: 100%; margin-bottom: 10px; padding: 10px;
            background: #444; color: #fff; border: none; border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #666; }
        input[type="text"] {
            width: 100%; padding: 10px; margin-bottom: 10px;
            background: #2a2a2a; color: #eee; border: 1px solid #555; border-radius: 5px;
        }
        .output {
            background: #111; padding: 15px; border-radius: 5px;
            min-height: 100px; max-height: 250px; overflow-y: auto;
            white-space: pre-wrap; word-wrap: break-word;
        }
    </style>
</head>
<body>

<div class="layout">
    <form method="POST" class="sidebar">
        <h3>Comandos Meta</h3>
        <button type="submit" name="cs2_command_action" value="rcon">
            <input type="hidden" name="rcon_command_input" value="meta list">
            meta list
        </button>
    </form>

    <div class="container">
        <h2>Painel CS2</h2>
        <form method="POST">
            <input type="text" name="rcon_command_input" placeholder="Digite comando RCON">
            <button type="submit" name="cs2_command_action" value="rcon">Executar Comando</button>
        </form>

        <form method="POST">
            <button type="submit" name="cs2_command_action" value="start">Iniciar Servidor</button>
            <button type="submit" name="cs2_command_action" value="stop">Parar Servidor</button>
            <button type="submit" name="cs2_command_action" value="status">Ver Status</button>
        </form>

        <div class="output"><?php echo $output_message; ?></div>
    </div>

    <form method="POST" class="sidebar">
        <h3>Comandos Plugins</h3>
        <button type="submit" name="cs2_command_action" value="rcon">
            <input type="hidden" name="rcon_command_input" value="css_plugins list">
            Listar Plugins
        </button>
        <button type="submit" name="cs2_command_action" value="rcon">
            <input type="hidden" name="rcon_command_input" value="css_plugins stop 2">
            Parar Plugin #2
        </button>
        <button type="submit" name="cs2_command_action" value="rcon">
            <input type="hidden" name="rcon_command_input" value="css_plugins restart 2">
            Reiniciar Plugin #2
        </button>
		
		meta version

            Comando RCON enviado: `meta version`
Resposta: 
 Metamod:Source Version Information
    Metamod:Source version 2.0.0-dev+1364
    Plugin interface version: 17:14
    SourceHook version: 5:5
    Loaded As: GameDLL (gameinfo.txt)
    Compiled on: Aug 14 2025 16:42:35
    Built from: https://github.com/alliedmodders/metamod-source/commit/c1cc0a6
    Build ID: 1364:c1cc0a6
    http://www.metamodsource.net/


meta list

            Comando RCON enviado: `meta list`
Resposta: 
Listing 3 plugins:
  [01] CleanerCS2 (1.0.5) by Poggu
  [02] CounterStrikeSharp (v1.0.337 @ adccc4b) by Roflmuffin
  [03] MultiAddonManager (v1.4.6-0-g3f416b1) by xen


css_plugins list

            Comando RCON enviado: `css_plugins list`
Resposta: 
  List of all plugins currently loaded by CounterStrikeSharp: 3 plugins loaded.
  [#1:LOADED]: "Admin Control with MySQL & CFG Sync" (11.0.0) by Amauri Bueno dos Santos & Gemini
  [#2:LOADED]: "Advertisement" (v1.0.8-recompile) by thesamefabius
  [#3:LOADED]: "Frozen_Elsa" (V. 4.0.4) by Astral + Copilot

css_plugins stop 2

            Comando RCON enviado: `css_plugins list`
Resposta: 
  List of all plugins currently loaded by CounterStrikeSharp: 3 plugins loaded.
  [#1:LOADED]: "Admin Control with MySQL & CFG Sync" (11.0.0) by Amauri Bueno dos Santos & Gemini
  [#2:UNLOADED]: "Advertisement" (v1.0.8-recompile) by thesamefabius
  [#3:LOADED]: "Frozen_Elsa" (V. 4.0.4) by Astral + Copilot



css_plugins restart 2

            Comando RCON enviado: `css_plugins list`
Resposta: 
  List of all plugins currently loaded by CounterStrikeSharp: 3 plugins loaded.
  [#1:LOADED]: "Admin Control with MySQL & CFG Sync" (11.0.0) by Amauri Bueno dos Santos & Gemini
  [#2:LOADED]: "Advertisement" (v1.0.8-recompile) by thesamefabius
  [#3:LOADED]: "Frozen_Elsa" (V. 4.0.4) by Astral + Copilot
    </form>
</div>

</body>
<footer style="margin-top: 40px; font-size: 0.85em; color: #666;">
    © 2025 — Criado por Amauri Bueno dos Santos com apoio da Copilot. Código limpo, servidor afiado.
</footer>
</html>