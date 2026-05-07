<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();
ensure_endpoints_trmm_columns($pdo);
$selectedEndpointId = filter_input(INPUT_GET, 'endpoint', FILTER_VALIDATE_INT);

$totals = [
    'total' => (int)$pdo->query('SELECT COUNT(*) FROM endpoints')->fetchColumn(),
    'compliant' => (int)$pdo->query("SELECT COUNT(*) FROM endpoints WHERE compliance = 'compliant'")->fetchColumn(),
    'non_compliant' => (int)$pdo->query("SELECT COUNT(*) FROM endpoints WHERE compliance = 'non_compliant'")->fetchColumn(),
    'reboot_required' => (int)$pdo->query('SELECT COUNT(*) FROM endpoints WHERE reboot_required = 1')->fetchColumn(),
];

$rows = $pdo->query('SELECT e.*, COUNT(mu.id) AS missing_count
    FROM endpoints e
    LEFT JOIN missing_updates mu ON mu.endpoint_id = e.id
    GROUP BY e.id
    ORDER BY e.last_reported_at DESC')->fetchAll();

$selectedEndpoint = null;
$selectedMissingUpdates = [];
if (is_int($selectedEndpointId) && $selectedEndpointId > 0) {
    $endpointStmt = $pdo->prepare('SELECT e.*, COUNT(mu.id) AS missing_count
        FROM endpoints e
        LEFT JOIN missing_updates mu ON mu.endpoint_id = e.id
        WHERE e.id = :id
        GROUP BY e.id');
    $endpointStmt->execute(['id' => $selectedEndpointId]);
    $selectedEndpoint = $endpointStmt->fetch();

    if ($selectedEndpoint !== false) {
        $updatesStmt = $pdo->prepare('SELECT kb, title, severity
            FROM missing_updates
            WHERE endpoint_id = :id
            ORDER BY COALESCE(severity, ""), COALESCE(kb, ""), COALESCE(title, "")');
        $updatesStmt->execute(['id' => $selectedEndpointId]);
        $selectedMissingUpdates = $updatesStmt->fetchAll();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Windows Update Compliance Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2rem; background: #f3f5f8; color: #1f2937; }
    .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .card, .panel { background: #fff; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 0.7rem; border-bottom: 1px solid #ddd; text-align: left; font-size: 0.9rem; }
    th { background: #0b3d91; color: #fff; }
    .tag { padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.8rem; }
    .ok { background: #d1fae5; color: #065f46; }
    .bad { background: #fee2e2; color: #991b1b; }
    .host-link { color: #0b3d91; text-decoration: none; font-weight: 700; }
    .host-link:hover { text-decoration: underline; }
    .selected-row { background: #eef4ff; }
    .panel { margin-bottom: 1.5rem; }
    .details-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
    .detail-item { background: #f9fafb; border-radius: 6px; padding: 0.6rem; }
    .detail-item small { color: #6b7280; display: block; }
    .empty-state { color: #4b5563; font-style: italic; }
  </style>
</head>
<body>
  <h1>Windows Update Compliance Dashboard</h1>
  <div class="cards">
    <div class="card"><strong>Total endpoints</strong><div><?= $totals['total'] ?></div></div>
    <div class="card"><strong>Compliant</strong><div><?= $totals['compliant'] ?></div></div>
    <div class="card"><strong>Non-compliant</strong><div><?= $totals['non_compliant'] ?></div></div>
    <div class="card"><strong>Reboot required</strong><div><?= $totals['reboot_required'] ?></div></div>
  </div>

  <div class="panel" id="agent-details">
    <h2>Agent Details</h2>
    <?php if ($selectedEndpoint !== null && $selectedEndpoint !== false): ?>
      <div class="details-grid">
        <div class="detail-item"><small>Hostname</small><?= htmlspecialchars((string)$selectedEndpoint['hostname']) ?></div>
        <div class="detail-item"><small>Client</small><?= htmlspecialchars((string)($selectedEndpoint['trmm_client'] ?? '')) ?></div>
        <div class="detail-item"><small>Site</small><?= htmlspecialchars((string)($selectedEndpoint['trmm_site'] ?? '')) ?></div>
        <div class="detail-item"><small>OS Version</small><?= htmlspecialchars((string)($selectedEndpoint['os_version'] ?? '')) ?></div>
        <div class="detail-item"><small>Last Scan</small><?= htmlspecialchars((string)($selectedEndpoint['last_scan_time'] ?? '')) ?></div>
        <div class="detail-item"><small>Last Report</small><?= htmlspecialchars((string)($selectedEndpoint['last_reported_at'] ?? '')) ?></div>
        <div class="detail-item"><small>Compliance</small><?= htmlspecialchars((string)$selectedEndpoint['compliance']) ?></div>
        <div class="detail-item"><small>Reboot</small><?= (int)$selectedEndpoint['reboot_required'] === 1 ? 'Required' : 'No' ?></div>
        <div class="detail-item"><small>Missing Updates</small><?= (int)$selectedEndpoint['missing_count'] ?></div>
      </div>

      <h3>Missing update list</h3>
      <?php if ($selectedMissingUpdates === []): ?>
        <p class="empty-state">No missing updates reported for this agent.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>KB</th>
              <th>Title</th>
              <th>Severity</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($selectedMissingUpdates as $update): ?>
              <tr>
                <td><?= htmlspecialchars((string)($update['kb'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($update['title'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($update['severity'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php else: ?>
      <p class="empty-state">Click an agent hostname in the table below to view endpoint details and missing updates.</p>
    <?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>Hostname</th>
        <th>Client</th>
        <th>Site</th>
        <th>OS Version</th>
        <th>Compliance</th>
        <th>Missing Updates</th>
        <th>Reboot</th>
        <th>Last Scan</th>
        <th>Last Report</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr class="<?= $selectedEndpointId === (int)$row['id'] ? 'selected-row' : '' ?>">
        <td><a class="host-link" href="?endpoint=<?= (int)$row['id'] ?>#agent-details"><?= htmlspecialchars($row['hostname']) ?></a></td>
        <td><?= htmlspecialchars((string)($row['trmm_client'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['trmm_site'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)$row['os_version']) ?></td>
        <td>
          <span class="tag <?= $row['compliance'] === 'compliant' ? 'ok' : 'bad' ?>">
            <?= htmlspecialchars($row['compliance']) ?>
          </span>
        </td>
        <td><?= (int)$row['missing_count'] ?></td>
        <td><?= (int)$row['reboot_required'] === 1 ? 'Required' : 'No' ?></td>
        <td><?= htmlspecialchars((string)$row['last_scan_time']) ?></td>
        <td><?= htmlspecialchars((string)$row['last_reported_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
