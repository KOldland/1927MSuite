#!/bin/bash
# Test runner script that handles Brain Monkey tests separately
# Usage: ./bin/run-tests.sh

set -e

echo "=== Running KHM Plugin Tests ==="
echo ""

# Set PHP path
export PATH="/usr/local/opt/php@8.1/bin:$PATH"

cd "$(dirname "$0")/.."

# Run regular tests (exclude Brain Monkey tests)
echo "▶ Running regular tests..."
./vendor/bin/phpunit \
    --exclude-group brain-monkey \
    --testdox \
    --colors=always

echo ""
echo "▶ Running Brain Monkey tests..."
BRAIN_MONKEY_TEST=1 ./vendor/bin/phpunit \
    --group brain-monkey \
    --testdox \
    --colors=always

echo ""
echo "✅ All tests passed!"
