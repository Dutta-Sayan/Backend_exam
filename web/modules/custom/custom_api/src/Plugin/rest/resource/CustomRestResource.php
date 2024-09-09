<?php

namespace Drupal\custom_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get list of movie content type nodes.
 *
 * @RestResource(
 *  id = "custom_get_rest_resource",
 *  label = @Translation("Custom Get Rest Resource"),
 *  uri_paths = {
 *    "canonical" = "/students-rest"
 *  }
 * )
 */
class CustomRestResource extends ResourceBase {
  /**
   * A current user instance which is logged in the session.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $loggedUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $config
   *   Configuration array containing the information about the plugin instance.
   * @param string $module_id
   *   The module_id for the plugin instance.
   * @param mixed $module_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A currently logged user instance.
   */
  public function __construct(
    array $config,
    $module_id,
    $module_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);
    $this->loggedUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
    return new static(
      $config,
      $module_id,
      $module_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sample_rest_resource'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET request.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Returns a list of student users.
   */
  public function get(): ResourceResponse {
    // Checks if the current user has access permission for the content.
    if (!$this->loggedUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    // Role of the users to be shown.
    $role = 'student';
    // Stores the user idof the required users.
    $uids = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('roles', $role)->execute();
    // Stores the user objects.
    $users = User::loadMultiple($uids);

    // Creating a serialization data.
    $response = new ResourceResponse($users);
    $response->addCacheableDependency($users);
    return $response;
  }

}
