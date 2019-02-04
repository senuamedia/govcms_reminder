<?php

namespace Drupal\govcms_reminder;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

/**
 * Class definition for logger.
 */
class LoggerStorage {

  /**
   * The table for the reminder logger storage.
   */
  const TABLE = 'govcms_reminder_logger';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Path CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing reminder logs.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $fields, $rlid = NULL) {
    // Must validate the fields first.
    if (empty($rlid)) {
      try {
        $query = $this->connection->insert(static::TABLE)
          ->fields($fields);
        $rlid = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::logger('govcms_reminder')->error($e->getMessage());
        throw $e;
      }

      $fields['rlid'] = $rlid;
    }
    else {
      try {
        $query = $this->connection->update(static::TABLE)
          ->fields($fields)
          ->condition('rlid', $rlid);
        $updated = $query->execute();
        $fields['rlid'] = $rlid;
      }
      catch (\Exception $e) {
        \Drupal::logger('govcms_reminder')->error($e->getMessage());
        throw $e;
      }
    }
    if ($rlid) {
      return $fields;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load($conditions) {
    $select = $this->connection->select(static::TABLE);
    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }
    try {
      return $select
        ->fields(static::TABLE)
        ->orderBy('rlid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple($conditions) {
    $select = $this->connection->select(static::TABLE);
    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }
    try {
      return $select
        ->fields(static::TABLE)
        ->orderBy('rlid', 'DESC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogsForAdminListing($conditions) {
    $select = $this->connection->select(static::TABLE, 'l')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $select->join('govcms_reminders', 'r', 'r.rid = l.rid');

    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }

    try {
      $result = $select->fields('l')
        ->fields('r', ['entity_label'])
        ->orderBy('rlid', 'DESC')
        ->limit(50)
        ->execute()
        ->fetchAll();

      return $result;
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions) {
    $query = $this->connection->delete(static::TABLE);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    try {
      $deleted = $query->execute();
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      $deleted = FALSE;
    }

    return $deleted;
  }

}
