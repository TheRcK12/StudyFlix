<?php
// api/save_score.php
header('Content-Type: application/json; charset=utf-8');

include 'db_config.php'; 

$db = $pdo ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: Banco de dados não disponível.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

if (!isset($_POST['username']) || !isset($_POST['correct']) || !isset($_POST['attempted']) || !isset($_POST['display_name'])) {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => 'Dados incompletos. São necessários username, correct, attempted e display_name.']);
    exit;
}

$username = trim($_POST['username']);
$correct = (int)$_POST['correct'];
$attempted = (int)$_POST['attempted'];
$display_name = trim($_POST['display_name']); 
// $last_session_date = date('Y-m-d H:i:s'); // Removido: A coluna não existe ou tem o case errado

if ($attempted === 0) {
    echo json_encode(['success' => true, 'message' => 'Nenhuma tentativa registrada, pontuação não salva.']);
    exit;
}

try {
    // SINTAXE POSTGRESQL (ON CONFLICT DO UPDATE)
    // REMOVIDO: 'last_session_date' para evitar o erro 'Undefined column'
    $sql = "INSERT INTO user_scores (username, display_name, total_correct, total_attempted) 
            VALUES (:username, :display_name, :correct, :attempted)
            ON CONFLICT (username) DO UPDATE 
            SET total_correct = user_scores.total_correct + :correct_update, 
                total_attempted = user_scores.total_attempted + :attempted_update,
                display_name = :display_name_update";

    $stmt = $db->prepare($sql);
    
    // Binds para INSERT
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':display_name', $display_name);
    $stmt->bindParam(':correct', $correct);
    $stmt->bindParam(':attempted', $attempted);
    // Bind de :last_session_date removido

    // Binds para UPDATE
    $stmt->bindParam(':correct_update', $correct);
    $stmt->bindParam(':attempted_update', $attempted);
    $stmt->bindParam(':display_name_update', $display_name);
    // Bind de :last_session_date_update removido
    
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Pontuação registrada/atualizada com sucesso!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>