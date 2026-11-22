#!/bin/bash
set -e

# Run database migrations on startup
if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    psql "$DATABASE_URL" -f /var/www/html/database/courtiq.postgres.sql
    echo "Migrations completed."
fi

# Start Apache
exec apache2-foreground