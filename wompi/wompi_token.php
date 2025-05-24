<?php
function obtenerTokenWompi() {
    $client_id = '2c27b2f9-845a-48b3-8a12-0ae2aa3761c7';
    $client_secret = 'cde4dceb-39fd-4996-bc84-e040201870e7';

    $postData = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'audience' => 'wompi_api'
    ]);

    $ch = curl_init('https://id.wompi.sv/connect/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response['access_token'] ?? null;
}
