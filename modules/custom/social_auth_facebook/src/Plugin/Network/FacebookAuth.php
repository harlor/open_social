<?php

namespace Drupal\social_auth_facebook\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a Network Plugin for Social Auth Facebook.
 *
 * @package Drupal\social_auth_facebook\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_facebook",
 *   social_network = "Facebook",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_facebook\Settings\FacebookAuthSettings",
 *       "config_id": "social_auth_facebook.settings"
 *     }
 *   }
 * )
 */
class FacebookAuth extends SocialAuthNetwork {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler
   */
  protected $persistentDataHandler;

  /**
   * FacebookAuth constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\social_auth_facebook\FacebookAuthPersistentDataHandler $persistent_data_handler
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, FacebookAuthPersistentDataHandler $persistent_data_handler) {
    $this->loggerFactory = $logger_factory;
    $this->persistentDataHandler = $persistent_data_handler;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('social_auth_facebook.persistent_data_handler')
    );
  }

  /**
   * Returns an instance of sdk.
   *
   * @return mixed
   *   Returns a new Facebook instance or FALSE if the config was incorrect.
   *
   * @throws \Drupal\social_api\SocialApiException
   */
  public function initSdk() {
    $class_name = '\Facebook\Facebook';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Facebook could not be found. Class: %s.', $class_name));
    }

    if (!$this->validateConfig($this->settings)) {
      return FALSE;
    }

    $settings = [
      'app_id' => $this->settings->getAppId(),
      'app_secret' => $this->settings->getAppSecret(),
      'default_graph_version' => 'v' . $this->settings->getGraphVersion(),
      'persistent_data_handler' => $this->getDataHandler(),
    ];

    return new $class_name($settings);
  }

  /**
   * Returns status of social network.
   *
   * @return bool
   *   The status of the social network.
   */
  public function isActive() {
    return (bool) $this->settings->isActive();
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings
   *   The Facebook auth settings.
   *
   * @return bool
   *   True if module is configured, False otherwise.
   */
  protected function validateConfig(FacebookAuthSettings $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();

    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return $this->settings->getSocialNetworkKey();
  }

  /**
   * Returns an instance of storage that handles data.
   *
   * @return object
   *   An instance of the storage that handles the data.
   */
  public function getDataHandler() {
    $this->persistentDataHandler->setPrefix('social_auth_facebook_');

    return $this->persistentDataHandler;
  }

}
