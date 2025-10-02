<?php

namespace Drupal\claude_http\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
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
    * Drupal logger channel factory service.
    *
    * @var LoggerChannelFactory $loggerFactory
    */
  protected LoggerChannelFactory $loggerFactory;

    /**
     * @param ClientInterface $http_client
     * @param LoggerChannelFactory $logger_factory
     */
    public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactory $logger_factory) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * @param ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   * @throws ContainerExceptionInterface
   * @throws NotFoundExceptionInterface
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('http_client'),
    $container->get('logger.factory'),
    );
  }

  /**
   * @param $url
   * @param $headers
   * @param $body
   * @return false|string
   * @throws GuzzleException
   */
  protected function httpPost($url, $headers, $body = NULL) {
    $data = FALSE;
    $options['headers'] = $headers;
    $options['body'] = $body;
    try {
      $response = $this->httpClient->post($url, $options);
      $status = $response->getStatusCode();
      if($status == 200) {
        $data = $response->getBody()
          ->getContents();
      }
      else {
        $this->loggerFactory->get('claude_http')
          ->warning('httpPost() returned a status '.$status. ' with the response '.$response->getBody()
              ->getContents());
      }
    }
    catch (RequestException | ConnectException $e) {
      $this->loggerFactory->get('claude_http')
        ->error($e);
    }
    return $data;
  }

  /**
   * @param $url
   * @param $params
   * @param $headers
   * @return false|string
   * @throws GuzzleException
   */
  protected function httpGet($url, $params, $headers) {
    $data = FALSE;
    try {
      $response = $this->httpClient->get($url, [
        'headers' => $headers,
        'query' => $params,
      ]);
      $status = $response->getStatusCode();
      if ($status == 200) {
        $data = $response->getBody()
          ->getContents();
      }
      else {
        $this->loggerFactory->get('claude_http')
          ->warning('httpGet() returned a status ' . $status . ' with the response ' . $response->getBody()
              ->getContents());
      }
    }
    catch (RequestException | ConnectException $e) {
      $this->loggerFactory->get('claude_http')
        ->error($e);
    }
    return $data;
  }

}
