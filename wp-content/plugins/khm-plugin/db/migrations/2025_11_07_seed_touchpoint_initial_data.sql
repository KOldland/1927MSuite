-- TouchPoint Marketing Suite Initial Data Seeding
-- Migration: 2025_11_07_seed_touchpoint_initial_data.sql
-- Purpose: Insert default configuration data for TouchPoint Marketing Suite

-- Insert default membership levels if they don't exist
INSERT IGNORE INTO `khm_membership_levels` 
(`id`, `name`, `description`, `confirmation`, `initial_payment`, `billing_amount`, `cycle_number`, `cycle_period`, `billing_limit`, `trial_amount`, `trial_limit`, `allow_signups`, `expiration_number`, `expiration_period`, `monthly_credits`) 
VALUES
(1, 'Bronze Member', 'Basic membership with 5 monthly article credits', 'Welcome to Bronze membership! You now have access to 5 article downloads per month.', 9.99, 9.99, 1, 'Month', 0, 0.00, 0, 1, 0, 'Month', 5),
(2, 'Silver Member', 'Premium membership with 15 monthly article credits', 'Welcome to Silver membership! You now have access to 15 article downloads per month.', 19.99, 19.99, 1, 'Month', 0, 0.00, 0, 1, 0, 'Month', 15),
(3, 'Gold Member', 'Pro membership with 30 monthly article credits', 'Welcome to Gold membership! You now have access to 30 article downloads per month.', 39.99, 39.99, 1, 'Month', 0, 0.00, 0, 1, 0, 'Month', 30),
(4, 'Platinum Member', 'Enterprise membership with unlimited article access', 'Welcome to Platinum membership! You now have unlimited access to all articles.', 79.99, 79.99, 1, 'Month', 0, 0.00, 0, 1, 0, 'Month', 999);

-- Insert membership level metadata
INSERT IGNORE INTO `khm_membership_levelmeta` (`level_id`, `meta_key`, `meta_value`) VALUES
(1, 'features', '["5 Monthly Downloads", "Basic Support", "Member Pricing", "Library Access"]'),
(1, 'badge_color', '#CD7F32'),
(1, 'badge_icon', 'bronze-star'),
(1, 'priority', '1'),
(2, 'features', '["15 Monthly Downloads", "Priority Support", "Member Pricing", "Advanced Library", "Sharing Features"]'),
(2, 'badge_color', '#C0C0C0'),
(2, 'badge_icon', 'silver-star'),
(2, 'priority', '2'),
(3, 'features', '["30 Monthly Downloads", "Premium Support", "Exclusive Pricing", "Advanced Library", "Sharing & Gifting", "Early Access"]'),
(3, 'badge_color', '#FFD700'),
(3, 'badge_icon', 'gold-star'),
(3, 'priority', '3'),
(4, 'features', '["Unlimited Downloads", "24/7 Support", "Best Pricing", "Full Library Access", "All Features", "Priority Processing"]'),
(4, 'badge_color', '#E5E4E2'),
(4, 'badge_icon', 'platinum-star'),
(4, 'priority', '4');

-- Insert default article product pricing for existing posts
INSERT IGNORE INTO `khm_article_products` (`post_id`, `regular_price`, `member_price`, `member_discount_percent`, `is_purchasable`, `purchase_gives_pdf`, `purchase_saves_to_library`)
SELECT 
    p.ID,
    5.99,
    4.99,
    15,
    1,
    1,
    1
FROM (SELECT ID FROM (SELECT ID FROM wp_posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 100) as temp_posts) p
WHERE NOT EXISTS (SELECT 1 FROM `khm_article_products` WHERE `post_id` = p.ID);

-- Insert default library categories for system use
INSERT IGNORE INTO `khm_library_categories` (`id`, `user_id`, `name`, `description`, `color`, `sort_order`, `is_default`) VALUES
(1, 0, 'Purchased Articles', 'Articles you have purchased', '#007cba', 1, 1),
(2, 0, 'Credit Downloads', 'Articles downloaded with credits', '#28a745', 2, 1),
(3, 0, 'Gifted Articles', 'Articles received as gifts', '#dc3545', 3, 1),
(4, 0, 'Favorites', 'Your favorite articles', '#ffc107', 4, 1);

-- Insert default discount codes for launch
INSERT IGNORE INTO `khm_discount_codes` (`code`, `description`, `type`, `value`, `max_uses`, `uses_count`, `active`, `start_date`, `end_date`) VALUES
('WELCOME10', 'Welcome discount for new members', 'percentage', 10.00, 100, 0, 1, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH)),
('LOYALTY20', 'Loyalty discount for returning members', 'percentage', 20.00, 50, 0, 1, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH)),
('STUDENT15', 'Student discount on memberships', 'percentage', 15.00, 200, 0, 1, NOW(), DATE_ADD(NOW(), INTERVAL 12 MONTH));

