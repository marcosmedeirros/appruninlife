# RunInLife - Betting App

This repository now includes a PHP-based betting app that stores data in MySQL instead of browser storage.

## App URL
- https://marcosmedeiros.page/app

## Files
- `app.php`: UI for the betting app.
- `app_api.php`: JSON API used by the app for CRUD operations.
- `config.php`: Database connection and base tables.

## API Actions
Requests go to `app_api.php` with `?action=`:
- `list` (GET)
- `create` (POST)
- `update` (POST)
- `delete` (POST)

## Notes
- The API creates tables `bets` and `bet_selections` automatically.
- Service worker bypasses caching for `/app` and `/app_api.php` to avoid stale data.

