<?php

namespace Drupal\news_importer\Commands;

use Drush\Commands\DrushCommands;
use Drupal\news_importer\NewsImporter;

class NewsImporterCommands extends DrushCommands {

  protected $newsImporter;

  public function __construct(NewsImporter $news_importer) {
    $this->newsImporter = $news_importer;
  }

  /**
   * Imports news articles from NewsData.io API.
   *
   * @command news:import
   * @aliases ni
   */
  public function importNews() {
    $result = $this->newsImporter->fetchAndImport();
    $this->output()->writeln($result);
  }
}
