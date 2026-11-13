# CourtIQ cPanel Deployment

## Requirements

- PHP 8.1 or newer
- MySQL/MariaDB
- Apache with PHP enabled
- PHP extensions: `mysqli`, `mbstring`, `openssl`, `session`, and `json`

## Upload

1. Create a MySQL database and database user in cPanel.
2. Grant the user all privileges on the CourtIQ database.
3. Import `database/courtiq.sql` through phpMyAdmin.
4. Upload the project contents into `public_html` or the desired subdomain directory.
5. Update `config/database.php` with the hosting database host, username, password, database name, and port.
6. Enable HTTPS in the hosting control panel.

The configuration also supports these environment variables when the host provides them:

- `COURTIQ_DB_HOST`
- `COURTIQ_DB_USER`
- `COURTIQ_DB_PASSWORD`
- `COURTIQ_DB_NAME`
- `COURTIQ_DB_PORT`

## First check

Open the public domain in a browser and confirm the landing page loads. Test login with the administrator account, then change the seeded development passwords immediately.

## Security

- Do not upload or expose database backups.
- Do not use the local `root` database account in production.
- Do not share cPanel or database passwords in chat.
- Keep HTTPS enabled.
- Remove or disable demo credentials before production use.
