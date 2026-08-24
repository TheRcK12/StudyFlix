<?php
declare(strict_types=1);

function studyflix_mongo_namespace(string $database, string $collection): string
{
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $database)) {
        throw new InvalidArgumentException('Banco MongoDB inválido.');
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $collection)) {
        throw new InvalidArgumentException('Coleção MongoDB inválida.');
    }

    return $database . '.' . $collection;
}

function studyflix_mongo_find_one(
    MongoDB\Driver\Manager $manager,
    string $database,
    string $collection,
    array $filter,
    array $options = []
): ?array {
    $options['limit'] = 1;
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery(
        studyflix_mongo_namespace($database, $collection),
        $query
    );
    $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

    foreach ($cursor as $document) {
        return is_array($document) ? $document : (array) $document;
    }

    return null;
}

function studyflix_mongo_find_many(
    MongoDB\Driver\Manager $manager,
    string $database,
    string $collection,
    array $filter,
    array $options = []
): array {
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery(
        studyflix_mongo_namespace($database, $collection),
        $query
    );
    $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

    $items = [];
    foreach ($cursor as $document) {
        $items[] = is_array($document) ? $document : (array) $document;
    }

    return $items;
}

function studyflix_mongo_insert_one(
    MongoDB\Driver\Manager $manager,
    string $database,
    string $collection,
    array $document
): void {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($document);
    $manager->executeBulkWrite(studyflix_mongo_namespace($database, $collection), $bulk);
}

function studyflix_mongo_update_one(
    MongoDB\Driver\Manager $manager,
    string $database,
    string $collection,
    array $filter,
    array $update,
    bool $upsert = false
): void {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update($filter, $update, ['multi' => false, 'upsert' => $upsert]);
    $manager->executeBulkWrite(studyflix_mongo_namespace($database, $collection), $bulk);
}

function studyflix_mongo_aggregate_one(
    MongoDB\Driver\Manager $manager,
    string $database,
    string $collection,
    array $pipeline
): ?array {
    $command = new MongoDB\Driver\Command([
        'aggregate' => $collection,
        'pipeline' => $pipeline,
        'cursor' => (object) [],
    ]);

    $cursor = $manager->executeCommand($database, $command);
    $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

    foreach ($cursor as $document) {
        return is_array($document) ? $document : (array) $document;
    }

    return null;
}
