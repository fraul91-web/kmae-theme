# PROJECT MEMORY MAP — SCOPE

## Snapshot scope

This map covers **only the WordPress theme** from:

- source archive: `kmae-theme(20260918-104056).zip`
- archive SHA-256: `1c7119a191094d9a40816a28e0d142349599006d63050bdcba50b65163d78fdf`
- extracted theme directory: `kmae/`
- theme name in `style.css`: **Albira**
- theme runtime constant: `KMAE_THEME_VERSION = 3.25.112`
- `style.css` metadata version: `3.25.8`

The runtime constant and stylesheet metadata version are different in this snapshot. This map records that fact only; it does not infer which one is intended.

## Deliberately excluded

The plugin is **not analyzed in this phase**. Any plugin class, shortcode implementation, plugin template, plugin stylesheet, database method, AJAX/REST endpoint, or JavaScript behavior referenced by the theme is marked `PLUGIN-OWNED` and/or `UNVERIFIED` until the separate plugin mapping pass.

No project source file was modified while creating this map.

## Evidence rule

- `VERIFIED`: directly present in this theme snapshot.
- `PLUGIN-OWNED / UNVERIFIED`: the theme references or expects it, but the implementation is outside this archive.
- `UNREFERENCED-IN-THEME`: file/symbol exists in this theme archive but no caller/reference was found inside the theme snapshot.

## V2 source-truth rule

This memory is a navigation system, not an authority over source.

`SOURCE CODE > PROJECT MEMORY`

Default V2 context budget:
`1 ROUTER + 1 FEATURE MAP + 1–5 SOURCE SYMBOLS`.

If a mapped claim conflicts with source, verify source first and update the map.
