<?php
// Run a set of prioritized webhook scenarios across gateways
// Usage: php run_all.php <BASE_URL>
// Example: php run_all.php http://local.test

$base = $argv[1] ?? 'http://localhost';

$commands = [
    "php stripe_test.php $base happy",
    "php stripe_test.php $base duplicate",
    "php stripe_test.php $base invalid-signature",
    "php paypal_ipn_test.php $base happy",
    "php paypal_ipn_test.php $base duplicate",
    "php paypal_ipn_test.php $base invalid-validation",
    "php braintree_test.php $base happy",
    "php braintree_test.php $base challenge",
    "php braintree_test.php $base invalid-signature",
    "php twocheckout_test.php $base sale",
    "php twocheckout_test.php $base duplicate",
    "php twocheckout_test.php $base invalid-md5",
    "php authnet_test.php $base approved",
    "php authnet_test.php $base duplicate",
    "php authnet_test.php $base invalid-hmac",
];

foreach ($commands as $cmd) {
    echo "Running: $cmd\n";
    $out = [];
    $code = 0;
    exec($cmd . ' 2>&1', $out, $code);
    echo implode("\n", $out) . "\n";
    echo "Exit code: $code\n---\n";
}

echo "All done. Review outputs above and check DB state on your dev site.\n";
