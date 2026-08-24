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

$nome = trim(strip_tags((string) ($_POST['nome'] ?? '')));
$emailInput = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');
$confirmarSenha = (string) ($_POST['confirmarSenha'] ?? '');

if ($nome === '' || $emailInput === '' || $senha === '' || $confirmarSenha === '') {
    respond(['success' => false, 'message' => 'Preencha todos os campos.'], 400);
}

if (mb_strlen($nome) > 120) {
    respond(['success' => false, 'message' => 'O nome deve ter no máximo 120 caracteres.'], 400);
}

$email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    respond(['success' => false, 'message' => 'Informe um e-mail válido.'], 400);
}
$email = strtolower($email);

if (strlen($senha) < 6) {
    respond(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres.'], 400);
}

if ($senha !== $confirmarSenha) {
    respond(['success' => false, 'message' => 'As senhas não coincidem.'], 400);
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn()) {
        respond(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)'
    );
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
    ]);

    session_regenerate_id(true);
    $_SESSION['user_email'] = $email;
    $_SESSION['user_display_name'] = $nome;

    respond([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso.',
        'redirect' => 'page.html',
    ], 201);
} catch (PDOException $e) {
    // 23505 = unique_violation no PostgreSQL. Evita condição de corrida no cadastro.
    if ($e->getCode() === '23505') {
        respond(['success' => false, 'message' => 'Este e-mail já está cadastrado.'], 409);
    }

    error_log('[StudyFlix][CADASTRO] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Não foi possível concluir o cadastro agora.'], 500);
} catch (Throwable $e) {
    error_log('[StudyFlix][CADASTRO] ' . $e->getMessage());
    respond(['success' => false, 'message' => 'Não foi possível concluir o cadastro agora.'], 500);
}
