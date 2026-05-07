<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbFile = __DIR__ . '/../storage.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function ensure_endpoints_trmm_columns(PDO $pdo): void
{
    $columns = $pdo->query("PRAGMA table_info(endpoints)")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        return;
    }

    $existing = array_column($columns, 'name');
    if (!in_array('trmm_client', $existing, true)) {
        $pdo->exec('ALTER TABLE endpoints ADD COLUMN trmm_client TEXT');
    }
    if (!in_array('trmm_site', $existing, true)) {
        $pdo->exec('ALTER TABLE endpoints ADD COLUMN trmm_site TEXT');
    }
}
