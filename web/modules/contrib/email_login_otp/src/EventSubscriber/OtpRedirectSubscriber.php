<?php

namespace Drupal\email_login_otp\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\email_login_otp\Services\Otp;
use Drupal\Core\Url;

/**
 * Class for redirecting event.
 */
class OtpRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Drupal\email_login_otp\Services\Otp definition.
   *
   * @var \Drupal\email_login_otp\Services\Otp
   */
  protected $emailLoginOtp;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $enityTypeManager;

  /**
   * @var \Drupal\email_login_otp\Services\Otp
   */
  private Otp $email_login_otp;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private EntityTypeManager $entityTypeManager;

  /**
   * Constructs a new OtpRedirectSubscriber object.
   */
  public function __construct(AccountInterface $current_user, PrivateTempStoreFactory $tempStore, Otp $emailLoginOtp, CurrentRouteMatch $routeMatch, ConfigFactory $configFactory, MessengerInterface $messenger, EntityTypeManager $entityTypeManager) {
    $this->currentUser       = $current_user;
    $this->tempStore         = $tempStore;
    $this->email_login_otp   = $emailLoginOtp;
    $this->routeMatch        = $routeMatch;
    $this->configFactory     = $configFactory;
    $this->messenger         = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['loginRedirect'];
    $events[KernelEvents::REQUEST][] = ['check2fa'];
    return $events;
  }

  /**
   * This method is called when the login_redirect is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function loginRedirect(RequestEvent $event) {
    $uid = $this->tempStore->get('email_login_otp')->get('uid');
    if (($this->routeMatch->getRouteName() == 'email_login_otp.otp_form' && $this->currentUser->isAuthenticated()) ||
    ($this->routeMatch->getRouteName() == 'email_login_otp.otp_form' && $uid == NULL) ||
    ($this->routeMatch->getRouteName() == 'email_login_otp.resend' && $uid == NULL)) {
      $redirect = new RedirectResponse(Url::fromRoute('user.page')->toString());
      return $redirect->send();
    }
  }

  /**
   * This method is called when the check2fa is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function check2fa(RequestEvent $event) {
    if ($this->currentUser->isAuthenticated()) {
      $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $config = $this->configFactory->get('email_login_otp.config');
      $bypass_routes = [
        'entity.user.edit_form',
        'user.pass',
        'email_login_otp.otp_settings_form',
        'system.js_asset',
        'system.css_asset',
      ];
      if (
        !$account->hasRole('administrator') &&
        !$config->get('allow_enable_disable') &&
        !$this->email_login_otp->isEnabled($this->currentUser->id()) &&
        !in_array($this->routeMatch->getRouteName(), $bypass_routes) &&
        $config->get('redirect')
      ) {
        $this->messenger->addMessage($config->get('redirect_message'), $config->get('message_type'), TRUE);
        $event->setResponse(
          new RedirectResponse(Url::fromRoute(
            'email_login_otp.otp_settings_form',
            ['user' => $this->currentUser->id()]
          )->toString())
        );
      }
    }
  }

}
