#!/bin/bash
set -e

echo "Starting deployment setup..."

# Wait for database connection (optional but recommended for Render)
# It ensures the DB is ready before trying to run migrations
sleep 5

echo "Running database migrations..."
# Run schema update to automatically create tables since migrations are missing
php bin/console doctrine:schema:update --force --env=prod

echo "Setup complete. Starting Apache server..."
# Execute the original CMD from Dockerfile (apache2-foreground)
exec "$@"
