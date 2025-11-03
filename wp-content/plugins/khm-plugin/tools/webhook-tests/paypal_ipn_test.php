<?php
// Scenario-driven PayPal IPN tester
// Usage: php paypal_ipn_test.php <BASE_URL> <SCENARIO> [repeat]
// Example: php paypal_ipn_test.php http://local.test happy repeat

$base = $argv[1] ?? 'http://localhost';
$scenario = $argv[2] ?? 'happy';
$repeat = ($argv[3] ?? '') === 'repeat';
$endpoint = rtrim($base, '/') . '/services/ipnhandler.php';

function run_request_form($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$info['http_code'], $response];
}

$scenarios = [
    'happy' => function() {
        return [
            'txn_id' => 'PP_' . uniqid(),
            'payment_status' => 'Completed',
            'mc_gross' => '49.00',
            'mc_currency' => 'USD',
            'payer_email' => 'test@example.com',
            'custom' => '42',
            'item_number' => '3'
        ];
    },
    // ... rest copied as-is
];

if (!isset($scenarios[$scenario])) {
    echo "Unknown scenario: $scenario\nAvailable: " . implode(', ', array_keys($scenarios)) . "\n";
    exit(2);
}

$data = $scenarios[$scenario]();

echo "POST $endpoint\nScenario: $scenario\n";
[$code, $response] = run_request_form($endpoint, $data);
echo "HTTP/$code\nResponse:\n$response\n";

if ($repeat) {
    echo "-- repeating request for idempotency check --\n";
    [$code2, $response2] = run_request_form($endpoint, $data);
    echo "HTTP/$code2\nResponse:\n$response2\n";
}

exit(($code >= 200 && $code < 300) ? 0 : 1);
