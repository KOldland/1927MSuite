#!/bin/bash

# Enhanced Email System Test Runner
# Comprehensive testing script for the KHM Enhanced Email System

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_DIR="${PLUGIN_DIR}/tests"
COVERAGE_DIR="${TEST_DIR}/coverage-html"
RESULTS_DIR="${TEST_DIR}/results"

# Create directories if they don't exist
mkdir -p "${RESULTS_DIR}"
mkdir -p "${COVERAGE_DIR}"

# Print header
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Enhanced Email System - Test Suite Runner    ${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Function to print status messages
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if PHPUnit is available
check_phpunit() {
    if ! command -v phpunit &> /dev/null; then
        if [ -f "${PLUGIN_DIR}/vendor/bin/phpunit" ]; then
            PHPUNIT="${PLUGIN_DIR}/vendor/bin/phpunit"
        else
            print_error "PHPUnit not found. Please install via Composer:"
            echo "  composer require --dev phpunit/phpunit"
            exit 1
        fi
    else
        PHPUNIT="phpunit"
    fi
    
    print_status "Using PHPUnit: ${PHPUNIT}"
}

# Function to check PHP version
check_php_version() {
    local php_version=$(php -r "echo PHP_VERSION;")
    local major_version=$(echo $php_version | cut -d. -f1)
    local minor_version=$(echo $php_version | cut -d. -f2)
    
    if [ $major_version -lt 8 ] || ([ $major_version -eq 8 ] && [ $minor_version -lt 0 ]); then
        print_warning "PHP version $php_version detected. PHP 8.0+ recommended for optimal testing."
    else
        print_status "PHP version: $php_version ✓"
    fi
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    check_php_version
    check_phpunit
    
    # Check required PHP extensions
    local required_extensions=("json" "mbstring" "curl" "openssl")
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_status "PHP extension $ext: ✓"
        else
            print_warning "PHP extension $ext: Missing (some tests may fail)"
        fi
    done
    
    # Check memory limit
    local memory_limit=$(php -r "echo ini_get('memory_limit');")
    print_status "PHP memory limit: $memory_limit"
    
    echo ""
}

# Function to run specific test suite
run_test_suite() {
    local suite_name="$1"
    local suite_file="$2"
    local description="$3"
    
    echo -e "${BLUE}Running: $description${NC}"
    echo "Suite: $suite_name"
    echo "File: $suite_file"
    echo ""
    
    if [ ! -f "$suite_file" ]; then
        print_error "Test file not found: $suite_file"
        return 1
    fi
    
    local start_time=$(date +%s)
    
    # Run the test suite
    if $PHPUNIT --configuration="${PLUGIN_DIR}/phpunit.xml" \
                --testsuite="$suite_name" \
                --colors=always \
                --verbose \
                --log-junit="${RESULTS_DIR}/${suite_name}-junit.xml" 2>&1 | tee "${RESULTS_DIR}/${suite_name}-output.log"; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        print_status "$description completed successfully in ${duration}s"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        print_error "$description failed after ${duration}s"
        return 1
    fi
}

# Function to run all tests
run_all_tests() {
    print_status "Running complete test suite..."
    echo ""
    
    local total_tests=0
    local passed_tests=0
    local failed_tests=0
    local start_time=$(date +%s)
    
    # Core functionality tests
    if run_test_suite "Enhanced Email Service" \
                     "${TEST_DIR}/EnhancedEmailServiceTest.php" \
                     "Core Email Service Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    echo ""
    
    # Integration tests
    if run_test_suite "Email Delivery Integration" \
                     "${TEST_DIR}/EnhancedEmailDeliveryTest.php" \
                     "Email Delivery Integration Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    echo ""
    
    # Queue system tests
    if run_test_suite "Email Queue System" \
                     "${TEST_DIR}/EnhancedEmailQueueTest.php" \
                     "Email Queue System Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    echo ""
    
    # Admin interface tests
    if run_test_suite "Admin Interface" \
                     "${TEST_DIR}/EnhancedEmailAdminTest.php" \
                     "Admin Interface Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    echo ""
    
    # Performance tests
    if run_test_suite "Performance Tests" \
                     "${TEST_DIR}/EnhancedEmailPerformanceTest.php" \
                     "Performance and Load Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    echo ""
    
    # Security tests
    if run_test_suite "Security Tests" \
                     "${TEST_DIR}/EnhancedEmailSecurityTest.php" \
                     "Security and Validation Tests"; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    ((total_tests++))
    
    local end_time=$(date +%s)
    local total_duration=$((end_time - start_time))
    
    # Print summary
    echo ""
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}                  TEST SUMMARY                  ${NC}"
    echo -e "${BLUE}================================================${NC}"
    echo "Total test suites: $total_tests"
    echo -e "Passed: ${GREEN}$passed_tests${NC}"
    echo -e "Failed: ${RED}$failed_tests${NC}"
    echo "Total duration: ${total_duration}s"
    echo ""
    
    if [ $failed_tests -eq 0 ]; then
        print_status "All test suites passed! ✓"
        return 0
    else
        print_error "Some test suites failed! ✗"
        return 1
    fi
}

