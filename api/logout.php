<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/session.php';
studyflix_start_session();

studyflix_destroy_session();

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
