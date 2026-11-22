#!/bin/bash
set -e

# Run database migrations on startup
if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    psql "$DATABASE_URL" -f /var/www/html/database/courtiq.postgres.sql
    echo "Migrations completed."

    echo "Creating default users..."
    # Wait for database to be ready
    for i in {1..30}; do
        if psql "$DATABASE_URL" -c "SELECT 1" >/dev/null 2>&1; then
            break
        fi
        echo "Waiting for database... ($i/30)"
        sleep 2
    done
    php /var/www/html/database/seed-users.php
    echo "Default users created."
fi

# Start Apache
exec apache2-foreground