<?php

namespace Drupal\govcms_reminder;

use Drupal\Core\Database\Connection;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Handle create, edit, delete database.
 */
class ReminderType {

  /**
   * Reminder ID.
   *
   * @var int
   */
  protected $rtid = NULL;

  /**
   * Entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * Bundle of entity type.
   *
   * @var string
   */
  protected $bundle;

  /**
   * To whom this mail is going.
   *
   * @var array
   */
  protected $mail_to;

  /**
   * Email subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * Mail template.
   *
   * @var string
   */
  protected $mail_content;

  /**
   * The date that reminder is created.
   *
   * @var int
   */
  protected $created;

  /**
   * The date that reminder is updated.
   *
   * @var int
   */
  protected $changed;


  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The table for the reminder types.
   */
  const TABLE = 'govcms_reminder_types';

  /**
   * Constructs a new Reminder object.
   */
  public function __construct() {
    $this->connection = \Drupal::database();
  }

  /**
   * Save reminder to database.
   */
  public function save() {
    $fields = $this->prepareFields();

    $this->setChanged(REQUEST_TIME);
    $fields['changed'] = $this->getChanged();

    if ($this->id()) {
      $number_records = $this->connection
        ->update(static::TABLE)
        ->fields($fields)
        ->condition('rtid', $this->id(), '=')
        ->execute();

      return $number_records;
    }
    else {
      $this->setCreated(REQUEST_TIME);
      $fields['created'] = $this->getCreated();

      $rtid = $this->connection
        ->insert(static::TABLE)
        ->fields($fields)
        ->execute();

      if ($rtid) {
        $this->rtid = $rtid;
      }

      return $rtid;
    }
  }

  /**
   * Delete the reminder from database.
   */
  public function delete() {
    $num_deleted = $this->connection->delete(static::TABLE)
      ->condition('rtid', $this->id())
      ->execute();

    if ($num_deleted) {
      $this->clean();
    }
    return $num_deleted;
  }

  /**
   * Prepare fields data will be saved to database.
   */
  public function prepareFields() {
    return [
      'entity_type' => $this->getEntityType(),
      'bundle' => $this->getBundle(),
      'mail_to' => $this->getMailToRaw(),
      'subject' => $this->getSubject(),
      'mail_content' => serialize($this->getMailContent()),
      'created' => $this->getCreated(),
      'changed' => $this->getChanged(),
    ];
  }

  /**
   * Get unserialize the mail to data.
   */
  public function id() {
    return $this->rtid;
  }

  /**
   * Clean rid.
   */
  protected function clean() {
    $this->rtid = NULL;
    return $this;
  }

  /**
   * Get unserialize the mail to data.
   */
  public function getMailToRaw() {
    return $this->mail_to;
  }

  /**
   * Get unserialize the mail to data.
   */
  public function getDecodedMailTo() {
    return unserialize($this->mail_to);
  }

  /**
   * Set serialize the mail to.
   */
  public function setMailTo($mailTo) {
    if (is_array($mailTo) || is_object($mailTo)) {
      $mailTo = unserialize($mailTo);
    }

    $this->mail_to = $mailTo;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailTo($type) {
    $mail_to = $this->getDecodedMailTo();
    return isset($mail_to[$type]) ? $mail_to[$type] : [];
  }

  /**
   * Get reminder entity type.
   */
  public function getEntityType() {
    return $this->entity_type;
  }

  /**
   * Set reminder entity type.
   */
  public function setEntityType($entity_type) {
    $this->entity_type = $entity_type;

    return $this;
  }

  /**
   * Get bundle.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Set bundle.
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * Get mail template.
   */
  public function getRawMailContent() {
    return $this->mail_content;
  }

  /**
   * Get mail template.
   */
  public function getMailContent() {
    if (is_string($this->mail_content)) {
      $un_mail_content = unserialize($this->mail_content);
      $this->setMailContent($un_mail_content);
    }

    return $this->mail_content;
  }

  /**
   * Set mail template.
   */
  public function setMailContent($mailContent) {
    $this->mail_content = $mailContent;
    return $this;
  }

  /**
   * Get subject.
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Set subject.
   */
  public function setSubject($subject) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * Get created.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Set created.
   */
  public function setCreated($created) {
    $this->created = $created;
    return $this;
  }

  /**
   * Get changed.
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * Set changed.
   */
  public function setChanged($changed) {
    $this->changed = $changed;
    return $this;
  }

  /**
   * Static function to load the reminder type.
   */
  public static function load($rtid) {
    $connection = \Drupal::database();
    $results = $connection->select(static::TABLE, 'r')
      ->fields('r')
      ->condition('rtid', $rtid, '=')
      ->execute()
      ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'Drupal\\govcms_reminder\\ReminderType');

    return is_array($results) ? reset($results) : FALSE;
  }

  /**
   * Static function to load the multiple reminder types.
   */
  public static function loadMultiple(array $rtids = [], array $conditions = []) {
    $connection = \Drupal::database();
    $query = $connection->select(static::TABLE, 'r')
      ->fields('r');

    if ($rtids) {
      $query->condition('rtid', $rtids, 'IN');
    }

    if ($conditions) {
      foreach ($conditions as $name => $value) {
        $query->condition($name, $value);
      }
    }

    $results = $query->execute()->fetchAll(
      \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
      'Drupal\\govcms_reminder\\ReminderType'
    );

    return $results;
  }

}
