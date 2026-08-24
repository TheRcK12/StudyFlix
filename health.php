<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(200);

$mongoConfigured = trim((string) (getenv('MONGO_URL') ?: getenv('MONGODB_URI') ?: '')) !== ''
    || trim((string) (getenv('MONGOHOST') ?: '')) !== '';

echo json_encode([
    'status' => 'ok',
    'service' => getenv('RAILWAY_SERVICE_NAME') ?: 'studyflix',
    'web_server' => 'nginx+php-fpm',
    'database_type' => 'mongodb',
    'database_configured' => $mongoConfigured,
    'port' => getenv('PORT') ?: '8080',
    'timestamp' => gmdate('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
