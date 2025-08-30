<?php
require_once 'rcon.php';

// Configurações do servidor RCON
$host = '100.114.210.67';
$port = 27515;
$password = 'GZPWA3PyZ7zonPf';
$timeout = 3;

echo "<h3>🔍 Iniciando conexão RCON com debug...</h3>";

$rcon = new Rcon($host, $port, $password, $timeout);

echo "➡️ Tentando conectar ao servidor {$host}:{$port}<br>";

if ($rcon->connect()) {
    echo "✅ Conexão estabelecida com sucesso.<br>";

    echo "➡️ Enviando comando: <code>status</code><br>";
    $resposta = $rcon->send_command('status');

    if ($resposta !== false) {
        echo "✅ Resposta recebida:<br><pre>" . htmlspecialchars($resposta) . "</pre>";
    } else {
        echo "⚠️ Nenhuma resposta válida recebida do servidor.<br>";
        echo "🧾 Última resposta bruta: <pre>" . htmlspecialchars($rcon->get_response()) . "</pre>";
    }

    $rcon->disconnect();
    echo "🔌 Conexão encerrada.<br>";
} else {
    echo "❌ Falha ao conectar ao servidor RCON.<br>";
    echo "🧾 Erro: <pre>" . htmlspecialchars($rcon->get_response()) . "</pre>";
    echo "📌 Verifique se o IP, porta e senha estão corretos.<br>";
    echo "📌 Certifique-se de que o servidor está online e que o RCON está habilitado.<br>";
    echo "📌 Verifique se há firewall ou NAT bloqueando a porta {$port}.<br>";
}
?>