<?php
// Script para adicionar questões ao banco

try {
    $mongoClient = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    $dbName = "test_database";
    echo "✅ Conectado\n";
} catch (Exception $e) {
    die("❌ Erro: " . $e->getMessage() . "\n");
}

// ⚠️ ADICIONE SUAS QUESTÕES AQUI
$QUESTOES = [
    // NATUREZA
    [
        "area" => "Natureza",
        "enunciado" => "A fotossíntese produz qual substância?",
        "option_a" => "Glicose e oxigênio",
        "option_b" => "Glicose e gás carbônico",
        "option_c" => "Água e oxigênio",
        "option_d" => "Água e gás carbônico",
        "option_e" => null,
        "correct_option" => "a"
    ],
    [
        "area" => "Natureza",
        "enunciado" => "Um corpo de 10 kg recebe força de 50 N. Sua aceleração é:",
        "option_a" => "2 m/s²",
        "option_b" => "5 m/s²",
        "option_c" => "10 m/s²",
        "option_d" => "50 m/s²",
        "option_e" => null,
        "correct_option" => "b"
    ],
    
    // HUMANAS
    [
        "area" => "Humanas",
        "enunciado" => "A Revolução Industrial trouxe como consequência:",
        "option_a" => "Fortalecimento do feudalismo",
        "option_b" => "Êxodo rural e urbanização",
        "option_c" => "Diminuição da produção",
        "option_d" => "Retorno à economia agrária",
        "option_e" => null,
        "correct_option" => "b"
    ],
    [
        "area" => "Humanas",
        "enunciado" => "A Constituição de 1988 estabelece:",
        "option_a" => "Centralização do poder",
        "option_b" => "Soberania popular e direitos fundamentais",
        "option_c" => "Monarquia",
        "option_d" => "Sufrágio censitário",
        "option_e" => null,
        "correct_option" => "b"
    ],
    
    // MATEMÁTICA
    [
        "area" => "Matematica",
        "enunciado" => "Se f(x) = 2x + 3, quanto é f(5)?",
        "option_a" => "8",
        "option_b" => "10",
        "option_c" => "13",
        "option_d" => "15",
        "option_e" => null,
        "correct_option" => "c"
    ],
    [
        "area" => "Matematica",
        "enunciado" => "Catetos 3cm e 4cm. Hipotenusa:",
        "option_a" => "5 cm",
        "option_b" => "7 cm",
        "option_c" => "12 cm",
        "option_d" => "25 cm",
        "option_e" => null,
        "correct_option" => "a"
    ],
    
    // LINGUAGENS
    [
        "area" => "Linguagens",
        "enunciado" => "'O vento sussurrava' é exemplo de:",
        "option_a" => "Metáfora",
        "option_b" => "Metonímia",
        "option_c" => "Personificação",
        "option_d" => "Hipérbole",
        "option_e" => null,
        "correct_option" => "c"
    ],
    [
        "area" => "Linguagens",
        "enunciado" => "Colocação pronominal correta:",
        "option_a" => "Me diga a verdade",
        "option_b" => "Diga-me a verdade",
        "option_c" => "Diga a verdade-me",
        "option_d" => "A verdade me diga",
        "option_e" => null,
        "correct_option" => "b"
    ],
];

// Limpar questões antigas
try {
    echo "\n🗑️  Limpando...\n";
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete([]);
    $mongoClient->executeBulkWrite($dbName . '.questions', $bulk);
} catch (Exception $e) {
    echo "⚠️  " . $e->getMessage() . "\n";
}

// Adicionar novas
echo "\n📝 Adicionando " . count($QUESTOES) . " questões...\n";
$bulk = new MongoDB\Driver\BulkWrite;

foreach ($QUESTOES as $i => $questao) {
    $questao['question_id'] = uniqid('q_', true);
    $bulk->insert($questao);
    echo "   ✅ Questão " . ($i+1) . "/" . count($QUESTOES) . "\n";
}

$mongoClient->executeBulkWrite($dbName . '.questions', $bulk);
echo "\n✨ Pronto!\n\n";
?>