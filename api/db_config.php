<?php
declare(strict_types=1);

/**
 * Conexão MongoDB compatível com Railway.
 *
 * Prioridade:
 * 1) MONGO_URL (recomendado no Railway)
 * 2) MONGODB_URI
 * 3) MONGOHOST/MONGOPORT/MONGOUSER/MONGOPASSWORD
 *
 * Banco lógico:
 * 1) MONGO_DATABASE
 * 2) MONGODATABASE
 * 3) nome presente no caminho da URI
 * 4) studyflix
 */

function studyflix_mongo_connection(): array
{
    if (!extension_loaded('mongodb')) {
        throw new RuntimeException('Extensão mongodb do PHP não está carregada.');
    }

    $uri = trim((string) (getenv('MONGO_URL') ?: getenv('MONGODB_URI') ?: ''));
    $database = trim((string) (getenv('MONGO_DATABASE') ?: getenv('MONGODATABASE') ?: ''));

    if ($uri === '') {
        $host = trim((string) (getenv('MONGOHOST') ?: ''));
        $port = (int) (getenv('MONGOPORT') ?: 27017);
        $user = (string) (getenv('MONGOUSER') ?: '');
        $password = (string) (getenv('MONGOPASSWORD') ?: '');

        if ($host === '') {
            throw new RuntimeException(
                'Banco não configurado. Defina MONGO_URL ou MONGOHOST/MONGOPORT.'
            );
        }

        $credentials = '';
        if ($user !== '') {
            $credentials = rawurlencode($user) . ':' . rawurlencode($password) . '@';
        }

        $uri = sprintf('mongodb://%s%s:%d', $credentials, $host, $port);
    }

    if ($database === '') {
        $parts = parse_url($uri);
        if (is_array($parts) && isset($parts['path'])) {
            $candidate = trim(rawurldecode((string) $parts['path']), '/');
            if ($candidate !== '') {
                $database = $candidate;
            }
        }
    }

    if ($database === '') {
        $database = 'studyflix';
    }

    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $database)) {
        throw new RuntimeException('Nome do banco MongoDB inválido.');
    }

    $manager = new MongoDB\Driver\Manager($uri, [
        'serverSelectionTimeoutMS' => 5000,
        'connectTimeoutMS' => 5000,
    ]);

    // Força uma conexão real agora. O construtor do Manager é lazy.
    $manager->executeCommand($database, new MongoDB\Driver\Command(['ping' => 1]));

    return [$manager, $database];
}

$mongo = null;
$mongo_db = null;
$db_connection_error = null;

try {
    [$mongo, $mongo_db] = studyflix_mongo_connection();
} catch (Throwable $e) {
    $mongo = null;
    $mongo_db = null;
    $db_connection_error = $e->getMessage();
    error_log('[StudyFlix][MongoDB] Falha de conexão: ' . $e->getMessage());
}
