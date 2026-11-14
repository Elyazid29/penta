#!/usr/bin/env bash
# Backup database MySQL Penta Tri Saka.
# Pakai: DB_USER=root DB_PASSWORD= DB_NAME=penta_trisaka ./scripts/dump-db.sh
set -euo pipefail

DB_NAME="${DB_NAME:-penta_trisaka}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
OUT_DIR="${OUT_DIR:-backups}"

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mkdir -p "$root_dir/$OUT_DIR"
file="$root_dir/$OUT_DIR/penta-$DB_NAME-$(date +%Y%m%d-%H%M%S).sql"

MYSQL_PWD="$DB_PASSWORD" mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_NAME" > "$file"

echo "Backup selesai: $file"
