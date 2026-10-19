<?php
// ARCHIVO TEMPORAL: public/test_api.php
$apiKey = '4d1e04868f1ac59d4fb3d218b3b19571eaaecf17d0006163833adf895f21046a';

// Probamos con las posibles rutas de la API
$url = 'https://rates.dolarvzla.com/bcv/current.json';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'x-dolarvzla-key: ' . $apiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

echo "<h2>Diagnóstico de Conexión API DolarVzla</h2>";
echo "<p><strong>Código HTTP recibido:</strong> {$httpCode}</p>";
if ($error) {
    echo "<p style='color:red;'><strong>Error cURL:</strong> {$error}</p>";
}
echo "<p><strong>Respuesta cruda del servidor:</strong></p>";
echo "<pre style='background:#1e1e1e; color:#00ff00; padding:15px; border-radius:8px;'>";
var_dump($response);
echo "</pre>";