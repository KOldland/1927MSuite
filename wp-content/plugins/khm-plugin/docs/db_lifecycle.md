# Membership Database Lifecycle

## Table Overview

### khm_memberships_users
Primary table for user membership state:
```sql
CREATE TABLE khm_memberships_users (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NOT NULL,
  membership_id bigint(20) unsigned NOT NULL,
  code_id bigint(20) unsigned DEFAULT NULL,
  initial_payment decimal(10,2) NOT NULL,
  billing_amount decimal(10,2) NOT NULL,
  cycle_number int(11) NOT NULL,
  cycle_period enum('Day','Week','Month','Year') NOT NULL,
  billing_limit int(11) NOT NULL,
  trial_amount decimal(10,2) NOT NULL,
  trial_limit int(11) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  startdate datetime NOT NULL,
  enddate datetime DEFAULT NULL,
  modified datetime NOT NULL,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY membership_id (membership_id),
  KEY status (status),
  KEY modified (modified)
);
```

### khm_membership_orders
Records all order/payment transactions:
```sql
CREATE TABLE khm_membership_orders (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(32) NOT NULL,
  session_id varchar(64) NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  membership_id bigint(20) unsigned NOT NULL,
  paypal_token varchar(64) NOT NULL,
  billing_name varchar(128) NOT NULL,
  billing_street varchar(128) NOT NULL,
  billing_city varchar(128) NOT NULL,
  billing_state varchar(32) NOT NULL,
  billing_zip varchar(16) NOT NULL,
  billing_country varchar(128) NOT NULL,
  billing_phone varchar(32) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  tax decimal(10,2) NOT NULL,
  total decimal(10,2) NOT NULL,
  payment_type varchar(64) NOT NULL,
  cardtype varchar(32) NOT NULL,
  accountnumber varchar(32) NOT NULL,
  expirationmonth char(2) NOT NULL,
  expirationyear varchar(4) NOT NULL,
  status varchar(32) NOT NULL,
  gateway varchar(64) NOT NULL,
  gateway_environment varchar(64) NOT NULL,
  payment_transaction_id varchar(64) NOT NULL,
  subscription_transaction_id varchar(64) NOT NULL,
  timestamp datetime NOT NULL,
  affiliate_id varchar(32) NOT NULL,
  affiliate_subid varchar(32) NOT NULL,
  notes text NOT NULL,
  PRIMARY KEY (id),
  KEY code (code),
  KEY session_id (session_id),
  KEY user_id (user_id),
  KEY membership_id (membership_id),
  KEY status (status),
  KEY timestamp (timestamp),
  KEY gateway (gateway),
  KEY gateway_environment (gateway_environment),
  KEY payment_transaction_id (payment_transaction_id),
  KEY subscription_transaction_id (subscription_transaction_id)
);
```

## Lifecycle Events

(See full file in repo for complete details.)
