<?php

namespace Drupal\news_translate\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;
use Drupal\news_translate\Service\LibreTranslateService;
use Drupal\language\Entity\ConfigurableLanguage;

class NewsTranslateCommands extends DrushCommands {

  protected $translator;

  public function __construct(LibreTranslateService $translator) {
    $this->translator = $translator;
  }

  /**
   * Say hello.
   *
   * @command news:say-hello
   */
  public function sayHello() {
    $this->output()->writeln('✅ Hello from news_translate!');
  }

  /**
   * Translate English news articles to Hindi if not already translated.
   *
   * @command news:translate-hindi
   * @aliases nth
   */
  public function translateHindi() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('langcode', 'en')
      ->execute();

    $this->output()->writeln('Total English articles: ' . count($nids));

    foreach ($nids as $nid) {
      $node = Node::load($nid);

      // Skip if already translated.
      if ($node->hasTranslation('hi')) {
        $this->output()->writeln("Skipping node {$nid}, already translated.");
        continue;
      }

      // Translate title and body.
      $title = $node->getTitle();
      $body = $node->get('body')->value;

      $translated_title = $this->translator->translateText($title);
      $translated_body = $this->translator->translateText($body);

      // Create Hindi translation.
      $translated_node = $node->addTranslation('hi');
      $translated_node->setTitle($translated_title);
      $translated_node->set('body', [
        'value' => $translated_body,
        'format' => $node->get('body')->format,
      ]);
      $translated_node->save();

      $this->output()->writeln("Translated node {$nid} into Hindi.");
    }

    $this->output()->writeln('✅ Translation complete.');
  }
}