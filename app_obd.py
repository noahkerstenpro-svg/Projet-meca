#!/usr/bin/env python3
"""
OBD Bluetooth Control Panel
- Bluetooth et VIN : SIMULATION (pas de vrai materiel requis)
- MySQL : REEL (enregistrement en base)
Lancer : python app.py
"""

from flask import Flask, Response, request, jsonify
import json, time, random, string, os

try:
    import mysql.connector
    MYSQL_OK = True
except ImportError:
    MYSQL_OK = False

app = Flask(__name__)
MAC = "01:23:45:67:89:BA"


def sse(data):
    return f"data: {json.dumps(data, ensure_ascii=False)}\n\n"

def sse_headers():
    return {"Cache-Control": "no-cache", "X-Accel-Buffering": "no"}

def sleep(s):
    time.sleep(s)


# ── Simulation Bluetooth ───────────────────────────────────────────────────────

@app.route("/connect")
def bt_connect():
    def gen():
        yield sse({"line": f"lancement du pairing vers {MAC}"})
        sleep(0.4)
        yield sse({"line": f"$ bluetoothctl pair {MAC}"})
        sleep(0.8)
        yield sse({"line": "discovery started"})
        sleep(0.6)
        yield sse({"line": f"attempting to pair with {MAC}"})
        sleep(1.0)
        yield sse({"line": f"pairing successful — {MAC}"})
        sleep(0.4)
        yield sse({"line": f"$ bluetoothctl trust {MAC}"})
        sleep(0.6)
        yield sse({"line": f"[CHG] Device {MAC} Trusted: yes"})
        yield sse({"line": f"{MAC} ajouté aux appareils de confiance"})
        yield sse({"done": True, "status": "ok"})
    return Response(gen(), mimetype="text/event-stream", headers=sse_headers())


@app.route("/reset")
def bt_reset():
    def gen():
        steps = [
            ("net stop bthserv",    "arrêt service Bluetooth..."),
            ("net start bthserv",   "démarrage service Bluetooth..."),
            ("pnputil /restart-device", "rechargement adaptateur BT..."),
        ]
        for cmd, label in steps:
            yield sse({"line": label})
            sleep(0.3)
            yield sse({"line": "$ " + cmd})
            sleep(0.8)
            yield sse({"line": "→ ok"})
        yield sse({"line": "reset terminé — relancez la connexion"})
        yield sse({"done": True, "status": "ok"})
    return Response(gen(), mimetype="text/event-stream", headers=sse_headers())


# ── Simulation VIN ─────────────────────────────────────────────────────────────

def gen_vin():
    chars = "ABCDEFGHJKLMNPRSTUVWXYZ0123456789"
    prefixes = ["VF3", "VF1", "VF7", "WBA", "WDD", "ZFA", "SCC", "TRU"]
    prefix = random.choice(prefixes)
    suffix = "".join(random.choices(chars, k=14))
    return prefix + suffix


@app.route("/vin")
def read_vin():
    def gen():
        yield sse({"line": "connexion /dev/rfcomm0 @ 38400 baud..."})
        sleep(0.4)
        yield sse({"line": "$ python3 vin_reader.py"})
        sleep(0.5)
        yield sse({"line": "import obd — ok"})
        sleep(0.3)
        yield sse({"line": "obd.logger level: DEBUG"})
        sleep(0.9)
        yield sse({"line": "Connecté !"})
        sleep(0.5)
        yield sse({"line": "requête OBD command: VIN"})
        sleep(0.8)
        vin = gen_vin()
        yield sse({"line": f"VIN = {vin}"})
        yield sse({"done": True, "status": "ok", "vin": vin})
    return Response(gen(), mimetype="text/event-stream", headers=sse_headers())


# ── MySQL réel ─────────────────────────────────────────────────────────────────

@app.route("/db/test", methods=["POST"])
def db_test():
    if not MYSQL_OK:
        return jsonify({"ok": False, "msg": "mysql-connector-python non installé.\npip install mysql-connector-python"})
    d = request.json
    try:
        cx = mysql.connector.connect(
            host=d["host"], port=int(d["port"]),
            database=d["db"], user=d["user"], password=d["password"],
            connect_timeout=5
        )
        cx.close()
        return jsonify({"ok": True, "msg": f"Connecté → {d['user']}@{d['host']}:{d['port']}/{d['db']}"})
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})


@app.route("/db/save", methods=["POST"])
def db_save():
    if not MYSQL_OK:
        return jsonify({"ok": False, "msg": "mysql-connector-python non installé"})
    d = request.json
    cfg, data = d["cfg"], d["data"]
    try:
        cx = mysql.connector.connect(
            host=cfg["host"], port=int(cfg["port"]),
            database=cfg["db"], user=cfg["user"], password=cfg["password"],
            connect_timeout=5
        )
        cur = cx.cursor()
        tbl = cfg["table"]
        cur.execute(
            f"INSERT INTO `{tbl}` (`vin`, `marque/modèle`, `client_id`) VALUES (%s, %s, %s)",
            (data["vin"], data["marque"], data["client"])
        )
        cx.commit()
        new_id = cur.lastrowid
        cur.close(); cx.close()
        return jsonify({
            "ok": True, "id": new_id,
            "sql": f"INSERT INTO `{tbl}` (`vin`, `marque/modèle`, `client_id`) VALUES ('{data['vin']}', '{data['marque']}', '{data['client']}')"
        })
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})


# ── Frontend ───────────────────────────────────────────────────────────────────

@app.route("/")
def index():
    with open(os.path.join(os.path.dirname(__file__), "index.html"), encoding="utf-8") as f:
        return f.read()


if __name__ == "__main__":
    if not MYSQL_OK:
        print("⚠  mysql-connector-python manquant : pip install mysql-connector-python")
    print("✓  OBD Panel (simulation) → http://localhost:5000")
    app.run(host="0.0.0.0", port=5000, debug=False)
