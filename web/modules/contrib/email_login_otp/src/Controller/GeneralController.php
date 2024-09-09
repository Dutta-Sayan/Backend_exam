<?php

namespace Drupal\email_login_otp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class for the general controller.
 */
class GeneralController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempstorePrivate;

  /**
   * Drupal\email_login_otp\Services\Otp definition.
   *
   * @var \Drupal\email_login_otp\Services\Otp
   */
  protected $otp;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $enityTypeManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->tempstorePrivate = $container->get('tempstore.private');
    $instance->otp              = $container->get('email_login_otp.otp');
    $instance->currentUser      = $container->get('current_user');
    $instance->currentPath      = $container->get('path.current');
    $instance->enityTypeManager = $container->get('entity_type.manager');
    $instance->messenger        = $container->get('messenger');

    return $instance;
  }

  /**
   * Resend.
   *
   * @return string
   *   Return RedirectResponse.
   */
  public function resend() {
    $otp = $this->otp;
    $uid = $this->tempstorePrivate->get('email_login_otp')->get('uid');
    $account = $this->enityTypeManager->getStorage('user')->load($uid);
    $otp_code = $otp->generate($account->getDisplayname());
    if ($otp_code && $otp->send($otp_code)) {
      $this->messenger->addMessage($this->t('An OTP was sent to you via email. Please check your inbox.'));
      $redirect = new RedirectResponse(Url::fromRoute('email_login_otp.otp_form')->toString());
      return $redirect->send();
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: resend'),
    ];
  }

  /**
   * Custom access callback.
   */
  public function access() {
    $path = $this->currentPath->getPath();
    $params = explode('/', $path);
    if ($this->currentUser->id() == $params[2]) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
