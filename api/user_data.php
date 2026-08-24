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

if (!$pdo) {
    respond(['logged_in' => false, 'error' => 'Banco de dados indisponível.'], 503);
}

$userEmail = $_SESSION['user_email'] ?? null;
if (!is_string($userEmail) || $userEmail === '') {
    respond(['logged_in' => false]);
}

try {
    $stmt = $pdo->prepare('SELECT email, nome FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $userEmail]);
    $user = $stmt->fetch();

    if (!$user) {
        studyflix_destroy_session();
        respond(['logged_in' => false]);
    }

    respond([
        'logged_in' => true,
        'username' => (string) $user['email'],
        'display_name' => (string) $user['nome'],
    ]);
} catch (Throwable $e) {
    error_log('[StudyFlix][USER_DATA] ' . $e->getMessage());
    respond(['logged_in' => false, 'error' => 'Não foi possível consultar a sessão.'], 500);
}
