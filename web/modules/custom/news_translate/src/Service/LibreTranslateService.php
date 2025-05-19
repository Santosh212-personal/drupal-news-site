<?php

namespace Drupal\news_translate\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class LibreTranslateService {
  protected $httpClient;
  protected $cache;

  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
  }

  public function translateText($text, $source = 'en', $target = 'hi') {
    // Return empty string if no text is provided
    if (empty(trim($text))) {
      return '';
    }

    // Generate a unique cache ID for this text
    $cid = 'news_translate:' . md5($source . $target . $text);

    // Check cache first
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    try {
      // Add delay to prevent rate limiting (3.6 seconds between requests)
      usleep(360000);

      $response = $this->httpClient->post('https://libretranslate.com/translate', [
        'timeout' => 15, // 15 second timeout
        'json' => [  // Using json instead of form_params for better performance
          'q' => $text,
          'source' => $source,
          'target' => $target,
          'format' => 'text',
        ],
        'headers' => [
          'Accept' => 'application/json',
          'User-Agent' => 'Drupal NewsTranslate/1.0'
        ]
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (!isset($data['translatedText'])) {
        \Drupal::logger('news_translate')->warning('Translation response missing translatedText', [
          'response' => $data
        ]);
        return $text; // Return original text if translation fails
      }

      // Cache the translation for 1 week
      $this->cache->set($cid, $data['translatedText'], time() + 604800, ['translations']);

      return $data['translatedText'];
      
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      if ($e->getCode() === 429) {
        \Drupal::logger('news_translate')->error('Rate limit exceeded: ' . $e->getMessage());
        \Drupal::messenger()->addError($this->t('Translation service is currently busy. Please try again later.'));
      } else {
        \Drupal::logger('news_translate')->error('Translation API error: ' . $e->getMessage());
      }
      return $text; // Return original text on failure
    } catch (\Exception $e) {
      \Drupal::logger('news_translate')->error('Translation error: ' . $e->getMessage());
      return $text;
    }
  }
}