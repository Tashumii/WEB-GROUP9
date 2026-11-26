#!/usr/bin/env bash
# One-shot bootstrap for Oracle Cloud Ubuntu 24.04: sales-tracker + OpenCode 1.x
#
# Upload the project first, then run:
#   sudo bash /var/www/sales-tracker/deploy/oracle-bootstrap.sh <domain-or-ip> <db_password> <opencode_password> [letsencrypt_email]
#
# Example:
#   sudo bash deploy/oracle-bootstrap.sh 140.238.1.2 'Sd8kQ2mN7pXr4tYz' 'Oc7vB3nM9kL2jH5g'
#   sudo bash deploy/oracle-bootstrap.sh shop.example.com 'Sd8kQ2mN7pXr4tYz' 'Oc7vB3nM9kL2jH5g' me@example.com
#
# Passwords: letters, digits and . _ @ % + = : - only (no spaces, quotes, $, #, !).
set -euo pipefail

APP_DIR="/var/www/sales-tracker"
WEB_USER="www-data"
OC_VERSION="1.19.6"

DOMAIN="${1:-}"
DB_PASS="${2:-}"
OC_PASS="${3:-}"
LE_EMAIL="${4:-}"

die() { echo "ERROR: $*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "run as root: sudo bash $0 ..."
[[ -n "$DOMAIN" && -n "$DB_PASS" && -n "$OC_PASS" ]] || \
  die "usage: sudo bash $0 <domain-or-ip> <db_password> <opencode_password> [letsencrypt_email]"
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || die "domain/IP contains invalid characters"
[[ "$DB_PASS" =~ ^[A-Za-z0-9._@%+=:-]{12,}$ ]] || die "DB password must be 12+ chars from A-Za-z0-9._@%+=:-"
[[ "$OC_PASS" =~ ^[A-Za-z0-9._@%+=:-]{12,}$ ]] || die "OpenCode password must be 12+ chars from A-Za-z0-9._@%+=:-"
[[ -f "$APP_DIR/spark" ]] || die "project not found at $APP_DIR (upload it first)"

export DEBIAN_FRONTEND=noninteractive
APT_OPTS=(-y -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold)

echo "==> [1/9] 2 GB swap (1 GB RAM instance)"
if ! swapon --show | grep -q '/swapfile'; then
  fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 status=none
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> [2/9] Installing LAMP + tools"
apt-get update "${APT_OPTS[@]}"
apt-get upgrade "${APT_OPTS[@]}"
apt-get install "${APT_OPTS[@]}" \
  apache2 mysql-server php php-cli php-mysql php-mbstring php-intl php-curl \
  php-xml php-zip php-gd libapache2-mod-php composer unzip git curl ca-certificates \
  fail2ban unattended-upgrades certbot python3-certbot-apache

a2enmod rewrite headers ssl >/dev/null
systemctl restart apache2

echo "==> [3/9] MySQL tuning + database/user"
cat > /etc/mysql/mysql.conf.d/zz-sales-tracker.cnf <<'CNF'
[mysqld]
innodb_buffer_pool_size = 128M
performance_schema = OFF
CNF
systemctl restart mysql

mysql <<SQL
CREATE DATABASE IF NOT EXISTS SalesDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'salestracker'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER 'salestracker'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON SalesDB.* TO 'salestracker'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> [4/9] Production .env"
cat > "$APP_DIR/.env" <<ENV
CI_ENVIRONMENT = production
app.baseURL = 'http://${DOMAIN}/'
app.forceGlobalSecureRequests = false

DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = SalesDB
DB_USER = salestracker
DB_PASS = ${DB_PASS}

OPENCODE_URL = http://127.0.0.1:4096
OPENCODE_PASSWORD = ${OC_PASS}
OPENCODE_MODEL =
ENV

echo "==> [5/9] Permissions"
chown -R "$WEB_USER:$WEB_USER" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 "$APP_DIR/writable"
chmod 640 "$APP_DIR/.env"
chown root:"$WEB_USER" "$APP_DIR/.env"

echo "==> [6/9] Apache vhost (doc root = public/)"
sed "s/yourdomain.com/${DOMAIN}/g" "$APP_DIR/deploy/apache-sales-tracker.conf" \
  > /etc/apache2/sites-available/sales-tracker.conf
a2ensite sales-tracker >/dev/null
a2dissite 000-default >/dev/null 2>&1 || true
apache2ctl configtest
systemctl reload apache2

echo "==> [7/9] Database migrations"
sudo -u "$WEB_USER" php "$APP_DIR/spark" migrate
sudo -u "$WEB_USER" php "$APP_DIR/spark" cache:clear >/dev/null 2>&1 || true

echo "==> [8/9] OpenCode 1.x + systemd service"
if ! command -v opencode >/dev/null 2>&1; then
  curl -fsSL https://opencode.ai/install | bash -s -- --version "$OC_VERSION"
  install -m 755 /root/.opencode/bin/opencode /usr/local/bin/opencode
fi
install -d -o "$WEB_USER" -g "$WEB_USER" /var/lib/opencode
install -d -m 700 /etc/opencode
cat > /etc/opencode/opencode.env <<ENV
HOME=/var/lib/opencode
XDG_DATA_HOME=/var/lib/opencode/.local/share
XDG_CONFIG_HOME=/var/lib/opencode/.config
XDG_CACHE_HOME=/var/lib/opencode/.cache
OPENCODE_SERVER_PASSWORD=${OC_PASS}
OPENCODE_DISABLE_AUTOUPDATE=true
ENV
chmod 600 /etc/opencode/opencode.env
cp "$APP_DIR/deploy/opencode.service" /etc/systemd/system/opencode.service
systemctl daemon-reload
systemctl enable --now opencode

echo "==> [9/9] Firewall + fail2ban + unattended upgrades"
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
systemctl enable --now fail2ban
dpkg-reconfigure -plow unattended-upgrades >/dev/null 2>&1 || true

if [[ -n "$LE_EMAIL" && ! "$DOMAIN" =~ ^[0-9.]+$ ]]; then
  echo "==> Let's Encrypt certificate for ${DOMAIN}"
  if certbot --apache -d "$DOMAIN" --non-interactive --agree-tos -m "$LE_EMAIL" --redirect; then
    sed -i "s|app.baseURL = 'http://${DOMAIN}/'|app.baseURL = 'https://${DOMAIN}/'|" "$APP_DIR/.env"
    sed -i "s|app.forceGlobalSecureRequests = false|app.forceGlobalSecureRequests = true|" "$APP_DIR/.env"
  else
    echo "WARN: certbot failed (DNS not pointing here yet?). Site stays on HTTP."
  fi
fi

echo
echo "==> Health checks"
sleep 2
curl -fsS -u "opencode:${OC_PASS}" http://127.0.0.1:4096/global/health && echo " <- OpenCode OK"
curl -fsS -o /dev/null -w "Apache HTTP %{http_code}\n" "http://127.0.0.1/" || true
systemctl --no-pager --lines=0 status opencode || true

echo
echo "DONE."
echo "  Site:      http://${DOMAIN}/"
echo "  Next:      create the first real account at /register, then delete test data."
echo "  AI auth:   sudo -u www-data env HOME=/var/lib/opencode XDG_DATA_HOME=/var/lib/opencode/.local/share opencode auth login"
echo "             (or copy your local ~/.local/share/opencode/auth.json into /var/lib/opencode/.local/share/opencode/)"
