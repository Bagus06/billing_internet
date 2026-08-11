#!/bin/sh
set -eu

SOURCE=/tmp/nginx-captive-portal.conf
TARGET=/etc/nginx/sites-available/isolation
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="${TARGET}.backup-${STAMP}"

test -f "$SOURCE"
cp "$TARGET" "$BACKUP"
install -o root -g root -m 0644 "$SOURCE" "$TARGET"

if ! nginx -t; then
    cp "$BACKUP" "$TARGET"
    nginx -t
    exit 1
fi

systemctl reload nginx
printf '[%s] Updated Nginx captive-portal endpoints; backup=%s\n' "$(date -Is)" "$BACKUP" >> /var/log/codex-config.log
rm -f "$SOURCE" /tmp/install-nginx-captive-portal.sh
