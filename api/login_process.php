<?php
// login_process.php - Exemplo de script que processa o login
// 🚨 CRÍTICO: session_start() deve ser a primeira instrução, antes de qualquer output!
session_start(); 

// ---------------------------------------------------------------------------------
// 1. Receber e validar dados (email e senha)
$email = $_POST['email'] ?? ''; 
$senha = $_POST['senha'] ?? ''; 

// Supondo que você use $pdo do seu db_config para buscar o usuário e validar a senha
include 'api/db_config.php'; 
$db = $pdo;

$login_sucesso = false;
$user_email_identificado = null;

if ($db && $email && $senha) {
    try {
        // Busca o usuário no banco (aqui você verifica a senha, usa hash, etc.)
        // ⚠️ AJUSTE ESTA QUERY para buscar a senha e o nome_completo para validar!
        $stmt = $db->prepare("SELECT email, senha_hash FROM users WHERE email = ?"); 
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Exemplo: Validação da senha
        if ($user && password_verify($senha, $user['senha_hash'])) {
            $login_sucesso = true;
            $user_email_identificado = $user['email'];
        }

    } catch (PDOException $e) {
        // Tratar erro de banco
    }
}

// ---------------------------------------------------------------------------------
// 2. Ação de Sucesso ou Falha

if ($login_sucesso) {
    // 🎉 SINCRONIZAÇÃO: ESCREVE O ID NA SESSÃO
    $_SESSION['user_email'] = $user_email_identificado; 
    
    // Redireciona para o quiz
    header("Location: questoese.html");
    exit;

} else {
    // Tratar erro (redirecionar para login com mensagem de erro, etc.)
    header("Location: login.html?error=invalid");
    exit;
}
?>