#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Please run as root (use sudo)." >&2
  exit 1
fi

APP_DIR_DEFAULT="/opt/wureport"
APP_USER_DEFAULT="wureport"
APP_GROUP_DEFAULT="wureport"
PORT_DEFAULT="8080"

APP_DIR="${APP_DIR:-$APP_DIR_DEFAULT}"
APP_USER="${APP_USER:-$APP_USER_DEFAULT}"
APP_GROUP="${APP_GROUP:-$APP_GROUP_DEFAULT}"
APP_PORT="${APP_PORT:-$PORT_DEFAULT}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ ! -f "${REPO_ROOT}/public/index.php" ]]; then
  echo "Could not locate application root from ${SCRIPT_DIR}." >&2
  exit 1
fi

echo "[1/7] Installing system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends php-cli php-sqlite3 sqlite3 curl ca-certificates rsync

echo "[2/7] Creating service account..."
if ! getent group "${APP_GROUP}" >/dev/null; then
  groupadd --system "${APP_GROUP}"
fi
if ! id -u "${APP_USER}" >/dev/null 2>&1; then
  useradd --system --gid "${APP_GROUP}" --home-dir "${APP_DIR}" --create-home --shell /usr/sbin/nologin "${APP_USER}"
fi

echo "[3/7] Installing application files to ${APP_DIR}..."
mkdir -p "${APP_DIR}"
rsync -a --delete \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='*.log' \
  "${REPO_ROOT}/" "${APP_DIR}/"

chown -R "${APP_USER}:${APP_GROUP}" "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} \;
find "${APP_DIR}" -type f -exec chmod 644 {} \;
chmod +x "${APP_DIR}/scripts/install_debian.sh"


echo "[4/7] Initializing SQLite database..."
runuser -u "${APP_USER}" -- php "${APP_DIR}/scripts/init_db.php"

SERVICE_FILE="/etc/systemd/system/wureport.service"
echo "[5/7] Writing systemd service: ${SERVICE_FILE}"
cat > "${SERVICE_FILE}" <<SERVICE
[Unit]
Description=Windows Update Compliance Dashboard (PHP)
After=network.target

[Service]
Type=simple
User=${APP_USER}
Group=${APP_GROUP}
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php -S 0.0.0.0:${APP_PORT} -t ${APP_DIR}/public
Restart=on-failure
RestartSec=3
Environment=PHP_CLI_SERVER_WORKERS=4

NoNewPrivileges=true
PrivateTmp=true
ProtectHome=true
ProtectSystem=full
ReadWritePaths=${APP_DIR}

[Install]
WantedBy=multi-user.target
SERVICE

echo "[6/7] Enabling and starting service..."
systemctl daemon-reload
systemctl enable --now wureport.service

echo "[7/7] Checking service health..."
sleep 1
systemctl --no-pager --full status wureport.service || true

echo
echo "Install complete."
echo "Dashboard: http://<server-ip>:${APP_PORT}/"
echo "API endpoint: http://<server-ip>:${APP_PORT}/api/report.php"
echo "App directory: ${APP_DIR}"
echo "Service: wureport.service"
