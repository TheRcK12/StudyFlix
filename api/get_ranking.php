<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/db_config.php';
require __DIR__ . '/mongo_helpers.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$mongo || !$mongo_db) {
    respond(['error' => 'Banco de dados indisponível.'], 503);
}

try {
    $ranking = studyflix_mongo_find_many(
        $mongo,
        $mongo_db,
        'user_scores',
        ['username' => ['$not' => new MongoDB\BSON\Regex('^guest_')]],
        [
            'projection' => [
                '_id' => 0,
                'display_name' => 1,
                'total_correct' => 1,
                'total_attempted' => 1,
            ],
            'sort' => [
                'total_correct' => -1,
                'total_attempted' => -1,
                'display_name' => 1,
            ],
            'limit' => 10,
        ]
    );

    respond($ranking);
} catch (Throwable $e) {
    error_log('[StudyFlix][RANKING] ' . $e->getMessage());
    respond(['error' => 'Não foi possível carregar o ranking.'], 500);
}
