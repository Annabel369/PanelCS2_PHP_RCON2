<?php
require_once 'rcon.php';

header('Content-Type: application/json');

$response = [];

try {
    $rcon = new Rcon("192.168.100.170", 25575, "12312sdafa134", 3);
    
    if ($rcon->connect()) {
        $rawResult = $rcon->send_command("list");
        $rcon->disconnect();

        // Remove caracteres estranhos
        $cleanResult = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $rawResult));

        // Extrai número de jogadores e nomes
        preg_match('/There are (\d+) of a max of \d+ players online: ?(.*)/i', $cleanResult, $matches);

        $numPlayers = isset($matches[1]) ? (int)$matches[1] : 0;
        $playerNames = isset($matches[2]) && !empty($matches[2]) ? explode(', ', $matches[2]) : [];

        $response = [
            'success' => true,
            'jogadores_online' => $numPlayers,
            'nomes' => $playerNames
        ];
    } else {
        throw new Exception('Falha ao conectar via RCON');
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>