"""Böngészés v2 lista nézet: sqlite „adatbázis-szerver” a tests/test-browse-list.php-nak.

A PHP-teszt (nincs pdo_sqlite) soronként JSON-ban küldi a KMAE_Browse_Feed által épített SQL-t
(stdin), és soronként JSON-ban kapja az eredményt (stdout): {"cols": [...], "rows": [[...]]}.
Az index-tábla neve itt a valódi: wp_kmae_browse_index; a sémát a test-browse-feed-sqlite.py DDL-je adja.

Használat: python3 -I tests/test-browse-list-sqlite.py <index-sorok.json>
"""

import importlib.util
import json
import math
import os
import sqlite3
import sys

TABLE = "wp_kmae_browse_index"


def load_ddl():
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test-browse-feed-sqlite.py")
    spec = importlib.util.spec_from_file_location("feed_sqlite", path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod.DDL.replace("CREATE TABLE idx", "CREATE TABLE " + TABLE)


def fn1(f):
    return lambda x: None if x is None else f(x)


def main(path):
    rows = json.load(open(path, encoding="utf-8"))
    db = sqlite3.connect(":memory:")
    # = test-browse-feed-sqlite.py: a MySQL beépített függvényei (RADIANS a PHP deg2rad képletével)
    db.create_function("RADIANS", 1, fn1(lambda x: x / 180.0 * math.pi), deterministic=True)
    db.create_function("SIN", 1, fn1(math.sin), deterministic=True)
    db.create_function("COS", 1, fn1(math.cos), deterministic=True)
    db.create_function("SQRT", 1, fn1(math.sqrt), deterministic=True)
    db.create_function("ATAN2", 2, lambda y, x: None if y is None or x is None else math.atan2(y, x), deterministic=True)
    db.create_function("POW", 2, lambda x, y: None if x is None or y is None else math.pow(x, y), deterministic=True)
    db.executescript(load_ddl())

    cols = list(rows[0].keys())
    db.executemany(
        "INSERT INTO %s (%s) VALUES (%s)" % (TABLE, ", ".join(cols), ", ".join("?" * len(cols))),
        [tuple(r[c] for c in cols) for r in rows],
    )

    for line in sys.stdin:
        line = line.strip()
        if not line:
            continue
        sql = json.loads(line)["sql"]
        try:
            cur = db.execute(sql)
            out = {"cols": [d[0] for d in cur.description], "rows": [list(r) for r in cur.fetchall()]}
        except Exception as exc:  # noqa: BLE001 - a hibát a PHP-teszt kapja meg
            out = {"error": str(exc)}
        sys.stdout.write(json.dumps(out) + "\n")
        sys.stdout.flush()


if __name__ == "__main__":
    main(sys.argv[1])
