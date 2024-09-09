<?php

namespace Drupal\email_login_otp\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Otp service class.
 */
class Otp {
  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * The database object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * The database object.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;
  /**
   * The mailManager object.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;
  /**
   * The languageManager object.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * The username.
   *
   * @var string
   */
  private $username;
  /**
   * The tempoStorage object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private $tempStorageFactory;

  /**
   * @var \Drupal\Core\Password\PasswordInterface
   */
  private PasswordInterface $hasher;

  /**
   * Constructor of the class.
   */
  public function __construct(Connection $connection, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, PasswordInterface $hasher, PrivateTempStoreFactory $tempStoreFactory, ConfigFactory $config_factory, ExtensionPathResolver $extension_path_resolver) {
    $this->tempStorageFactory = $tempStoreFactory;
    $this->database           = $connection;
    $this->configFactory      = $config_factory;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->mailManager        = $mail_manager;
    $this->languageManager    = $language_manager;
    $this->hasher             = $hasher;
  }

  /**
   * Generates a new OTP.
   */
  public function generate($username) {
    $this->username = $username;
    $uid = $this->getUserField('uid');
    $this->tempStorageFactory->get('email_login_otp')->set('uid', $uid);

    if ($this->exists($uid)) {
      return $this->update($uid);
    }
    return $this->new($uid);
  }

  /**
   * Sends the OTP to user via email.
   */
  public function send($otp, $to = NULL) {
    $to                = $to ? $to : $this->getField('email');
    $langcode          = $this->languageManager->getCurrentLanguage()->getId();
    $mail_template     = file_get_contents($this->extensionPathResolver->getPath('module', 'email_login_otp') . '/templates/email_login_otp_mail.html.twig');
    $mail_message      = str_replace('{{username}}', $this->username, $mail_template);
    $mail_message      = str_replace('{{otp}}', $otp, $mail_message);
    $params['message'] = $mail_message;
    return $this->mailManager->mail('email_login_otp', 'email_login_otp_mail', $to, $langcode, $params, NULL, TRUE);
  }

  /**
   * Checks if the given OTP is valid.
   */
  public function check($uid, $otp) {
    if ($this->exists($uid)) {
      $select = $this->database->select('email_login_otp', 'u')
        ->fields('u', ['otp', 'expiration'])
        ->condition('uid', $uid, '=')
        ->execute()
        ->fetchAssoc();
      if ($select['expiration'] >= time() && $this->hasher->check($otp, $select['otp'])) {
        return TRUE;
      }
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Checks if the OTP of a user has expired.
   */
  public function expire($uid) {
    $delete = $this->database->delete('email_login_otp')
      ->condition('uid', $uid)
      ->execute();
    return $delete;
  }

  /**
   * Checks if the user has enabled the 2FA.
   */
  public function isEnabled($uid) {
    $exists = $this->database->select('otp_settings', 'o')
      ->fields('o')
      ->condition('uid', $uid, '=')
      ->execute()
      ->fetchAssoc();
    return $exists ? $exists['enabled'] : FALSE;
  }

  /**
   * Store the user settings.
   */
  public function storeSettings(array $settings) {
    $exists = $this->database->select('otp_settings', 'o')
      ->fields('o')
      ->condition('uid', $settings['uid'], '=')
      ->execute();
    if ($exists->fetchAssoc()) {
      $update = $this->database->update('otp_settings')
        ->fields([
          'email' => $settings['email'],
          'enabled' => $settings['enabled'],
        ])
        ->condition('uid', $settings['uid'], '=')
        ->execute();
      return $update ?? TRUE;
    }
    $store_settings = $this->database->insert('otp_settings')
      ->fields($settings)
      ->execute();

    return $store_settings ?? TRUE;
  }

  /**
   * Returns the remaining expiration time.
   */
  public function getExpirationTime($uid) {
    $unixTime = $this->database->select('email_login_otp', 'o')
      ->fields('o', ['expiration'])
      ->condition('uid', $uid, '=')
      ->condition('otp', '', '!=')
      ->execute()
      ->fetchAssoc();
    if ($unixTime) {
      return $unixTime['expiration'];
    }
    return FALSE;
  }

  /**
   * Fetches a user value by given field name.
   */
  private function getUserField($field) {
    $query = $this->database->select('users_field_data', 'u')
      ->fields('u', [$field])
      ->condition('name', $this->username, '=')
      ->execute()
      ->fetchAssoc();
    return $query[$field];
  }

  /**
   * Fetches a value from the settings table by the given field.
   */
  private function getField($field) {
    $uid = $this->tempStorageFactory->get('email_login_otp')->get('uid');
    $query = $this->database->select('otp_settings', 'u')
      ->fields('u', [$field])
      ->condition('uid', $uid, '=')
      ->execute()
      ->fetchAssoc();
    return $query[$field];
  }

  /**
   * Checks if the OTP already exists for a user.
   */
  private function exists($uid) {
    $exists = $this->database->select('email_login_otp', 'u')
      ->fields('u')
      ->condition('uid', $uid, '=')
      ->execute()
      ->fetchAssoc();
    return $exists ?? TRUE;
  }

  /**
   * Generates a new OTP.
   */
  private function new($uid) {
    $resend_wait_time = $this->configFactory
                      ->get('email_login_otp.config')
                      ->get('resend_wait_time');
    $human_readable_otp = rand(100000, 999999);
    $this->database->insert('email_login_otp')->fields([
      'uid' => $uid,
      'otp' => $this->hasher->hash($human_readable_otp),
      'expiration' => strtotime("+$resend_wait_time minutes", time()),
    ])->execute();
    return $human_readable_otp;
  }

  /**
   * Updates the existing OTP.
   */
  private function update($uid) {
    $human_readable_otp = rand(100000, 999999);
    $this->database->update('email_login_otp')
      ->fields([
        'otp' => $this->hasher->hash($human_readable_otp),
        'expiration' => strtotime("+5 minutes", time()),
      ])
      ->condition('uid', $uid, '=')
      ->execute();
    return $human_readable_otp;
  }

}
