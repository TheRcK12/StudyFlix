<?php
// api/db_config.php - Configuração para PostgreSQL (Render)
// 🚨 CRÍTICO: Removendo die() e garantindo que o JSON de erro seja retornado pelo script principal.

// Inicializa a variável $pdo como null
$pdo = null;

// Sua string de conexão (ajustada para variáveis)
$db_url = "postgresql://studyflix_user:iofU2bx0K4LEvFJU7kHYjoHnXaKj2R2y@dpg-d4kbinodl3ps73dh16l0-a/studyflix_db_qurq_hi3g";

// Parseia a URL de conexão para obter as credenciais separadas
$url_parts = parse_url($db_url);

// Verifica se o parse_url foi bem-sucedido e se as partes cruciais existem
if ($url_parts === false || !isset($url_parts['host'], $url_parts['user'], $url_parts['pass'], $url_parts['path'])) {
    // Se a string de conexão for inválida, $pdo permanece null
    return;
}

$host = $url_parts['host'];
$user = $url_parts['user'];
$password = $url_parts['pass'];
$dbname = ltrim($url_parts['path'], '/'); 
$port = $url_parts['port'] ?? 5432; // Adiciona a porta padrão 5432, se não estiver na URL

try {
    // String de conexão DSN completa para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    $pdo = new PDO($dsn, $user, $password);
    
    // Configura o PDO para lançar exceções em caso de erro (CRÍTICO para o try/catch)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Se a conexão falhar, define $pdo como null (não usa die()!)
    $pdo = null; 
    // O script principal (submit_answer.php ou user_data.php) checará se $pdo é null e retornará o erro JSON.
}

// REMOVA A TAG DE FECHAMENTO ?>