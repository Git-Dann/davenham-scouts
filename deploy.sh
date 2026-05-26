#!/usr/bin/env bash
# Local FTP deploy wrapper. See scripts/deploy.py for details.
set -euo pipefail
cd "$(dirname "$0")"
exec python3 scripts/deploy.py "$@"
