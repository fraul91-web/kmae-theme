"""Böngészés v2 feed: a KMAE_Browse_Feed::build_sql() által épített SQL futtatása sqlite3-mal.

A tests/test-browse-feed.php hívja egy JSON-nal (index-sorok + esetek a régi PHP-s
sorrenddel). Ellenőrzi: ids-sorrend = régi sorrend, COUNT = darabszám, a 12-es
oldalak egymás után = a teljes sorrend, távolság = KMAE_Geo::distance_km.

MySQL-specifikus rész: a RADIANS/SIN/COS/ATAN2/SQRT/POW a MySQL-ben beépített, itt
Python-függvényként van regisztrálva (a RADIANS a PHP deg2rad képletével: x / 180 * pi).
"""

import json
import math
import sqlite3
import sys

DDL = """
CREATE TABLE idx (
  listing_id INTEGER PRIMARY KEY,
  author_id INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 0,
  in_category INTEGER NOT NULL DEFAULT 0,
  is_reserved INTEGER NOT NULL DEFAULT 0,
  post_date TEXT NOT NULL,
  post_date_gmt TEXT NOT NULL,
  county TEXT NOT NULL DEFAULT '',
  body TEXT NOT NULL DEFAULT '',
  brand_key TEXT NOT NULL DEFAULT '',
  model_key TEXT NOT NULL DEFAULT '',
  sort_name TEXT DEFAULT NULL,
  model_year INTEGER NOT NULL DEFAULT 0,
  mileage INTEGER NOT NULL DEFAULT 0,
  engine_cc INTEGER NOT NULL DEFAULT 0,
  power_kw INTEGER NOT NULL DEFAULT 0,
  power_hp INTEGER NOT NULL DEFAULT 0,
  fuel TEXT NOT NULL DEFAULT '',
  gear TEXT NOT NULL DEFAULT '',
  cond TEXT NOT NULL DEFAULT '',
  price_mode TEXT NOT NULL DEFAULT 'open',
  has_cash INTEGER NOT NULL DEFAULT 0,
  vehicle_value INTEGER NOT NULL DEFAULT 0,
  price_amount REAL DEFAULT NULL,
  mot_date TEXT DEFAULT NULL,
  lat REAL DEFAULT NULL,
  lon REAL DEFAULT NULL,
  card TEXT NOT NULL DEFAULT '{}'
);
"""


def ranks(values):
    """= KMAE_Browse_Feed::ranks()."""
    out, rank, prev = [], -1, None
    for i, v in enumerate(values):
        same = i > 0 and ((prev is None) if v is None else (prev is not None and v == prev))
        if not same:
            rank += 1
        out.append(rank)
        prev = v
    return out


def client_personalize(s_ids, s_ranks, d_ids, me):
    """A BROWSE-V2.md „Személyes adat a kliensen” szabálya, szó szerint.

    s_ids/s_ranks: a kért feed ids + ranks (alap-rendezésnél ranks nincs);
    d_ids: a válasz `base` query-jének ids-e (a régi get_listings adag, dátum szerint).
    """
    excluded = set(me["own"]) | set(me["contacted"])
    d = [i for i in d_ids if i not in excluded]
    passed_set = set(me["passed"])
    fresh = [i for i in d if i not in passed_set]
    passed = [i for i in d if i in passed_set]
    if not fresh:
        merged = passed
    elif not passed:
        merged = fresh
    else:
        merged, k, n = [], 0, me["passed_interval"]
        for idx, lid in enumerate(fresh):
            merged.append(lid)
            if (idx + 1) % n == 0:
                merged.append(passed[k % len(passed)])
                k += 1
    in_feed = set(s_ids)
    kept = [i for i in merged if i in in_feed]  # pl. a távolság-körön kívüliek ki
    if s_ranks is None:
        return kept
    rank = dict(zip(s_ids, s_ranks))
    return sorted(kept, key=lambda i: rank[i])  # stabil: egyenlő rangnál a beszúrt sorrend marad


def fn1(f):
    return lambda x: None if x is None else f(x)


def main(path):
    data = json.load(open(path, encoding="utf-8"))
    db = sqlite3.connect(":memory:")
    db.create_function("RADIANS", 1, fn1(lambda x: x / 180.0 * math.pi), deterministic=True)
    db.create_function("SIN", 1, fn1(math.sin), deterministic=True)
    db.create_function("COS", 1, fn1(math.cos), deterministic=True)
    db.create_function("SQRT", 1, fn1(math.sqrt), deterministic=True)
    db.create_function("ATAN2", 2, lambda y, x: None if y is None or x is None else math.atan2(y, x), deterministic=True)
    db.create_function("POW", 2, lambda x, y: None if x is None or y is None else math.pow(x, y), deterministic=True)
    db.executescript(DDL)

    cols = [c for c in data["rows"][0].keys()]
    db.executemany(
        "INSERT INTO idx (%s) VALUES (%s)" % (", ".join(cols), ", ".join("?" * len(cols))),
        [tuple(r[c] for c in cols) for r in data["rows"]],
    )

    me = data["me"]
    stats = {"exact": 0, "total": 0, "prefiltered": 0}

    ok = 0
    fails = []
    for case in data["cases"]:
        name = case["name"]
        expected = case["expected"]

        id_rows = list(db.execute(case["ids_sql"]))
        got = [r[0] for r in id_rows]
        got_ranks = ranks([r[1] for r in id_rows]) if not case["personal"]["default_sort"] else None
        if got == expected:
            ok += 1
        else:
            fails.append("%s: sorrend\n   régi: %s\n   új:   %s" % (name, expected, got))

        count = db.execute(case["count_sql"]).fetchone()[0]
        if count == len(expected):
            ok += 1
        else:
            fails.append("%s: count %d != %d" % (name, count, len(expected)))

        paged = []
        dist_bad = []
        for sql in case["pages"]:
            for row in db.execute(sql):
                paged.append(row[0])
                if len(row) > 3:
                    want = case["distances"].get(str(row[0]))
                    if (want is None) != (row[3] is None) or (want is not None and abs(want - row[3]) > 1e-9):
                        dist_bad.append((row[0], want, row[3]))
        if paged == expected:
            ok += 1
        else:
            fails.append("%s: lapozás\n   régi: %s\n   új:   %s" % (name, expected, paged))
        if dist_bad:
            fails.append("%s: távolság %s" % (name, dist_bad[:3]))
        else:
            ok += 1

        pers = case["personal"]
        if not pers["prefilter_equal"]:
            stats["prefiltered"] += 1
        d_ids = [r[0] for r in db.execute(case["base_ids_sql"])]
        merged = client_personalize(got, None if pers["default_sort"] else got_ranks, d_ids, me)
        stats["total"] += 1
        if merged == pers["expected"]:
            ok += 1
            stats["exact"] += 1
        else:
            fails.append("%s: személyre szabás\n   régi: %s\n   új:   %s" % (name, pers["expected"], merged))

    for f in fails:
        print("FAIL  " + f)
    print("Személyre szabás (belépett user): pontos egyezés %(exact)d/%(total)d (ebből %(prefiltered)d, ahol a szűrés a beszúrás után vesz el)" % stats)
    print("SQLite: %d ok, %d hiba (%d eset, sqlite %s)" % (ok, len(fails), len(data["cases"]), sqlite3.sqlite_version))
    sys.exit(1 if fails else 0)


if __name__ == "__main__":
    main(sys.argv[1])
