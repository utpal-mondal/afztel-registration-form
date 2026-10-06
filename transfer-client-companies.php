<?php

/**
 * Transfer client_companies data from a source MySQL database to a target MySQL database.
 *
 * WARNING: This file contains plain-text database credentials.
 * Do not commit or push it to Git. Keep it on your local machine only.
 *
 * Fill in the $source and $target arrays below, then run:
 *
 *     php transfer-client-companies.php
 */

// ----- Database credentials -----

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
    'id'   => 'id',
    'name' => 'name',
    // No source mapping for: user_id, created_at, updated_at.
];

// Transform a source value before it is written to the target.
// Keyed by the source column name.
$transformers = [
    'name' => function ($value) {
        return $value === null ? null : mb_substr((string) $value, 0, 255);
    },
];

// Computed columns: target column => closure(row) returning the value.
$computed = [
    'created_at' => function ($row) {
        return date('Y-m-d H:i:s');
    },
    'updated_at' => function ($row) {
        return date('Y-m-d H:i:s');
    },
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

foreach ($computed as $targetCol => $closure) {
    $expandedMap[] = ['target' => $targetCol, 'closure' => $closure];
    $targetColumns[] = $targetCol;
}

// Make sure each target column exists in the real target table.
$existingColumns = $targetPdo->query('SHOW COLUMNS FROM client_companies')
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

$insertSql = "INSERT INTO client_companies ({$columnsSql}) VALUES ({$placeholders})";
$insertStmt = $targetPdo->prepare($insertSql);

// ----- Transfer -----

// Disable foreign key checks while the table is cleared and rebuilt.
$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE client_companies');
        echo "Target table truncated.\n";
    }

    $sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM client_companies')->fetchColumn();
    echo "Source client_companies rows: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query('SELECT * FROM client_companies');
    $transferred = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = [];

        foreach ($expandedMap as $map) {
            if (isset($map['closure']) && is_callable($map['closure'])) {
                $value = $map['closure']($row);
            } else {
                $sourceCol = $map['source'];
                $value = $row[$sourceCol] ?? null;

                if (isset($transformers[$sourceCol]) && is_callable($transformers[$sourceCol])) {
                    $value = $transformers[$sourceCol]($value);
                }
            }

            $values[] = $value;
        }

        $insertStmt->execute($values);
        $transferred++;
    }

    $targetPdo->commit();

    echo "Transferred {$transferred} client_companies rows to target database.\n";
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
