<?php
namespace Drupal\news_importer\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\news_importer\NewsImporter;

class FixImageController extends ControllerBase {

  protected $newsImporter;

  public function __construct(NewsImporter $newsImporter) {
    $this->newsImporter = $newsImporter;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('news_importer.importer')
    );
  }

  public function fixImages() {
    $message = $this->newsImporter->fixMissingImages(72);
    return new Response($message);
  }
}