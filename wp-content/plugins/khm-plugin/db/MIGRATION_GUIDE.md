# Migration Smoke Test Guide

This guide covers running migration smoke tests locally and via CI.

## Local Dry-Run Test

Before applying migrations to a production or staging database, always run a dry-run:

```bash
# From khm-plugin directory
cd /path/to/Membership-Manager/khm-plugin

# Ensure composer dependencies are installed
composer install

# Run dry-run (previews changes without modifying DB)
php bin/migrate.php
```

Expected output:
```
Dry run mode (no changes will be made)

0001_create_khm_tables.sql: would-run
Would execute:
CREATE TABLE IF NOT EXISTS `khm_memberships_users` (
  ...
);
...

2025_10_27_create_membership_levels.sql: would-run
Would execute:
CREATE TABLE IF NOT EXISTS `khm_membership_levels` (
  ...
);

0002_migrate_pmpro_to_khm.sql: would-run
Would execute:
INSERT INTO khm_memberships_users (
  ...
);
...
```

## Apply Migrations (Staging Copy)

Once dry-run looks good, apply to a staging database copy:

```bash
# IMPORTANT: Point to a staging/test WordPress install first!
# The migrate.php script auto-detects wp-config.php

# Apply migrations (creates automatic backup)
php bin/migrate.php --apply
```

Output:
```
Running migrations...

0001_create_khm_tables.sql: success
0002_migrate_pmpro_to_khm.sql: success
0003_indexes.sql: success
2025_10_27_create_membership_levels.sql: success
```

Backups are saved to: `khm-plugin/db/backups/backup-YYYY-MM-DD-HHMMSS.sql`

## Validate Results

After applying, run the validation script:

```bash
php bin/validate_migration.php
```

Expected output:
```
KHM Migration Validation
========================

1. Row Count Comparison:
  membership_levels: pmpro_membership_levels=12, khm_membership_levels=12 ✓
  membership_levelmeta: pmpro_membership_levelmeta=48, khm_membership_levelmeta=48 ✓
  memberships_users: pmpro_memberships_users=1234, khm_memberships_users=1234 ✓
  membership_orders: pmpro_membership_orders=5678, khm_membership_orders=5678 ✓

2. Sample Membership Levels (first 5 rows):
  ✓ Level 1 (Gold)
  ✓ Level 2 (Silver)

3. Sample Memberships Validation (first 5 rows):
  ✓ User 1, Level 2
  ✓ User 3, Level 1
  ...

4. Sample Orders Validation (first 5 rows):
  ✓ Order ABC123, Total 49.00, Status success
  ...

5. Index Check (khm_membership_orders):
  PRIMARY: ✓
  code: ✓
  payment_transaction_id: ✓
  ...

✓ Validation PASSED: All checks successful.
```

## GitHub Actions CI (Automatic)

The repository includes a smoke test workflow that runs automatically on:
- Pull requests that modify `db/migrations/**`, `src/Services/Migration.php`, `src/Services/DB.php`, or `bin/migrate.php`
- Manual trigger via workflow_dispatch

### What the CI does:

1. Spins up MySQL 8.0 in a container
2. Creates mock wp-config.php with test credentials
3. Seeds legacy pmpro_* tables with sample data
4. Runs `migrate.php` dry-run and captures output
5. Applies migrations with `--apply`
6. Runs `validate_migration.php` to check results
7. Uploads migration output as an artifact

### View CI results:

1. Go to the **Actions** tab in GitHub
2. Click on the **Migration Smoke Test** workflow
3. Review the logs for each step
4. Download the `migration-output` artifact to see full dry-run output

## Troubleshooting

### wp-config.php not found

```
Error: Could not find WordPress wp-config.php
```

**Solution**: Run `migrate.php` from your WordPress root directory, or place a wp-config.php in the khm-plugin parent directories.

### Table already exists

```
Error: Table 'khm_memberships_users' already exists
```

**Solution**: The migration includes `IF NOT EXISTS` checks. If tables exist, only the data copy will run (with NOT EXISTS checks to prevent duplicates).

### Type mismatch (varchar to decimal)

PMPro stores `subtotal`, `tax`, `total` as varchar. The migration uses `CAST(NULLIF(field, '') AS DECIMAL(10,2))` to safely convert empty strings to NULL and numeric strings to decimals.

### Validation fails

If validation shows mismatches:

1. Check the backup file in `db/backups/`
2. Review migration logs
3. Manually query both tables to identify differences:
   ```sql
   SELECT * FROM pmpro_memberships_users WHERE user_id = <failing_user_id>;
   SELECT * FROM khm_memberships_users WHERE user_id = <failing_user_id>;
   ```

## Rollback

If issues occur:

1. Restore from the automatic backup:
   ```bash
   mysql -u<user> -p<password> <database> < db/backups/backup-YYYY-MM-DD-HHMMSS.sql
   ```

2. Or drop the new tables:
   ```sql
  DROP TABLE IF EXISTS khm_membership_levelmeta;
  DROP TABLE IF EXISTS khm_membership_levels;
  DROP TABLE IF EXISTS khm_memberships_users;
   DROP TABLE IF EXISTS khm_membership_orders;
   DROP TABLE IF EXISTS khm_webhook_events;
   DROP TABLE IF EXISTS khm_migrations;
   ```

## Production Checklist

Before running on production:

- [ ] Dry-run tested on staging copy
- [ ] Validation passed on staging
- [ ] Backup file reviewed and confirmed complete
- [ ] Site in maintenance mode
- [ ] Database backup via hosting control panel or manual mysqldump
- [ ] Team notified of migration window
- [ ] Rollback plan confirmed
- [ ] Apply migrations: `php bin/migrate.php --apply`
- [ ] Run validation: `php bin/validate_migration.php`
- [ ] Spot-check a few live orders/memberships in admin
- [ ] Monitor logs for errors
- [ ] Exit maintenance mode

## Support

For issues or questions, consult:
- [Data Model Mapping](../docs/data_model_mapping.md)
- [Architecture](../docs/architecture.md)
- GitHub Issues
