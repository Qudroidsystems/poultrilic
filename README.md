# Poultrilic

Farm records, analytics and reports for poultry and any other farm enterprise (cattle, goats, fish ponds, pigs,
crops…). Laravel 12, MySQL, Blade on the Steex admin theme, Sanctum for the mobile API.

- **Poultry**: flocks, daily records, a flock ledger that works out birds alive / deaths / feed / eggs in farm from
  the daily figures, flock merging, data checks.
- **Livestock builder**: the admin defines any farm unit and its record form; recording, Excel import, totals and
  reports work for every type.
- **Excel import**: the farm's own sheets or the templates, with a preview before anything is saved.
- **Reports**: a farm performance report (web + PDF) and admin-designed reports (Excel, PDF, CSV).
- **Sales & finance**: invoices, payments, expenses, feed stock.
- **Mobile API v1** for the Poultrilic app (`docs/API.md`).

## Set up / upgrade

```bash
composer install
php artisan migrate          # adds the ledger columns, livestock, imports, reports; recalculates every flock
php artisan db:seed          # permissions, farm roles, admin user, farm settings (safe to run again)
php artisan optimize:clear
php artisan storage:link     # if not done before
```

### Seeding

`php artisan db:seed` runs each seeder once and logs it in `seeder_log`; permissions and roles are refreshed every
time, so new permissions reach existing roles after an upgrade. Nothing that already exists is overwritten.

| Command | What it does |
|---|---|
| `php artisan db:seed` | permissions, roles (Super Admin, Admin, Farm Manager, Record Keeper, Sales Officer, Accountant, Viewer), the admin user, default prices/costs |
| `SEED_DEMO=true php artisan db:seed` | also a demo farm: 4 flocks with months of daily records, feed purchases, customers, invoices and payments, expenses, fish ponds and goats, starter reports (only on a farm with no flocks) |
| `php artisan db:seed --class=DemoFarmSeeder` | the demo farm on its own |
| `SEED_FORCE=true php artisan db:seed` | run every seeder again, ignoring the log |

`.env` options: `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` (first admin; an existing password is never reset),
`FARM_NAME`, `FARM_ADDRESS`, `FARM_PHONE`, `SEED_LIVESTOCK=fish,goats,cattle,pigs,crops`.

### Look and feel

The design layer from csskabba-portal: `public/js/portal-theme.js` (Light / Dark / Auto and **Default / Apple**
style, chosen from the sun icon in the top bar and remembered per browser), `public/css/cb-ui.css` (shared design
system; remove `class="cb-ui"` from `<body>` to switch the global skin off), `public/css/portal-theme.css`,
`public/css/poultrilic.css` (brand, Spotlight, hero and KPI tiles), Blade components `<x-cb.hero>`, `<x-cb.stat>`,
`<x-cb.card>`, Apple-style dialogs (`partials/apple-alert`, used for every `data-confirm` form) and **Spotlight**:
press ⌘K / Ctrl+K anywhere to jump to a page, flock, pond, customer, invoice or report.

`php artisan migrate` on an existing farm recalculates every flock's running totals from its daily records
(`2026_11_18_100100_rebuild_flock_ledgers`). Run it again any time with `php artisan poultry:rebuild`.

Tests: `php artisan test` (SQLite in memory).

## Look and feel (from csskabba-portal)

The admin layout is csskabba-portal's: the same master page, sidebar (collapsible groups,
user card and Sign Out at the bottom), top bar (Spotlight search on Ctrl/⌘ K, theme menu,
user menu), light / dark / auto themes and the Apple style. The Roles, Role details, Add
users, Permissions, Users, User overview and Profile settings pages are csskabba's pages with
the school parts (students, staff records, classes, parents) taken out.

- Users: add, edit (name, email, roles, password), delete one or many, filter, sort.
- Roles: role cards; role details with permissions and assigned users; bulk remove.
- Profile settings: anyone can edit their own profile, photo, email and password; editing
  someone else needs the "Update user" permission. Passwords are stored hashed only.

## Documentation

- `docs/GUIDE.md` – how to use the platform and exactly how every figure is worked out
- `docs/API.md` – the mobile API
