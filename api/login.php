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
    respond(['success' => false, 'message' => 'Método não permitido.'], 405);
}

if (!$mongo || !$mongo_db) {
    respond(['success' => false, 'message' => 'Banco de dados temporariamente indisponível.'], 503);
}

$emailInput = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if ($emailInput === '' || $senha === '') {
    respond(['success' => false, 'message' => 'Preencha todos os campos.'], 400);
}

$email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    respond(['success' => false, 'message' => 'Informe um e-mail válido.'], 400);
}
$email = strtolower($email);

try {
    $user = studyflix_mongo_find_one(
        $mongo,
        $mongo_db,
        'usuarios',
        ['email' => $email],
        ['projection' => ['_id' => 0, 'email' => 1, 'nome' => 1, 'senha' => 1]]
    );

    if (!$user || !isset($user['senha']) || !password_verify($senha, (string) $user['senha'])) {
        respond(['success' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_display_name'] = (string) ($user['nome'] ?? 'Aluno');

    respond([
        'success' => true,
        'message' => 'Login realizado com sucesso.',
        'redirect' => 'page.html',
    ]);
} catch (Throwable $e) {
    error_log('[StudyFlix][LOGIN] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Não foi possível realizar o login agora.'], 500);
}
