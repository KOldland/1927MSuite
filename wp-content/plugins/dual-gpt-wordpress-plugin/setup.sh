#!/bin/bash

# Dual-GPT WordPress Plugin - Environment Setup Script
# This script helps prepare a WordPress environment for testing

set -e

echo "Dual-GPT WordPress Plugin - Environment Setup"
echo "=============================================="

# Check if we're in the right directory
if [ ! -f "dual-gpt-wordpress-plugin.php" ]; then
    echo "❌ Error: Please run this script from the plugin directory"
    exit 1
fi

echo "✓ Running from plugin directory"

# Check for required tools
command -v php >/dev/null 2>&1 || { echo "❌ Error: PHP is required but not installed"; exit 1; }
echo "✓ PHP found: $(php --version | head -n 1)"

# Run PHP syntax checks
echo ""
echo "Running PHP syntax checks..."
syntax_errors=$(find . -name "*.php" -type f -exec php -l {} \; 2>&1 | grep -v "No syntax errors detected" || true)
if [ -z "$syntax_errors" ]; then
    echo "✓ All PHP files have valid syntax"
else
    echo "❌ PHP syntax errors found:"
    echo "$syntax_errors"
    exit 1
fi

# Run basic plugin tests
echo ""
echo "Running basic plugin tests..."
if php test-plugin.php > /dev/null 2>&1; then
    echo "✓ Basic plugin tests passed"
else
    echo "❌ Basic plugin tests failed"
    exit 1
fi

# Check file permissions
echo ""
echo "Checking file permissions..."
if find . -type f -not -perm 644 | grep -q .; then
    echo "⚠️  Some files have incorrect permissions (should be 644)"
    echo "   Run: find . -type f -exec chmod 644 {} \;"
else
    echo "✓ File permissions are correct"
fi

if find . -type d -not -perm 755 | grep -q .; then
    echo "⚠️  Some directories have incorrect permissions (should be 755)"
    echo "   Run: find . -type d -exec chmod 755 {} \;"
else
    echo "✓ Directory permissions are correct"
fi

# Check for large files that shouldn't be in repo
echo ""
echo "Checking for large files..."
large_files=$(find . -type f -size +1M -not -path "./.git/*" | wc -l)
if [ "$large_files" -gt 0 ]; then
    echo "⚠️  Found $large_files files larger than 1MB"
    find . -type f -size +1M -not -path "./.git/*" -exec ls -lh {} \;
else
    echo "✓ No large files found"
fi

# Generate plugin checksums for integrity checking
echo ""
echo "Generating plugin checksums..."
find . -type f -not -path "./.git/*" -not -name "checksums.txt" -exec sha256sum {} \; > checksums.txt
echo "✓ Checksums saved to checksums.txt"

# Create deployment package
echo ""
echo "Creating deployment package..."
plugin_name="dual-gpt-wordpress-plugin"
timestamp=$(date +%Y%m%d_%H%M%S)
package_name="${plugin_name}_${timestamp}.zip"

# Exclude development files
zip -r "$package_name" . \
    -x "*.git*" \
    -x "*test*" \
    -x "*.md" \
    -x "setup.sh" \
    -x "checksums.txt" \
    > /dev/null 2>&1

echo "✓ Deployment package created: $package_name"

# Summary
echo ""
echo "Environment Setup Complete!"
echo "=========================="
echo "✓ PHP syntax validation passed"
echo "✓ Basic functionality tests passed"
echo "✓ File permissions checked"
echo "✓ Large files audit completed"
echo "✓ Integrity checksums generated"
echo "✓ Deployment package created"
echo ""
echo "Next Steps:"
echo "1. Copy $package_name to your WordPress wp-content/plugins/ directory"
echo "2. Activate the plugin in WordPress admin"
echo "3. Run wordpress-test-suite.php via browser or WP-CLI"
echo "4. Follow INSTALLATION_GUIDE.md for detailed testing"
echo ""
echo "For WordPress testing environment:"
echo "- Ensure WordPress 5.8+ is installed"
echo "- PHP 7.4+ with curl, json, openssl extensions"
echo "- Configure API key in wp-config.php"
echo "- Enable WP_DEBUG for detailed logging"