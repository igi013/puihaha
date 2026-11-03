#!/bin/sh
set -eu
umask 077
: "${APP_KEY:?APP_KEY is required}"
: "${DB_HOST:?DB_HOST is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"
: "${AIVEN_CA_PEM:?AIVEN_CA_PEM is required}"
mkdir -p "$LARAVEL_STORAGE_PATH/framework/cache/data" "$LARAVEL_STORAGE_PATH/framework/views" "$LARAVEL_STORAGE_PATH/framework/sessions" "$LARAVEL_STORAGE_PATH/logs"
printf '%s\n' "$AIVEN_CA_PEM" > "$MYSQL_ATTR_SSL_CA"
exec "$@"
