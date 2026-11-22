#!/bin/bash
set -e

# Run database migrations on startup
if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    psql "$DATABASE_URL" -f /var/www/html/database/courtiq.postgres.sql
    echo "Migrations completed."

    echo "Creating default users..."
    psql "$DATABASE_URL" <<'SQL'
-- Admin user
INSERT INTO users (username, email, password_hash, role, status, approval_status)
VALUES ('admin', 'admin@courtiq.local', crypt('admin123', gen_salt('bf')), 'administrator', 'active', 'approved')
ON CONFLICT (username) DO NOTHING;

-- Warden user
INSERT INTO users (username, email, password_hash, role, status, approval_status)
VALUES ('warden', 'warden@courtiq.local', crypt('warden123', gen_salt('bf')), 'warden', 'active', 'approved')
ON CONFLICT (username) DO NOTHING;

-- Student user
INSERT INTO users (username, email, password_hash, role, status, approval_status)
VALUES ('student', 'student@courtiq.local', crypt('student123', gen_salt('bf')), 'student', 'active', 'approved')
ON CONFLICT (username) DO NOTHING;
SQL
    echo "Default users created."
fi

# Start Apache
exec apache2-foreground