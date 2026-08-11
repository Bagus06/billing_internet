#!/bin/sh
set -eu

VPN_SUBNET="10.88.0.0/24"
SERVER_VPN_IP="10.88.0.1/24"
CLIENT_VPN_IP="10.88.0.2/32"
PUBLIC_ENDPOINT="103.85.52.33:51888"
WG_PORT="51888"
KEY_DIR="/etc/wireguard/keys"
CLIENT_DIR="/home/administrator/wireguard-clients"

umask 077
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y wireguard-tools
install -d -m 0700 "$KEY_DIR" "$CLIENT_DIR"

if [ ! -s "$KEY_DIR/server_private.key" ]; then
    wg genkey | tee "$KEY_DIR/server_private.key" | wg pubkey > "$KEY_DIR/server_public.key"
fi
if [ ! -s "$KEY_DIR/client_private.key" ]; then
    wg genkey | tee "$KEY_DIR/client_private.key" | wg pubkey > "$KEY_DIR/client_public.key"
fi

SERVER_PRIVATE=$(cat "$KEY_DIR/server_private.key")
SERVER_PUBLIC=$(cat "$KEY_DIR/server_public.key")
CLIENT_PRIVATE=$(cat "$KEY_DIR/client_private.key")
CLIENT_PUBLIC=$(cat "$KEY_DIR/client_public.key")

cat > /etc/wireguard/wg0.conf <<EOF
[Interface]
Address = $SERVER_VPN_IP
ListenPort = $WG_PORT
PrivateKey = $SERVER_PRIVATE

[Peer]
PublicKey = $CLIENT_PUBLIC
AllowedIPs = $CLIENT_VPN_IP
EOF
chmod 0600 /etc/wireguard/wg0.conf

cat > "$CLIENT_DIR/codex-laptop.conf" <<EOF
[Interface]
PrivateKey = $CLIENT_PRIVATE
Address = 10.88.0.2/32

[Peer]
PublicKey = $SERVER_PUBLIC
Endpoint = $PUBLIC_ENDPOINT
AllowedIPs = $VPN_SUBNET
PersistentKeepalive = 25
EOF
chown -R administrator:administrator "$CLIENT_DIR"
chmod 0700 "$CLIENT_DIR"
chmod 0600 "$CLIENT_DIR/codex-laptop.conf"

ufw --force delete allow 30003/tcp >/dev/null 2>&1 || true
ufw --force delete allow 80/tcp >/dev/null 2>&1 || true
ufw allow from 10.5.5.0/29 to any port 30003 proto tcp comment 'SSH local management'
ufw allow from 10.5.5.0/29 to any port 80 proto tcp comment 'Web local management'
ufw allow from 10.7.0.0/29 to any port 80 proto tcp comment 'Isolation customers'
ufw allow from "$VPN_SUBNET" to any port 30003 proto tcp comment 'SSH via WireGuard'
ufw allow from "$VPN_SUBNET" to any port 80 proto tcp comment 'Web via WireGuard'
ufw allow "$WG_PORT"/udp comment 'WireGuard VPN'
ufw reload

systemctl enable wg-quick@wg0
systemctl restart wg-quick@wg0
wg show wg0
ufw status verbose
