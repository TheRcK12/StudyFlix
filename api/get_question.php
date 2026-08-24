<?php
// api/get_question.php
header('Content-Type: application/json; charset=utf-8');

// Inclui o arquivo de configuração do banco (que define $pdo)
include __DIR__ . '/db_config.php'; 

// CRÍTICO: Garante que a variável correta seja usada ou exibe erro limpo
$db = $pdo ?? null;

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: Variável de conexão com o banco ($pdo) não disponível.']);
    exit;
}

// Obtém a área da URL, usa 'Natureza' como padrão
$area = $_GET['area'] ?? 'Natureza';

try {
    // 1. PostgreSQL usa RANDOM() para ordem aleatória
    // A query parece correta e pronta para PostgreSQL.
    $sql = "SELECT question_id, enunciado, option_a, option_b, option_c, option_d, option_e 
            FROM questions 
            WHERE area = ? 
            ORDER BY RANDOM() 
            LIMIT 1";
            
    // 2. CORREÇÃO: Usa $db (que é $pdo) para preparar a query
    $stmt = $db->prepare($sql);
    $stmt->execute([$area]); 

    if ($stmt->rowCount() > 0) {
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($question);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Nenhuma questão encontrada para a área: ' . htmlspecialchars($area)]);
    }

} catch (PDOException $e) {
    // Se a query falhar (ex: tabela questions não existe), retorna JSON de erro
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar questão (SQL): ' . $e->getMessage()]);
}

// 3. CORREÇÃO: Fecha a variável de conexão correta
$db = null; 
?>