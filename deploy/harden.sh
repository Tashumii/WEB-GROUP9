#!/usr/bin/env bash
# Run on the production server as root after deploying sales-tracker.
# Review before running.

set -euo pipefail

APP_DIR="/var/www/sales-tracker"
WEB_USER="www-data"

echo "==> Fixing ownership & permissions"
chown -R "$WEB_USER:$WEB_USER" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 "$APP_DIR/writable"
# protect secrets
chmod 640 "$APP_DIR/.env"
chown root:"$WEB_USER" "$APP_DIR/.env"

echo "==> Enabling Apache modules"
a2enmod rewrite headers ssl
systemctl reload apache2

echo "==> Firewall (UFW)"
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> fail2ban"
apt-get install -y fail2ban
systemctl enable --now fail2ban

echo "==> Unattended security upgrades"
apt-get install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades

echo "==> OpenCode systemd service"
install -d -o "$WEB_USER" -g "$WEB_USER" /var/lib/opencode
install -d -m 700 /etc/opencode
if [[ ! -f /etc/opencode/opencode.env ]]; then
  install -m 600 "$APP_DIR/deploy/opencode.env.example" /etc/opencode/opencode.env
fi
cp "$APP_DIR/deploy/opencode.service" /etc/systemd/system/opencode.service
systemctl daemon-reload
if grep -q 'CHANGE_ME' /etc/opencode/opencode.env; then
  echo "  !! /etc/opencode/opencode.env still has CHANGE_ME - set OPENCODE_SERVER_PASSWORD, then:"
  echo "     systemctl enable --now opencode"
else
  systemctl enable --now opencode
fi

echo "==> Done. Remember to:"
echo "  - Set OPENCODE_SERVER_PASSWORD in /etc/opencode/opencode.env"
echo "  - Run certbot for HTTPS"
echo "  - Remove test accounts from the database"