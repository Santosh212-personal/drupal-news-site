<?php

namespace Drupal\news_importer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\news_importer\NewsImporter;

class NewsImporterController extends ControllerBase {

  protected $newsImporter;

  public function __construct(NewsImporter $newsImporter) {
    $this->newsImporter = $newsImporter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('news_importer.importer')
    );
  }

  public function import() {
    $result = $this->newsImporter->fetchAndImport();
    return [
      '#markup' => "<p>$result</p>",
    ];
  }
}