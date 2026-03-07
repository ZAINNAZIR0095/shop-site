#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done

echo "MySQL is up - executing command"

# Execute the main command
exec "$@"
