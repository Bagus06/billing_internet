#!/bin/sh
set -eu

id olt-relay >/dev/null 2>&1 || useradd --system --home /nonexistent --shell /usr/sbin/nologin olt-relay
install -d -o root -g root -m 0755 /opt/olt-relay
install -o root -g root -m 0755 /tmp/olt-relay.py /opt/olt-relay/olt-relay.py
install -o root -g root -m 0644 /tmp/olt-relay.service /etc/systemd/system/olt-relay.service

if [ ! -s /etc/olt-relay.env ]; then
    TOKEN=$(openssl rand -hex 32)
    cat > /etc/olt-relay.env <<EOF
RELAY_HOST=127.0.0.1
RELAY_PORT=8787
OLT_HOST=192.168.99.1
OLT_COMMUNITY=public
CACHE_TTL=5
RELAY_TOKEN=$TOKEN
EOF
fi
chown root:olt-relay /etc/olt-relay.env
chmod 0640 /etc/olt-relay.env

systemctl daemon-reload
systemctl enable olt-relay
systemctl restart olt-relay
sleep 2
systemctl --no-pager --full status olt-relay
wget -qO- http://127.0.0.1:8787/healthz
TOKEN=$(sed -n 's/^RELAY_TOKEN=//p' /etc/olt-relay.env)
wget -qO- --header="Authorization: Bearer $TOKEN" http://127.0.0.1:8787/api/v1/olt/system
