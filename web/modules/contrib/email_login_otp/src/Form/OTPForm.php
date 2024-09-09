<?php

namespace Drupal\email_login_otp\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;

/**
 * Class for bulding OTP Form.
 */
class OtpForm extends FormBase {

  /**
   * Drupal\Core\Messenger\Messenger definition.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Drupal\email_login_otp\Services\Otp definition.
   *
   * @var \Drupal\email_login_otp\Services\Otp
   */
  protected $otp;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheService;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $enityTypeManager;

  /**
   * Create method to inject the dependencies/services.
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->setStringTranslation($container->get('string_translation'));
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->otp = $container->get('email_login_otp.otp');
    $instance->messenger = $container->get('messenger');
    $instance->cacheService = $container->get('cache.render');
    $instance->enityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = ['max-age' => 0];
    $expirationTime = $this->otp->getExpirationTime($this->tempStoreFactory->get('email_login_otp')->get('uid'));
    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP'),
      '#description' => $this->t('Enter the OTP you received in email. Didn\'t receive the OTP? You can resend OTP in: <span id="time">00:01</span>'),
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#ajax' => [
        'callback' => '::ajaxOtpCallback',
        'event' => 'click',
      ],
    ];
    $form['resend'] = [
      '#type' => 'markup',
      '#markup' => "<span id='replace'>" . $this->t('Resend') . "</span>",
    ];

    $form['#attached']['library'][] = 'email_login_otp/email_login_otp.frontend';

    if ((int) $expirationTime > time()) {
      $form['otp']['#description'] = $this->t('Enter the OTP you received in email. Didn\'t receive the OTP? You can resend OTP in: <span id="time">@time</span>', ['@time' => date('i:s', (int) $expirationTime - time())]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tempstore = $this->tempStoreFactory->get('email_login_otp');
    $uid = $tempstore->get('uid');
    $value = $form_state->getValue('otp');
    if ($this->otp->check($uid, $value) == FALSE) {
      $form_state->setErrorByName('otp', $this->t('Invalid or expired OTP.'));
    }
  }

  /**
   * Ajax callback of the form.
   */
  public function ajaxOtpCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $tempstore = $this->tempStoreFactory->get('email_login_otp');
    $uid = $tempstore->get('uid');
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type'   => 'status_messages',
        '#weight' => -10,
      ];
      $form_state->setRebuild();
      $response->addCommand(new ReplaceCommand('.otp-form', $form));
      return $response;
    }
    unset($form['#prefix']);
    unset($form['#suffix']);
    $form['status_messages'] = [
      '#type'   => 'status_messages',
      '#weight' => -10,
    ];
    $response->addCommand(new ReplaceCommand('.otp-form', $form));
    $account = $this->enityTypeManager->getStorage('user')->load($uid);
    $this->otp->expire($uid);
    $tempstore->delete('uid');
    user_login_finalize($account);
    $redirect_command = new RedirectCommand(Url::fromRoute('user.page')->toString());
    $response->addCommand($redirect_command);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
