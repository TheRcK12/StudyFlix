<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/session.php';
studyflix_start_session();
require __DIR__ . '/db_config.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Método não permitido.'], 405);
}

if (!$pdo) {
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
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT correct_option FROM questions WHERE question_id = :id LIMIT 1');
    $stmt->execute([':id' => $questionId]);
    $question = $stmt->fetch();

    if (!$question) {
        $pdo->rollBack();
        respond(['error' => 'Questão não encontrada.'], 404);
    }

    $correctOption = strtolower((string) $question['correct_option']);
    $isCorrect = $userAnswer === $correctOption;
    $correctIncrement = $isCorrect ? 1 : 0;

    $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $userEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        respond(['error' => 'Usuário da sessão não encontrado. Faça login novamente.'], 401);
    }

    $displayName = (string) $user['nome'];

    $stmt = $pdo->prepare(
        'INSERT INTO user_scores (user_id, username, display_name, total_attempted, total_correct)
         VALUES (:user_id, :username, :display_name, 1, :correct)
         ON CONFLICT (username) DO UPDATE
         SET total_attempted = user_scores.total_attempted + 1,
             total_correct = user_scores.total_correct + EXCLUDED.total_correct,
             display_name = EXCLUDED.display_name,
             updated_at = NOW()'
    );
    $stmt->execute([
        ':user_id' => $userEmail,
        ':username' => $userEmail,
        ':display_name' => $displayName,
        ':correct' => $correctIncrement,
    ]);

    $pdo->commit();

    respond([
        'is_correct' => $isCorrect,
        'correct_option' => $correctOption,
        'message' => $isCorrect ? 'Correto!' : 'Incorreto.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[StudyFlix][SUBMIT_ANSWER] ' . $e->getMessage());
    respond(['error' => 'Não foi possível salvar a resposta.'], 500);
}
