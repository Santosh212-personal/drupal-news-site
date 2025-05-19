<?php

namespace Drupal\news_translate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\news_translate\Service\LibreTranslateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class NewsTranslateController extends ControllerBase {

  use DependencySerializationTrait;

  protected $translator;
  protected $currentUser;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('news_translate.libre_translate'),
      $container->get('current_user')
    );
  }

  public function __construct(LibreTranslateService $translator,AccountProxyInterface $current_user) {
    $this->translator = $translator;
    $this->currentUser = $current_user;
  }

  /**
   * Translate news articles with batch processing.
   */
  public function translateNewsContent() {
    $nids = $this->getTranslatableNewsNodes();
  
    $batch = [
      'title' => $this->t('Translating News Articles'),
      'init_message' => $this->t('Starting translation'),
      'progress_message' => $this->t('Processed @current out of @total'),
      'operations' => [],
      'finished' => [$this, 'finishTranslationBatch'],
    ];
    
    // Add each node as separate operation
    foreach ($nids as $nid) {
      $batch['operations'][] = [
        [$this, 'processSingleNode'],
        [$nid] // Only pass node ID, not full object
      ];
    }
    
    batch_set($batch);
    return batch_process();
  }

  /**
   * Get all translatable news node IDs.
   */
  protected function getTranslatableNewsNodes() {
    return $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'news')
      ->condition('langcode', 'en')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();
  }

    /**
     * Processes a single node
     */
   public function processSingleNode($nid, &$context) {
    if (!isset($context['results']['count'])) {
      $context['results'] = [
        'count' => 0,
        'errors' => []
      ];
    }
    
    $node = Node::load($nid);
    if (!$node) {
      $context['results']['errors'][] = $this->t('Node @nid not found', ['@nid' => $nid]);
      return;
    }
    
    try {
      $this->translateNode($node);
      $context['results']['count']++;
      $context['message'] = $this->t('Translating: @title', ['@title' => $node->getTitle()]);
    } catch (\Exception $e) {
      $context['results']['errors'][] = $e->getMessage();
    }
  }

  /**
   * Batch completion callback.
   */
  public function finishTranslationBatch($success, $results, $operations) {
    if ($success) {
      $message = $this->t('Translated @count news articles.', ['@count' => $results['count']]);
      if (!empty($results['errors'])) {
        $message .= ' ' . $this->t('Encountered @errors errors.', ['@errors' => count($results['errors'])]);
      }
    } else {
      $message = $this->t('Translation completed with errors.');
    }
    
    $this->messenger()->addStatus($message);
  }

  /**
   * Translate a single node.
   */
  protected function translateNode(Node $node) {
    if ($node->hasTranslation('hi')) {
      throw new \Exception($this->t('Node @id already has Hindi translation', ['@id' => $node->id()]));
    }

    $title = $node->getTitle();
    $body = $node->get('body')->value;

    if (empty($title) || empty($body)) {
      throw new \Exception($this->t('Empty content in node @id', ['@id' => $node->id()]));
    }

    $translated_title = $this->translator->translateText($title) ?: $title;
    $translated_body = $this->translator->translateText($body) ?: $body;

    $node->addTranslation('hi', [
      'title' => $translated_title,
      'body' => [
        'value' => $translated_body,
        'format' => $node->get('body')->format,
      ],
      'uid' => $this->currentUser->id(),
    ])->save();
  }
}