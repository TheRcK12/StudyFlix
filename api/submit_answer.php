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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Método não permitido.'], 405);
}

if (!$mongo || !$mongo_db) {
    respond(['error' => 'Banco de dados indisponível.'], 503);
}

$userEmail = $_SESSION['user_email'] ?? null;
if (!is_string($userEmail) || $userEmail === '') {
    respond(['error' => 'É necessário estar logado para responder.'], 401);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
    respond(['error' => 'Corpo JSON inválido.'], 400);
}

$questionId = trim((string) ($data['question_id'] ?? ''));
$userAnswer = strtolower(trim((string) ($data['answer'] ?? '')));

if ($questionId === '' || !in_array($userAnswer, ['a', 'b', 'c', 'd', 'e'], true)) {
    respond(['error' => 'Dados da resposta inválidos.'], 400);
}

try {
    $question = studyflix_mongo_find_one(
        $mongo,
        $mongo_db,
        'questions',
        ['question_id' => $questionId],
        ['projection' => ['_id' => 0, 'correct_option' => 1]]
    );

    if (!$question || !isset($question['correct_option'])) {
        respond(['error' => 'Questão não encontrada.'], 404);
    }

    $user = studyflix_mongo_find_one(
        $mongo,
        $mongo_db,
        'usuarios',
        ['email' => $userEmail],
        ['projection' => ['_id' => 0, 'nome' => 1]]
    );

    if (!$user) {
        studyflix_destroy_session();
        respond(['error' => 'Usuário da sessão não encontrado. Faça login novamente.'], 401);
    }

    $correctOption = strtolower((string) $question['correct_option']);
    $isCorrect = $userAnswer === $correctOption;
    $now = new MongoDB\BSON\UTCDateTime();

    studyflix_mongo_update_one(
        $mongo,
        $mongo_db,
        'user_scores',
        ['username' => $userEmail],
        [
            '$set' => [
                'user_id' => $userEmail,
                'username' => $userEmail,
                'display_name' => (string) ($user['nome'] ?? 'Aluno'),
                'updated_at' => $now,
            ],
            '$setOnInsert' => [
                'created_at' => $now,
            ],
            '$inc' => [
                'total_attempted' => 1,
                'total_correct' => $isCorrect ? 1 : 0,
            ],
        ],
        true
    );

    respond([
        'is_correct' => $isCorrect,
        'correct_option' => $correctOption,
        'message' => $isCorrect ? 'Correto!' : 'Incorreto.',
    ]);
} catch (Throwable $e) {
    error_log('[StudyFlix][SUBMIT_ANSWER] ' . $e->getMessage());
    respond(['error' => 'Não foi possível salvar a resposta.'], 500);
}
