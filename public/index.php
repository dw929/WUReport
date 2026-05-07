<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pdo = db();
ensure_endpoints_trmm_columns($pdo);
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Windows Update Compliance Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2rem; background: #f3f5f8; }
    .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .card { background: #fff; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 0.7rem; border-bottom: 1px solid #ddd; text-align: left; font-size: 0.9rem; }
    th { background: #0b3d91; color: #fff; }
    .tag { padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.8rem; }
    .ok { background: #d1fae5; color: #065f46; }
    .bad { background: #fee2e2; color: #991b1b; }
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
      <tr>
        <td><?= htmlspecialchars($row['hostname']) ?></td>
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
