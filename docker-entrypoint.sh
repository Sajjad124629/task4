#!/bin/bash
set -e

echo "Starting deployment setup..."

# Wait for database connection (optional but recommended for Render)
# It ensures the DB is ready before trying to run migrations
sleep 5

echo "Running database migrations..."
# Run migrations automatically when the container starts in prod mode
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --allow-no-migration

echo "Setup complete. Starting Apache server..."
# Execute the original CMD from Dockerfile (apache2-foreground)
exec "$@"
