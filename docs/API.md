# Mobile API v1

Base: `{site}/api/v1`. JSON in and out. Same contract as the Qudroid app client (`lib/core/api.dart`):

```json
{ "success": true, "message": "…", "data": …, "meta": { … } }
```

Errors: HTTP 4xx/5xx with `message`, and for validation `errors: { field: ["…"] }`; sign-in failures carry
`code: "invalid_credentials"`. Authenticate with `Authorization: Bearer {token}` (Laravel Sanctum).

| Method | Path | Notes |
|---|---|---|
| GET | `app/config` | public; `name`, `api_version: 1`, `eggs_per_crate`, `bag_weight_kg`, `color` |
| POST | `auth/login` | `login` (email, username or phone), `password`, `device_name` → `token`, `user` |
| POST | `auth/forgot-password` | `email` |
| GET | `auth/me` | user with `roles`, `permissions` (`*` for Super Admin) and `modules` (`poultry`, `livestock:{slug}`, `reports`, `sales`) |
| POST | `auth/logout`, `auth/logout-all` | |
| GET | `dashboard?period=last_30` | `summary` (every metric), `birds_now`, `feed`, `observations`, `flocks`, daily `trend`; `meta.metrics` = catalogue |
| GET | `flocks?status=active\|all` | |
| GET | `flocks/{id}?period=` | flock + `lifetime`, `summary`, `weekly`, `issues` |
| GET | `flocks/{id}/entries?from&to&per_page` | paged, newest first; `meta.page/last_page/total` |
| POST | `flocks/{id}/entries` | save a day (creates, or updates that date): `entry_date`, `daily_mortality`, `daily_feeds`, optional `birds_stolen`, `birds_culled`, `sick_bay`, `available_feeds`, `eggs_produced`, `eggs_sold`, `broken_egg`, `bad_eggs` (pieces), `drugs`, `notes`, `avg_weight_g`, `reported_birds_alive` |
| DELETE | `entries/{id}` | |
| GET | `livestock/types` | active types with `fields` (build forms from them) and `units` |
| GET | `livestock/units/{id}` | unit + `summary`, `monthly`; `meta.metrics` |
| GET | `livestock/units/{id}/records` | paged |
| POST | `livestock/units/{id}/records` | `record_date`, `values: {field_key: value}`, `notes` (creates or updates that date) |
| DELETE | `livestock/records/{id}` | |
| GET | `reports` | saved reports with PDF / Excel links |
| GET | `reports/{id}?period=` | `metrics`, `rows`, `total`, `chart` (`labels`, `series`) |

Periods: `last_7, last_30, last_90, this_month, last_month, this_year, last_12m, all, custom` (+ `from`, `to`).
Permissions are the web ones (e.g. `Create daily-entry`, `Create livestock record`, `View reports`).
