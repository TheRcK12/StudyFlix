<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/db_config.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$pdo) {
    respond(['error' => 'Banco de dados indisponível.'], 503);
}

try {
    $stmt = $pdo->query(
        "SELECT display_name, total_correct, total_attempted
         FROM user_scores
         WHERE username NOT LIKE 'guest_%'
         ORDER BY total_correct DESC, total_attempted DESC, display_name ASC
         LIMIT 10"
    );

    respond($stmt->fetchAll());
} catch (Throwable $e) {
    error_log('[StudyFlix][RANKING] ' . $e->getMessage());
    respond(['error' => 'Não foi possível carregar o ranking.'], 500);
}
