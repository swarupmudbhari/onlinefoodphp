<?php
require_once 'db.php';

if (!isset($_GET['order_id'])) {
    die("Invalid request.");
}

$order_id = intval($_GET['order_id']);
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Order not found.");
}

$data = [
    'return_url' => 'http://localhost/verify.php', // change to your domain in prod
    'website_url' => 'http://localhost',
    'amount' => $order['amount'],
    'purchase_order_id' => $order['id'],
    'purchase_order_name' => $order['order_name']
];

$payload = json_encode($data);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://a.khalti.com/api/v2/epayment/initiate/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        "Authorization: Key test_secret_key_dc74f03fda254824b4ed57dcab07e6fd",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    die("Curl Error: $err");
}

$res = json_decode($response, true);

if (isset($res['pidx'])) {
    $pidx = $res['pidx'];
    $payment_url = $res['payment_url'];

    // Store pidx in DB
    $stmt = $conn->prepare("UPDATE orders SET pidx = ? WHERE id = ?");
    $stmt->bind_param("si", $pidx, $order_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to Khalti payment page
    header("Location: " . $payment_url);
    exit;
} else {
    echo "<pre>Payment initiation failed:\n";
    print_r($res);
    echo "</pre>";
}
"