<?php

namespace Drupal\ys_otp_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Exception;

class OtpLoginController extends ControllerBase {

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * Constructs a new OtpLoginController object.
     *
     * @param \Drupal\Core\Session\AccountInterface $current_user
     *   The current user.
     */
    public function __construct(AccountInterface $current_user) {
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('current_user')
        );
    }

    /*front-end user entry form*/
    public function phoneNumberPage() {
        return [
        '#theme' => 'phone_number_form',
        '#attached' => [
            'library' => [
                'ys_otp_login/otp_login_js',
            ],
        ],
        ];
    }

    public function sendOtp(Request $request) {
        $phone_number = $request->request->get('phone_number');
        if ($phone_number) {
            \Drupal::logger('ys_otp_login')->notice('OTP requested for phone number: @phone_number', [
                '@phone_number' => $phone_number,
            ]);
            // Implement OTP generation and sending logic here.
            $otp_sent = $this->sendOTPVia2Factor($phone_number);
            if ($otp_sent) {
                return new JsonResponse(['status' => 'success']);

            } else {
                return new JsonResponse(['status' => 'error', 'message' => 'Failed to send OTP.']);
            }
        } else {
            return new JsonResponse(['status' => 'error', 'message' => 'Phone number is required.']);
        }
    }

    private function sendOTPVia2Factor($phone_number) {

        $api_key = 'c6b2d752-308c-11ef-8b60-0200cd936042';
        $otp_code = mt_rand(100000, 999999);

        // Construct Guzzle HTTP client
        $client = new Client();
        try {
            // Send SMS message via 2factor API
			$response = $client->post('https://2factor.in/API/V1/'. $api_key .'/SMS/'. $phone_number .'/'. $otp_code .'/Yodasoft');

            $responseBody = json_decode($response->getBody(), true);

            // Check if message sent successfully
            if (isset($responseBody['Status']) && $responseBody['Status'] == 'Success') {
                \Drupal::logger('ys_otp_login')->notice('SMS sent via 2factor API.');
                try {
                    \Drupal::database()->insert('ys_otp_login')
                      ->fields([
                        'phone_number' => $phone_number,
                        'otp' => $otp_code,
                        'created' => \Drupal::time()->getRequestTime(),
                      ])
                      ->execute();
                }
                catch (Exception $e) {
                    \Drupal::logger('ys_otp_login')->error('Exception while inserting into the ys_otp_login table: @message', 
                    ['@message' => $e->getMessage()]);
                }
                return true; // Message sent successfully
            } else {
                \Drupal::logger('ys_otp_login')->error('Failed to send SMS via 2factor API.');
                return false;
            }
        } catch (ClientException  $e) {
            // Handle exceptions (e.g., network errors)
            \Drupal::logger('ys_otp_login')->error('Exception while sending SMS via 2factor API: ' . $e->getMessage());
            return false;
        }
        //return true;
    }

    public function verifyOtp(Request $request) {
        $otp = $request->get('otp');
        $phone_number = $request->get('phone_number');
    
        // Verify OTP logic here.
        $query = \Drupal::database()->select('ys_otp_login', 'o')
          ->fields('o', ['id'])
          ->condition('phone_number', $phone_number)
          ->condition('otp', $otp)
          ->execute();
    
        if ($id = $query->fetchField()) {
          // OTP is valid.
          $user_storage = \Drupal::entityTypeManager()->getStorage('user');
          $users = $user_storage->loadByProperties(['field_phone_number' => $phone_number]);
          $user = reset($users);
    
          if (!$user) {
            // Create a new user.
            $password = \Drupal::service('password_generator')->generate();
            $user = User::create([
              'name' => $phone_number,
              'mail' => $phone_number . '@example.com', // Use a dummy email.
              'status' => 1,
              'pass' => $password,
              'field_phone_number' => $phone_number,
            ]);
            $user->save();
    
            // Log in the new user.
            user_login_finalize($user);
    
            // Update the ys_otp_login table with user_id.
            try {
              \Drupal::database()->update('ys_otp_login')
                ->fields(['user_id' => $user->id()])
                ->condition('id', $id)
                ->execute();
            }
            catch (Exception $e) {
              \Drupal::logger('ys_otp_login')->error('Exception while updating the ys_otp_login table: @message', ['@message' => $e->getMessage()]);
            }
    
            // Redirect to the user-details form page for new users.
            return new JsonResponse(['status' => 'success', 'redirect' => 'new-user']);
          } else {
            // Log in the existing user.
            user_login_finalize($user);
    
            // Redirect to the homepage for existing users.
            return new JsonResponse(['status' => 'success', 'redirect' => 'already-user']);
          }
        }
        else {
          // OTP is invalid.
          return new JsonResponse(['status' => 'error', 'message' => 'Invalid OTP.']);
        }
    }

    public function logoutAndDeleteUser(Request $request) {    
        // Delete user from ys_otp_login table
        $uid = \Drupal::currentUser()->id();
        try {
            \Drupal::database()->delete('ys_otp_login')
            ->condition('user_id', $uid)
            ->execute();

            user_logout();
          }
          catch (Exception $e) {
            \Drupal::logger('ys_otp_login')->error('Exception while deleting the ys_otp_login table: @message', ['@message' => $e->getMessage()]);
          }    
        return new JsonResponse(['status' => 'success', 'message' => 'Logout and user deletion successful']);
    
    }

}
