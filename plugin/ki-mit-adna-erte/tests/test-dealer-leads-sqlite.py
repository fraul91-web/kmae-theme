"""Csoportos kereskedői megkeresés: sqlite „adatbázis-szerver” a tests/test-dealer-leads.php-nak.

A PHP-teszt (nincs pdo_sqlite) soronként JSON-ban küldi az SQL-t (stdin), és soronként JSON-ban
kapja az eredményt (stdout): {"cols": [...], "rows": [[...]], "rowcount": n, "lastrowid": n}.
A sémát (sqlite-változat) a PHP-teszt küldi el első lekérdezésként.

Használat: python3 -I tests/test-dealer-leads-sqlite.py
"""

import json
import sqlite3
import sys


def main():
    db = sqlite3.connect(":memory:", isolation_level=None)
    for line in sys.stdin:
        line = line.strip()
        if not line:
            continue
        sql = json.loads(line)["sql"]
        try:
            cur = db.execute(sql)
            out = {
                "cols": [d[0] for d in cur.description] if cur.description else [],
                "rows": [list(r) for r in cur.fetchall()] if cur.description else [],
                "rowcount": cur.rowcount,
                "lastrowid": cur.lastrowid,
            }
        except Exception as exc:  # noqa: BLE001 - a hibát a PHP-teszt kapja meg
            out = {"error": str(exc)}
        sys.stdout.write(json.dumps(out) + "\n")
        sys.stdout.flush()


if __name__ == "__main__":
    main()
