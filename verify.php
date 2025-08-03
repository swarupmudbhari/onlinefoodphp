if (!isset($_GET['pidx'])) {
    die("Missing pidx");
}

$pidx = $_GET['pidx'];

$data = ['pidx' => $pidx];
$payload = json_encode($data);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://a.khalti.com/api/v2/epayment/lookup/",
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

// Update order in DB if payment completed
if (isset($res['status']) && $res['status'] === 'Completed') {
    $stmt = $conn->prepare("UPDATE orders SET status = 'Paid' WHERE pidx = ?");
    $stmt->bind_param("s", $pidx);
    $stmt->execute();
    $stmt->close();

    echo "<h2>✅ Payment Successful!</h2>";
    echo "<p>Txn ID: " . $res['transaction_id'] . "</p>";
} else {
    echo "<h2>❌ Payment Failed or Not Completed</h2>";
    echo "<pre>";
    print_r($res);
    echo "</pre>";
}"