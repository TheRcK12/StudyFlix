<?php
// api/cadastro.php - CÓDIGO COMPLETO E FINALIZADO

session_set_cookie_params([
    'lifetime' => 0,      
    'path' => '/',        
    'httponly' => true,   
    'samesite' => 'Lax'   
]);

session_start();
header('Content-Type: application/json');

// 🚨 CORREÇÃO CRÍTICA: Se db_config.php estiver na mesma pasta 'api', remova o prefixo 'api/'.
// Se o db_config.php estiver em outro lugar, ajuste o caminho relativo (ex: '../db_config.php')
include 'db_config.php'; 

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha_clara = $_POST['senha'] ?? ''; 
$confirmarSenha = $_POST['confirmarSenha'] ?? ''; // Pega o campo de confirmação

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// 1. Validação de Campos Vazios
if (empty($nome) || empty($email) || empty($senha_clara) || empty($confirmarSenha)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    exit;
}

// 2. Validação de Comprimento Mínimo da Senha
if (strlen($senha_clara) < 6) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 6 caracteres.']);
    exit;
}

// 3. Validação de Confirmação da Senha
if ($senha_clara !== $confirmarSenha) {
    echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
    exit;
}

$senha_hash = password_hash($senha_clara, PASSWORD_DEFAULT);

try {
    $db = $pdo ?? null; 
    if (!$db) {
        // Isso deve ser resolvido pelo include correto, mas é uma proteção
        throw new Exception("Falha na conexão: Variável \$pdo não encontrada (Verifique db_config.php).");
    }
    
    // 4. Verifica se o email já existe
    $stmt_check = $db->prepare("SELECT email FROM usuarios WHERE email = ?");
    $stmt_check->execute([$email]);
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email já está cadastrado.']);
        exit;
    }

    // 5. Insere o novo usuário
    $query = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':nome' => $nome, 
        ':email' => $email, 
        ':senha' => $senha_hash
    ]);

    if ($result) {
        // SINCRONIZAÇÃO DA SESSÃO após o cadastro
        $_SESSION['user_email'] = $email;    
        $_SESSION['user_display_name'] = $nome;

        echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!', 'redirect' => 'page.html']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro fatal: ' . $e->getMessage()]);
}
?>