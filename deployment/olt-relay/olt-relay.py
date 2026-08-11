#!/usr/bin/env python3
import json
import hashlib
import hmac
import os
import re
import subprocess
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlparse

HOST = os.getenv("RELAY_HOST", "127.0.0.1")
PORT = int(os.getenv("RELAY_PORT", "8787"))
OLT_HOST = os.getenv("OLT_HOST", "192.168.99.1")
COMMUNITY = os.getenv("OLT_COMMUNITY", "public")
TOKEN = os.getenv("RELAY_TOKEN", "")
CACHE_TTL = max(1, int(os.getenv("CACHE_TTL", "5")))
ALLOWED_OIDS = ("1.3.6.1.2.1.", "1.3.6.1.4.1.50224.")
OID_PATTERN = re.compile(r"^\d+(?:\.\d+)+$")
CACHE = {}


def snmp_get(oids):
    key = tuple(oids)
    cached = CACHE.get(key)
    if cached and time.time() - cached[0] < CACHE_TTL:
        return cached[1], True
    command = ["/usr/bin/snmpget", "-On", "-v1", "-c", COMMUNITY, "-t", "3", "-r", "1", OLT_HOST] + list(oids)
    result = subprocess.run(command, capture_output=True, text=True, timeout=8, check=False)
    if result.returncode != 0:
        raise RuntimeError((result.stderr or result.stdout or "SNMP request failed").strip())
    values = {}
    for line in result.stdout.splitlines():
        match = re.match(r"\.?(\d+(?:\.\d+)*)\s+=\s+([^:]+):\s*(.*)$", line.strip())
        if match:
            values[match.group(1)] = {"type": match.group(2).strip(), "value": match.group(3).strip().strip('"')}
    CACHE[key] = (time.time(), values)
    return values, False


def snmp_walk(oid):
    key = ("walk", oid)
    cached = CACHE.get(key)
    if cached and time.time() - cached[0] < CACHE_TTL:
        return cached[1], True
    command = ["/usr/bin/snmpwalk", "-On", "-v1", "-c", COMMUNITY, "-t", "3", "-r", "1", OLT_HOST, oid]
    result = subprocess.run(command, capture_output=True, text=True, timeout=8, check=False)
    if result.returncode != 0:
        raise RuntimeError((result.stderr or result.stdout or "SNMP walk failed").strip())
    values = {}
    for line in result.stdout.splitlines():
        match = re.match(r"\.?(\d+(?:\.\d+)*)\s+=\s+([^:]+):\s*(.*)$", line.strip())
        if match:
            values[match.group(1)] = match.group(3).strip().strip('"')
    CACHE[key] = (time.time(), values)
    return values, False


def last_oid_part(oid):
    return oid.rsplit(".", 1)[-1]


def integer_value(value):
    match = re.search(r"-?\d+", str(value))
    return int(match.group(0)) if match else -4000


def olt_devices():
    names, names_cached = snmp_walk("1.3.6.1.4.1.50224.3.12.2.1.2")
    serials, serials_cached = snmp_walk("1.3.6.1.4.1.50224.3.12.2.1.15")
    powers, powers_cached = snmp_walk("1.3.6.1.4.1.50224.3.12.3.1.4")
    devices = {}
    for oid, value in names.items():
        index = last_oid_part(oid)
        devices[index] = {"ont_index": index, "ont_name": value, "serial_number": "", "rx": None}
    for oid, value in serials.items():
        index = last_oid_part(oid)
        devices.setdefault(index, {"ont_index": index, "ont_name": "ONT " + index, "serial_number": "", "rx": None})
        devices[index]["serial_number"] = value.upper()
    for oid, value in powers.items():
        match = re.search(r"\.(\d+)\.0\.0$", oid)
        if not match or match.group(1) not in devices:
            continue
        raw = integer_value(value)
        devices[match.group(1)]["rx"] = None if raw <= -4000 else round(raw / 100.0, 2)
    result = []
    for device in devices.values():
        if not device["serial_number"]:
            continue
        rx = device["rx"]
        device["status"] = "offline" if rx is None else ("normal" if rx >= -25 else ("warning" if rx >= -28 else "critical"))
        result.append(device)
    return result, names_cached and serials_cached and powers_cached


class Handler(BaseHTTPRequestHandler):
    server_version = "BataraOLTRelay/1.0"

    def log_message(self, fmt, *args):
        print('%s - %s' % (self.address_string(), fmt % args), flush=True)

    def send_json(self, status, payload):
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Cache-Control", "no-store")
        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def authorized(self):
        timestamp = self.headers.get("X-Relay-Timestamp", "")
        signature = self.headers.get("X-Relay-Signature", "")
        if timestamp.isdigit() and abs(int(time.time()) - int(timestamp)) <= 60:
            message = timestamp + "\n" + self.path
            expected = hmac.new(TOKEN.encode("utf-8"), message.encode("utf-8"), hashlib.sha256).hexdigest()
            if hmac.compare_digest(signature, expected):
                return True
        return False

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == "/healthz":
            self.send_json(200, {"success": True, "service": "olt-relay"})
            return
        allowed_paths = ("/api/v1/olt/system", "/api/v1/olt/devices", "/api/v1/snmp/get")
        if parsed.path not in allowed_paths:
            self.send_json(404, {"success": False, "error": "Not found"})
            return
        if not self.authorized():
            self.send_json(401, {"success": False, "error": "Unauthorized"})
            return
        try:
            if parsed.path == "/api/v1/olt/system":
                oids = ["1.3.6.1.2.1.1.1.0", "1.3.6.1.2.1.1.2.0", "1.3.6.1.2.1.1.3.0", "1.3.6.1.2.1.1.5.0"]
            elif parsed.path == "/api/v1/olt/devices":
                devices, cached = olt_devices()
                self.send_json(200, {"success": True, "olt": OLT_HOST, "cached": cached, "collected_at": int(time.time()), "devices": devices})
                return
            elif parsed.path == "/api/v1/snmp/get":
                oids = parse_qs(parsed.query).get("oid", [])
                if not oids or len(oids) > 20:
                    self.send_json(400, {"success": False, "error": "Provide 1-20 oid parameters"})
                    return
                if any(not OID_PATTERN.fullmatch(oid) or not oid.startswith(ALLOWED_OIDS) for oid in oids):
                    self.send_json(400, {"success": False, "error": "OID is outside the allowlist"})
                    return
            values, cached = snmp_get(oids)
            self.send_json(200, {"success": True, "olt": OLT_HOST, "cached": cached, "collected_at": int(time.time()), "data": values})
        except subprocess.TimeoutExpired:
            self.send_json(504, {"success": False, "error": "SNMP timeout"})
        except Exception as exc:
            self.send_json(502, {"success": False, "error": str(exc)})


if not TOKEN:
    raise SystemExit("RELAY_TOKEN must be configured")
ThreadingHTTPServer((HOST, PORT), Handler).serve_forever()
