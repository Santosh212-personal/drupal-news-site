<?php 

namespace Drupal\ys_otp_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'WelcomeMessageBlock' block.
 *
 * @Block(
 *   id = "welcome_message_block",
 *   admin_label = @Translation("Welcome Message Block"),
 * )
 */
class WelcomeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $currentUser;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  public function build() {
    $build = [];

    if ($this->currentUser->isAuthenticated()) {
            $build = [
                '#type' => 'container',
                '#attributes' => ['id' => 'custom-welcome-block'],
                'content' => [
                    '#markup' => '<p id="welcome-message">'
                     . $this->t('Welcome  <span class="welcome_title"> @username!', ['@username' => $this->currentUser->getDisplayName()]) . '</span></p>',
                ],
            ];
        }

        return $build;
    }

}
