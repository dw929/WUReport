<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();

$pdo->exec('CREATE TABLE IF NOT EXISTS api_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_name TEXT NOT NULL UNIQUE,
    api_key TEXT NOT NULL UNIQUE,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS endpoints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hostname TEXT NOT NULL UNIQUE,
    os_version TEXT,
    last_scan_time TEXT,
    reboot_required INTEGER NOT NULL DEFAULT 0,
    compliance TEXT NOT NULL,
    last_reported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS missing_updates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    endpoint_id INTEGER NOT NULL,
    kb TEXT,
    title TEXT,
    severity TEXT,
    FOREIGN KEY(endpoint_id) REFERENCES endpoints(id) ON DELETE CASCADE
)');

$stmt = $pdo->prepare('INSERT OR IGNORE INTO api_clients (client_name, api_key, is_active) VALUES (:name, :key, 1)');
$stmt->execute(['name' => 'demo-client', 'key' => 'change-me-demo-key']);

echo "Database initialized.\n";
