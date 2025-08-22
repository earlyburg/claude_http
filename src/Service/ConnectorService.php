<?php

namespace Drupal\claude_http\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Psr\Container\NotFoundExceptionInterface;

/**
 * The claude_http connector service class.
 *
 * \Drupal\claude_http\Service\ConnectorService.
 */
class ConnectorService
{

    /**
     * The Drupal http client interface.
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

  /**
   * The config factory interface.
   *
   * @var ConfigFactoryInterface $config
   */
  private ConfigFactoryInterface $config;

  /**
    * Drupal logger channel factory service.
    *
    * @var LoggerChannelFactory $loggerFactory
    */
 protected LoggerChannelFactory $loggerFactory;

    /**
     * @param ClientInterface $http_client
     * @param ConfigFactoryInterface $config_interface
     * @param LoggerChannelFactory $logger_factory
     */

    public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_interface,
    LoggerChannelFactory $logger_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_interface;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('http_client'),
    $container->get('config.factory'),
    $container->get('logger.factory'),
    );
  }

  /**
   * @param $url
   * @param $headers
   * @param $body
   * @return false|mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function httpPost($url, $headers, $body = NULL) {
    $return = FALSE;
    $data = FALSE;
    $options['headers'] = $headers;
    $options['body'] = $body;
    try {
      $response = $this->httpClient->post($url, $options);
      $status = $response->getStatusCode();
      if($status == '200') {
        $data = $response->getBody()->getContents();
      }
      else {
        $this->loggerFactory->get('claude_http')
          ->warning('httpPost() returned a status '.$status. ' with the response '.$response->getBody()
              ->getContents());
      }
    } catch (RequestException $e) {
      $this->loggerFactory->get('claude_http')
        ->error($e);
    }
    if ($data) {
      $return = Json::decode($data);
    }
    return $return;
  }

  /**
   * @param $url
   * @param $params
   * @param $headers
   * @return false|mixed
   * @throws GuzzleException
   */
  protected function httpGet($url, $params, $headers) {
    $return = FALSE;
    $data = FALSE;
    try {
      $response = $this->httpClient->get($url, [
        'headers' => $headers,
        'query' => $params,
      ]);
      $status = $response->getStatusCode();
      if($status == '200') {
        $data = $response->getBody()->getContents();
      } else {
        $this->loggerFactory->get('claude_http')
          ->warning('httpGet() returned a status '.$status. ' with the response '.$response->getBody()
              ->getContents());
      }
    } catch (RequestException $e) {
      $this->loggerFactory->get('claude_http')
        ->error($e);
    }
    if ($data) {
        $return = Json::decode($data);
    }
    return $return;
  }

}
