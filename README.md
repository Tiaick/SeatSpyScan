# seat-spy-detection

Manual "Spy Detection" plugin for SeAT (EVE Online). Scans a SeAT user and their linked characters for risk signals without storing results in the database.

## Features
- Manual scan UI (Recruiting -> Spy Detection)
- Checks: negative wallet links, suspicious assets, corp/alliance history risk, alt network anomalies
- Optional queue fallback with short-lived cache
- No migrations, no new tables, no persistence of scan results

## Installation
1. Install the package in your SeAT instance and register the service provider if not auto-discovered.
2. Publish the config:
   - `php artisan vendor:publish --tag=spy-detection-config`
3. Assign the permission `spy-detection.view` to Director/Recruiter roles.

## Configuration
Edit `config/spy-detection.php`:
- `negative_character_ids`, `negative_corporation_ids`, `negative_alliance_ids`
- `suspicious_type_ids`, `suspicious_group_ids`, `suspicious_category_ids`
- `scan.force_queue`, `scan.queue_if_chars_gt`, `scan.cache_ttl_minutes`
- Model and column mappings for your SeAT schema

## Usage
- UI: Open the menu item and enter a character name, then start the scan.
- Optional CLI:
  - `php artisan spy-detection:scan "Character Name"`

## Data Handling
- No database writes for scan results.
- Results exist only in the browser (sessionStorage).
- Optional queued scans store results in cache under `spy_scan:{token}` with short TTL.

## Notes
- No ESI calls are performed; only SeAT DB data is used.
- The permission entry uses existing SeAT ACL tables (no new tables).
