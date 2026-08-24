<?php
// api/get_ranking.php - CÓDIGO FINAL
header('Content-Type: application/json; charset=utf-8');

include 'db_config.php'; 

$db = $pdo ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: A conexão com o banco não foi estabelecida.']);
    exit;
}

try {
    $sql = "SELECT display_name, total_correct, total_attempted 
            FROM user_scores 
            WHERE username NOT LIKE 'guest_%' 
            ORDER BY total_correct DESC, total_attempted DESC
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $ranking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ranking_data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao executar a query no banco de dados. Detalhe: ' . $e->getMessage()]);
}
?>