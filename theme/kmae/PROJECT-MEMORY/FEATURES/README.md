# FEATURE MAP INDEX

See `../MASTER-MAP.md` for the token-efficient feature routing table. Each file contains READ FIRST / THEN IF NEEDED / DO NOT LOAD UNLESS RELEVANT guidance.

## V2 usage rule

Do not open all feature maps. `DIAGNOSTIC-ROUTER.md` selects **one** first feature map.

Every feature file now has a `V2 DIAGNOSTIC ROUTING` block with:
- common symptoms;
- exact first source target;
- expected flow/checkpoints;
- branch routing;
- search aliases;
- negative loading rule;
- plugin boundary;
- change risk;
- regression checks;
- source-verification state.

If the V2 block conflicts with source, source wins and the map must be corrected.
