<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function validate_api_key(?string $apiKey): bool
{
    if (!$apiKey) {
        return false;
    }

    $stmt = db()->prepare('SELECT 1 FROM api_clients WHERE api_key = :api_key AND is_active = 1 LIMIT 1');
    $stmt->execute(['api_key' => $apiKey]);

    return (bool) $stmt->fetchColumn();
}
