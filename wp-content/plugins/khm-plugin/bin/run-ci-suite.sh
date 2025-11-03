#!/usr/bin/env bash
# Comprehensive test runner for CI pipelines.
# Executes PHPUnit (with JUnit/log output) and Playwright E2E tests,
# writing artifacts under tests/reports/.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPORT_DIR="${ROOT_DIR}/tests/reports"

export PATH="/usr/local/opt/php@8.1/bin:/usr/local/opt/node@20/bin:${PATH}"

mkdir -p "${REPORT_DIR}"

if [ ! -x "${ROOT_DIR}/vendor/bin/phpunit" ]; then
  echo "Missing vendor/bin/phpunit. Run 'composer install' first." >&2
  exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
  echo "npm not found on PATH. Ensure Node.js (>=20) is installed." >&2
  exit 1
fi

if [ ! -d "${ROOT_DIR}/node_modules" ]; then
  echo "Installing npm dependencies..."
  (cd "${ROOT_DIR}" && npm install --no-audit --no-fund)
fi

echo "=== PHPUnit (with JUnit report) ==="
"${ROOT_DIR}/vendor/bin/phpunit" \
  --log-junit "${REPORT_DIR}/phpunit-results.xml" \
  | tee "${REPORT_DIR}/phpunit-latest.log"

echo ""
echo "=== Playwright E2E (with JUnit report) ==="
(cd "${ROOT_DIR}" && npm run test:e2e) \
  | tee "${REPORT_DIR}/playwright-latest.log"
