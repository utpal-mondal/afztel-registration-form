<?php

/**
 * Transfer tracking control data (essentials_holidays -> track_controls)
 * from a source MySQL database to a target MySQL database.
 *
 * WARNING: This file contains plain-text database credentials.
 * Do not commit or push it to Git. Keep it on your local machine only.
 *
 * Fill in the $source and $target arrays below, then run:
 *
 *     php transfer-track-controls.php
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

// Map source user_id values to target responsible_person names.
$responsiblePersonMap = [
    2  => 'Akmal Rashid',
    3  => 'Sarim Ahmad',
    6  => 'Waheed',
    31 => 'Asim Baloch',
    32 => 'Aqib Ali',
];

// Column mapping: source column => target column
// Use a string for one target, or an array to copy one source value into multiple targets.
$columnMap = [
    'id'                         => 'id',
    'type'                       => 'tracking_type',
    'invoice_number'             => 'invoice_number',
    'start_date'                 => 'start_date',
    'date'                       => ['created_at', 'updated_at'],
    'our_company'                => ['our_company_id', 'our_company'],
    'client_company'             => ['client_company_id', 'client_company'],
    'total_amount'               => 'total_amount',
    'note'                       => 'note',
    'first_ref_date'             => 'first_reference_date',
    'first_ref_total'            => 'first_reference_amount',
    'first_ref_bank'             => 'first_reference_bank_name',
    'second_ref_date'            => 'second_reference_date',
    'second_ref_total'           => 'second_reference_amount',
    'second_ref_bank'            => 'second_reference_bank_name',
    'third_ref_date'             => 'third_reference_date',
    'third_ref_total'            => 'third_reference_amount',
    'third_ref_bank'             => 'third_reference_bank_name',
    'fourth_ref_date'            => 'fourth_reference_date',
    'fourth_ref_total'           => 'fourth_reference_amount',
    'fourth_ref_bank'            => 'fourth_reference_bank_name',
    'fifth_ref_date'             => 'fifth_reference_date',
    'fifth_ref_total'            => 'fifth_reference_amount',
    'fifth_ref_bank'             => 'fifth_reference_bank_name',
    'first_shipping_ref_date'    => 'first_shipping_reference_date',
    'first_shipping_ref_method'  => 'first_shipping_reference_method',
    'first_shipping_ref_box'     => 'first_reference_t_box',
    'first_shipping_ref_amount'  => 'first_reference_tracking_id',
    'second_shipping_ref_date'   => 'second_shipping_reference_date',
    'second_shipping_ref_method' => 'second_shipping_reference_method',
    'second_shipping_ref_box'    => 'second_reference_t_box',
    'second_shipping_ref_amount' => 'second_reference_tracking_id',
    'user_id'                    => 'user_id',
    'final_trakcing_id'          => 'tracking_number',
    'status'                     => 'status',
];

// ----- Shared transformers -----

// Parse a text/varchar date into 'Y-m-d H:i:s', or null if empty/unparseable.
$toDatetime = function ($value) {
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    $ts = strtotime((string) $value);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
};

// Convert a text amount into a decimal number, or null if empty/unparseable.
$toDecimal = function ($value) {
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    $clean = preg_replace('/[^\d.\-]/', '', (string) $value);
    return is_numeric($clean) ? (float) $clean : null;
};

// Convert a text id into an integer (0 fallback, target column is NOT NULL).
$toInt = function ($value) {
    return is_numeric($value) ? (int) $value : 0;
};

// Truncate a value to fit a varchar(50) target column.
$toString50 = function ($value) {
    return $value === null ? null : mb_substr((string) $value, 0, 50);
};

// Transform a source value before it is written to the target.
// Keyed by the source column name.
$transformers = [
    'start_date'                 => function ($value) use ($toDatetime) {
        // start_date is NOT NULL in target; fall back to now if unparseable.
        return $toDatetime($value) ?? date('Y-m-d H:i:s');
    },
    'date'                       => $toDatetime,
    'invoice_number'             => $toString50,
    'our_company'                => $toString50,
    'client_company'             => $toString50,
    'total_amount'               => $toDecimal,
    'first_ref_date'             => $toDatetime,
    'first_ref_total'            => $toDecimal,
    'first_ref_bank'             => $toString50,
    'second_ref_date'            => $toDatetime,
    'second_ref_total'           => $toDecimal,
    'second_ref_bank'            => $toString50,
    'third_ref_date'             => $toDatetime,
    'third_ref_total'            => $toDecimal,
    'third_ref_bank'             => $toString50,
    'fourth_ref_date'            => $toDatetime,
    'fourth_ref_total'           => $toDecimal,
    'fourth_ref_bank'            => $toString50,
    'fifth_ref_date'             => $toDatetime,
    'fifth_ref_total'            => $toDecimal,
    'fifth_ref_bank'             => $toString50,
    'first_shipping_ref_date'    => $toDatetime,
    'first_shipping_ref_method'  => $toString50,
    'first_shipping_ref_box'     => $toString50,
    'second_shipping_ref_date'   => $toDatetime,
    'second_shipping_ref_method' => $toString50,
    'second_shipping_ref_box'    => $toString50,
    'user_id'                    => $toInt,
    'final_trakcing_id'          => $toString50,
    'status'                     => function ($value) {
        $status = strtolower(trim((string) $value));
        if ($status === '' ) {
            return 'in-progress';
        }
        $statusMap = [
            'green' => 'completed',
            'red'   => 'cancelled',
        ];
        return $statusMap[$status] ?? $status;
    },
];

// Computed columns: target column => closure(row) returning the value.
$computed = [
    'responsible_person' => function ($row) use ($responsiblePersonMap) {
        $userId = $row['user_id'] ?? null;
        return ($userId !== null && isset($responsiblePersonMap[(int) $userId]))
            ? $responsiblePersonMap[(int) $userId]
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
$existingColumns = $targetPdo->query('SHOW COLUMNS FROM track_controls')
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

$insertSql = "INSERT INTO track_controls ({$columnsSql}) VALUES ({$placeholders})";
$insertStmt = $targetPdo->prepare($insertSql);

// ----- Transfer -----

// Disable foreign key checks while the table is cleared and rebuilt.
$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE track_controls');
        echo "Target table truncated.\n";
    }

    $sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM essentials_holidays')->fetchColumn();
    echo "Source track control rows: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query('SELECT * FROM essentials_holidays');
    $transferred = 0;
    $failed = 0;

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

        try {
            $insertStmt->execute($values);
            $transferred++;
        } catch (Throwable $rowError) {
            $failed++;
            echo "Row {$row['id']} failed: {$rowError->getMessage()}\n";
        }
    }

    $targetPdo->commit();

    echo "Transferred {$transferred} track control rows to target database.\n";
    if ($failed > 0) {
        echo "Failed rows: {$failed}\n";
    }
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
