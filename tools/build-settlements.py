#!/usr/bin/env python3
"""
Build plugin/ki-mit-adna-erte/assets/data/settlements.json (Hungarian settlements + postal codes).

Run:  python3 -I tools/build-settlements.py [--cache DIR] [--refresh]
(stdlib only; downloads are cached, --refresh re-downloads; the cache lives OUTSIDE the repo)

SOURCES
  * KSH "Helysegnevtar" (hnt_letoltes_<year>.xlsx, ksh.hu)  -> official settlement list:
    name, KSH code, county (3154 settlements + Budapest as one settlement; the 23 "fovarosi kerulet"
    rows are dropped). Authoritative for NAME / COUNTY.
  * Wikidata SPARQL (CC0)                                   -> latitude/longitude, matched by KSH code
    (P939). Also used to cross-check county and name.
  * Magyar Posta "Iranyitoszam-Internet_uj.xlsx" (posta.hu) -> postal codes. Sheet "Telepulesek":
    zip + settlement (+ settlement part); the 5 big towns (Miskolc, Debrecen, Szeged, Pecs, Gyor) are
    split into street sheets, from which their zip list is read. Budapest zips come from sheet "Bp.u.".
    Posta settlement parts ("Telepulesresz") are folded into the parent settlement.
  No OpenStreetMap-derived data is used.

OUTPUT FORMAT (v2, compact UTF-8 JSON)
  {
   "v": 2, "source": "...", "generated": "YYYY-MM-DD",
   "counties": { "<slug>": [ ["Name", lat, lon, "kshCode", ["zip",...]], ... ], ... },
   "zip": { "4400": [["szabolcs-szatmar-bereg","Nyiregyhaza"]], "8981": [[slug,name],[slug,name]], ... },
   "budapestZipPrefix": "1"
  }
  * slugs = KMAE_Counties::get_all() keys; "budapest" holds exactly ["Budapest", ...].
  * settlements are sorted by Hungarian collation inside each county (see collation_key()).
  * lat/lon: 5 decimals. kshCode: 5-char string (keeps leading zero).
  * "zip" maps a 4-digit code to EVERY settlement that code serves (shared codes -> several pairs;
    the UI offers a choice). The pair is [countySlug, settlementName]; the name is unique country-wide
    (KSH official names are unique), so county+name finds the row in "counties".
  * Budapest: every code starting with "1" -> Budapest. Those ~190 codes are NOT in the "zip" index
    (they are listed in the Budapest settlement row's zip array and covered by "budapestZipPrefix").
  * NOTE for PHP: json_decode(..., true) turns numeric-looking keys of "zip" into ints -> cast to
    string / use (string) $zip when looking up. JS keeps them as strings.
"""
import argparse
import collections
import datetime
import gzip
import json
import math
import os
import re
import statistics
import sys
import time
import unicodedata
import urllib.parse
import urllib.request
import zipfile
import xml.etree.ElementTree as ET

REPO = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(REPO, 'plugin', 'ki-mit-adna-erte', 'assets', 'data', 'settlements.json')
DEFAULT_CACHE = '/tmp/claude-1000/-home-reyes91-Dokumentumok-kmae/444327f7-f4de-4b59-9f27-eff53c6ac776/scratchpad/cache'

KSH_URL = 'https://www.ksh.hu/docs/helysegnevtar/hnt_letoltes_2026.xlsx'
POSTA_URL = 'https://www.posta.hu/static/internet/download/Iranyitoszam-Internet_uj.xlsx'
WIKIDATA = 'https://query.wikidata.org/sparql'
BROWSER_UA = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
WD_UA = 'albira-build-settlements/1.0 (https://albira.hu; fraul91@gmail.com)'

COUNTIES = {  # KSH county name -> slug (KMAE_Counties::get_all())
    'Bács-Kiskun': 'bacs-kiskun', 'Baranya': 'baranya', 'Békés': 'bekes',
    'Borsod-Abaúj-Zemplén': 'borsod-abauj-zemplen', 'főváros': 'budapest',
    'Csongrád-Csanád': 'csongrad-csanad', 'Fejér': 'fejer', 'Győr-Moson-Sopron': 'gyor-moson-sopron',
    'Hajdú-Bihar': 'hajdu-bihar', 'Heves': 'heves', 'Jász-Nagykun-Szolnok': 'jasz-nagykun-szolnok',
    'Komárom-Esztergom': 'komarom-esztergom', 'Nógrád': 'nograd', 'Pest': 'pest', 'Somogy': 'somogy',
    'Szabolcs-Szatmár-Bereg': 'szabolcs-szatmar-bereg', 'Tolna': 'tolna', 'Vas': 'vas',
    'Veszprém': 'veszprem', 'Zala': 'zala',
}
SLUGS = sorted(COUNTIES.values())
BIG_TOWN_SHEETS = {'Miskolc u.': 'Miskolc', 'Debrecen u.': 'Debrecen', 'Szeged u.': 'Szeged',
                   'Pécs u.': 'Pécs', 'Győr u.': 'Győr'}
