<?php
// Inicia a sessão no início do arquivo.
session_start();

// Verifica se o usuário está logado. Se não, redireciona para a página de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Inclui a classe Rcon
require_once 'rcon2.php';

// Configurações do seu servidor CS2
$cs2_rcon_host = '100.114.210.67';
$cs2_rcon_port = 27018;
$cs2_rcon_password = 'GZPWA3PyZ7zonPf';
$rcon_timeout = 3;

// URL base do seu agente Python
$agent_url = "http://localhost:5000";

// Variável para exibir mensagens ao usuário
$output_message = "Nenhum comando executado ainda.";

// --- Funções ---

// Função para chamar os endpoints do agente Python
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

// Lógica para processar as ações do formulário
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["cs2_command_action"] ?? null;

    switch ($action) {
        case "start":
            $response = callPythonAgent('/start_server', 'POST');
            if ($response['success']) {
                $output_message = "<span style='color: lime;'>Servidor iniciado com sucesso!</span>";
            } else {
                $output_message = "<span style='color: red;'>Erro ao iniciar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            }
            break;

        case "stop":
            $response = callPythonAgent('/stop_server', 'POST');
            if ($response['success']) {
                $output_message = "<span style='color: red;'>Servidor parado com sucesso!</span>";
            } else {
                $output_message = "<span style='color: red;'>Erro ao parar: " . ($response['error'] ?? 'desconhecido') . "</span>";
            }
            break;

        case "status":
            $response = callPythonAgent('/server_status', 'GET');
            if ($response['success']) {
                $status = ($response['is_running'] ?? false) ? "<span style='color: lime;'>✔ Rodando</span>" : "<span style='color: red;'>✘ Parado</span>";
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
                    if ($response_rcon !== false) {
                        // Sanitiza a resposta
                        $clean_response = trim(str_replace("\x01", '', $response_rcon));

                        // Define ícone com base no conteúdo
                        $is_success = stripos($clean_response, 'sucesso') !== false;
                        // Detecta sucesso com base no tipo de comando
                        if (stripos($command, 'status') !== false) {
                            $icon = '📊'; // ícone neutro para status
                        } elseif (stripos($clean_response, 'sucesso') !== false) {
                            $icon = '✅';
                        } elseif (stripos($clean_response, 'uso:') !== false || stripos($clean_response, 'erro') !== false) {
                            $icon = '❌';
                        } else {
                            $icon = 'ℹ️'; // ícone genérico para resposta informativa
                        }

                        // Monta mensagem final
                        $output_message = "Comando RCON enviado: `{$command}`<br>Resposta: <pre>{$icon} " . htmlspecialchars($clean_response) . "</pre>";
                    } else {
                        $output_message = "<span style='color: red;'>Erro ao enviar comando: " . htmlspecialchars($rcon->get_response()) . "</span>";
                    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.png" type="image/png">
    <title>Gerenciar Counter-Strike 2</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #222; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .container { width: 90%; max-width: 700px; margin-bottom: 20px; background: #333; padding: 20px; border-radius: 8px; box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.4); box-sizing: border-box; }
        h2 { color: #e58715; margin-top: 0; margin-bottom: 20px; }
        .output { background: #111; padding: 15px; border-radius: 5px; min-height: 100px; max-height: 250px; overflow-y: auto; text-align: left; margin-bottom: 15px; white-space: pre-wrap; word-wrap: break-word; }
        input[type="text"], button { font-size: 1em; padding: 12px 15px; margin-top: 10px; border: 1px solid #555; border-radius: 5px; box-sizing: border-box; transition: all 0.3s ease; }
        input[type="text"] { width: 100%; background: #2a2a2a; color: #eee; }
        button { background: #e48716; color: #222; border: none; cursor: pointer; width: 100%; font-weight: bold; text-transform: uppercase; }
        button:hover { background: #e59f49; transform: translateY(-2px); }
        .imagem-container { width: 90%; max-width: 400px; margin-top: 20px; text-align: center; box-sizing: border-box; }
        .imagem-container img { width: 80%; max-width: 250px; height: auto; display: block; margin: 0 auto; border-radius: 8px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body>
    <div style="text-align: right; margin-bottom: 15px;">
        <button onclick="window.location.href='logout.php'" style="width: auto;">Sair</button>
    </div>

    <div class="imagem-container">
        <img src="Counter-Strike 2 - W.png" alt="Counter-Strike 2">
    </div>
    
    <div class="container">
        <h2>Gerenciar Counter-Strike 2</h2>
        <form method="POST">
            <div><button type="submit" name="cs2_command_action" value="start">Iniciar Servidor</button></div>
            <div><button type="submit" name="cs2_command_action" value="stop">Parar Servidor</button></div>
            <div><button type="submit" name="cs2_command_action" value="status">Ver Status</button></div>
        </form>

        <hr style="border-color:#555; margin: 20px 0;">

        <h3>Comando RCON</h3>
        <form method="POST">
            <input type="text" name="rcon_command_input" placeholder="Ex: status">
            <button type="submit" name="cs2_command_action" value="rcon">Executar Comando RCON</button>
        </form>
        
        <!-- Botão para entrar no servidor via Steam -->
        <a href="steam://connect/<?php echo $cs2_rcon_host . ':' . $cs2_rcon_port; ?>">
            <button type="button">Entrar no servidor</button>
        </a>

        <div class="output">
            <?php echo $output_message; ?>
        </div>
    </div>
    
    <footer style="margin-top: 40px; font-size: 0.85em; color: #666;">
        © 2025 — Criado por Amauri Bueno dos Santos com apoio da Copilot. Código limpo, servidor afiado.
    </footer>
</body>
</html>
