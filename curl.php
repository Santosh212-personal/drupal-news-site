<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://EPRXYU1JRC.algolia.net/1/indexes');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'X-Algolia-API-Key: b33d88dbe8dc97f356bc0054445bc563',
  'X-Algolia-Application-Id: EPRXYU1JRC'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_CAINFO, 'C:/xampp/php/extras/ssl/cacert.pem');

$response = curl_exec($ch);
if ($response === false) {
  echo "cURL Error: " . curl_error($ch) . "\n";
} else {
  echo "Response: " . $response . "\n";
}
curl_close($ch);