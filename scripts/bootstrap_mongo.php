<?php
declare(strict_types=1);

/**
 * Bootstrap idempotente do MongoDB para Railway.
 * Cria índices e insere/atualiza um conjunto mínimo de questões para testes.
 */

$dbConfigCandidates = [
    '/var/www/html/api/db_config.php',
    dirname(__DIR__) . '/api/db_config.php',
];

$dbConfig = null;
foreach ($dbConfigCandidates as $candidate) {
    if (is_file($candidate)) {
        $dbConfig = $candidate;
        break;
    }
}

if ($dbConfig === null) {
    fwrite(STDERR, "[StudyFlix] Falha: db_config.php não encontrado.\n");
    exit(1);
}

require $dbConfig;

$maxAttempts = max(1, (int) (getenv('DB_CONNECT_RETRIES') ?: 20));
$retryDelay = max(1, (int) (getenv('DB_CONNECT_RETRY_SECONDS') ?: 3));

if (!$mongo || !$mongo_db) {
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            [$mongo, $mongo_db] = studyflix_mongo_connection();
            break;
        } catch (Throwable $e) {
            $db_connection_error = $e->getMessage();
            fwrite(STDERR, sprintf(
                "[StudyFlix] MongoDB ainda indisponível (tentativa %d/%d).\n",
                $attempt,
                $maxAttempts
            ));

            if ($attempt < $maxAttempts) {
                sleep($retryDelay);
            }
        }
    }
}

if (!$mongo || !$mongo_db) {
    fwrite(STDERR, "[StudyFlix] Falha: MONGO_URL/MONGOHOST não configurado ou MongoDB indisponível.\n");
    if (!empty($db_connection_error)) {
        fwrite(STDERR, "[StudyFlix] Detalhe: {$db_connection_error}\n");
    }
    exit(1);
}

try {
    $indexCommands = [
        new MongoDB\Driver\Command([
            'createIndexes' => 'usuarios',
            'indexes' => [[
                'key' => ['email' => 1],
                'name' => 'uniq_usuarios_email',
                'unique' => true,
            ]],
        ]),
        new MongoDB\Driver\Command([
            'createIndexes' => 'questions',
            'indexes' => [[
                'key' => ['question_id' => 1],
                'name' => 'uniq_questions_question_id',
                'unique' => true,
            ], [
                'key' => ['area' => 1],
                'name' => 'idx_questions_area',
            ]],
        ]),
        new MongoDB\Driver\Command([
            'createIndexes' => 'user_scores',
            'indexes' => [[
                'key' => ['username' => 1],
                'name' => 'uniq_user_scores_username',
                'unique' => true,
            ], [
                'key' => ['total_correct' => -1, 'total_attempted' => -1, 'display_name' => 1],
                'name' => 'idx_ranking',
            ]],
        ]),
    ];

    foreach ($indexCommands as $command) {
        $mongo->executeCommand($mongo_db, $command);
    }

    $questions = [
        [
            'question_id' => 'natureza_fotossintese_001',
            'area' => 'Natureza',
            'enunciado' => 'A fotossíntese produz qual substância?',
            'option_a' => 'Glicose e oxigênio',
            'option_b' => 'Glicose e gás carbônico',
            'option_c' => 'Água e oxigênio',
            'option_d' => 'Água e gás carbônico',
            'option_e' => null,
            'correct_option' => 'a',
        ],
        [
            'question_id' => 'natureza_fisica_001',
            'area' => 'Natureza',
            'enunciado' => 'Um corpo de 10 kg recebe força de 50 N. Sua aceleração é:',
            'option_a' => '2 m/s²',
            'option_b' => '5 m/s²',
            'option_c' => '10 m/s²',
            'option_d' => '50 m/s²',
            'option_e' => null,
            'correct_option' => 'b',
        ],
        [
            'question_id' => 'humanas_revolucao_industrial_001',
            'area' => 'Humanas',
            'enunciado' => 'A Revolução Industrial trouxe como consequência:',
            'option_a' => 'Fortalecimento do feudalismo',
            'option_b' => 'Êxodo rural e urbanização',
            'option_c' => 'Diminuição da produção',
            'option_d' => 'Retorno à economia agrária',
            'option_e' => null,
            'correct_option' => 'b',
        ],
        [
            'question_id' => 'humanas_constituicao_1988_001',
            'area' => 'Humanas',
            'enunciado' => 'A Constituição de 1988 estabelece:',
            'option_a' => 'Centralização do poder',
            'option_b' => 'Soberania popular e direitos fundamentais',
            'option_c' => 'Monarquia',
            'option_d' => 'Sufrágio censitário',
            'option_e' => null,
            'correct_option' => 'b',
        ],
        [
            'question_id' => 'matematica_funcao_001',
            'area' => 'Matematica',
            'enunciado' => 'Se f(x) = 2x + 3, quanto é f(5)?',
            'option_a' => '8',
            'option_b' => '10',
            'option_c' => '13',
            'option_d' => '15',
            'option_e' => null,
            'correct_option' => 'c',
        ],
        [
            'question_id' => 'matematica_pitagoras_001',
            'area' => 'Matematica',
            'enunciado' => 'Catetos 3 cm e 4 cm. A hipotenusa mede:',
            'option_a' => '5 cm',
            'option_b' => '7 cm',
            'option_c' => '12 cm',
            'option_d' => '25 cm',
            'option_e' => null,
            'correct_option' => 'a',
        ],
        [
            'question_id' => 'linguagens_personificacao_001',
            'area' => 'Linguagens',
            'enunciado' => "'O vento sussurrava' é exemplo de:",
            'option_a' => 'Metáfora',
            'option_b' => 'Metonímia',
            'option_c' => 'Personificação',
            'option_d' => 'Hipérbole',
            'option_e' => null,
            'correct_option' => 'c',
        ],
        [
            'question_id' => 'linguagens_colocacao_pronominal_001',
            'area' => 'Linguagens',
            'enunciado' => 'Colocação pronominal adequada à norma-padrão:',
            'option_a' => 'Me diga a verdade',
            'option_b' => 'Diga-me a verdade',
            'option_c' => 'Diga a verdade-me',
            'option_d' => 'A verdade me diga',
            'option_e' => null,
            'correct_option' => 'b',
        ],
    ];

    $bulk = new MongoDB\Driver\BulkWrite();
    foreach ($questions as $question) {
        $now = new MongoDB\BSON\UTCDateTime();
        $bulk->update(
            ['question_id' => $question['question_id']],
            [
                '$set' => array_merge($question, ['updated_at' => $now]),
                '$setOnInsert' => ['created_at' => $now],
            ],
            ['multi' => false, 'upsert' => true]
        );
    }

    $mongo->executeBulkWrite($mongo_db . '.questions', $bulk);

    fwrite(STDOUT, sprintf(
        "[StudyFlix] MongoDB pronto. database=%s, questions_seed=%d\n",
        $mongo_db,
        count($questions)
    ));
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[StudyFlix] Bootstrap MongoDB falhou: ' . $e->getMessage() . "\n");
    exit(1);
}
