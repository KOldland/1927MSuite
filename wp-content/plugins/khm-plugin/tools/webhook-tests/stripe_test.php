<?php
// Scenario-driven Stripe webhook tester
// Usage: php stripe_test.php <BASE_URL> <SCENARIO> [WEBHOOK_SECRET] [repeat]
// Example: php stripe_test.php http://local.test happy whsec_testsecret

$base = $argv[1] ?? 'http://localhost';
$scenario = $argv[2] ?? 'happy';
$secret = $argv[3] ?? null; // optional Stripe webhook secret
$repeat = ($argv[4] ?? '') === 'repeat';
$endpoint = rtrim($base, '/') . '/services/stripe-webhook.php';

function run_request($url, $headers, $body) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$info['http_code'], $response];
}

$scenarios = [
    'happy' => function() use ($secret) {
        $payload = json_encode([
            'id' => 'evt_' . uniqid(),
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['id' => 'in_' . uniqid(), 'amount_paid' => 4900, 'customer' => 'cus_test_1', 'lines' => ['data' => [['plan' => ['id' => 'pmpro_level_3']]]]]]
        ]);
        $headers = ['Content-Type: application/json'];
        if ($secret) {
            $t = time();
            $signed_payload = $t . '.' . $payload;
            $sig = hash_hmac('sha256', $signed_payload, $secret);
            $headers[] = 'Stripe-Signature: t=' . $t . ',v1=' . $sig;
        }
        return [$headers, $payload];
    },
    // ... rest of scenarios copied as-is
];

if (!isset($scenarios[$scenario])) {
    echo "Unknown scenario: $scenario\nAvailable: " . implode(', ', array_keys($scenarios)) . "\n";
    exit(2);
}

[$headers, $body] = $scenarios[$scenario]();

echo "POST $endpoint\nScenario: $scenario\n";
[$code, $response] = run_request($endpoint, $headers, $body);
echo "HTTP/$code\nResponse:\n$response\n";

if ($repeat) {
    echo "-- repeating request for idempotency check --\n";
    [$code2, $response2] = run_request($endpoint, $headers, $body);
    echo "HTTP/$code2\nResponse:\n$response2\n";
}

exit(($code >= 200 && $code < 300) ? 0 : 1);
