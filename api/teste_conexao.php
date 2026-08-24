<?php
$host = 'localhost';
$db = 'studyflix_db';
$user = 'postgres';
$pass = 'AnnaLuisa12_';
$port = '5433';
$dsn = "pgsql:host=$host;port=$port;dbname=$db;";
try {
    $conn = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexão realizada com sucesso!";
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
?>
