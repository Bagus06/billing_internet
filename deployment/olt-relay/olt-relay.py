#!/usr/bin/env python3
import json
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
        return TOKEN and self.headers.get("Authorization", "") == "Bearer " + TOKEN

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == "/healthz":
            self.send_json(200, {"success": True, "service": "olt-relay"})
            return
        if not self.authorized():
            self.send_json(401, {"success": False, "error": "Unauthorized"})
            return
        try:
            if parsed.path == "/api/v1/olt/system":
                oids = ["1.3.6.1.2.1.1.1.0", "1.3.6.1.2.1.1.2.0", "1.3.6.1.2.1.1.3.0", "1.3.6.1.2.1.1.5.0"]
            elif parsed.path == "/api/v1/snmp/get":
                oids = parse_qs(parsed.query).get("oid", [])
                if not oids or len(oids) > 20:
                    self.send_json(400, {"success": False, "error": "Provide 1-20 oid parameters"})
                    return
                if any(not OID_PATTERN.fullmatch(oid) or not oid.startswith(ALLOWED_OIDS) for oid in oids):
                    self.send_json(400, {"success": False, "error": "OID is outside the allowlist"})
                    return
            else:
                self.send_json(404, {"success": False, "error": "Not found"})
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
