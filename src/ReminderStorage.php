<?php

namespace Drupal\govcms_reminder;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

/**
 * Provides a class for CRUD operations on reminders.
 */
class ReminderStorage {

  /**
   * The table for the url_alias storage.
   */
  const TABLE = 'govcms_reminders';

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
   *   A database connection for reading and writing path aliases.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $fields, $rid = NULL) {
    // Must validate the fields first.
    if (empty($rid)) {
      try {
        $query = $this->connection->insert(static::TABLE)
          ->fields($fields);
        $rid = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::logger('govcms_reminder')->error($e->getMessage());
        throw $e;
      }

      $fields['rid'] = $rid;
    }
    else {
      try {
        $query = $this->connection->update(static::TABLE)
          ->fields($fields)
          ->condition('rid', $rid);
        $updated = $query->execute();
        $fields['rid'] = $rid;
      }
      catch (\Exception $e) {
        \Drupal::logger('govcms_reminder')->error($e->getMessage());
        throw $e;
      }
    }
    if ($rid) {
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
        ->orderBy('rid', 'DESC')
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

  /**
   * {@inheritdoc}
   */
  public function checkRequiredFields($fields) {

  }

  /**
   * {@inheritdoc}
   */
  public function getRemindersForAdminListing($keys = NULL) {
    $query = $this->connection->select(static::TABLE)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    if ($keys) {
      // Replace wildcards with PDO wildcards.
      $query->condition('entity_label', '%' . preg_replace('!\*+!', '%', $keys) . '%', 'LIKE');
    }
    try {
      return $query
        ->fields(static::TABLE)
        ->orderBy('rid', 'DESC')
        ->limit(50)
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      return [];
    }
  }

}
