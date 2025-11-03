<?php
// Scenario-driven Braintree webhook tester
// Usage: php braintree_test.php <BASE_URL> <SCENARIO> [PUBLIC_KEY] [PRIVATE_KEY] [repeat]
// Example: php braintree_test.php http://local.test happy public_key private_key

$base = $argv[1] ?? 'http://localhost';
$scenario = $argv[2] ?? 'happy';
$public = $argv[3] ?? null;
$private = $argv[4] ?? null;
$repeat = ($argv[5] ?? '') === 'repeat';
$endpoint = rtrim($base, '/') . '/services/braintree-webhook.php';

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

$make_payload = function($kind, $sub_id = null, $txn_id = null, $amount = '49.00') {
    $payload = ['kind' => $kind, 'subscription' => ['id' => ($sub_id ?: 'sub_' . uniqid())], 'transaction' => ['id' => ($txn_id ?: 'txn_' . uniqid()), 'amount' => $amount]];
    return base64_encode(json_encode($payload));
};

$scenarios = [
    'happy' => function() use ($make_payload, $public, $private) {
        $bt_payload = $make_payload('subscription_charged_successfully');
        if ($public && $private) {
            $hash = hash_hmac('sha1', $bt_payload, $private);
            $bt_signature = $public . '|' . $hash;
        } else {
            $bt_signature = 'sample_signature';
        }
        return ['bt_signature' => $bt_signature, 'bt_payload' => $bt_payload];
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
