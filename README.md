# Windows Update Compliance Dashboard (PHP)

A PHP-based dashboard + JSON API for collecting Windows Update compliance status from Windows endpoints via PowerShell scripts.

## Features
- Endpoint registration by API key
- Ingestion API for update/compliance payloads
- SQLite-backed storage (easy local deployment)
- Dashboard for compliance overview and endpoint drill-down
- Basic endpoint trend visibility (last check-in and pending updates)

## Quick Start
1. Ensure PHP 8.1+ with SQLite extension enabled.
2. Initialize database:
   ```bash
   php scripts/init_db.php
   ```
3. Start server:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```
4. Open dashboard: `http://localhost:8080`

## API
### `POST /api/report.php`
Headers:
- `Content-Type: application/json`
- `X-API-Key: <your_api_key>`

Payload example:
```json
{
  "hostname": "PC-001",
  "os_version": "Windows 11 Pro 23H2",
  "last_scan_time": "2026-05-07T20:01:00Z",
  "reboot_required": false,
  "compliance": "non_compliant",
  "missing_updates": [
    {"kb": "KB5051234", "title": "2026-05 Cumulative Update", "severity": "critical"},
    {"kb": "KB5055678", "title": "Defender Intelligence Update", "severity": "important"}
  ]
}
```

Response:
```json
{"status":"ok","message":"Report stored"}
```

## Demo API key
Default generated in `scripts/init_db.php`:
- `demo-client` : `change-me-demo-key`

Replace this key before production use.
