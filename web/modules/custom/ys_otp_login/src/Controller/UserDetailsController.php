<?php
namespace Drupal\ys_otp_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;

/**
 * Defines UserDetailsController class.
 */
class UserDetailsController extends ControllerBase {

  /**
   * Renders the user details form.
   */
  public function userDetailsForm() {
    $current_user = \Drupal::currentUser();
    return [
      '#theme' => 'user_details_form',
      '#attached' => [
        'library' => [
          'ys_otp_login/user_details_form',
        ],
      ],
      '#current_user_name' => $current_user->getDisplayName(),
      '#current_user_email' => $current_user->getEmail(),
    ];
  }

  /**
   * Handles form submission via AJAX.
   */
  public function submitUserDetailsForm(Request $request) {
    $name = $request->get('name');
    $mail = $request->get('mail');

    $user = User::load(\Drupal::currentUser()->id());
    if ($user) {
        $user->setUsername($name);
        $user->setEmail($mail);
        $user->save();

        return new JsonResponse([
          'status' => 'success',
          'message' => 'Your details have been saved. Redirecting...',
        ]);
    }

    return new JsonResponse([
      'status' => 'error',
      'message' => 'Failed to update your details.'
    ]);
  }
}
