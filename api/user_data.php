<?php
// api/user_data.php - CÓDIGO FINAL E SINCRONIZADO
header('Content-Type: application/json; charset=utf-8');

// 🚨 LÊ A SESSÃO ESCRITA PELO login.php ou cadastro.php
session_start(); 

include __DIR__ . '/db_config.php';

$db = $pdo ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['logged_in' => false, 'error' => 'Erro de conexão DB.']);
    exit;
}

// Lendo a chave de sessão configurada no login/cadastro
$user_email = $_SESSION['user_email'] ?? null; 

if ($user_email) {
    try {
        // Busca o nome real para o frontend
        $stmt = $db->prepare("SELECT email, nome FROM usuarios WHERE email = ?"); // ⚠️ AJUSTE A COLUNA 'nome' se necessário!
        $stmt->execute([$user_email]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            echo json_encode([
                'logged_in' => true,
                'username' => $user_data['email'],      
                'display_name' => $user_data['nome'] 
            ]);
        } else {
            echo json_encode(['logged_in' => false, 'error' => 'Usuário logado não encontrado no banco de dados.']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['logged_in' => false, 'error' => 'Erro SQL ao buscar dados.']);
    }
} else {
    echo json_encode(['logged_in' => false]);
}
?>