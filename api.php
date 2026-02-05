<?php
date_default_timezone_set('Asia/Jakarta');

/* ================== KONFIG ================== */
$config = [
    'source_api' => 'http://112.78.170.196:212/myhostrest/api/bedinfo', //kalau ipnya dari rsud ganti, tinggal sesuaikan aja
    'source_headers' => [
        'id: i3c8s9',
        'key: 3fe837'
    ],
    'api_token' => 'WAmq9x9XsiPzyjfphP1dkYopGmKt2Tkv7B2rWRB6Oue32Ax6sJP5VmfcTVrqWUQl' //sesuaikan tokennya
];https://fazz.bnt.my.id/rsud-pwd/api.php

header("Content-Type: application/json; charset=UTF-8");

/* ================== AUTENTIKASI ================== */
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Authorization required']);
    exit;
}

if ($matches[1] !== $config['api_token']) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'Invalid token']);
    exit;
}

/* ================== FETCH DATA SOURCE ================== */
$ch = curl_init($config['source_api']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => $config['source_headers']
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(502);
    echo json_encode([
        'status' => false,
        'message' => 'Source API error',
        'error' => curl_error($ch)
    ]);
    exit;
}

curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['response'])) {
    http_response_code(502);
    echo json_encode([
        'status' => false,
        'message' => 'Invalid response from source API'
    ]);
    exit;
}

/* ================== FORMAT DATA ================== */
$result = [];
foreach ($data['response'] as $row) {
    $total  = (int) $row['JumlahBedTotal'];
    $kosong = (int) $row['JumlahBedKosong'];

    $result[] = [
        'ruangan' => $row['NamaBangsal'],
        'kamar'   => $row['NamaKamar'],
        'kelas'   => $row['NamaKelas'],
        'total'   => $total,
        'kosong'  => $kosong,
        'terisi'  => $total - $kosong
    ];
}

/* ================== RESPON API ================== */
echo json_encode([
    'status'      => true,
    'updated_at'  => date('Y-m-d H:i:s'),
    'total_data'  => count($result),
    'data'        => $result
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
