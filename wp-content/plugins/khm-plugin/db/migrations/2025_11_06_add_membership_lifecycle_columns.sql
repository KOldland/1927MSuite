-- 2025_11_06_add_membership_lifecycle_columns.sql
-- Adds lifecycle tracking columns for pause/resume and grace-period handling.

ALTER TABLE `khm_memberships_users`
  ADD COLUMN `status_reason` VARCHAR(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN `grace_enddate` DATETIME DEFAULT NULL AFTER `enddate`,
  ADD COLUMN `paused_at` DATETIME DEFAULT NULL AFTER `grace_enddate`,
  ADD COLUMN `pause_until` DATETIME DEFAULT NULL AFTER `paused_at`;
