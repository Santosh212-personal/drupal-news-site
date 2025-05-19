<?php

$autoloader = 'C:/xampp/htdocs/mynews/vendor/autoload.php';
if (!file_exists($autoloader)) {
  die("Error: Autoloader not found at $autoloader. Run 'composer require algolia/algoliasearch-client-php'.\n");
}

require $autoloader;
use Algolia\AlgoliaSearch\SearchClient;

// Check required PHP extensions.
if (!extension_loaded('curl')) {
  die("Error: Missing PHP extension 'curl'. Enable it in C:\\xampp\\php\\php.ini.\n");
}
if (!extension_loaded('openssl')) {
  die("Error: Missing PHP extension 'openssl'. Enable it in C:\\xampp\\php\\php.ini.\n");
}

try {
  // Initialize Algolia client with verbose debugging and CA bundle.
  $client = SearchClient::create(
    'EPRXYU1JRC',
    'b33d88dbe8dc97f356bc0054445bc563',
    [
      'http' => [
        'debug' => true,
        'curl' => [
          CURLOPT_CAINFO => 'C:/xampp/php/extras/ssl/cacert.pem'
        ]
      ]
    ]
  );

  // Test connectivity with a simple API call.
  $indices = $client->listIndices();
  echo "Success! Connected to Algolia. Available indices: " . json_encode($indices) . "\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}