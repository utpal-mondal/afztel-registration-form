<?php

/**
 * Transfer brands from a source MySQL database to a target MySQL database.
 *
 * WARNING: This file now contains plain-text database credentials.
 * Do not commit or push it to Git. Keep it on your local machine only.
 *
 * Fill in the $source and $target arrays below, then run:
 *
 *     php transfer-brands.php
 */

// ----- Database credentials -----
// Fill these in with your source and target database details.
/*
$source = [
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'user'     => 'root',
    'pass'     => '',
    'database' => 'source_db',
];

$target = [
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'user'     => 'root',
    'pass'     => '',
    'database' => 'target_db',
];
*/
$source = [
    'host'     => 'afztelcrm.cp8csqqkyq51.eu-central-1.rds.amazonaws.com',
    'port'     => '3306',
    'user'     => 'admin',
    'pass'     => '$*Apple4678',
    'database' => 'afztel_crm',
];

$target = [
    'host'     => 'afztelcrm.cp8csqqkyq51.eu-central-1.rds.amazonaws.com',
    'port'     => '3306',
    'user'     => 'admin',
    'pass'     => '$*Apple4678',
    'database' => 'afztel',
];

// Set to false if you want to append instead of replacing existing rows.
$clearTarget = true;

// Column mapping: source column => target column
// Use a string for one target, or an array to copy one source value into multiple targets.
$columnMap = [
    'id'          => 'id',
    'business_id' => 'company_id',
    'name'        => 'name',
    'description' => 'description',
    'created_at'  => 'created_at',
    'updated_at'  => 'updated_at',
    // Add more mappings below, e.g.:
    // 'use_for_repair' => 'priority',
];

// Transform a source value before it is written to the target.
// Keyed by the source column name. Use a closure or a PHP function name.
$transformers = [
    'id'          => 'intval',
    'business_id' => 'intval',
    'created_at'  => function ($value) { return $value ?? date('Y-m-d H:i:s'); },
    'updated_at'  => function ($value) { return $value ?? date('Y-m-d H:i:s'); },
];

// ----- Connect -----

$sourcePdo = new PDO(
    "mysql:host={$source['host']};port={$source['port']};dbname={$source['database']};charset=utf8mb4",
    $source['user'],
    $source['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$targetPdo = new PDO(
    "mysql:host={$target['host']};port={$target['port']};dbname={$target['database']};charset=utf8mb4",
    $target['user'],
    $target['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ----- Prepare target columns -----

$expandedMap = [];
$targetColumns = [];

foreach ($columnMap as $sourceCol => $targets) {
    $targets = is_array($targets) ? $targets : [$targets];
    foreach ($targets as $targetCol) {
        $expandedMap[] = ['source' => $sourceCol, 'target' => $targetCol];
        $targetColumns[] = $targetCol;
    }
}

// Make sure each target column exists in the real target table.
$existingColumns = $targetPdo->query('SHOW COLUMNS FROM brands')
    ->fetchAll(PDO::FETCH_COLUMN);

$validMap = [];
$validColumns = [];

foreach ($expandedMap as $map) {
    if (in_array($map['target'], $existingColumns, true)) {
        $validMap[] = $map;
        $validColumns[] = $map['target'];
    } else {
        echo "Skipping target column '{$map['target']}' (not in target table).\n";
    }
}

$expandedMap = $validMap;
$targetColumns = $validColumns;

$columnsSql = '`' . implode('`, `', $targetColumns) . '`';
$placeholders = implode(',', array_fill(0, count($targetColumns), '?'));

$insertSql = "INSERT INTO brands ({$columnsSql}) VALUES ({$placeholders})";
$insertStmt = $targetPdo->prepare($insertSql);

// ----- Transfer -----

// Disable foreign key checks while the table is cleared and rebuilt.
$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE brands');
        echo "Target table truncated.\n";
    }

    $sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM brands')->fetchColumn();
    echo "Source brands: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query('SELECT * FROM brands');
    $transferred = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = [];

        foreach ($expandedMap as $map) {
            $sourceCol = $map['source'];
            $value = $row[$sourceCol] ?? null;

            if (isset($transformers[$sourceCol]) && is_callable($transformers[$sourceCol])) {
                $value = $transformers[$sourceCol]($value);
            }

            $values[] = $value;
        }

        $insertStmt->execute($values);
        $transferred++;
    }

    $targetPdo->commit();

    echo "Transferred {$transferred} brands to target database.\n";
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
