<?php

namespace Drupal\email_login_otp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CssCommand;

/**
 * Class for building OTP Settings Form.
 */
class OtpSettingsForm extends FormBase {

  /**
   * Drupal\Core\StringTranslation\TranslationManager definition.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Messenger\Messenger definition.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Drupal\email_login_otp\Services\Otp definition.
   *
   * @var \Drupal\email_login_otp\Services\Otp
   */
  protected $otp;

  /**
   * Drupal\Component\Utility\EmailValidator definition.
   *
   * @var \Drupal\Component\Utility\EmailValidator
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->stringTranslation = $container->get('string_translation');
    $instance->currentUser = $container->get('current_user');
    $instance->emailValidator = $container->get('email.validator');
    $instance->database = $container->get('database');
    $instance->messenger = $container->get('messenger');
    $instance->otp = $container->get('email_login_otp.otp');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $expirationTime = $this->otp->getExpirationTime($this->currentUser->id());
    $config = $this->config('email_login_otp.config');

    if ($config->get('allow_enable_disable')) {
      $form['enable_email_otp'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable 2FA via email'),
        '#default_value' => self::getDefault('enabled'),
        '#weight' => 0,
      ];
    }
    $form['otp_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('2FA Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="enable_email_otp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['otp_fieldset']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => self::getDefault('email'),
      '#weight' => 1,
      '#states' => [
        'required' => [
          'input[name="enable_email_otp"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['otp_fieldset']['otp'] = [
      '#type' => 'textfield',
      '#name' => 'otp',
      '#title' => $this->t('OTP'),
      '#description' => $this->t('Enter the OTP you received in the email.'),
      '#weight' => 2,
    ];
    $form['otp_fieldset']['send'] = [
      '#type' => 'button',
      '#name' => 'send-otp',
      '#value' => $this->t('Send OTP'),
      '#weight' => 3,
      '#ajax' => [
        'callback' => '::otpSendCallback',
      ],
    ];
    if ($expirationTime && time() < (int) $expirationTime) {
      $form['#attached']['library'][] = 'email_login_otp/email_login_otp.frontend';
      $form['otp_fieldset']['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Enter the OTP you received in email. Didn\'t receive the OTP? You can resend OTP in: <span id="time">@time</span>', ['@time' => date('i:s', (int) $expirationTime - time())]),
        '#weight' => 2.5,
        '#prefix' => "<div class='resend-message'>",
        '#suffix' => "</div>",
      ];
      $form['otp_fieldset']['send']['#attributes']['style'] = ['display: none;'];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_login_otp.config');
    if (!$config->get('allow_enable_disable')) {
      $form_state->setValue('enable_email_otp', 1);
    }
    if ($form_state->getValue('enable_email_otp') && $form_state->getValue('email') == NULL) {
      $form_state->setErrorByName('email', $this->t('Email is required.'));
    }
    if (!$this->emailValidator->isValid($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('Email is invalid.'));
    }
    if ($this->otp->check($this->currentUser->id(), $form_state->getValue('otp')) == FALSE && $form_state->getValue('enable_email_otp') && $form_state->getValue('op') && $form_state->getValue('op')->__toString() == 'Submit') {
      $form_state->setErrorByName('otp', $this->t('OTP is invalid or expired.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger->addMessage($this->t('2FA settings has been saved.'));
    $email = $form_state->getValue('email');
    $enabled = $form_state->getValue('enable_email_otp');
    $this->otp->storeSettings([
      'uid' => $this->currentUser->id(),
      'email' => $email,
      'enabled' => $enabled,
    ]);
    $this->otp->expire($this->currentUser->id());
    $this->messenger->addMessage($this->t('2FA settings has been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function otpSendCallback(array &$form, FormStateInterface $form_state) {
    $expirationTime = $this->otp->getExpirationTime($this->currentUser->id());
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type'   => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new ReplaceCommand('.otp-settings-form', $form));
      return $response;
    }

    $otp_code = $this->otp->generate($this->currentUser->getDisplayName());
    $email    = $form_state->getValue('email');
    if ($otp_code && $this->otp->send($otp_code, $email)) {
      $form['validate_otp']['#value'] = 1;
      $this->messenger->addMessage($this->t('An OTP has been sent to your email. Please enter that in the OTP field below.'));
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type'   => 'status_messages',
        '#weight' => -10,
      ];
    }
    $form['#attached']['library'][] = 'email_login_otp/email_login_otp.frontend';
    $form['otp_fieldset']['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Enter the OTP you received in email. Didn\'t receive the OTP? You can resend OTP in: <span id="time">@time</span>', ['@time' => date('i:s', (int) $expirationTime - time())]),
      '#weight' => 2.5,
      '#prefix' => "<div class='resend-message'>",
      '#suffix' => "</div>",
    ];
    $response->addCommand(new ReplaceCommand('.otp-settings-form', $form));
    $response->addCommand(new CssCommand('input[name="send-otp"]', ['display' => 'none']));
    return $response;

  }

  /**
   * Returns the defaul value of a field from settings table.
   */
  private function getDefault($field) {
    $uid = $this->currentUser->id();
    $exists = $this->database->select('otp_settings', 'o')
      ->fields('o', [$field])
      ->condition('uid', $uid, '=')
      ->execute()
      ->fetchAssoc();
    if ($exists) {
      return $exists[$field];
    }
    if ($field == 'email') {
      return $this->currentUser->getEmail();
    }
    return NULL;
  }

}
