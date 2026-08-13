<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

// ── PUT YOUR ANTHROPIC API KEY HERE ──
$api_key = 'YOUR_ANTHROPIC_API_KEY_HERE';

$data        = json_decode(file_get_contents('php://input'), true);
$user_msg    = trim($data['message'] ?? '');
$history     = $data['history']  ?? [];

if (empty($user_msg)) {
    echo json_encode(['error' => 'Empty message']); exit;
}

$system = "You are Amina, a friendly and helpful AI customer care assistant for AB FASHIONSTORE (also known as Blessings Collection), an import and shipping company based in Dar es Salaam, Tanzania.

ABOUT THE COMPANY:
- We import quality products from China and deliver to customers in Tanzania
- Phone: +255 759 431 401
- Email: benardkahayi@gmail.com
- WhatsApp: +255 759 431 401
- Location: Dar es Salaam, Tanzania

OUR SERVICES:
- Product sourcing from Taobao, 1688, Alibaba, AliExpress, and any Chinese store
- Sea shipping: 15–30 days transit time (cheaper)
- Air shipping: 7–14 days transit time (faster)
- Customs clearance and doorstep delivery
- Consolidation: we combine multiple orders into one shipment to save cost
- Safe packaging and inspection at our China warehouse

HOW IT WORKS:
1. Customer sends us product links from any Chinese store
2. We purchase the items on their behalf
3. Items arrive at our China warehouse
4. We ship to Tanzania by sea or air
5. We handle customs clearance
6. We deliver to the customer's doorstep

PAYMENT METHODS: M-Pesa, Airtel Money, Tigo Pesa, Bank Transfer

ORDER TRACKING: Customers log in to their account and use their Order ID to track shipments.

REFUNDS: Available for delivered orders within 30 days. Customers submit requests through their account portal.

INSTRUCTIONS:
- Keep responses concise (2–4 sentences max)
- Be warm, friendly, and professional
- Respond in the same language the customer uses (English or Swahili)
- For specific order status or prices, ask them to log in or contact us directly
- Never make up specific prices — say 'Contact us for a custom quote'
- Always end with a helpful follow-up question or offer";

// Build conversation history for API
$messages = [];
foreach ($history as $h) {
    if (in_array($h['role'] ?? '', ['user','assistant']) && !empty($h['content'])) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }
}
$messages[] = ['role' => 'user', 'content' => $user_msg];

$payload = [
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 400,
    'system'     => $system,
    'messages'   => $messages,
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

if ($curl_err) {
    echo json_encode(['error' => 'Connection error: ' . $curl_err]); exit;
}
if ($http_code !== 200) {
    echo json_encode(['error' => 'API error (HTTP ' . $http_code . ')']); exit;
}

$result = json_decode($response, true);
$reply  = $result['content'][0]['text'] ?? 'Sorry, I could not process that. Please try again or contact us directly.';

echo json_encode(['reply' => $reply]);
