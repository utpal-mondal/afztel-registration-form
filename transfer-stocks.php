<?php

/**
 * Transfer stock/location details from a source MySQL database to a target MySQL database.
 *
 * WARNING: This file now contains plain-text database credentials.
 * Do not commit or push it to Git. Keep it on your local machine only.
 *
 * Fill in the $source and $target arrays below, then run:
 *
 *     php transfer-stocks.php
 */

// ----- Database credentials -----
// Fill these in with your source and target database details.

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

// Map source location_id values to target warehouse_id values.
$warehouseMap = [
    1  => 15,
    2  => 14,
    3  => 18,
    4  => 3,
    5  => 16,
    10 => 12,
    11 => 13,
    13 => 17,
    14 => 11,
];

// Column mapping: source column => target column
// Use a string for one target, or an array to copy one source value into multiple targets.
$columnMap = [
    'id'           => 'id',
    'product_id'   => 'product_id',
    'qty_available' => 'stock_no',
    'created_at'   => 'created_at',
    'updated_at'   => 'updated_at',
    // Add more mappings below, e.g.:
    // 'product_variation_id' => 'product_variation_id',
];

// Transform a source value before it is written to the target.
// Keyed by the source column name. Use a closure or a PHP function name.
$transformers = [
    'qty_available' => function ($value) {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    },
    'created_at' => function ($value) {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', strtotime($value));
    },
    'updated_at' => function ($value) {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', strtotime($value));
    },
];

// Computed columns: target column => closure(row) returning the value.
$computed = [
    'warehouse_id' => function ($row) use ($warehouseMap) {
        $locationId = $row['location_id'] ?? null;
        return ($locationId !== null && isset($warehouseMap[(int) $locationId]))
            ? $warehouseMap[(int) $locationId]
            : null;
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
$existingColumns = $targetPdo->query('SHOW COLUMNS FROM products_stocks')
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

$insertSql = "INSERT INTO products_stocks ({$columnsSql}) VALUES ({$placeholders})";
$insertStmt = $targetPdo->prepare($insertSql);

// ----- Transfer -----

// Disable foreign key checks while the table is cleared and rebuilt.
$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE products_stocks');
        echo "Target table truncated.\n";
    }

    $sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM variation_location_details')->fetchColumn();
    echo "Source stock rows: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query('SELECT * FROM variation_location_details');
    $transferred = 0;
    $skipped = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $locationId = $row['location_id'] ?? null;

        if ($locationId === null || ! isset($warehouseMap[(int) $locationId])) {
            $skipped++;
            continue;
        }

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

    echo "Transferred {$transferred} stock rows to target database.\n";
    if ($skipped > 0) {
        echo "Skipped {$skipped} rows with unmapped location_id.\n";
    }
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
