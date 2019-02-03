<?php

namespace Drupal\govcms_reminder;

use Drupal\Core\Database\Connection;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Reminder utilities.
 */
class ReminderUtilities {

  /**
   * Table name.
   *
   * @var string
   */
  protected $table = 'govcms_reminder';

  /**
   * Field name.
   *
   * @var string
   */
  protected $fieldName = 'field_reminder';

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new Reminder object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection service.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Get reminder from database.
   */
  public function getFromId($rid) {
    $results = $this->database->select($this->table, 'r')
      ->fields('r')
      ->condition('rid', $rid, '=')
      ->execute()
      ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'Drupal\\govcms_reminder\\ReminderType');
      // ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'Drupal\\govcms_reminder\\Reminder');

    return is_array($results) ? reset($results) : FALSE;
  }

  /**
   * Get reminder from database.
   */
  public function get($fields = []) {
    $query = $this->database->select($this->table, 'r')
      ->fields('r');

    foreach ($fields as $field_name => $field_value) {
      $query->condition($field_name, $field_value, '=');
    }

    return $query
      ->execute()
      ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'Drupal\\govcms_reminder\\ReminderType');
      // ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'Drupal\\govcms_reminder\\Reminder');
  }

  /**
   * Create reminder field.
   */
  public function attachReminderField($reminder) {
    $field_name = $this->fieldName;
    $field_storage = FieldStorageConfig::loadByName($reminder->getEntityType(), $field_name);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $reminder->getEntityType(),
        'type' => 'timestamp',
      ]);
      $field_storage->save();
    }

    $field = FieldConfig::loadByName($reminder->getEntityType(), $reminder->getBundle(), $field_name);
    if (!$field) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $reminder->getBundle(),
        'label' => 'Reminder',
      ]);
      $field->save();
    }

    entity_get_form_display($reminder->getEntityType(), $reminder->getBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'datetime_timestamp',
        'weight' => 999,
      ])
      ->save();
  }

  /**
   * Detach field if removed.
   */
  public function detachReminderField($reminder) {
    $field = FieldConfig::loadByName($reminder->getEntityType(), $reminder->getBundle(), $this->fieldName);
    if ($field) {
      $field->delete();
    }
  }

}
