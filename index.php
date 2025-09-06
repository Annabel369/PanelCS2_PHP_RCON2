<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'rcon2.php';

$cs2_rcon_host = '100.114.210.67';
$cs2_rcon_port = 27018;
$cs2_rcon_password = 'GZPWA3PyZ7zonPf';
$rcon_timeout = 3;

$agent_url = "http://localhost:5000";
$output_message = "Nenhum comando executado ainda.";

function callPythonAgent($endpoint, $method = 'GET', $data = []) {
    global $agent_url;
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
                ? "✅ <span style='color: lime;'>Servidor iniciado com sucesso!</span>"
                : "❌ <span style='color: red;'>Erro ao iniciar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            break;

        case "stop":
            $response = callPythonAgent('/stop_server', 'POST');
            $output_message = $response['success']
                ? "🛑 <span style='color: red;'>Servidor parado com sucesso!</span>"
                : "❌ <span style='color: red;'>Erro ao parar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            break;

        case "status":
            $response = callPythonAgent('/server_status', 'GET');
            if ($response['success']) {
                $status = ($response['is_running'] ?? false)
                    ? "<span style='color: lime;'>✔ Rodando</span>"
                    : "<span style='color: red;'>✘ Parado</span>";
                $ram_usage = $response['ram_usage'] ?? 'N/A';
                $output_message = "📊 <strong>Status do Servidor:</strong><br>• {$status}<br>• Uso de RAM: {$ram_usage}";
            } else {
                $output_message = "❌ <span style='color: red;'>Erro ao obter status: " . ($response['error'] ?? 'desconhecido') . "</span>";
            }
            break;

        case "update":
            $output_message = "⏳ Atualizando servidor...<br><em>Aguarde enquanto o SteamCMD executa a atualização.</em>";
            flush();
            ob_flush();
            $response = callPythonAgent('/update_server', 'POST');
            if ($response['success']) {
                $output_message = "✅ <strong>Atualização concluída:</strong><br><pre style='color: #ccc; background: #111; padding: 10px; border-radius: 5px;'>" . htmlspecialchars(implode("\n", $response['output'])) . "</pre>";
            } else {
                $output_message = "❌ <span style='color: red;'>Erro na atualização: " . htmlspecialchars($response['error'] ?? 'desconhecido') . "</span>";
            }
            break;

        case "rcon":
            $command = $_POST["rcon_command_input"] ?? '';
            if (!empty($command)) {
                $rcon = new Rcon($cs2_rcon_host, $cs2_rcon_port, $cs2_rcon_password, $rcon_timeout);
                if ($rcon->connect()) {
                    $response_rcon = $rcon->send_command($command);
                    if ($response_rcon !== false) {
                        $clean_response = trim(str_replace("\x01", '', $response_rcon));
                        $icon = stripos($command, 'status') !== false ? '📊' :
                                (stripos($clean_response, 'sucesso') !== false ? '✅' :
                                (stripos($clean_response, 'uso:') !== false || stripos($clean_response, 'erro') !== false ? '❌' : 'ℹ️'));
                        $output_message = "{$icon} Comando RCON enviado: `{$command}`<br>Resposta:<br><pre>" . htmlspecialchars($clean_response) . "</pre>";
                    } else {
                        $output_message = "❌ <span style='color: red;'>Erro ao enviar comando: " . htmlspecialchars($rcon->get_response()) . "</span>";
                    }
                    $rcon->disconnect();
                } else {
                    $output_message = "❌ <span style='color: red;'>Falha ao conectar ao RCON.</span>";
                }
            } else {
                $output_message = "⚠️ Por favor, digite um comando RCON.";
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Counter-Strike 2</title>
    <link rel="icon" href="./favicon.png" type="image/png">
    <style>
        body { font-family: Arial, sans-serif; background-color: #222; color: #fff; text-align: center; padding: 20px; }
        .container { max-width: 700px; margin: auto; background: #333; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.4); }
        h2 { color: #e58715; }
        .output { background: #111; padding: 15px; border-radius: 5px; min-height: 100px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; margin-top: 15px; text-align: left; }
        input[type="text"], button { font-size: 1em; padding: 12px 15px; margin-top: 10px; border-radius: 5px; border: none; }
        input[type="text"] { width: 100%; background: #2a2a2a; color: #eee; border: 1px solid #555; }
        button { background: #e48716; color: #222; cursor: pointer; width: 100%; font-weight: bold; text-transform: uppercase; }
        button:hover { background: #e59f49; transform: translateY(-2px); }
        .imagem-container img { width: 80%; max-width: 250px; border-radius: 8px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body>
    <div style="text-align: right;"><button onclick="window.location.href='logout.php'" style="width: auto;">Sair</button></div>

    <div class="imagem-container"><img src="Counter-Strike 2 - W.png" alt="Counter-Strike 2"></div>

    <div class="container">
        <h2>Gerenciar Counter-Strike 2</h2>
        <form method="POST">
            <div><button type="submit" name="cs2_command_action" value="start">Iniciar Servidor</button></div>
            <div><button type="submit" name="cs2_command_action" value="stop">Parar Servidor</button></div>
            <div><button type="submit" name="cs2_command_action" value="status">Ver Status</button></div>
            <div><button type="submit" name="cs2_command_action" value="update">Atualizar Servidor</button></div>
        </form>

        <hr style="border-color:#555; margin: 20px 0;">

        <h3>Comando RCON</h3>
        <form method="POST">
            <input type="text" name="rcon_command_input" placeholder="Ex: status">
            <button type="submit" name="cs2_command_action" value="rcon">Executar Comando RCON</button>
        </form>

        <a href="steam://connect/<?php echo $cs2_rcon_host . ':' . $cs2_rcon_port; ?>">
            <button type="button">Entrar no servidor</button>
        </a>

        <div class="output"><?php echo $output_message; ?></div>
    </div>

    <footer style="margin-top: 40px; font-size: 0.85em; color: #666;">
        © 2025 — Criado por Amauri Bueno dos Santos com apoio da Copilot. Código limpo, servidor afiado.
    </footer>
</body>
</html>