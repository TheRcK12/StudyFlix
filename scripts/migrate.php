<?php
declare(strict_types=1);

/**
 * Migração idempotente executada pelo pre-deploy do Railway.
 * Cria as tabelas necessárias e garante um conjunto mínimo de questões para testes.
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

$maxAttempts = max(1, (int) (getenv('DB_CONNECT_RETRIES') ?: 20));
$retryDelay = max(1, (int) (getenv('DB_CONNECT_RETRY_SECONDS') ?: 3));
$pdo = null;
$db_connection_error = null;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    include $dbConfig;

    if ($pdo) {
        break;
    }

    fwrite(STDERR, sprintf(
        "[StudyFlix] Banco ainda indisponível (tentativa %d/%d).\n",
        $attempt,
        $maxAttempts
    ));

    if ($attempt < $maxAttempts) {
        sleep($retryDelay);
    }
}

if (!$pdo) {
    fwrite(STDERR, "[StudyFlix] Falha: DATABASE_URL/PG* não configurado ou banco indisponível.\n");
    if (!empty($db_connection_error)) {
        fwrite(STDERR, "[StudyFlix] Detalhe: {$db_connection_error}\n");
    }
    exit(1);
}

$schemaCandidates = [
    '/opt/studyflix/database/schema.sql',
    dirname(__DIR__) . '/database/schema.sql',
];
$schemaFile = null;
foreach ($schemaCandidates as $candidate) {
    if (is_file($candidate)) {
        $schemaFile = $candidate;
        break;
    }
}
if ($schemaFile === null || !is_file($schemaFile)) {
    fwrite(STDERR, "[StudyFlix] Falha: schema.sql não encontrado.\n");
    exit(1);
}

try {
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new RuntimeException('Não foi possível ler schema.sql.');
    }

    $pdo->exec($schema);

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

    $sql = <<<'SQL'
INSERT INTO questions (
    question_id, area, enunciado, option_a, option_b, option_c, option_d, option_e, correct_option
) VALUES (
    :question_id, :area, :enunciado, :option_a, :option_b, :option_c, :option_d, :option_e, :correct_option
)
ON CONFLICT (question_id) DO UPDATE SET
    area = EXCLUDED.area,
    enunciado = EXCLUDED.enunciado,
    option_a = EXCLUDED.option_a,
    option_b = EXCLUDED.option_b,
    option_c = EXCLUDED.option_c,
    option_d = EXCLUDED.option_d,
    option_e = EXCLUDED.option_e,
    correct_option = EXCLUDED.correct_option,
    updated_at = NOW()
SQL;

    $stmt = $pdo->prepare($sql);
    foreach ($questions as $question) {
        $stmt->execute($question);
    }

    $counts = [
        'usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
        'questions' => (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
        'user_scores' => (int) $pdo->query('SELECT COUNT(*) FROM user_scores')->fetchColumn(),
    ];

    fwrite(STDOUT, sprintf(
        "[StudyFlix] Migração concluída. usuarios=%d, questions=%d, user_scores=%d\n",
        $counts['usuarios'],
        $counts['questions'],
        $counts['user_scores']
    ));
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[StudyFlix] Migração falhou: ' . $e->getMessage() . "\n");
    exit(1);
}
