<?php
// api/teste_banco.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Teste de Conexão com o Banco</h1>";

// Tenta incluir o arquivo de configuração
if (!file_exists('db_config.php')) {
    die("❌ ERRO CRÍTICO: O arquivo 'db_config.php' não foi encontrado na pasta api/.");
}
echo "<p>✅ Arquivo db_config.php encontrado.</p>";

include 'db_config.php';

// Verifica drivers instalados
echo "<h3>Drivers PDO Instalados:</h3>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

if (!in_array('pgsql', PDO::getAvailableDrivers())) {
    echo "<h2 style='color:red'>❌ ERRO FATAL: O Driver PostgreSQL não está instalado neste servidor!</h2>";
    echo "<p>Você precisa instalar o 'php-pdo-pgsql'.</p>";
    exit;
}

// Verifica a conexão
if (isset($pdo)) {
    echo "<h2 style='color:green'>✅ SUCESSO! A variável \$pdo existe.</h2>";
    try {
        $stmt = $pdo->query("SELECT count(*) FROM user_scores");
        $count = $stmt->fetchColumn();
        echo "<p>Conexão testada com sucesso. Existem <b>$count</b> registros na tabela user_scores.</p>";
    } catch (Exception $e) {
        echo "<h2 style='color:orange'>⚠️ Conexão OK, mas erro ao ler tabela:</h2>";
        echo $e->getMessage();
    }
} else {
    echo "<h2 style='color:red'>❌ ERRO: A conexão falhou em db_config.php e não retornou a variável \$pdo.</h2>";
}
?>