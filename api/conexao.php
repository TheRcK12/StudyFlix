<?php
// Conexão com banco de dados Render (usando interno)
$host = 'dpg-d4kbinodl3ps73dh16l0-a';
$port = '5432';
$dbname = 'studyflix_db_qurq_hi3g';
$user = 'studyflix_user';
$password = 'iofU2bx0K4LEvFJU7kHYjoHnXaKj2R2y';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão bem-sucedida com o Render PostgreSQL!";
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage();
}
?>
