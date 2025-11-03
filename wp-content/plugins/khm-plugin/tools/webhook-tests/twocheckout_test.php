<?php
// Scenario-driven 2Checkout INS tester
// Usage: php twocheckout_test.php <BASE_URL> <SCENARIO> [repeat]
// Example: php twocheckout_test.php http://local.test sale

$base = $argv[1] ?? 'http://localhost';
$scenario = $argv[2] ?? 'sale';
$repeat = ($argv[3] ?? '') === 'repeat';
$endpoint = rtrim($base, '/') . '/services/twocheckout-ins.php';

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
    'sale' => function() {
        return [
            'sale_id' => '2CO_' . uniqid(),
            'merchant_order_id' => rand(100,999),
            'invoice_id' => 'INV_' . rand(1000,9999),
            'invoice_list_amount' => '49.00',
            'item_id_1' => '1',
            'customer_email' => 'test@example.com'
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
