# Bootstrap initialization for NextDoorSA

This folder contains a minimal web entrypoint demonstrating Bootstrap CSS integrated via the CDN.

Files:
- `index.html` — example page that loads Bootstrap CSS & JS from jsDelivr CDN and `styles.css`.
- `styles.css` — small custom overrides and starting place for your styles.

How to use
1. Open `web/index.html` in your browser (double-click or serve from a static server).
2. To use Bootstrap locally or via npm, you can initialize a package.json in this folder and install bootstrap:

   ```bash
   cd web
   npm init -y
   npm install bootstrap@5
   ```

   Then update the HTML to reference the local CSS and JS (e.g. `node_modules/bootstrap/dist/css/bootstrap.min.css`).

Notes
- The index uses CDN links for quick setup. Replace the CDN references with local files if you need offline usage or a build pipeline.
- If your project is a Spring Boot or other server app, copy `web` contents into the appropriate static resource folder (e.g. `src/main/resources/static`).

## Basic MySQL + PHP setup

This repo now includes a minimal PHP MySQL setup:

- `database.php` — reusable MySQL connection helper (`getDatabaseConnection()`).
- `init_database.php` — CLI-only deployment script that imports `schema.sql` into an existing database.
- `schema.sql` — database schema used by the deployment script.

### Requirements

- PHP with `mysqli` enabled
- A running MySQL server

### Environment variables (optional)

- `DB_HOST` (default: `127.0.0.1`)
- `DB_PORT` (default: `3306`)
- `DB_USER` (required for deployment)
- `DB_PASSWORD` (default: empty)
- `DB_NAME` (required for deployment)

### Initialize the database

From the repository root, run the script from the command line after the hosting provider has created the database:

```bash
php web/init_database.php
```

Important:
- The script exits immediately if accessed through a browser.
- It does not create the database for you; create it in your hosting control panel first.
- After deployment, keep `init_database.php` out of the public web flow or remove it entirely.

After deployment, you can include `database.php` in PHP scripts and call `getDatabaseConnection()`.
