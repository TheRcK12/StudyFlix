<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(200);

$dbConfigured = trim((string) (getenv('DATABASE_URL') ?: '')) !== ''
    || (
        trim((string) (getenv('PGHOST') ?: '')) !== ''
        && trim((string) (getenv('PGDATABASE') ?: '')) !== ''
        && trim((string) (getenv('PGUSER') ?: '')) !== ''
    );

echo json_encode([
    'status' => 'ok',
    'service' => getenv('RAILWAY_SERVICE_NAME') ?: 'studyflix',
    'port' => getenv('PORT') ?: '8080',
    'database_configured' => $dbConfigured,
    'timestamp' => gmdate('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
