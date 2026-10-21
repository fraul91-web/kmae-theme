# PLUGIN BOUNDARIES — V2

MAP-TYPE: ON-DEMAND

This map marks where plugin debugging must stop or cross into WordPress/theme/external runtime.

## Email transport
- **PLUGIN PRODUCER:** `KMAE_Brand::send_mail()` / direct contact/auth mail paths.
- **ACTION:** calls WordPress `wp_mail`.
- **BOUNDARY CHECK:** correct recipient, subject, body and headers passed to `wp_mail`?
- **WHEN TO STOP PLUGIN DEBUGGING:** yes → delivery/MTA/provider is outside plugin source.
- **STATUS:** VERIFIED plugin side; external delivery UNVERIFIED.

## Theme guest shell
- **PLUGIN CONSUMER:** conditional calls to `kmae_is_guest_simple_marketing_shell()`.
- **THEME RESPONSIBILITY:** implementation of that function and its visual/layout behavior.
- **WHEN TO STOP PLUGIN DEBUGGING:** plugin condition/call is correct and symptom belongs to theme implementation.
- **TARGET:** UNVERIFIED THEME TARGET.

## WordPress authentication/session
- Plugin wraps WordPress user/auth APIs. If plugin passes correct credentials/state and WordPress API returns an external/runtime failure, inspect WP/runtime rather than unrelated plugin features.

## WordPress cron
- Plugin registers/schedules `kmae_purge_archived_listings`; actual cron execution timing depends on WordPress runtime. If hook is correctly scheduled but does not execute, runtime is the next boundary.

## Google OAuth
- **PLUGIN:** builds authorization flow, callback handling, token/profile requests and user linking.
- **EXTERNAL:** Google endpoint availability/credentials/consent configuration.
- Correct outbound request + configured IDs but remote failure → external/config boundary.

## Remote media/config assets
Google/YouTube/Albira URLs referenced by settings/config are external availability boundaries.

## Theme rendering/styling
Plugin owns shortcode/template markup and data. Once plugin emits correct markup/data/classes, layout/color/cascade faults route to theme/CSS ownership rather than business services.

## WordPress REST
No plugin-defined `register_rest_route` exists in this snapshot. `show_in_rest` on CPT/taxonomy is WordPress REST exposure, not a custom plugin route. VERIFIED.
