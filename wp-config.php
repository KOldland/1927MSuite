<?php

/** The name of the database for WordPress */
define('DB_NAME', 'magazine_db');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', '127.0.0.1');

/* Authentication unique keys and salts */
define('AUTH_KEY',         'Q5K0gb4A&v-t;2Pqg^Y(|d$SS9rGzB/3]QdE+k;M^6XvVQc7$]]PH!2Hx,c0c.f>');
define('SECURE_AUTH_KEY',  '-Ho:+nnS%VdJyAaksf3-!I+kQ+bww#4rMW[-|Sv+RHhvUJ85rfx}PvS1..NH#-Q/');
define('LOGGED_IN_KEY',    '8]vITmcsla,2iV:0mTNaA&-LTP=i|x94nIO-|)|cUHsp,|<Z$&[dIc=x(Xz8ur$');
define('NONCE_KEY',        '33/-F${l l?q.hIrqAEy$fEr7!!l|rAoN)Y:tf+;/H.]y]kEGA|-7 kJM^>BEl7(');
define('AUTH_SALT',        'w1+Y$)IwFv;C B/Z0eve|Z!N01>C6{9!rux{$c[6!=&G?oHNshT),1%%HHt--7W');
define('SECURE_AUTH_SALT', 'c2v{I}=Hs&GOB||:hto{#Ydn~y#+Op6et+<,Tb?~9N#f!0Qur1^k%,KijF5gHwIR');
define('LOGGED_IN_SALT',   '6e!- hOl5VudL6BA J}eE5,#,J5+k+O~/H}!viYSJ,>|wge6&.XQ0N<VoxXHz|aM');
define('NONCE_SALT',       ';|8B^!>QQ0+mb|,%w&:x9;I?f062,cSm@VWYG%&fDGc3zSb{=a3oA]U;^<WH|[U');

// ==============================
// 🔍 DEBUGGING MODE
// ==============================

define('WP_DEBUG', true); // Turns on debugging mode
define('WP_DEBUG_LOG', true); // Logs to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false); // Prevents output on screen — change to true if you want on-screen messages
@ini_set('display_errors', 0); // Ensures PHP won't echo errors if WP_DEBUG_DISPLAY is false


/**  WordPress database table prefix. */
$table_prefix = 'wp_';

define('FS_METHOD', 'direct');

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
