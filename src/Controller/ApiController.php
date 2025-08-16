<?php

namespace Drupal\claude_http\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for custom HTTP API endpoints.
 */
class ApiController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs an ApiController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $logger_factory) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')
    );
  }

  /**
   * GET method - Retrieve data by ID.
   *
   * @param int $id
   *   The ID of the data to retrieve.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the requested data.
   */
  public function getData($id) {
    try {
      // Example: Fetch from a custom table or entity
      $query = $this->database->select('claude_http', 'c')
        ->fields('c')
        ->condition('id', $id)
        ->execute();

      $result = $query->fetchAssoc();

      if ($result) {
        return new JsonResponse([
          'status' => 'success',
          'data' => $result,
          'message' => 'Data retrieved successfully'
        ], Response::HTTP_OK);
      } else {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Data not found'
        ], Response::HTTP_NOT_FOUND);
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('claude_http')->error('GET request failed: @message', ['@message' => $e->getMessage()]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Internal server error'
      ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * POST method - Create new data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with creation status.
   */
  public function postData(Request $request) {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (empty($content['name']) || empty($content['description'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Missing required fields: name and description'
        ], Response::HTTP_BAD_REQUEST);
      }

      $fields = [
        'name' => $content['name'],
        'description' => $content['description'],
        'created' => time(),
        'updated' => time(),
      ];

      $id = $this->database->insert('claude_http')
        ->fields($fields)
        ->execute();

      return new JsonResponse([
        'status' => 'success',
        'data' => ['id' => $id] + $fields,
        'message' => 'Data created successfully'
      ], Response::HTTP_CREATED);

    } catch (\Exception $e) {
      $this->loggerFactory->get('claude_http')->error('POST request failed: @message', ['@message' => $e->getMessage()]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to create data'
      ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * PUT method - Update existing data.
   *
   * @param int $id
   *   The ID of the data to update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with update status.
   */
  public function putData($id, Request $request) {
    try {
      $content = json_decode($request->getContent(), TRUE);

      // Check if record exists
      $exists = $this->database->select('claude_http', 'c')
        ->fields('c', ['id'])
        ->condition('id', $id)
        ->countQuery()
        ->execute()
        ->fetchField();

      if (!$exists) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Data not found'
        ], Response::HTTP_NOT_FOUND);
      }

      $fields = [
        'updated' => time(),
      ];

      if (!empty($content['name'])) {
        $fields['name'] = $content['name'];
      }

      if (!empty($content['description'])) {
        $fields['description'] = $content['description'];
      }

      $this->database->update('claude_http')
        ->fields($fields)
        ->condition('id', $id)
        ->execute();

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Data updated successfully'
      ], Response::HTTP_OK);

    } catch (\Exception $e) {
      $this->loggerFactory->get('claude_http')->error('PUT request failed: @message', ['@message' => $e->getMessage()]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to update data'
      ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * DELETE method - Delete data by ID.
   *
   * @param int $id
   *   The ID of the data to delete.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with deletion status.
   */
  public function deleteData($id) {
    try {
      // Check if record exists
      $exists = $this->database->select('claude_http', 'c')
        ->fields('c', ['id'])
        ->condition('id', $id)
        ->countQuery()
        ->execute()
        ->fetchField();

      if (!$exists) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Data not found'
        ], Response::HTTP_NOT_FOUND);
      }

      $this->database->delete('claude_http')
        ->condition('id', $id)
        ->execute();

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Data deleted successfully'
      ], Response::HTTP_OK);

    } catch (\Exception $e) {
      $this->loggerFactory->get('claude_http')->error('DELETE request failed: @message', ['@message' => $e->getMessage()]);

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to delete data'
      ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
