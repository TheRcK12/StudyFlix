<?php
// api/login.php - CÓDIGO FINAL E SINCRONIZADO
// 🚨 CRÍTICO: Define o cookie para ser válido em todo o site
session_set_cookie_params([
    'lifetime' => 0,      
    'path' => '/',        
    'httponly' => true,   
    'samesite' => 'Lax'   
]);

session_start();
header('Content-Type: application/json');

include 'db_config.php'; 

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (empty($email) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    exit;
}

try {
    $db = $pdo ?? null; 
    
    if (!$db) {
        throw new Exception("Falha na conexão: Variável \$pdo não encontrada.");
    }

    $sql = "SELECT email, nome, senha FROM usuarios WHERE email = :email LIMIT 1"; 
    $stmt = $db->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($senha, $user['senha'])) {
        
        // 🚨 CRÍTICO: SINCRONIZAÇÃO DA SESSÃO
        $_SESSION['user_email'] = $user['email'];    
        $_SESSION['user_display_name'] = $user['nome']; 

        echo json_encode(['success' => true, 'message' => 'Login realizado!', 'redirect' => 'page.html']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro fatal: ' . $e->getMessage()]);
}
?>