# Function to run tests with coverage
run_with_coverage() {
    print_status "Running tests with code coverage analysis..."
    echo ""
    
    if ! php -m | grep -q "xdebug\|pcov"; then
        print_warning "Neither Xdebug nor PCOV extension found. Coverage analysis may not work."
        echo "To enable coverage:"
        echo "  - Install Xdebug: https://xdebug.org/docs/install"
        echo "  - Or install PCOV: pecl install pcov"
        echo ""
    fi
    
    $PHPUNIT --configuration="${PLUGIN_DIR}/phpunit.xml" \
             --coverage-html="${COVERAGE_DIR}" \
             --coverage-clover="${RESULTS_DIR}/coverage.xml" \
             --coverage-text="${RESULTS_DIR}/coverage.txt" \
             --log-junit="${RESULTS_DIR}/junit-results.xml" \
             --testdox-html="${RESULTS_DIR}/testdox.html" \
             --colors=always \
             --verbose
    
    if [ $? -eq 0 ]; then
        print_status "Tests with coverage completed successfully!"
        print_status "Coverage report: ${COVERAGE_DIR}/index.html"
        print_status "Test documentation: ${RESULTS_DIR}/testdox.html"
    else
        print_error "Tests with coverage failed!"
        return 1
    fi
}

# Function to run specific test
run_specific_test() {
    local test_filter="$1"
    
    print_status "Running specific test: $test_filter"
    echo ""
    
    $PHPUNIT --configuration="${PLUGIN_DIR}/phpunit.xml" \
             --filter="$test_filter" \
             --colors=always \
             --verbose
}

# Function to run performance tests only
run_performance_tests() {
    export TEST_PERFORMANCE_HEAVY=true
    
    print_status "Running performance tests with heavy load..."
    echo ""
    
    run_test_suite "Performance Tests" \
                   "${TEST_DIR}/EnhancedEmailPerformanceTest.php" \
                   "Performance and Load Tests (Heavy)"
}

# Function to run security tests only
run_security_tests() {
    print_status "Running security and validation tests..."
    echo ""
    
    run_test_suite "Security Tests" \
                   "${TEST_DIR}/EnhancedEmailSecurityTest.php" \
                   "Security and Validation Tests"
}

# Function to clean up test artifacts
cleanup() {
    print_status "Cleaning up test artifacts..."
    
    # Remove temporary files
    find /tmp -name "khm-*" -type f -mtime +1 -delete 2>/dev/null || true
    find /tmp -name "khm-*" -type d -empty -delete 2>/dev/null || true
    
    print_status "Cleanup completed"
}

# Function to show help
show_help() {
    echo "Enhanced Email System Test Runner"
    echo ""
    echo "Usage: $0 [OPTIONS] [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  all                 Run all test suites (default)"
    echo "  coverage           Run tests with code coverage analysis"
    echo "  performance        Run performance tests only"
    echo "  security           Run security tests only"
    echo "  specific <filter>  Run specific test matching filter"
    echo "  cleanup            Clean up test artifacts"
    echo "  help               Show this help message"
    echo ""
    echo "Options:"
    echo "  --no-coverage      Skip coverage analysis"
    echo "  --verbose          Enable verbose output"
    echo "  --quiet            Minimize output"
    echo ""
    echo "Environment Variables:"
    echo "  TEST_EMAIL_RECIPIENT    Test email recipient address"
    echo "  GMAIL_SMTP_USERNAME     Gmail SMTP username for integration tests"
    echo "  GMAIL_SMTP_PASSWORD     Gmail SMTP password for integration tests"
    echo "  SENDGRID_API_KEY       SendGrid API key for integration tests"
    echo "  MAILGUN_API_KEY        Mailgun API key for integration tests"
    echo "  SKIP_LIVE_EMAIL_TESTS  Skip tests that send actual emails"
    echo ""
    echo "Examples:"
    echo "  $0                     # Run all tests"
    echo "  $0 coverage           # Run with coverage"
    echo "  $0 performance        # Run performance tests only"
    echo "  $0 specific testName  # Run specific test"
    echo ""
}

# Main execution
main() {
    cd "$PLUGIN_DIR"
    
    # Parse command line arguments
    local command="${1:-all}"
    
    case "$command" in
        "all"|"")
            check_requirements
            run_all_tests
            ;;
        "coverage")
            check_requirements
            run_with_coverage
            ;;
        "performance")
            check_requirements
            run_performance_tests
            ;;
        "security")
            check_requirements
            run_security_tests
            ;;
        "specific")
            if [ -z "$2" ]; then
                print_error "Please specify a test filter"
                echo "Example: $0 specific testMethodName"
                exit 1
            fi
            check_requirements
            run_specific_test "$2"
            ;;
        "cleanup")
            cleanup
            ;;
        "help"|"-h"|"--help")
            show_help
            ;;
        *)
            print_error "Unknown command: $command"
            show_help
            exit 1
            ;;
    esac
}

# Trap cleanup on exit
trap cleanup EXIT

# Run main function
main "$@"