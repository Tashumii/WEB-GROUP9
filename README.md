# Sales Tracker (CodeIgniter 4)

Sales Tracker is being moved into CodeIgniter 4. The web pages are rendered by `Pages`, the JSON API is under `app/Controllers/Api`, shared authentication and session behavior lives in `app/Libraries`, and table models are in `app/Models`.

## Local setup

1. Copy `env` to `.env`.
2. Set the MySQL connection values in `.env`. The defaults match a local XAMPP installation (`localhost`, user `root`, blank password, database `SalesDB`).
3. Create the `SalesDB` database in MySQL or phpMyAdmin if it does not exist yet.
4. From the project root, run `php spark migrate` to create any missing Sales Tracker tables. Existing tables are preserved.
5. Point Apache's document root to this project's `public/` directory, or use the local URL that serves `public/index.php`.

The application uses MySQL/MariaDB and PHP 8.2 or newer. The original import script dropped and recreated the tables; avoid running it against a database with data. The CodeIgniter migration uses `CREATE TABLE IF NOT EXISTS` and keeps existing records.

## Routes

Pages use routes such as `/dashboard`, `/products`, `/record-sale`, `/sales`, `/analytics`, and `/settings`. The JSON endpoints use `/api/auth`, `/api/businesses`, `/api/users`, `/api/sessions`, `/api/products`, `/api/services`, `/api/record-sales`, `/api/sales`, and `/api/analytics`.

State-changing API requests require the session's `X-CSRF-Token` header. The shared `public/js/api.js` client sends it for the existing screens.
