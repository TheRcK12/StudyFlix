<?php
declare(strict_types=1);

require __DIR__ . '/api/session.php';
studyflix_start_session();

studyflix_destroy_session();
header('Location: login.html', true, 302);
exit;
