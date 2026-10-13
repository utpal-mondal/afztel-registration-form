<?php

/**
 * Transfer tracking control data (essentials_holidays -> track_controls)
 * from a source MySQL database to a target MySQL database.
 *
 * Also creates a row in tracking_control_files for each source row that has
 * an image filename, pointing to the file's S3 URL.
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

// S3 base URL used to build file_url values in tracking_control_files.
// Final URL: {s3FileBaseUrl}/{track_control_id}/{source image filename}
$s3FileBaseUrl = 'https://asset.s3.eu-central-1.amazonaws.com/tracking/img';

// Source reference column groups: [date, total, bank].
// A track_control_references row is inserted only when at least one
// of the three source values is non-empty.
$referenceGroups = [
    ['first_ref_date',  'first_ref_total',  'first_ref_bank'],
    ['second_ref_date', 'second_ref_total', 'second_ref_bank'],
    ['third_ref_date',  'third_ref_total',  'third_ref_bank'],
    ['fourth_ref_date', 'fourth_ref_total', 'fourth_ref_bank'],
    ['fifth_ref_date',  'fifth_ref_total',  'fifth_ref_bank'],
];

// Source shipping column groups: [date, method, box, amount].
// A track_control_shippings row is inserted only when at least one
// of the four source values is non-empty.
$shippingGroups = [
    ['first_shipping_ref_date',  'first_shipping_ref_method',  'first_shipping_ref_box',  'first_shipping_ref_amount'],
    ['second_shipping_ref_date', 'second_shipping_ref_method', 'second_shipping_ref_box', 'second_shipping_ref_amount'],
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
    // The five *_ref_* column groups go to track_control_references instead
    // (see $referenceGroups above), and the two *_shipping_ref_* groups go
    // to track_control_shippings (see $shippingGroups above).
    'user_id'                    => 'user_id',
    'final_trakcing_id'          => 'tracking_number',
    'status'                     => 'status',
];

// ----- Shared transformers -----

// Parse a text/varchar date into 'Y-m-d H:i:s', or null if empty/unparseable.
// Source uses day-first formats like 30/09/2026, which strtotime() would
// misparse as m/d/Y, so explicit d/m/Y formats are tried first.
$toDatetime = function ($value) {
    if ($value === null || trim((string) $value) === '') {
        return null;
    }
    $value = trim((string) $value);

    $formats = [
        'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
        'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y',
        'd.m.Y H:i:s', 'd.m.Y H:i', 'd.m.Y',
        'Y-m-d H:i:s', 'Y-m-d',
    ];

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt !== false) {
            $errors = DateTime::getLastErrors();
            if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
    }

    $ts = strtotime($value);
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

// ----- Prepare tracking_control_files insert -----

$fileColumns = ['track_control_id', 'file_url', 'created_at', 'updated_at'];
$fileExisting = $targetPdo->query('SHOW COLUMNS FROM tracking_control_files')
    ->fetchAll(PDO::FETCH_COLUMN);
$fileValidCols = array_values(array_intersect($fileColumns, $fileExisting));

$fileStmt = null;
if ($fileValidCols) {
    $fileColumnsSql = '`' . implode('`, `', $fileValidCols) . '`';
    $filePlaceholders = implode(',', array_fill(0, count($fileValidCols), '?'));
    $fileStmt = $targetPdo->prepare(
        "INSERT INTO tracking_control_files ({$fileColumnsSql}) VALUES ({$filePlaceholders})"
    );
}

// ----- Prepare track_control_references insert -----

$refColumns = [
    'track_control_id', 'reference_date', 'reference_amount',
    'reference_bank_name', 'created_at', 'updated_at',
];
$refExisting = $targetPdo->query('SHOW COLUMNS FROM track_control_references')
    ->fetchAll(PDO::FETCH_COLUMN);
$refValidCols = array_values(array_intersect($refColumns, $refExisting));

$refStmt = null;
if ($refValidCols) {
    $refColumnsSql = '`' . implode('`, `', $refValidCols) . '`';
    $refPlaceholders = implode(',', array_fill(0, count($refValidCols), '?'));
    $refStmt = $targetPdo->prepare(
        "INSERT INTO track_control_references ({$refColumnsSql}) VALUES ({$refPlaceholders})"
    );
}

// ----- Prepare track_control_shippings insert -----

$shipColumns = [
    'track_control_id', 'shipping_date', 'shipping_method',
    't_box', 'tracking_id', 'created_at', 'updated_at',
];
$shipExisting = $targetPdo->query('SHOW COLUMNS FROM track_control_shippings')
    ->fetchAll(PDO::FETCH_COLUMN);
$shipValidCols = array_values(array_intersect($shipColumns, $shipExisting));

$shipStmt = null;
if ($shipValidCols) {
    $shipColumnsSql = '`' . implode('`, `', $shipValidCols) . '`';
    $shipPlaceholders = implode(',', array_fill(0, count($shipValidCols), '?'));
    $shipStmt = $targetPdo->prepare(
        "INSERT INTO track_control_shippings ({$shipColumnsSql}) VALUES ({$shipPlaceholders})"
    );
}

// ----- Transfer -----

// Disable foreign key checks while the table is cleared and rebuilt.
$targetPdo->exec('SET FOREIGN_KEY_CHECKS = 0');

try {
    if ($clearTarget) {
        $targetPdo->exec('TRUNCATE TABLE track_control_shippings');
        $targetPdo->exec('TRUNCATE TABLE track_control_references');
        $targetPdo->exec('TRUNCATE TABLE tracking_control_files');
        $targetPdo->exec('TRUNCATE TABLE track_controls');
        echo "Target tables truncated.\n";
    }

    $sourceCount = (int) $sourcePdo->query('SELECT COUNT(*) FROM essentials_holidays')->fetchColumn();
    echo "Source track control rows: {$sourceCount}\n";

    $targetPdo->beginTransaction();

    $stmt = $sourcePdo->query('SELECT * FROM essentials_holidays');
    $transferred = 0;
    $failed = 0;
    $files = 0;
    $refs = 0;
    $shippings = 0;

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
            continue;
        }

        // Insert a tracking_control_files row when the source row has an image.
        $image = trim((string) ($row['image'] ?? ''));
        if ($image !== '' && $fileStmt !== null) {
            $fileUrl = mb_substr($s3FileBaseUrl . '/' . $image, 0, 500);

            $fileValues = [];
            foreach ($fileValidCols as $col) {
                if ($col === 'track_control_id') {
                    $fileValues[] = $row['id'];
                } elseif ($col === 'file_url') {
                    $fileValues[] = $fileUrl;
                } elseif ($col === 'created_at' || $col === 'updated_at') {
                    $fileValues[] = date('Y-m-d H:i:s');
                } else {
                    $fileValues[] = null;
                }
            }

            try {
                $fileStmt->execute($fileValues);
                $files++;
            } catch (Throwable $fileError) {
                echo "File insert for row {$row['id']} failed: {$fileError->getMessage()}\n";
            }
        }

        // Insert a track_control_references row for each reference group
        // that has at least one non-empty source value.
        if ($refStmt !== null) {
            foreach ($referenceGroups as $group) {
                [$dateCol, $totalCol, $bankCol] = $group;

                $hasData = trim((string) ($row[$dateCol] ?? '')) !== ''
                    || trim((string) ($row[$totalCol] ?? '')) !== ''
                    || trim((string) ($row[$bankCol] ?? '')) !== '';

                if (! $hasData) {
                    continue;
                }

                $refValues = [];
                foreach ($refValidCols as $col) {
                    if ($col === 'track_control_id') {
                        $refValues[] = $row['id'];
                    } elseif ($col === 'reference_date') {
                        $refValues[] = $toDatetime($row[$dateCol] ?? null);
                    } elseif ($col === 'reference_amount') {
                        $refValues[] = $toDecimal($row[$totalCol] ?? null);
                    } elseif ($col === 'reference_bank_name') {
                        $refValues[] = $toString50($row[$bankCol] ?? null);
                    } elseif ($col === 'created_at' || $col === 'updated_at') {
                        $refValues[] = date('Y-m-d H:i:s');
                    } else {
                        $refValues[] = null;
                    }
                }

                try {
                    $refStmt->execute($refValues);
                    $refs++;
                } catch (Throwable $refError) {
                    echo "Reference insert for row {$row['id']} failed: {$refError->getMessage()}\n";
                }
            }
        }

        // Insert a track_control_shippings row for each shipping group
        // that has at least one non-empty source value.
        if ($shipStmt !== null) {
            foreach ($shippingGroups as $group) {
                [$dateCol, $methodCol, $boxCol, $amountCol] = $group;

                $hasData = trim((string) ($row[$dateCol] ?? '')) !== ''
                    || trim((string) ($row[$methodCol] ?? '')) !== ''
                    || trim((string) ($row[$boxCol] ?? '')) !== ''
                    || trim((string) ($row[$amountCol] ?? '')) !== '';

                if (! $hasData) {
                    continue;
                }

                $shipValues = [];
                foreach ($shipValidCols as $col) {
                    if ($col === 'track_control_id') {
                        $shipValues[] = $row['id'];
                    } elseif ($col === 'shipping_date') {
                        $shipValues[] = $toDatetime($row[$dateCol] ?? null);
                    } elseif ($col === 'shipping_method') {
                        $shipValues[] = $toString50($row[$methodCol] ?? null);
                    } elseif ($col === 't_box') {
                        $shipValues[] = $toString50($row[$boxCol] ?? null);
                    } elseif ($col === 'tracking_id') {
                        $shipValues[] = $toString50($row[$amountCol] ?? null);
                    } elseif ($col === 'created_at' || $col === 'updated_at') {
                        $shipValues[] = date('Y-m-d H:i:s');
                    } else {
                        $shipValues[] = null;
                    }
                }

                try {
                    $shipStmt->execute($shipValues);
                    $shippings++;
                } catch (Throwable $shipError) {
                    echo "Shipping insert for row {$row['id']} failed: {$shipError->getMessage()}\n";
                }
            }
        }
    }

    $targetPdo->commit();

    echo "Transferred {$transferred} track control rows to target database.\n";
    echo "Inserted {$files} tracking_control_files rows.\n";
    echo "Inserted {$refs} track_control_references rows.\n";
    echo "Inserted {$shippings} track_control_shippings rows.\n";
    if ($failed > 0) {
        echo "Failed rows: {$failed}\n";
    }
} catch (Throwable $e) {
    $targetPdo->rollBack();
    throw $e;
} finally {
    $targetPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
