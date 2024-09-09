<?php

namespace Drupal\email_login_otp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for building Config Form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\StringTranslation\TranslationManager definition.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->stringTranslation = $container->get('string_translation');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'email_login_otp.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_login_otp_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_login_otp.config');
    $form['allow_enable_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Users to Enable/Disable 2FA?'),
      '#description' => $this->t('If checked, users will be able to disable or enable 2FA via email OTP. Keep it disabled if you want email OTP mandatory for site users.'),
      '#default_value' => $config->get('allow_enable_disable'),
    ];
    $form['resend_wait_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Resend wait time (in minutes)'),
      '#description' => $this->t('Time to wait before resending an new OTP.'),
      '#default_value' => $config->get('resend_wait_time') ? $config->get('resend_wait_time') : 2,
      '#attributes' => [
        'min' => 1,
        'max' => 5
      ]
    ];
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirect Settings'),
      '#states' => [
        'visible' => [
          'input[name="allow_enable_disable"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['fieldset']['redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force Redirect Users'),
      '#description' => $this->t("If checked, users will be redirected to their 2FA settings form until they setup 2FA, if they haven't already."),
      '#default_value' => $config->get('redirect'),
    ];
    $form['fieldset']['message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Message Type'),
      '#options' => [
        'status' => $this->t('Success'),
        'warning' => $this->t('Warning'),
        'error' => $this->t('Error'),
      ],
      '#default_value' => $config->get('message_type'),
    ];
    $form['fieldset']['redirect_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Redirect Message'),
      '#default_value' => $config->get('redirect_message'),
      '#states' => [
        'required' => [
          'input[name="allow_enable_disable"]' => ['checked' => FALSE],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$form_state->getValue('allow_enable_disable') && empty($form_state->getValue('redirect_message'))) {
      $form_state->setErrorByName('redirect_message', $this->t('Redirect Message is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('email_login_otp.config')
      ->set('allow_enable_disable', $form_state->getValue('allow_enable_disable'))
      ->set('redirect', $form_state->getValue('redirect'))
      ->set('message_type', $form_state->getValue('message_type'))
      ->set('redirect_message', $form_state->getValue('redirect_message'))
      ->set('resend_wait_time', $form_state->getValue('resend_wait_time'))
      ->save();
  }

}
