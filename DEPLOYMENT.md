# CourtIQ PostgreSQL Deployment

## Requirements

- PHP 8.3 or newer
- PostgreSQL
- Apache with PHP enabled
- Runtime extensions: `pdo_pgsql`, `mysqli`, `mbstring`, `openssl`, `session`, and `json`
- Migration CLI extension: `pdo_mysql`

## Create the PostgreSQL schema

Run `database/courtiq.postgres.sql` against an empty PostgreSQL database. The legacy `database/courtiq.sql` and files under `database/migrations/` are for MySQL/MariaDB and must not be used to initialize PostgreSQL.

The app accepts a PostgreSQL URL in `DATABASE_URL` (recommended) or separate values in these environment variables:

- `COURTIQ_DB_HOST`
- `COURTIQ_DB_USER`
- `COURTIQ_DB_PASSWORD`
- `COURTIQ_DB_NAME`
- `COURTIQ_DB_PORT`
- `COURTIQ_DB_SSLMODE` (optional)

## Migrate existing XAMPP records

The migration script copies data from the local MySQL database into the PostgreSQL schema, preserves IDs, and resets identity sequences. It refuses to run if any destination table already contains rows.

1. Keep XAMPP MySQL running and ensure the Render PostgreSQL database is empty. The migration script creates the schema before copying data.
2. Create a local `.env.migrate` file in the project root (it is ignored by Git) with the source MySQL settings and the PostgreSQL **external** connection URL:

   ```text
   COURTIQ_SOURCE_DB_HOST=host.docker.internal
   COURTIQ_SOURCE_DB_PORT=3306
   COURTIQ_SOURCE_DB_NAME=courtiq
   COURTIQ_SOURCE_DB_USER=root
   COURTIQ_SOURCE_DB_PASSWORD=your-local-mysql-password
   DATABASE_URL=your-render-postgresql-external-url
   ```

3. Build the image locally, then run the CLI migration:

   ```powershell
   docker build -t courtiq-migration .
   docker run --rm --env-file .env.migrate --entrypoint php courtiq-migration /var/www/html/database/migrate-mysql-to-postgres.php
   ```

4. Confirm the script reports successful row counts. Do not share or commit `.env.migrate`.

The migration copies existing data; it does not send records to this assistant. If XAMPP is on a different computer, use a network-accessible MySQL host and restrict access to your own machine while migrating.

## Render Deployment

CourtIQ is a PHP application. In Render, create the web service with **Docker** as its runtime. The root-level `Dockerfile` installs the PHP PostgreSQL driver and enables Apache rewrite rules.

- **Dockerfile Path:** `./Dockerfile`
- **Build Command:** leave blank; Render builds the Dockerfile.
- **Start Command / Docker Command:** leave blank, or use `apache2-foreground`.
- Set `PORT` to `80` to match the Apache listener.
- Set `DATABASE_URL` to the PostgreSQL **internal** connection URL for the Render web service. Use the external URL only for a migration run from your computer.

Do not use `yarn` or `yarn start`; this repository has no Node.js package manifest. Keep HTTPS enabled, use strong database credentials, and change any development passwords before production use.
