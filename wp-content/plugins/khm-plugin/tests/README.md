# KHM Migration Tests

## Running Tests

### Prerequisites
```bash
composer install
```

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Migration Tests Only
```bash
./vendor/bin/phpunit tests/MigrationTest.php
```

### Run with Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Test Coverage

### MigrationTest.php
Tests the migration service with the following scenarios:

1. **testDryRunDoesNotApplyMigrations**
   - Verifies dry run mode simulates migrations without applying changes
   - Checks that no database tables are created
   - Status: `would-run`

2. **testApplyRunsMigrations**
   - Verifies migrations are actually applied when not in dry run mode
   - Checks that database tables are created
   - Status: `success`

3. **testMigrationsAreTracked**
   - Verifies migrations are tracked in `khm_migrations` table
   - Ensures migrations are not re-run
   - Status: `up-to-date` on second run

4. **testMigrationErrorRollsBack**
   - Verifies invalid SQL rolls back transaction
   - Ensures no partial migrations are applied
   - Status: `error` with error message

5. **testMigrationWithMultipleStatements**
   - Documents limitation: PDO->prepare doesn't support multiple statements
   - Developers should use `exec()` or split statements for complex migrations

6. **testNoMigrationsReturnsNoMigrationsStatus**
   - Verifies empty migration directory returns appropriate status
   - Status: `no-migrations`

7. **testSpecificMigrationsCanBeRun**
   - Verifies selective migration execution
   - Ensures only specified migrations run

## Manual Testing

### Test Dry Run
```bash
cd /path/to/wordpress
php khm-plugin/bin/migrate.php
```

### Test Apply
```bash
cd /path/to/wordpress
php khm-plugin/bin/migrate.php --apply
```

### Test Specific Migrations
```bash
cd /path/to/wordpress
php khm-plugin/bin/migrate.php --migrations=0001_create_khm_tables.sql --apply
```

## Notes

- Tests use SQLite in-memory database for isolation
- Backup functionality tested implicitly (creates files before apply)
- WordPress wp-config.php detection tested separately (requires WP environment)
