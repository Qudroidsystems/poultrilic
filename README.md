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
php artisan db:seed          # fresh installs only
php artisan optimize:clear
php artisan storage:link     # if not done before
```

`php artisan migrate` on an existing farm recalculates every flock's running totals from its daily records
(`2026_11_18_100100_rebuild_flock_ledgers`). Run it again any time with `php artisan poultry:rebuild`.

Tests: `php artisan test` (SQLite in memory).

## Documentation

- `docs/GUIDE.md` – how to use the platform and exactly how every figure is worked out
- `docs/API.md` – the mobile API