BUDAPEST_SHEET = 'Bp.u.'

EXPECTED_TOTAL = (3150, 3160)
HU_BBOX = (45.7, 48.6, 16.1, 22.9)  # lat min/max, lon min/max

SPOT_SETTLEMENTS = {
    'Szeged': 'csongrad-csanad', 'Nyíregyháza': 'szabolcs-szatmar-bereg', 'Érd': 'pest',
    'Szentendre': 'pest', 'Sopron': 'gyor-moson-sopron', 'Tiszavasvári': 'szabolcs-szatmar-bereg',
    'Pécs': 'baranya', 'Budapest': 'budapest', 'Abaújalpár': 'borsod-abauj-zemplen',
    'Abaliget': 'baranya', 'Aba': 'fejer', 'Szentgyörgyvölgy': 'zala', 'Tákos': 'szabolcs-szatmar-bereg',
    'Ópusztaszer': 'csongrad-csanad', 'Hollókő': 'nograd', 'Őriszentpéter': 'vas',
    'Tihany': 'veszprem', 'Kisbér': 'komarom-esztergom', 'Hortobágy': 'hajdu-bihar',
    'Gyula': 'bekes', 'Kecskemét': 'bacs-kiskun', 'Eger': 'heves', 'Szolnok': 'jasz-nagykun-szolnok',
    'Kaposvár': 'somogy', 'Szekszárd': 'tolna', 'Hegyhátszentpéter': 'vas',
}
SPOT_ZIPS = {'6720': 'Szeged', '4400': 'Nyíregyháza', '2030': 'Érd', '2000': 'Szentendre',
             '9400': 'Sopron', '7621': 'Pécs', '1051': 'Budapest'}

errors = []
notes = []


def fail(msg):
    errors.append(msg)


# --------------------------------------------------------------------------- download / cache
def fetch(url, path, refresh, ua=BROWSER_UA, params=None, accept=None):
    if os.path.exists(path) and not refresh and os.path.getsize(path) > 0:
        return path
    if params:
        url = url + '?' + urllib.parse.urlencode(params)
    last = None
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers={'User-Agent': ua, 'Accept': accept or '*/*',
                                                       'Accept-Language': 'hu-HU,hu;q=0.9'})
            with urllib.request.urlopen(req, timeout=180) as r:
                data = r.read()
            tmp = path + '.part'
            with open(tmp, 'wb') as f:
                f.write(data)
            os.replace(tmp, path)
            return path
        except Exception as e:  # noqa
            last = e
            time.sleep(3 * (attempt + 1))
    raise SystemExit('download failed: %s (%s)' % (url, last))


# --------------------------------------------------------------------------- xlsx (stdlib)
M = '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'


class Xlsx:
    def __init__(self, path):
        self.z = zipfile.ZipFile(path)
        self.ss = [''.join(t.text or '' for t in si.iter(M + 't'))
                   for si in ET.fromstring(self.z.read('xl/sharedStrings.xml')).iter(M + 'si')]
        rels = {r.get('Id'): r.get('Target') for r in ET.fromstring(self.z.read('xl/_rels/workbook.xml.rels'))}
        self.sheets = {}
        for s in ET.fromstring(self.z.read('xl/workbook.xml')).find(M + 'sheets'):
            t = rels[s.get(R + 'id')].lstrip('/')
            self.sheets[s.get('name')] = t if t.startswith('xl/') else 'xl/' + t

    def names(self):
        return list(self.sheets)

    def rows(self, name):
        """Yield dict {column letter: str}."""
        for row in ET.fromstring(self.z.read(self.sheets[name])).iter(M + 'row'):
            d = {}
            for c in row.findall(M + 'c'):
                v = c.find(M + 'v')
                if v is None or v.text is None:
                    continue
                col = ''.join(ch for ch in c.get('r') if ch.isalpha())
                d[col] = self.ss[int(v.text)] if c.get('t') == 's' else v.text
            yield d