-- Link discount codes to membership levels
INSERT IGNORE INTO `khm_discount_codes_levels` (`code_id`, `level_id`) 
SELECT dc.id, ml.id 
FROM `khm_discount_codes` dc 
CROSS JOIN `khm_membership_levels` ml 
WHERE dc.code IN ('WELCOME10', 'LOYALTY20', 'STUDENT15');

-- Insert default email templates into options table
INSERT IGNORE INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('khm_email_template_welcome', '{"subject":"Welcome to TouchPoint Marketing Suite!","template":"<h2>Welcome {{user_name}}!</h2><p>Thank you for joining TouchPoint Marketing Suite as a {{membership_level}} member.</p><p>Your benefits include:</p><ul><li>{{monthly_credits}} monthly article downloads</li><li>Member-exclusive pricing</li><li>Personal article library</li><li>Social sharing features</li></ul><p>Get started by browsing our latest articles and building your personal library.</p><p>Best regards,<br>The TouchPoint Team</p>"}', 'no'),

('khm_email_template_purchase', '{"subject":"Purchase Confirmation - {{article_title}}","template":"<h2>Purchase Confirmed!</h2><p>Hi {{user_name}},</p><p>Thank you for your purchase of <strong>{{article_title}}</strong> for {{purchase_price}}.</p><p>Your article has been automatically added to your personal library and is ready for download.</p><p><a href=\"{{download_url}}\" style=\"background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Download PDF</a></p><p>You can access this article anytime from your <a href=\"{{library_url}}\">personal library</a>.</p>"}', 'no'),

('khm_email_template_gift', '{"subject":"You\'ve received a gift article: {{article_title}}","template":"<h2>You\'ve Got a Gift!</h2><p>Hi there,</p><p>{{sender_name}} has gifted you the article <strong>{{article_title}}</strong>!</p><blockquote>{{gift_message}}</blockquote><p><a href=\"{{redemption_url}}\" style=\"background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Claim Your Gift</a></p><p>This gift will expire on {{expiry_date}}, so be sure to claim it soon!</p>"}', 'no'),

('khm_email_template_credit_low', '{"subject":"Your credits are running low","template":"<h2>Credits Running Low</h2><p>Hi {{user_name}},</p><p>This is a friendly reminder that you have {{remaining_credits}} download credits remaining this month.</p><p>Your credits will refresh on {{next_renewal_date}}. If you need more articles before then, consider upgrading your membership level for more monthly credits.</p><p><a href=\"{{upgrade_url}}\">View Membership Options</a></p>"}', 'no'),

('khm_email_template_membership_expired', '{"subject":"Your membership has expired","template":"<h2>Membership Expired</h2><p>Hi {{user_name}},</p><p>Your {{membership_level}} membership has expired. To continue enjoying member benefits like discounted pricing and monthly credits, please renew your membership.</p><p><a href=\"{{renewal_url}}\" style=\"background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Renew Membership</a></p><p>Thank you for being part of TouchPoint Marketing Suite!</p>"}', 'no');

-- Insert default system settings
INSERT IGNORE INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('khm_system_version', '1.0.0', 'yes'),
('khm_install_date', NOW(), 'yes'),
('khm_credit_refresh_enabled', '1', 'yes'),
('khm_guest_cart_timeout', '7200', 'yes'),
('khm_max_cart_items', '20', 'yes'),
('khm_pdf_download_limit', '5', 'yes'),
('khm_gift_expiry_days', '30', 'yes'),
('khm_email_queue_enabled', '1', 'yes'),
('khm_email_batch_size', '50', 'yes'),
('khm_affiliate_commission_rate', '10.00', 'yes'),
('khm_default_article_price', '5.99', 'yes'),
('khm_member_discount_percent', '15', 'yes'),
('khm_social_sharing_enabled', '1', 'yes'),
('khm_library_categories_enabled', '1', 'yes'),
('khm_gift_system_enabled', '1', 'yes');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_posts_type_status ON `wp_posts` (`post_type`, `post_status`);
CREATE INDEX IF NOT EXISTS idx_usermeta_key_value ON `wp_usermeta` (`meta_key`, `meta_value`(50));
CREATE INDEX IF NOT EXISTS idx_postmeta_key_value ON `wp_postmeta` (`meta_key`, `meta_value`(50));