<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/session.php';
studyflix_start_session();
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

if (empty($_SESSION['user_email'])) {
    respond(['error' => 'É necessário estar logado para iniciar o quiz.'], 401);
}

$area = trim((string) ($_GET['area'] ?? 'Natureza'));
$areasPermitidas = ['Natureza', 'Humanas', 'Matematica', 'Linguagens'];
if (!in_array($area, $areasPermitidas, true)) {
    respond(['error' => 'Área inválida.'], 400);
}

try {
    $question = studyflix_mongo_aggregate_one($mongo, $mongo_db, 'questions', [
        ['$match' => ['area' => $area]],
        ['$sample' => ['size' => 1]],
        ['$project' => [
            '_id' => 0,
            'question_id' => 1,
            'enunciado' => 1,
            'option_a' => 1,
            'option_b' => 1,
            'option_c' => 1,
            'option_d' => 1,
            'option_e' => 1,
        ]],
    ]);

    if (!$question) {
        respond(['error' => 'Nenhuma questão encontrada para esta área.'], 404);
    }

    respond($question);
} catch (Throwable $e) {
    error_log('[StudyFlix][GET_QUESTION] ' . $e->getMessage());
    respond(['error' => 'Não foi possível carregar a questão.'], 500);
}
