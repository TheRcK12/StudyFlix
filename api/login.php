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
    respond(['success' => false, 'message' => 'Método não permitido.'], 405);
}

if (!$pdo) {
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
    $stmt = $pdo->prepare('SELECT email, nome, senha FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, (string) $user['senha'])) {
        respond(['success' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_display_name'] = (string) $user['nome'];

    respond([
        'success' => true,
        'message' => 'Login realizado com sucesso.',
        'redirect' => 'page.html',
    ]);
} catch (Throwable $e) {
    error_log('[StudyFlix][LOGIN] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Não foi possível realizar o login agora.'], 500);
}
