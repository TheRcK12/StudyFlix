<?php
declare(strict_types=1);

/**
 * Conexão PostgreSQL compatível com Railway.
 *
 * Prioridade:
 * 1) DATABASE_URL (recomendado no Railway)
 * 2) PGHOST, PGPORT, PGDATABASE, PGUSER e PGPASSWORD
 *
 * Nenhuma credencial fica gravada no código-fonte.
 */

$pdo = null;
$db_connection_error = null;

try {
    $databaseUrl = trim((string) (getenv('DATABASE_URL') ?: ''));

    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);

        if ($parts === false || !isset($parts['host'], $parts['user'], $parts['path'])) {
            throw new RuntimeException('DATABASE_URL inválida.');
        }

        $host = rawurldecode((string) $parts['host']);
        $port = isset($parts['port']) ? (int) $parts['port'] : 5432;
        $database = rawurldecode(ltrim((string) $parts['path'], '/'));
        $user = rawurldecode((string) $parts['user']);
        $password = rawurldecode((string) ($parts['pass'] ?? ''));

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }
        $sslmode = getenv('PGSSLMODE') ?: ($query['sslmode'] ?? null);
    } else {
        $host = trim((string) (getenv('PGHOST') ?: ''));
        $port = (int) (getenv('PGPORT') ?: 5432);
        $database = trim((string) (getenv('PGDATABASE') ?: ''));
        $user = trim((string) (getenv('PGUSER') ?: ''));
        $password = (string) (getenv('PGPASSWORD') ?: '');
        $sslmode = getenv('PGSSLMODE') ?: null;

        if ($host === '' || $database === '' || $user === '') {
            throw new RuntimeException(
                'Banco não configurado. Defina DATABASE_URL ou as variáveis PGHOST/PGDATABASE/PGUSER.'
            );
        }
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;connect_timeout=8',
        $host,
        $port,
        $database
    );

    if (is_string($sslmode) && $sslmode !== '') {
        $dsn .= ';sslmode=' . $sslmode;
    }

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    $pdo = null;
    $db_connection_error = $e->getMessage();
    error_log('[StudyFlix][DB] Falha de conexão: ' . $e->getMessage());
}
