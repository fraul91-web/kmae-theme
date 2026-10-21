# PROJECT MEMORY — PLUGIN ONLY

This folder is the persistent technical map for the uploaded Albira/KMAE WordPress plugin source.

## Recommended AI workflow

1. Read `MASTER-MAP.md` only.
2. Pick the matching `FEATURES/<feature>.md`.
3. Load only its `READ FIRST` source files/symbols.
4. Use `SYMBOL-MAP.md`, `DATA-MAP.md`, `HOOK-ROUTE-MAP.md` only when the problem crosses those layers.
5. Follow the relevant chain in `FLOWS.md`.
6. Before editing, check `CHANGE-RISK-MAP.md` and reverse callers.
7. After a verified code change/test, update only affected memory files.

## Scope

- Plugin source only.
- Theme implementation is intentionally not mapped here.
- No project source was modified while building this map.
- `UNVERIFIED` means the ZIP did not contain enough evidence to state a runtime fact.


## V2 diagnostic workflow

For a concrete bug, start with `DIAGNOSTIC-ROUTER.md`, then load exactly one matching `FEATURES/*.md` and the suggested **1–5 source symbols**. Use `DECISION-TREES.md` only when the first checkpoint is ambiguous. Use `HOTSPOTS.md` and `REGRESSION-MAP.md` before/after changing a shared symbol. `SEARCH-ALIASES.md` translates UI/user language into exact hooks/classes/actions. `PLUGIN-BOUNDARIES.md` says when to stop plugin debugging.

**Default context budget:** 1 router + 1 feature map + 1–5 source symbols. Expand only after a failed checkpoint. **SOURCE CODE > PROJECT MEMORY.**
