<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(200);

echo json_encode([
    'status' => 'ok',
    'service' => getenv('RAILWAY_SERVICE_NAME') ?: 'studyflix',
    'timestamp' => gmdate('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
