# V2 Project Analysis

## Scope

This `v2` layer keeps the existing Laravel models and database, while replacing the controller and view surface for operational users under `/v2`.

## Current V2 Modules

- Authentication with local Laravel users plus BOCSH token refresh
- Dashboard with FleetX analytics fallback
- Own vehicles and market vehicles
- LR tracking creation, refresh, and completion flow
- Weight corrections
- EPOD uploads
- Settings editor
- Reports and CSV export

## Data Flow

1. Users authenticate locally and then refresh BOCSH and FleetX access from the shared integration service.
2. Vehicle location is resolved through WheelsEye for own vehicles and FleetX for market vehicles.
3. LR tracking persists locally first, then syncs outward to BOCSH.
4. Delivered LR records move to `status = 1`.
5. EPOD upload marks the local tracking as `status = 3`, which is now treated as a closed shipment in `v2`.
6. Weight corrections persist locally and then sync to BOCSH.

## Fixes Applied In This Pass

- Added graceful fallback on FleetX-dependent detail and approval endpoints so those pages do not 500 on token or API failures.
- Fixed the completed LR flow to include EPOD-synced records with `status = 3`.
- Allowed the settings page to operate even when the `settings` row does not already exist.
- Added `v2` reports with date filters and CSV exports for trackings, vehicles, EPOD, and weights.
- Added the missing `weights` migration for clean installs.
- Tightened the EPOD flow so it now checks for an LR tracking before upload.
- Improved the weight correction fetch flow so `lspId` is filled from the selected LR.
- Aligned `v2` with the Travis 2.0 tracking API contract:
  LR status values now match the published list, required LR fields are validated before remote sync, weight correction payloads no longer send undocumented fields, and the new `/api/lr/epod-reupload` endpoint is supported.
- Removed the brittle login dependency on the exact text `Token is valid for 1 hour`; `v2` now accepts the documented boolean success plus token response.
- Added environment-based secret overrides and configurable TLS verification:
  `TRAVIS_SYSTEM_EMAIL`, `TRAVIS_SYSTEM_PASSWORD`, `FLEETX_API_USERNAME`, `FLEETX_API_PASSWORD`, `TRAVIS_VERIFY_TLS`.

## Remaining Risks

- FleetX credentials are still hardcoded in `app/Services/V2/ExternalLogisticsService.php`. That should move to environment or settings-backed configuration.
- The integration layer still depends on outbound API availability at request time. Sync retries and queued background jobs would make the flow more reliable.
- Existing legacy controllers outside `v2` still contain older behavior and should be retired or separately cleaned up if the old UI remains in use.

## Recommended Next Step

Move all integration secrets into `.env`, then shift LR sync, EPOD sync, and weight sync into queued jobs so the `v2` UI becomes resilient to remote API latency and downtime.
