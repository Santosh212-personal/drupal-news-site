<?php

namespace Drupal\news_importer;

use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;

use function file_save_data;

class NewsImporter {

  protected $httpClient;
  protected $entityTypeManager;

  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function fetchAndImport() {
    $apiKey = 'pub_7864529165fe8b4422ab7ef43cfda0fe5cbf0'; // Replace with your actual API key
    $url = "https://newsdata.io/api/1/news?country=in&language=en&apikey=$apiKey";

    $response = $this->httpClient->get($url);
    $data = json_decode($response->getBody(), TRUE);
    $imported = 0;

    foreach ($data['results'] as $item) {
      if (!empty($item['title'])) {

        // Category term
        $category_tid = NULL;
        if (!empty($item['category'][0])) {
          $term_name = $item['category'][0];
          $terms = $this->entityTypeManager
            ->getStorage('taxonomy_term')
            ->loadByProperties([
              'name' => $term_name,
              'vid' => 'news_categories',
            ]);

          if ($terms) {
            $term = reset($terms);
          } else {
            $term = Term::create([
              'vid' => 'news_categories',
              'name' => $term_name,
            ]);
            $term->save();
          }
          $category_tid = $term->id();
        }

        // Image as media
        $image_media_id = NULL;
        if (!empty($item['image_url'])) {
          $image_media_id = $this->createMediaImageFromUrl($item['image_url']);
        }

        // Create node
        $node = Node::create([
          'type' => 'news',
          'title' => $item['title'],
          'body' => [
            'value' => $item['description'] ?? 'No description available.',
            'format' => 'basic_html',
          ],
          'field_news_source' => $item['source_id'] ?? 'Unknown',
          'field_news_category' => $category_tid ? [['target_id' => $category_tid]] : [],
          'field_news_main_image' => $image_media_id ? [['target_id' => $image_media_id]] : [],
          'field_published_on' => !empty($item['pubDate']) ? [
            'value' => date('Y-m-d\TH:i:s', strtotime($item['pubDate'])),
          ] : NULL,
          'status' => 1,
        ]);
        $node->save();
        $imported++;
      }
    }

    return "$imported articles imported.";
  }

  protected function createMediaImageFromUrl($url) {
    try {
      $data = file_get_contents($url);
      if ($data === FALSE) {
        \Drupal::logger('news_importer')->error("Unable to fetch image: $url");
        return NULL;
      }
  
      // Extract filename safely.
      $filename = basename(parse_url($url, PHP_URL_PATH));
  
      // Use the file.repository service to save the file.
      $file = \Drupal::service('file.repository')->writeData(
        $data,
        "public://$filename",
        FileSystemInterface::EXISTS_REPLACE
      );
  
      if (!$file) {
        \Drupal::logger('news_importer')->error("Failed to save file: $filename");
        return NULL;
      }
  
      // Create media entity from file.
      $media = Media::create([
        'bundle' => 'image',
        'name' => $filename,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $filename,
        ],
        'status' => 1,
      ]);
      $media->save();
  
      return $media->id();
    }
    catch (\Exception $e) {
      \Drupal::logger('news_importer')->error($e->getMessage());
      return NULL;
    }
  }

}