# --------------------------------------------------------------------------- Hungarian collation
_DIGRAPHS = ('dzs', 'cs', 'dz', 'gy', 'ly', 'ny', 'sz', 'ty', 'zs')
_ALPHABET = ['a', 'b', 'c', 'cs', 'd', 'dz', 'dzs', 'e', 'f', 'g', 'gy', 'h', 'i', 'j', 'k', 'l', 'ly',
             'm', 'n', 'ny', 'o', 'ö', 'p', 'q', 'r', 's', 'sz', 't', 'ty', 'u', 'ü', 'v', 'w', 'x', 'y',
             'z', 'zs']
_RANK = {l: i for i, l in enumerate(_ALPHABET)}
_LONG = {'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ő': 'ö', 'ú': 'u', 'ű': 'ü'}


def collation_key(name):
    """Hungarian order: a=á (primary), b, c, cs, d, dz, dzs, e=é, ... o=ó, ö=ő, ... u=ú, ü=ű, ... zs.
    Digraphs (cs, dz, dzs, gy, ly, ny, sz, ty, zs) are letters of their own; spaces/hyphens/dots are
    ignored on the primary level. Ties are broken by accents (short before long), then by the raw text."""
    s = name.lower()
    prim, sec = [], []
    i = 0
    while i < len(s):
        ch = s[i]
        if not ch.isalpha():
            i += 1
            continue
        for dg in _DIGRAPHS:  # 'dzs' first
            if s.startswith(dg, i):
                prim.append(_RANK[dg])
                sec.append(0)
                i += len(dg)
                break
        else:
            base = _LONG.get(ch, ch)
            if base not in _RANK:  # unknown letter: fall back to NFKD base
                base = unicodedata.normalize('NFKD', ch)[0]
            prim.append(_RANK.get(base, 99))
            sec.append(1 if ch in _LONG else 0)
            i += 1
    return (prim, sec, name)


# --------------------------------------------------------------------------- Wikidata
def sparql(query, path, refresh):
    fetch(WIKIDATA, path, refresh, ua=WD_UA, params={'query': query, 'format': 'json'},
          accept='application/sparql-results+json')
    with open(path, 'rb') as f:
        return json.load(f)['results']['bindings']


Q_COORDS = """
SELECT ?s ?ksh ?coord ?rank ?sLabel WHERE {
  ?s wdt:P939 ?ksh .
  ?s p:P625 ?st . ?st ps:P625 ?coord ; wikibase:rank ?rank .
  FILTER(?rank != wikibase:DeprecatedRank)
  SERVICE wikibase:label { bd:serviceParam wikibase:language "hu,en". }
}"""
Q_COUNTY = """
SELECT ?s ?ksh ?countyLabel WHERE {
  ?s wdt:P939 ?ksh .
  ?county wdt:P31 wd:Q188604 .
  ?s wdt:P131* ?county .
  SERVICE wikibase:label { bd:serviceParam wikibase:language "hu,en". }
}"""


def km(lat1, lon1, lat2, lon2):
    p = math.pi / 180
    a = (math.sin((lat2 - lat1) * p / 2) ** 2 +
         math.cos(lat1 * p) * math.cos(lat2 * p) * math.sin((lon2 - lon1) * p / 2) ** 2)
    return 12742 * math.asin(math.sqrt(a))


def norm_name(s):
    return re.sub(r'\s+', ' ', s).strip()


