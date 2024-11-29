<?php

namespace Drupal\piksel\Plugin\media\PikselProvider;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Site\Settings;
use Drupal\piksel\Piksel;
use Drupal\piksel\Plugin\PikselProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a piksel media provider for Program.
 *
 * @PikselProvider(
 *   id = "program",
 *   label = "Program",
 * )
 */
class Program extends PluginBase implements PikselProviderInterface, ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new OEmbed instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->config = $config->get('piksel.settings');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory'),
    );
  }

  /**
   * Make a request to the piksel API v5.
   *
   * Search for projects.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function searchProjects(): array {
    $token = $this->config->get('token');
    $endpoint = $this->config->get('piksel_endpoint');
    $endpoint = str_replace('[token]', $token, $endpoint);
    $endpoint = str_replace('[ws]', 'ws_vod_project', $endpoint);
    $response = $this->httpClient->get($endpoint . '?end=50');
    $data = Json::decode($response->getBody()->getContents());
    return $data['response']['WsVodProjectResponse']['projects'];
  }

  /**
   * Make a request to the piksel API v5.
   *
   * Search for programs.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function searchPrograms(String $projectId): array {
    $token = $this->config->get('token');
    $endpoint = $this->config->get('piksel_endpoint');
    $endpoint = str_replace('[token]', $token, $endpoint);
    $endpoint = str_replace('[ws]', 'ws_program', $endpoint);
    $response = $this->httpClient->get($endpoint . '?p=' . $projectId . '&end=50');
    $data = Json::decode($response->getBody()->getContents());
    return $data['response']['WsProgramResponse']['programs'] ?? [];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function load(string $id): Piksel {
    $token = $this->config->get('token');
    $endpoint = $this->config->get('piksel_endpoint');
    $endpoint = str_replace('[token]', $token, $endpoint);
    $endpoint = str_replace('[ws]', 'ws_program', $endpoint);
    $response = $this->httpClient->get($endpoint . '?v=' . $id);
    $data = Json::decode($response->getBody()->getContents());

    if (!empty($data['response']['WsProgramResponse']) &&
     !empty($data['response']['WsProgramResponse']['program']['Title'])) {
      $program_object = $data['response']['WsProgramResponse']['program'];
      return new Piksel(
        $id,
        $program_object['Title'],
        $program_object['asset']['thumbnailUrl'] ? $program_object['asset']['thumbnailUrl'] : $program_object['thumbnailUrl'],
        $program_object['Description'],
        $program_object['Title'],
      );
    }
    else {
      throw new PluginException("API program $id , not found error.");
    }
  }

}
