<?php

namespace Drupal\claude_http\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service class for API operations.
 */
class ApiService {

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
   * Constructs an ApiService object.
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
   * Validates API request data.
   *
   * @param array $data
   *   The data to validate.
   * @param array $required_fields
   *   Array of required field names.
   *
   * @return array
   *   Array of validation errors, empty if valid.
   */
  public function validateData(array $data, array $required_fields = []) {
    $errors = [];

    foreach ($required_fields as $field) {
      if (empty($data[$field])) {
        $errors[] = "Missing required field: {$field}";
      }
    }

    // Additional validation rules can be added here
    if (!empty($data['name']) && strlen($data['name']) > 255) {
      $errors[] = 'Name field cannot exceed 255 characters';
    }

    return $errors;
  }

  /**
   * Sanitizes input data.
   *
   * @param array $data
   *   The data to sanitize.
   *
   * @return array
   *   The sanitized data.
   */
  public function sanitizeData(array $data) {
    $sanitized = [];

    if (isset($data['name'])) {
      $sanitized['name'] = trim(strip_tags($data['name']));
    }

    if (isset($data['description'])) {
      $sanitized['description'] = trim($data['description']);
    }

    return $sanitized;
  }
}
