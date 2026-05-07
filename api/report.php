<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!validate_api_key($apiKey)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

$hostname = trim((string)($data['hostname'] ?? ''));
$trmmClient = trim((string)($data['trmm_client'] ?? ''));
$trmmSite = trim((string)($data['trmm_site'] ?? ''));
$compliance = trim((string)($data['compliance'] ?? 'unknown'));
$osVersion = (string)($data['os_version'] ?? '');
$lastScanTime = (string)($data['last_scan_time'] ?? '');
$rebootRequired = !empty($data['reboot_required']) ? 1 : 0;
$missingUpdates = is_array($data['missing_updates'] ?? null) ? $data['missing_updates'] : [];

if ($hostname === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'hostname is required']);
    exit;
}

$pdo = db();
ensure_endpoints_trmm_columns($pdo);
$pdo->beginTransaction();

try {
    $upsert = $pdo->prepare('INSERT INTO endpoints (hostname, trmm_client, trmm_site, os_version, last_scan_time, reboot_required, compliance, last_reported_at)
        VALUES (:hostname, :trmm_client, :trmm_site, :os_version, :last_scan_time, :reboot_required, :compliance, CURRENT_TIMESTAMP)
        ON CONFLICT(hostname) DO UPDATE SET
            trmm_client = excluded.trmm_client,
            trmm_site = excluded.trmm_site,
            os_version = excluded.os_version,
            last_scan_time = excluded.last_scan_time,
            reboot_required = excluded.reboot_required,
            compliance = excluded.compliance,
            last_reported_at = CURRENT_TIMESTAMP');

    $upsert->execute([
        'hostname' => $hostname,
        'trmm_client' => $trmmClient,
        'trmm_site' => $trmmSite,
        'os_version' => $osVersion,
        'last_scan_time' => $lastScanTime,
        'reboot_required' => $rebootRequired,
        'compliance' => $compliance,
    ]);

    $idStmt = $pdo->prepare('SELECT id FROM endpoints WHERE hostname = :hostname');
    $idStmt->execute(['hostname' => $hostname]);
    $endpointId = (int)$idStmt->fetchColumn();

    $pdo->prepare('DELETE FROM missing_updates WHERE endpoint_id = :endpoint_id')->execute(['endpoint_id' => $endpointId]);

    if (!empty($missingUpdates)) {
        $insertUpdate = $pdo->prepare('INSERT INTO missing_updates (endpoint_id, kb, title, severity) VALUES (:endpoint_id, :kb, :title, :severity)');
        foreach ($missingUpdates as $mu) {
            $insertUpdate->execute([
                'endpoint_id' => $endpointId,
                'kb' => (string)($mu['kb'] ?? ''),
                'title' => (string)($mu['title'] ?? ''),
                'severity' => (string)($mu['severity'] ?? 'unknown'),
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => 'Report stored']);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
