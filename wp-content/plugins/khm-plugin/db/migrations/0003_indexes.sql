-- 0003_indexes.sql
-- Additional indexes and constraints for KHM tables. Run after initial data load.

ALTER TABLE `khm_memberships_users`
  ADD INDEX (`startdate`),
  ADD INDEX (`enddate`);

ALTER TABLE `khm_membership_orders`
  ADD INDEX (`payment_transaction_id`(64));

-- Example FK constraints (optional; uncomment and adapt if you want referential integrity)
-- ALTER TABLE `khm_memberships_users` ADD CONSTRAINT `fk_khm_user` FOREIGN KEY (`user_id`) REFERENCES `wp_users`(`ID`);
-- ALTER TABLE `khm_membership_orders` ADD CONSTRAINT `fk_khm_orders_user` FOREIGN KEY (`user_id`) REFERENCES `wp_users`(`ID`);
