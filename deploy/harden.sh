#!/usr/bin/env bash
# Run on the production server as root after deploying sales-tracker.
# Review before running.

set -euo pipefail

APP_DIR="/var/www/sales-tracker"
WEB_USER="www-data"
OC_USER="opencode"

echo "==> Fixing ownership & permissions"
chown -R "$WEB_USER:$WEB_USER" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
chmod -R 775 "$APP_DIR/writable"
# protect secrets
chmod 640 "$APP_DIR/.env"
chown root:"$WEB_USER" "$APP_DIR/.env"

echo "==> Checking .env"
if ! grep -Eq '^encryption\.key[[:space:]]*=[[:space:]]*[^[:space:]#]+' "$APP_DIR/.env"; then
  echo "  !! .env has no encryption.key. 2FA will fail until you add one:"
  echo "     echo \"encryption.key = hex2bin:\$(openssl rand -hex 32)\" | sudo tee -a $APP_DIR/.env"
fi

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

echo "==> OpenCode systemd service (own user, all tools denied)"
if ! id -u "$OC_USER" >/dev/null 2>&1; then
  useradd --system --user-group --home-dir /var/lib/opencode --no-create-home --shell /usr/sbin/nologin "$OC_USER"
fi
install -d -o "$OC_USER" -g "$OC_USER" -m 700 /var/lib/opencode
chown -R "$OC_USER:$OC_USER" /var/lib/opencode
install -d -m 755 /etc/opencode
install -m 644 "$APP_DIR/deploy/opencode.json" /etc/opencode/opencode.json
if [[ ! -f /etc/opencode/opencode.env ]]; then
  install -m 600 -o root -g root "$APP_DIR/deploy/opencode.env.example" /etc/opencode/opencode.env
fi
chmod 600 /etc/opencode/opencode.env
# An env file from an older version of this script has no OPENCODE_CONFIG line.
if ! grep -q '^OPENCODE_CONFIG=' /etc/opencode/opencode.env; then
  echo 'OPENCODE_CONFIG=/etc/opencode/opencode.json' >> /etc/opencode/opencode.env
fi
cp "$APP_DIR/deploy/opencode.service" /etc/systemd/system/opencode.service
systemctl daemon-reload
if grep -q 'CHANGE_ME' /etc/opencode/opencode.env; then
  echo "  !! /etc/opencode/opencode.env still has CHANGE_ME - set OPENCODE_SERVER_PASSWORD, then:"
  echo "     systemctl enable opencode && systemctl restart opencode"
else
  systemctl enable opencode
  systemctl restart opencode
fi

echo "==> Done. Remember to:"
echo "  - Set OPENCODE_SERVER_PASSWORD in /etc/opencode/opencode.env (same value as OPENCODE_PASSWORD in .env)"
echo "  - Log OpenCode in to a model: sudo -u $OC_USER env HOME=/var/lib/opencode XDG_DATA_HOME=/var/lib/opencode/.local/share opencode auth login"
echo "  - Open TCP 80/443 for this instance in the Oracle Cloud Console (security list or NSG)"
echo "  - Run certbot for HTTPS"
echo "  - Remove test accounts from the database"
