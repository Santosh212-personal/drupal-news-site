<?php

namespace Drupal\news_importer;

use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;
use Drupal\image\Entity\ImageStyle;

class NewsImporter {

  protected $httpClient;
  protected $entityTypeManager;

  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function fetchAndImport() {
    $apiKey = 'pub_7864529165fe8b4422ab7ef43cfda0fe5cbf0';
    $url = "https://newsdata.io/api/1/news?country=in&language=en&apikey=$apiKey";

    $response = $this->httpClient->get($url);
    $data = json_decode($response->getBody(), TRUE);
    $imported = 0;

    foreach ($data['results'] as $item) {
      if (empty($item['title'])) {
        continue;
      }

      // Check for duplicate
      $existing = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties([
          'title' => $item['title'],
          'type' => 'news',
        ]);

      if (!empty($existing)) {
        continue;
      }

      // Category
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

      // Set fallback image initially
      $image_media_id = $this->getFallbackMediaId();

      // Try actual image from API
      if (!empty($item['image_url'])) {
        $downloaded_id = $this->createMediaImageFromUrl($item['image_url']);
        if ($downloaded_id) {
          $image_media_id = $downloaded_id;
        }
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

    return "$imported articles imported.";
  }

  protected function createMediaImageFromUrl($url) {
    try {
      \Drupal::logger('news_importer')->notice("Trying to download image: $url");

      $filename = basename(parse_url($url, PHP_URL_PATH));
      $extension = pathinfo($filename, PATHINFO_EXTENSION);

      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      if (!in_array(strtolower($extension), $allowed)) {
        \Drupal::logger('news_importer')->warning("Unsupported image type: $filename");
        return NULL;
      }

      $unique_filename = md5($url . microtime()) . '.' . $extension;
      $file_path = 'public://' . $unique_filename;

      // Check if file already exists
      $existing_files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $file_path]);

      if (!empty($existing_files)) {
        $file = reset($existing_files);
      }
      else {
        // Use Guzzle to download the image
        try {
          $response = $this->httpClient->request('GET', $url, [
            'headers' => [
              'User-Agent' => 'Mozilla/5.0 (compatible; Drupal NewsImporter)',
            ],
            'timeout' => 10,
          ]);

          if ($response->getStatusCode() !== 200) {
            \Drupal::logger('news_importer')->error("Failed to fetch image. Status code: " . $response->getStatusCode());
            return NULL;
          }

          $data = $response->getBody()->getContents();
          if (strlen($data) < 1000) {
            \Drupal::logger('news_importer')->error("Image too small or corrupted: $url");
            return NULL;
          }

          $file = \Drupal::service('file.repository')->writeData(
            $data,
            $file_path,
            FileSystemInterface::EXISTS_REPLACE
          );

          if (!$file) {
            \Drupal::logger('news_importer')->error("Failed to save file: $unique_filename");
            return NULL;
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('news_importer')->error("Guzzle download failed for $url: " . $e->getMessage());
          return NULL;
        }
      }

      // Create Media entity
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
      \Drupal::logger('news_importer')->error("Image import failed: " . $e->getMessage());
      return NULL;
    }
  }

  protected function getFallbackMediaId() {
    return 72; // Replace with your actual fallback media ID
  }

  public function fixMissingImages($fallback_media_id = 72) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
  
    \Drupal::logger('news_importer')->notice("Loaded " . count($nids) . " news nodes for image check.");
  
    if (empty($nids)) {
      \Drupal::logger('news_importer')->notice("No news nodes found.");
      return "No nodes found.";
    }
  
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $updated = 0;
  
    foreach ($nodes as $node) {
      $image_field = $node->get('field_news_main_image');
      $needs_update = FALSE;
  
      if ($image_field->isEmpty()) {
        $needs_update = TRUE;
      }
      else {
        /** @var \Drupal\media\Entity\Media $media */
        $media = $image_field->entity;
        if (!$media || !$media->hasField('field_media_image') || $media->get('field_media_image')->isEmpty()) {
          $needs_update = TRUE;
        }
        else {
          $file = $media->get('field_media_image')->entity;
          if (!$file || !file_exists($file->getFileUri())) {
            \Drupal::logger('news_importer')->warning("Original file missing for Node {$node->id()}, Media ID: {$media->id()}");
            $needs_update = TRUE;
          }
          else {
            // Check if image style derivative exists (e.g., 'news_main_image')
            $style = ImageStyle::load('news_main_image');
            if ($style) {
              $derivative_uri = $style->buildUri($file->getFileUri());
              if (!file_exists($derivative_uri)) {
                \Drupal::logger('news_importer')->warning("Missing image style for Node {$node->id()}, Style URI: {$derivative_uri}");
                $needs_update = TRUE;
              }
            }
          }
        }
      }
  
      if ($needs_update) {
        \Drupal::logger('news_importer')->notice("Fixing image for Node ID {$node->id()}, Title: {$node->getTitle()}");
        $node->set('field_news_main_image', [['target_id' => $fallback_media_id]]);
        $node->save();
        $updated++;
      }
    }
  
    return "$updated nodes updated with fallback image.";
  }
  
}