# --------------------------------------------------------------------------- main
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--cache', default=DEFAULT_CACHE)
    ap.add_argument('--refresh', action='store_true')
    args = ap.parse_args()
    os.makedirs(args.cache, exist_ok=True)

    ksh_x = Xlsx(fetch(KSH_URL, os.path.join(args.cache, 'ksh-helysegnevtar.xlsx'), args.refresh))
    posta_x = Xlsx(fetch(POSTA_URL, os.path.join(args.cache, 'posta-iranyitoszam.xlsx'), args.refresh))
    wd_coords = sparql(Q_COORDS, os.path.join(args.cache, 'wd-coords.json'), args.refresh)
    wd_county = sparql(Q_COUNTY, os.path.join(args.cache, 'wd-county.json'), args.refresh)

    # ---- 1. KSH settlements --------------------------------------------------------------
    ksh_sheet = [n for n in ksh_x.names() if n.startswith('Helységek')][0]
    ksh_rows = [d for d in ksh_x.rows(ksh_sheet) if d.get('B') and re.fullmatch(r'\d{5}', d['B'].strip())]
    settlements = {}  # ksh code -> dict
    dropped = []
    for d in ksh_rows:
        name, code, status = norm_name(d['A']), d['B'].strip(), norm_name(d.get('C', ''))
        if status == 'fővárosi kerület':
            dropped.append((name, 'Budapest district (merged into the single settlement "Budapest")'))
            continue
        county = norm_name(d.get('D', '')) or ('főváros' if status == 'főváros' else '')
        if county not in COUNTIES:
            fail('unknown KSH county %r for %s' % (county, name))
            continue
        settlements[code] = {'name': name, 'ksh': code, 'county': COUNTIES[county], 'status': status,
                             'zips': set()}
    print('KSH rows: %d, settlements kept: %d, dropped: %d' % (len(ksh_rows), len(settlements), len(dropped)))
    for n, why in dropped[:1]:
        print('  dropped e.g. %s: %s (x%d)' % (n, why, len(dropped)))

    # ---- 2. Wikidata coordinates ---------------------------------------------------------
    by_code = collections.defaultdict(list)
    rank_val = {'http://wikiba.se/ontology#PreferredRank': 0, 'http://wikiba.se/ontology#NormalRank': 1}
    for b in wd_coords:
        m = re.fullmatch(r'Point\(([-\d.]+) ([-\d.]+)\)', b['coord']['value'])
        if not m:
            continue
        by_code[b['ksh']['value']].append((rank_val.get(b['rank']['value'], 1), float(m.group(2)),
                                           float(m.group(1)), b['s']['value'].rsplit('/', 1)[-1],
                                           b.get('sLabel', {}).get('value', '')))
    wd_total_items = len({b['s']['value'] for b in wd_coords})
    multi_coord = 0
    name_diff = []
    for code, st in settlements.items():
        cands = by_code.get(code)
        if not cands:
            fail('no Wikidata coordinate for %s (%s)' % (st['name'], code))
            continue
        best_rank = min(c[0] for c in cands)
        best = sorted({(c[1], c[2], c[3], c[4]) for c in cands if c[0] == best_rank})
        if len(best) > 1:
            multi_coord += 1
            far = max(km(best[0][0], best[0][1], x[0], x[1]) for x in best)
            if far > 3:
                notes.append('multiple coordinates for %s (%s): spread %.1f km, took first' %
                             (st['name'], code, far))
        st['lat'], st['lon'] = best[0][0], best[0][1]
        st['qid'] = best[0][2]
        if norm_name(best[0][3]) != st['name']:
            name_diff.append((st['name'], best[0][3]))
    # Wikidata county cross-check (only items in a county chain; Budapest is not one)
    wd_cty = collections.defaultdict(set)
    for b in wd_county:
        wd_cty[b['ksh']['value']].add(b['countyLabel']['value'].replace(' megye', ''))
    cty_mismatch = []
    for code, st in settlements.items():
        if st['county'] == 'budapest':
            continue
        got = wd_cty.get(code)
        if not got:
            cty_mismatch.append((st['name'], st['county'], 'no WD county chain'))
        elif COUNTIES.get(next(iter(got))) != st['county'] or len(got) != 1:
            cty_mismatch.append((st['name'], st['county'], sorted(got)))
    print('Wikidata items with KSH code+coords: %d; settlements matched: %d; items with >1 best coord: %d'
          % (wd_total_items, sum(1 for s in settlements.values() if 'lat' in s), multi_coord))
    print('Wikidata label != KSH name: %d %s' % (len(name_diff), name_diff[:8]))
    print('Wikidata county != KSH county: %d %s' % (len(cty_mismatch), cty_mismatch[:8]))
    if cty_mismatch:
        for m in cty_mismatch:
            fail('county mismatch WD vs KSH: %r' % (m,))
    # coordinate outliers: far from the county's median point
    for slug in SLUGS:
        pts = [(s['lat'], s['lon']) for s in settlements.values() if s['county'] == slug and 'lat' in s]
        if len(pts) < 3:
            continue
        mlat = statistics.median(p[0] for p in pts)
        mlon = statistics.median(p[1] for p in pts)
        for s in settlements.values():
            if s['county'] == slug and 'lat' in s and km(mlat, mlon, s['lat'], s['lon']) > 140:
                fail('coordinate outlier: %s (%s) %.1f km from county median' %
                     (s['name'], slug, km(mlat, mlon, s['lat'], s['lon'])))

    # ---- 3. Posta zips -------------------------------------------------------------------
    by_name = {}
    for st in settlements.values():
        if st['name'] in by_name:
            fail('duplicate settlement name country-wide: %s' % st['name'])
        by_name[st['name']] = st
    by_name_ci = {k.lower(): v for k, v in by_name.items()}
    posta_unmatched = collections.Counter()
    posta_parts = 0
    pairs = set()  # (zip, ksh)
    posta_names = posta_x.names()
    main_sheet = [n for n in posta_names if n.startswith('Települ')][0]

    def add_zip(z, name, source):
        z = z.strip()
        if not re.fullmatch(r'\d{4}', z):
            return False
        st = by_name.get(name) or by_name_ci.get(name.lower())
        if not st:
            posta_unmatched[(name, source)] += 1
            return False
        st['zips'].add(z)
        pairs.add((z, st['ksh']))
        return True

    for d in posta_x.rows(main_sheet):
        z = d.get('A', '').strip()
        if not re.fullmatch(r'\d{4}', z):
            continue
        if norm_name(d.get('C', '')):
            posta_parts += 1
        add_zip(z, norm_name(d.get('B', '')), main_sheet)
    for sheet, town in BIG_TOWN_SHEETS.items():
        if sheet not in posta_names:
            fail('Posta street sheet missing: %s' % sheet)
            continue
        for d in posta_x.rows(sheet):
            add_zip(d.get('A', ''), town, sheet)
    bp_zips = set()
    for d in posta_x.rows(BUDAPEST_SHEET):
        z = d.get('A', '').strip()
        if re.fullmatch(r'\d{4}', z):
            bp_zips.add(z)
    bp = by_name.get('Budapest')
    if not bp:
        fail('Budapest settlement missing')
    else:
        bp['zips'] = set(bp_zips)
    for (n, s), c in posta_unmatched.items():
        fail('Posta settlement name without KSH match: %r (sheet %s, %d rows)' % (n, s, c))
    print('Posta: settlement-part rows folded into parent: %d; unmatched names: %d; Budapest zips: %d'
          % (posta_parts, len(posta_unmatched), len(bp_zips)))

    # KSH settlement-part zips (informational: external-area codes Posta's list does not name)
    parts_sheet = [n for n in ksh_x.names() if n.startswith('Településrész')][0]
    ksh_only = []
    for d in ksh_x.rows(parts_sheet):
        code, z = d.get('A', '').strip(), d.get('F', '').strip()
        if code in settlements and re.fullmatch(r'\d{4}', z) and settlements[code]['county'] != 'budapest':
            if (z, code) not in pairs:
                ksh_only.append((z, code))
    ksh_only = sorted(set(ksh_only))
    posta_zip_counties = collections.defaultdict(set)
    for z, c in pairs:
        posta_zip_counties[z].add(settlements[c]['county'])
    cross = [(z, settlements[c]['name']) for z, c in ksh_only
             if settlements[c]['county'] not in posta_zip_counties.get(z, set())]
    print('KSH settlement-part zip pairs not in Posta list (NOT included): %d (of which county differs from the '
          'Posta county of that zip or zip unknown to Posta: %d)' % (len(ksh_only), len(cross)))

    # ---- 4. assemble ---------------------------------------------------------------------
    counties_out = {}
    for slug in SLUGS:
        rows = [s for s in settlements.values() if s['county'] == slug]
        rows.sort(key=lambda s: collation_key(s['name']))
        names = [s['name'] for s in rows]
        for n, c in collections.Counter(names).items():
            if c > 1:
                fail('duplicate name within county %s: %s' % (slug, n))
        counties_out[slug] = [[s['name'], round(s['lat'], 5), round(s['lon'], 5), s['ksh'],
                               sorted(s['zips'])] for s in rows if 'lat' in s]
    zip_index = collections.defaultdict(list)
    for slug in SLUGS:
        if slug == 'budapest':
            continue
        for name, _la, _lo, _k, zs in counties_out[slug]:
            for z in zs:
                zip_index[z].append([slug, name])
    zip_out = {z: sorted(v, key=lambda p: collation_key(p[1])) for z, v in sorted(zip_index.items())}
    data = {
        'v': 2,
        'source': ('KSH Helysegnevtar 2026-01-01 (names, counties, KSH codes); Wikidata CC0 (coordinates); '
                   'Magyar Posta iranyitoszam list (postal codes)'),
        'generated': datetime.date.today().isoformat(),
        'counties': counties_out,
        'zip': zip_out,
        'budapestZipPrefix': '1',
    }

    # ---- 5. validation -------------------------------------------------------------------
    total = sum(len(v) for v in counties_out.values())
    print('\nSettlements per county:')
    for slug in SLUGS:
        print('  %-24s %4d' % (slug, len(counties_out[slug])))
    print('TOTAL settlements: %d (expected %d-%d)' % (total, *EXPECTED_TOTAL))
    if set(counties_out) != set(SLUGS) or len(SLUGS) != 20:
        fail('county slug set mismatch')
    if [r[0] for r in counties_out['budapest']] != ['Budapest']:
        fail('budapest must contain exactly ["Budapest"]')
    if not (EXPECTED_TOTAL[0] <= total <= EXPECTED_TOTAL[1]):
        fail('total out of range: %d' % total)
    if total != len(settlements):
        fail('settlements lost during assembly: %d vs %d' % (total, len(settlements)))
    for slug, rows in counties_out.items():
        for name, lat, lon, ksh, zs in rows:
            if not name or lat is None or lon is None:
                fail('missing name/coords: %s %s' % (slug, name))
            elif not (HU_BBOX[0] <= lat <= HU_BBOX[1] and HU_BBOX[2] <= lon <= HU_BBOX[3]):
                fail('coordinate outside Hungary: %s (%s,%s)' % (name, lat, lon))
            if not re.fullmatch(r'\d{5}', ksh):
                fail('bad KSH code: %s %s' % (name, ksh))
    # spot checks (settlements)
    idx = {r[0]: slug for slug, rows in counties_out.items() for r in rows}
    for n, slug in SPOT_SETTLEMENTS.items():
        if idx.get(n) != slug:
            fail('spot check failed: %s expected %s got %s' % (n, slug, idx.get(n)))
    # zips
    no_zip = [(r[0], slug) for slug, rows in counties_out.items() for r in rows if not r[4]]
    print('Settlements without any postal code: %d %s' % (len(no_zip), no_zip))
    if len(no_zip) > 5:
        fail('too many settlements without a postal code: %d' % len(no_zip))
    rowset = {(slug, r[0]) for slug, rows in counties_out.items() for r in rows}
    shared = 0
    for z, lst in zip_out.items():
        if not re.fullmatch(r'\d{4}', z) or z[0] == '1':
            fail('bad zip in index: %s' % z)
        for slug, name in lst:
            if (slug, name) not in rowset:
                fail('zip %s -> unknown settlement %s/%s' % (z, slug, name))
            if idx.get(name) != slug:
                fail('zip %s -> county mismatch %s/%s' % (z, slug, name))
        if len(lst) > 1:
            shared += 1
    print('Postal codes (non-Budapest): %d; shared by >1 settlement: %d; max settlements on one code: %d'
          % (len(zip_out), shared, max(len(v) for v in zip_out.values())))
    for slug, rows in counties_out.items():
        for name, _la, _lo, _k, zs in rows:
            for z in zs:
                if slug == 'budapest':
                    if z[0] != '1':
                        fail('Budapest zip not starting with 1: %s' % z)
                elif z[0] == '1':
                    fail('non-Budapest zip starting with 1: %s %s' % (name, z))
    for z, n in SPOT_ZIPS.items():
        if n == 'Budapest':
            ok = z[0] == data['budapestZipPrefix'] and z in counties_out['budapest'][0][4]
        else:
            ok = any(p[1] == n for p in zip_out.get(z, []))
        print('  zip %s -> %s : %s' % (z, n, 'ok' if ok else 'FAIL'))
        if not ok:
            fail('zip spot check failed: %s -> %s (index: %s)' % (z, n, zip_out.get(z)))

    # ---- 6. write ------------------------------------------------------------------------
    if errors:
        print('\nVALIDATION FAILED (%d):' % len(errors))
        for e in errors:
            print('  - ' + e)
        sys.exit(1)
    raw = json.dumps(data, ensure_ascii=False, separators=(',', ':'))
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    with open(OUT, 'w', encoding='utf-8') as f:
        f.write(raw)
    raw_b = len(raw.encode('utf-8'))
    gz_b = len(gzip.compress(raw.encode('utf-8'), 9))
    for n in notes:
        print('note: ' + n)
    print('\nVALIDATION PASSED')
    print('wrote %s: %d bytes raw, %d bytes gzip' % (OUT, raw_b, gz_b))
    if raw_b > 400 * 1024:
        print('WARNING: > 400 KB raw; consider splitting the "zip" index into its own file')


if __name__ == '__main__':
    main()
