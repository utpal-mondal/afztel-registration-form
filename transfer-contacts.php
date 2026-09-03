<?php

/**
 * Transfer contacts from a source MySQL database.
 *
 * - If source `type` is 'customer', the row is inserted into the target `customers` table.
 * - If source `type` is 'supplier', the row is inserted into the target `suppliers` table.
 *
 * WARNING: This file contains plain-text database credentials.
 * Do not commit or push it to Git. Keep it on your local machine only.
 *
 * Fill in the $source and $target arrays below, then run:
 *
 *     php transfer-contacts.php
 */

// ----- Database credentials -----

/*$source = [
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

// Skip contacts that have a non-null deleted_at timestamp.
$skipDeleted = true;

// ---- Helpers ----

function limitString($value, $length) {
    $value = (string) ($value ?? '');
    return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
}

function countryCode($value) {
    $code = strtoupper(limitString($value, 2));
    return $code === '' ? null : $code;
}

// ----- Column mappings -----

$customerMap = [
    'id'            => ['id', 'customer_no'],
    'business_id'   => 'company_id',
    'name'          => ['fullname', 'contact_name'],
    'email'         => 'email',
    'mobile'        => 'mobileno',
    'address_line_1' => 'address',
    'address_line_2' => 'address2',
    'city'          => 'city',
    'state'         => 'region',
    'zip_code'      => 'pincode',
    'country'       => 'country',
    'contact_id'    => 'customer_id',
    'tax_number'    => 'VAT_number',
    'credit_limit'  => 'credit_limit',
    'balance'       => 'outstanding_balance',
    'customer_group_id' => 'customer_group_id',
    'created_at'    => 'created_at',
    'updated_at'    => 'updated_at',
];

$customerComputed = [
    'customer_type' => function ($row) {
        return !empty($row['supplier_business_name']) ? 'company' : 'person';
    },
];

$supplierMap = [
    'id'          => 'id',
    'business_id' => 'company_id',
    'name'        => 'contact_name',
    'email'       => 'email',
    'tax_number'  => 'VAT_number',
    'credit_limit' => 'credit_limit',
    'balance'     => 'outstanding_balance',
    'city'        => 'city',
    'zip_code'    => 'zip_code',
    'created_at'  => 'created_at',
    'updated_at'  => 'updated_at',
];

$supplierComputed = [
    'name'        => function ($row) {
        $name = !empty($row['supplier_business_name']) ? $row['supplier_business_name'] : ($row['name'] ?? '');
        return limitString($name, 50);
    },
    'phone_no'    => function ($row) {
        return limitString($row['mobile'] ?? '', 12);
    },
    'address'     => function ($row) {
        return limitString($row['address_line_1'] ?? '', 100);
    },
    'address2'    => function ($row) {
        return limitString($row['address_line_2'] ?? '', 100);
    },
    'country_code' => function ($row) {
        return countryCode($row['country'] ?? '') ?? 'NL';
    },
];

$transformers = [
    'id'            => 'intval',
    'business_id'   => 'intval',
    'customer_group_id' => 'intval',
    'name'          => function ($value) { return limitString($value, 50); },
    'email'         => function ($value) { return limitString($value, 50); },
    'mobile'        => function ($value) { return limitString($value, 15); },
    'address_line_1' => function ($value) { return limitString($value, 50); },
    'address_line_2' => function ($value) { return limitString($value, 50); },
    'city'          => function ($value) { return limitString($value, 50); },
    'state'         => function ($value) { return limitString($value, 50); },
    'zip_code'      => function ($value) { return limitString($value, 10); },
    'country'       => function ($value) { return countryCode($value); },
    'contact_id'    => function ($value) { return limitString($value, 20); },
    'tax_number'    => function ($value) { return limitString($value, 50); },
    'credit_limit'  => function ($value) { return is_numeric($value) ? intval($value) : 0; },
    'balance'       => function ($value) { return is_numeric($value) ? round((float) $value, 2) : 0.0; },
    'created_at'    => function ($value) { return $value ?? date('Y-m-d H:i:s'); },
    'updated_at'    => function ($value) { return $value ?? date('Y-m-d H:i:s'); },
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

// ----- Prepare target inserts -----

function prepareTable($pdo, $table, array $map, array $computed, array $transformers) {
    $expandedMap = [];
    $targetColumns = [];

    foreach ($map as $sourceCol => $targets) {
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

    $existingColumns = $pdo->query("SHOW COLUMNS FROM {$table}")
        ->fetchAll(PDO::FETCH_COLUMN);

    $validMap = [];
    $validColumns = [];

    foreach ($expandedMap as $map) {
        if (in_array($map['target'], $existingColumns, true)) {
            $validMap[] = $map;
            $validColumns[] = $map['target'];
        } else {
            echo "Skipping target column '{$table}.{$map['target']}' (not in target table).\n";
        }
    }

    $columnsSql = '`' . implode('`, `', $validColumns) . '`';
    $placeholders = implode(',', array_fill(0, count($validColumns), '?'));
    $insertSql = "INSERT INTO {$table} ({$columnsSql}) VALUES ({$placeholders})";

    return [
        'stmt' => $pdo->prepare($insertSql),
        'map' => $validMap,
        'transformers' => $transformers,
    ];
}

$customerInsert = prepareTable($targetPdo, 'customers', $customerMap, $customerComputed, $transformers);
$supplierInsert = prepareTable($targetPdo, 'suppliers', $supplierMap, $supplierComputed, $transformers);

// ----- Transfer -----

$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE customers');
        $targetPdo->exec('TRUNCATE TABLE suppliers');
        echo "Target tables truncated.\n";
    }

    $where = '';
    if ($skipDeleted) {
        $where = 'WHERE deleted_at IS NULL';
    }

    $sourceCount = (int) $sourcePdo->query("SELECT COUNT(*) FROM contacts {$where}")->fetchColumn();
    echo "Source contacts: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query("SELECT * FROM contacts {$where}");

    $customerCount = 0;
    $supplierCount = 0;
    $skippedCount = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['type'] ?? '');

        if ($type === 'customer') {
            insertRow($targetPdo, $customerInsert, $row);
            $customerCount++;
        } elseif ($type === 'supplier') {
            insertRow($targetPdo, $supplierInsert, $row);
            $supplierCount++;
        } else {
            $skippedCount++;
        }
    }

    $targetPdo->commit();

    echo "Transferred {$customerCount} customers and {$supplierCount} suppliers.\n";
    if ($skippedCount > 0) {
        echo "Skipped {$skippedCount} contacts with unrecognised type.\n";
    }
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function insertRow($pdo, $insert, $row) {
    $values = [];

    foreach ($insert['map'] as $map) {
        if (isset($map['closure']) && is_callable($map['closure'])) {
            $value = $map['closure']($row);
        } else {
            $sourceCol = $map['source'];
            $value = $row[$sourceCol] ?? null;

            if (isset($insert['transformers'][$sourceCol]) && is_callable($insert['transformers'][$sourceCol])) {
                $value = $insert['transformers'][$sourceCol]($value);
            }
        }

        $values[] = $value;
    }

    $insert['stmt']->execute($values);
}
