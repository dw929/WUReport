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

## Debian install (blank VM)
Run the installer as root (or with sudo) from the repository checkout:

```bash
chmod +x scripts/install_debian.sh
sudo ./scripts/install_debian.sh
```

What it does:
- Installs PHP + SQLite runtime packages
- Creates a dedicated `wureport` system user
- Copies the app to `/opt/wureport`
- Initializes the SQLite database
- Creates and starts `wureport.service` (systemd)

Optional environment overrides:
- `APP_DIR` (default: `/opt/wureport`)
- `APP_USER` (default: `wureport`)
- `APP_GROUP` (default: `wureport`)
- `APP_PORT` (default: `8080`)

## API
### `POST /api/report.php`
Headers:
- `Content-Type: application/json`
- `X-API-Key: <your_api_key>`

Payload example:
```json
{
  "hostname": "PC-001",
  "trmm_client": "Acme Corp",
  "trmm_site": "HQ",
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

## Tactical RMM integration
The provided `scripts/report_status.ps1` now includes Tactical RMM client/site context.

- It will automatically use `TRMM_CLIENT_NAME` and `TRMM_SITE_NAME` environment variables when available.
- You can also pass explicit values with `-Client` and `-Site`.

Example:
```powershell
powershell.exe -ExecutionPolicy Bypass -File .\scripts\report_status.ps1 -ApiUrl "http://your-server:8080/api/report.php" -ApiKey "your-key"
```

## Demo API key
Default generated in `scripts/init_db.php`:
- `demo-client` : `change-me-demo-key`

Replace this key before production use.

## Update from main repo
Use the helper script to fast-forward your current branch to the latest `origin/main`:

```bash
./scripts/update_from_main.sh
```

Optional overrides:
- `REMOTE_NAME` (default: `origin`)
- `MAIN_BRANCH` (default: `main`)

Example:
```bash
REMOTE_NAME=upstream MAIN_BRANCH=main ./scripts/update_from_main.sh
```
